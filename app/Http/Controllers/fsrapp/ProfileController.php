<?php

namespace App\Http\Controllers\fsrapp;

use App\Http\Controllers\Controller;
use App\Interfaces\ActionTrailRepositoryInterface;
use App\Models\AdminUser;
use App\Models\City;
use App\Models\ReferralCode;
use App\Models\State;
use App\Http\Middleware\FsrAppAuthMiddleware;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WalletHistory;
use App\Repositories\WhatsAppRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;
use App\Models\NotificationHistory;

class ProfileController extends Controller
{
    public function __construct(
        protected ActionTrailRepositoryInterface $actontrail,
        protected WhatsAppRepositoryInterface $whatsAppRepository
    ) {}

    /* ------------------------------------------------------------------ */
    /*  Profile index — pass $user to the Blade view */
    /* ------------------------------------------------------------------ */
    public function index()
    {
        $user = Auth::user();

        if (! $user) {
            return redirect()
                ->route('fsrapp.login')
                ->with('error', 'Please login first.');
        }

        // Fetch active Indian states list
        $states = State::where('status', 'active')->orderBy('name')->get();
        $quizHobbies = DB::table('quiz_hobby')->where('status', 'active')->orderBy('name')->get(['id', 'name']);
        $quizInterests = DB::table('quiz_interest')->where('status', 'active')->orderBy('name')->get(['id', 'name']);
        $quizGames = DB::table('quiz_game')->where('status', 'active')->orderBy('name')->get(['id', 'name']);
        $quizColors = DB::table('quiz_color')->where('status', 'active')->orderBy('name')->get(['id', 'name']);

        // Compute profile photo URL once to prevent duplicate S3 adapter instantiation
        $photoUrl = null;
        if ($user->profile_photo) {
            try {
                $photoUrl = Storage::disk('s3')->url($user->profile_photo);
            } catch (\Exception $e) {
                Log::warning('Failed to generate S3 URL: ' . $e->getMessage());
            }
        }

        return view('fsrapp.profile.index', compact('user', 'states', 'photoUrl', 'quizHobbies', 'quizInterests', 'quizGames', 'quizColors'));
    }

    /* ------------------------------------------------------------------ */
    /*  Complete profile (first-time setup after OTP) */
    /* ------------------------------------------------------------------ */
    public function completeProfile()
    {
        $mobile = session('pending_mobile') ?? session('fsr_user_mobile') ?? (Auth::check() ? Auth::user()->mobile : null);
        if (! $mobile && ! Auth::check()) {
            return redirect()->route('fsrapp.login')->with('error', 'Please enter your mobile number first.');
        }

        // Store back to session to keep consistent
        if ($mobile) {
            session(['pending_mobile' => $mobile, 'fsr_user_mobile' => $mobile]);
        }

        // ✅ NEW: Session se referral code nikalein
        $referralCode = session('pending_referral_code') ?? 'SANDE687'; 

        $user = null;
        if (Auth::check()) {
            $user = Auth::user();
        } elseif ($mobile) {
            $user = User::where('mobile', $mobile)->first();
        }

        if ($user && (!$user->is_active || (int) $user->is_active === 0)) {
            $userId = $user->id;
            Cache::forget('email_otp_' . $userId);
            Auth::logout();
            session()->flush();
            return redirect()->route('fsrapp.login')->with('error', 'Your account has been deactivated. Please contact administrator.');
        }

        // Active states
        $states = State::where('status', 'active')
            ->orderBy('name')
            ->get();

        // All active cities
        $cities = City::where('status', 'active')
            ->orderBy('name')
            ->get([
                'id',
                'state_id',
                'name',
            ]);

        $redirectParam = request('redirect') ?? session('redirect_to') ?? session('url.intended');
        if (!FsrAppAuthMiddleware::isValidRedirectUrl($redirectParam)) {
            $redirectParam = null;
            session()->forget(['redirect_to', 'url.intended']);
        }

        return view('fsrapp.profile.complete', compact('mobile', 'user', 'states',
            'cities', 'referralCode', 'redirectParam'));
    }

    // public function saveProfile(Request $request)
    // {
    //     $request->validate([
    //         'name' => 'required|string|min:3|max:191',
    //         'state' => 'nullable',
    //         'city' => 'nullable|string|max:191',
    //         'dob' => 'nullable|date',
    //         'gender' => 'nullable|string',
    //         'referral_code' => 'required|string|max:191',
    //         'language' => 'required|in:en,hi',
    //     ], [
    //         'name.required' => 'Please enter your full name.',
    //          'name.min' => 'Full name must be at least 3 characters.',
    //         'referral_code.required' => 'Please enter your referral code.',
    //         'language.required' => 'Please select your language.',
    //         'language.in' => 'Please select a valid language.',
    //     ]);

    //     $selectedState = null;

    //     if ($request->filled('state')) {
    //         $selectedState = State::where('id', $request->state)
    //             ->where('status', 'active')
    //             ->first();
    //     }

    //     $stateName = $selectedState?->name;

    //     $mobile = session('pending_mobile') ?? (Auth::check() ? Auth::user()->mobile : null);

    //     if (! $mobile) {
    //         return redirect()->route('fsrapp.login')->with('error', 'Session expired. Please login again.');
    //     }

    //     $nameStr = $request->input('name');
    //     $tenantId = 1;
    //     $initials = strtoupper(substr(preg_replace('/\s+/', '', $nameStr), 0, 3));
    //     $generatedReferral = $initials.$tenantId.strtoupper(Str::random(6));

    //     $referralUserId = null;
    //     $referral = null;
    //     $superReferralCode = AdminUser::where('role', 'superadmin')->value('referral_code');

    //     $code = trim($request->referral_code);
    //     if ($code != $superReferralCode) {
    //         $referral = ReferralCode::where('code', $code)
    //             ->where('used', 'pending')
    //             ->where('status', 'active')
    //             ->first();

    //         if (! $referral) {
    //             return back()->withInput()->with('error', 'Invalid or already used referral code.');
    //         }
    //         $tenantId = $referral->tenant_id;
    //         $referralUserId = $referral->user_id;
    //     } elseif ($code == $superReferralCode) {
    //         $tenantId = 1;
    //         $referralUserId = 1;
    //     } else {
    //         return back()
    //             ->withInput()
    //             ->with('error', 'Referral Code not verified');
    //     }

    //     // Find existing user by mobile or authenticated user ID
    //     $user = Auth::check() ? Auth::user() : User::where('mobile', $mobile)->first();

    //     if (! $user) {
    //         $user = User::create([
    //             'tenant_id' => $tenantId,
    //             'name' => $nameStr,
    //             'mobile' => $mobile,
    //             'language' => $request->input('language'),
    //             'login_method' => 'otp',
    //             'password' => bcrypt($mobile),
    //             'role' => 'user',
    //             'role_id' => 3,
    //             'verify_mobile' => 1,
    //             // 'state'                => $request->input('state'),
    //             'state' => $stateName,
    //             'city' => $request->input('city'),
    //             'date_of_birth' => $request->input('dob'),
    //             'gender' => $request->input('gender'),
    //             'referral_code' => $generatedReferral,
    //             'referral_code_to' => $request->input('referral_code'),
    //             'referral_user_id' => $referralUserId,
    //             'create_quiz_limit' => 4,
    //             'available_quiz_limit' => 4,
    //             'quiz_leave' => 0,
    //             'wallet_balance' => 0,
    //             'is_active' => 1,
    //             'can_login' => 1,
    //             'otp_enabled' => 1,
    //             'session_token' => Str::random(60),
    //             'last_login_at' => now(),
    //         ]);

    //         $this->actontrail->create([
    //             'tenant_id' => $user->tenant_id,
    //             'table_name' => 'users',
    //             'reference_id' => $user->id,
    //             'action' => 'add user profile',
    //             'details' => $user->toArray(),
    //         ]);
    //     } else {
    //         $userOldDetails = $user->toArray();
    //         $user->update([
    //             'name' => $nameStr,
    //             // 'state'            => $request->input('state'),
    //              'language' => $request->input('language'),
    //             'state' => $stateName,
    //             'city' => $request->input('city'),
    //             'date_of_birth' => $request->input('dob'),
    //             'gender' => $request->input('gender'),
    //             'referral_code_to' => $request->input('referral_code') ?? $user->referral_code_to,
    //             'referral_user_id' => $referralUserId ?? $user->referral_user_id,
    //             'verify_mobile' => 1,
    //             'is_active' => 1,
    //             'can_login' => 1,
    //             'session_token' => Str::random(60),
    //             'last_login_at' => now(),
    //         ]);

    //         $this->actontrail->create([
    //             'tenant_id' => $user->tenant_id,
    //             'table_name' => 'users',
    //             'reference_id' => $user->id,
    //             'action' => 'update user profile',
    //             'details' => $user->toArray(),
    //             'oldDetails' => $userOldDetails,
    //         ]);
    //     }

    //     // Process referral code if provided and active
    //     if ($referral && $referral->used !== 'used') {
    //         $referralOldDetails = $referral->toArray();
    //         $points = (int) ($referral->points ?? 0);

    //         $referral->update([
    //             'used' => 'used',
    //             'referral_user_id' => $user->id,
    //             'used_date' => now(),
    //         ]);

    //         $this->actontrail->create([
    //             'tenant_id' => $referral->tenant_id,
    //             'table_name' => 'referral_codes',
    //             'reference_id' => $referral->id,
    //             'action' => 'update user',
    //             'details' => $referral->toArray(),
    //             'oldDetails' => $referralOldDetails,
    //         ]);

    //         if ($referral->referral_point_provide === 'yes' && $points > 0 && $referral->user_id) {
    //             $referralUser = User::find($referral->user_id);
    //             if ($referralUser) {
    //                 $oldDetails = $referralUser->toArray();
    //                 $referralUser->increment('wallet_balance', $points);
    //                 $referralUser->refresh();

    //                 $this->actontrail->create([
    //                     'tenant_id' => $referralUser->tenant_id,
    //                     'table_name' => 'users',
    //                     'reference_id' => $referralUser->id,
    //                     'action' => 'update wallet',
    //                     'details' => $referralUser->toArray(),
    //                     'oldDetails' => $oldDetails,
    //                 ]);
    //             }
    //         }
    //     } elseif ($code == $superReferralCode) {
    //         $referral = ReferralCode::create([
    //             'tenant_id' => $tenantId,
    //             'user_id' => $referralUserId,
    //             'code' => $superReferralCode,
    //             'used' => 'used',
    //             'referral_user_id' => $user->id,
    //             'used_date' => now(),
    //         ]);

    //         $this->actontrail->create([
    //             'tenant_id' => $referralUserId,
    //             'table_name' => 'referral_codes',
    //             'reference_id' => $referral->id,
    //             'action' => 'add referral code used',
    //             'details' => $referral->toArray(),
    //         ]);

    //     }

    //     Auth::login($user);
    //     session([
    //         'fsr_authenticated' => true,
    //         'fsr_user_id' => $user->id,
    //         'fsr_user_name' => $user->name,
    //         'fsr_user_mobile' => $user->mobile,
    //     ]);

    //     return redirect()->route('fsrapp.home')->with('success', 'Profile completed! Welcome to FSRQuiz.');
    // }


        public function saveProfile(Request $request)
        {
            $request->validate([
                'name' => ['required', 'string', 'min:3', 'max:191', 'regex:/^[a-zA-Z\s]+$/'],
                'state' => 'nullable',
                'city' => 'nullable|string|max:191',
                'dob' => 'nullable|date',
                'gender' => 'nullable|string',
                'referral_code' => 'required|string|max:191',
                'language' => 'required|in:en,hi,hinglish',
            ], [
                'name.required' => 'Please enter your full name.',
                'name.min' => 'Full name must be at least 3 characters.',
                'name.regex' => 'Full name can only contain alphabets and spaces.',
                'referral_code.required' => 'Please enter your referral code.',
                'language.required' => 'Please select your language.',
                'language.in' => 'Please select a valid language.',
            ]);

            $profileCompletionStatus = function (?User $user = null) use ($request) {
                $name = trim((string) ($user?->name ?? $request->input('name') ?? ''));
                $state = trim((string) ($user?->state ?? $request->input('state') ?? ''));
                $city = trim((string) ($user?->city ?? $request->input('city') ?? ''));
                $dob = trim((string) ($user?->date_of_birth ?? $request->input('dob') ?? ''));
                $gender = trim((string) ($user?->gender ?? $request->input('gender') ?? ''));
                $referral = trim((string) ($request->input('referral_code') ?? $user?->referral_code_to ?? ''));

                $requiredFields = [$name, $state, $city, $dob, $gender, $referral];

                return collect($requiredFields)->every(fn ($value) => $value !== '') ? 'complete' : 'pending';
            };

            $selectedState = null;
            if ($request->filled('state')) {
                $selectedState = State::where('id', $request->state)
                    ->where('status', 'active')
                    ->first();
            }
            $stateName = $selectedState?->name;

            $mobile = session('pending_mobile') 
                ?? session('fsr_user_mobile') 
                ?? $request->input('mobile') 
                ?? (Auth::check() ? Auth::user()->mobile : null);

            if (!$mobile) {
                return redirect()->route('fsrapp.login')->with('error', 'Session expired. Please login again.');
            }

            // Ensure mobile is present in session
            session([
                'pending_mobile' => $mobile,
                'fsr_user_mobile' => $mobile,
            ]);

            $nameStr = $request->input('name');
            $tenantId = 1;
            //$initials = strtoupper(substr(preg_replace('/\s+/', '', $nameStr), 0, 3));
            //$generatedReferral = $initials . $tenantId . strtoupper(Str::random(6));
 
            $generatedReferral = $this->generateUniquePlayerName($nameStr);
  
            $referralUserId = null;
            $referralRecord = null; 
            $superReferralCode = AdminUser::where('role', 'superadmin')->value('referral_code');
            $code = trim($request->referral_code);
            $isSuperAdminCode = false;
            $givePoints = true; // ✅ Default: Points denge

             // ==========================================
            // USER CREATE / UPDATE LOGIC
            // ==========================================
            $firstUser = $user = Auth::check() ? Auth::user() : User::where('mobile', $mobile)->first();
       
            // ==========================================
            // ✅ FIXED REFERRAL CODE VALIDATION LOGIC
            // ==========================================
            if ($code == $superReferralCode) {
                // Super Admin Code: Unlimited use, no points, no wallet history
                $tenantId = 1;
                $referralUserId = 1;
                $isSuperAdminCode = true;
                $givePoints = false;
                
            } else {
                // Step 1: Check if this code already exists in ReferralCode table
                $existingReferral = ReferralCode::where('code', $code)
                    ->where('status', 'active')
                    ->orderByDesc('id')
                    ->first();

                 $currentUser = Auth::check() ? Auth::user() : User::where('mobile', $mobile)->first();

                $sameUserAlreadyLinked = $currentUser
                    && $existingReferral
                    && (int) ($existingReferral->referral_user_id ?? 0) === (int) $currentUser->id;

                // if ($existingReferral) {
                     if ($existingReferral && ($existingReferral->referral_user_id === null || $sameUserAlreadyLinked)) {
                    if ($existingReferral->used === 'pending') {
                        // ✅ Use this pending record
                           // Same user ke liye existing pending row reuse kar sakte hain
                        $referralRecord = $existingReferral;
                        $tenantId = $referralRecord->tenant_id;
                        $referralUserId = $referralRecord->user_id;
                    } else {
                        // ✅ Record is already used, check count
                        // $usedCount = ReferralCode::where('code', $code)
                        //     ->where('used', 'used')
                        //     ->count();

                        // Get referrer user details
                        $referrerUser = User::find($existingReferral->user_id);
                        
                        if (!$referrerUser) {
                            return back()->withInput()->with('error', 'Invalid referral code. User not found.');
                        }

                        $tenantId = $referrerUser->tenant_id;
                        $referralUserId = $referrerUser->id;

                        // ✅ CHECK LIMIT: Agar 5 se zyada ho gaya, to points band, par registration allow
                        // if ($usedCount >= 5) {
                        //     $givePoints = false; // Points nahi denge, par user register ho jayega
                        // }

                        // ✅ Create NEW pending record for tracking this usage
                        $referralRecord = ReferralCode::create([
                            'tenant_id' => $tenantId,
                            'user_id' => $referrerUser->id,
                            'code' => $code,
                            'status' => 'active',
                            'used' => 'pending',
                            'points' => $givePoints ? 10 : 0, // ✅ Limit cross hone par 0 points
                            'referral_point_provide' => $givePoints ? 'yes' : 'no',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }elseif ($existingReferral && $existingReferral->referral_user_id !== null && !$sameUserAlreadyLinked) {
                        // Same code kisi aur user ke saath already linked hai:
                        // old row overwrite nahi karna, naye record banao
                        $referrerUser = User::find($existingReferral->user_id);

                        if (!$referrerUser) {
                            return back()->withInput()->with('error', 'Invalid referral code. User not found.');
                        }

                        $tenantId = $referrerUser->tenant_id;
                        $referralUserId = $referrerUser->id;

                        $referralRecord = ReferralCode::create([
                            'tenant_id' => $tenantId,
                            'user_id' => $referrerUser->id,
                            'code' => $code,
                            'status' => 'active',
                            'used' => 'pending',
                            'points' => $givePoints ? 10 : 0,
                            'referral_point_provide' => $givePoints ? 'yes' : 'no',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        }
                 else {
                    // Step 2: No record in ReferralCode table, check User table
                    $referrerUser = User::where('referral_code', $code)->first();

                    if ($referrerUser) {
                        $tenantId = $referrerUser->tenant_id;
                        $referralUserId = $referrerUser->id;

                        // ✅ Create first record in ReferralCode table
                        $referralRecord = ReferralCode::create([
                            'tenant_id' => $tenantId,
                            'user_id' => $referrerUser->id,
                            'code' => $code,
                            'status' => 'active',
                            'used' => 'pending',
                            'points' => 10,
                            'referral_point_provide' => 'yes',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    } else {
                        return back()->withInput()->with('error', 'Invalid referral code. Please check and try again.');
                    }
                }
            }

           
            if ($user && (!$user->is_active || (int) $user->is_active === 0)) {
                $userId = $user->id;
                Cache::forget('email_otp_' . $userId);
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                session()->flush();
                return redirect()->route('fsrapp.login')->with('error', 'Your account has been deactivated. Please contact administrator.');
            }

            if (!$user) {
                $user = User::create([
                    'tenant_id' => $tenantId,
                    'name' => $nameStr,
                    'mobile' => $mobile,
                    'login_type' => 'web',
                    'language' => $request->input('language'),
                    'login_method' => 'otp',
                    'password' => bcrypt($mobile),
                    'role' => 'user',
                    'role_id' => 3,
                    'verify_mobile' => 1,
                    'state' => $stateName,
                    'city' => $request->input('city'),
                    'date_of_birth' => $request->input('dob'),
                    'gender' => $request->input('gender'),
                    'referral_code' => $generatedReferral,
                    'referral_code_to' => $request->input('referral_code'),
                    'referral_user_id' => $referralUserId,
                    'create_quiz_limit' => 4,
                    'available_quiz_limit' => 10,
                    'quiz_leave' => 0,
                    'wallet_balance' => 0,
                    'is_active' => 1,
                    'can_login' => 1,
                    'otp_enabled' => 1,
                    'session_token' => Str::random(60),
                    'last_login_at' => now(),
                ]);

                $this->actontrail->create([
                    'tenant_id' => $user->tenant_id,
                    'table_name' => 'users',
                    'reference_id' => $user->id,
                    'action' => 'add user profile',
                    'details' => $user->toArray(),
                ]);
            } else {
                $userOldDetails = $user->toArray();
                $user->update([
                    'name' => $nameStr,
                    'login_type' => 'web',
                    'language' => $request->input('language'),
                    'state' => $stateName,
                    'city' => $request->input('city'),
                    'date_of_birth' => $request->input('dob'),
                    'gender' => $request->input('gender'),
                    'referral_code_to' => $request->input('referral_code') ?? $user->referral_code_to,
                    'referral_user_id' => $referralUserId ?? $user->referral_user_id,
                    'verify_mobile' => 1,
                    'is_active' => 1,
                    'can_login' => 1,
                    'session_token' => Str::random(60),
                    'last_login_at' => now(),
                ]);

                $this->actontrail->create([
                    'tenant_id' => $user->tenant_id,
                    'table_name' => 'users',
                    'reference_id' => $user->id,
                    'action' => 'update user profile',
                    'details' => $user->toArray(),
                    'oldDetails' => $userOldDetails,
                ]);
            }

            // ==========================================
            // ✅ REFERRAL PROCESSING LOGIC
            // ==========================================
            
            if ($isSuperAdminCode) {
                // Super Admin Code: Sirf entry banao, koi points nahi, koi wallet history nahi
                $referralRecord = ReferralCode::create([
                    'tenant_id' => $tenantId,
                    'user_id' => $referralUserId,
                    'code' => $superReferralCode,
                    'status' => 'active',
                    'used' => 'used',
                    'referral_user_id' => $user->id,
                    'used_date' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $this->actontrail->create([
                    'tenant_id' => $referralUserId,
                    'table_name' => 'referral_codes',
                    'reference_id' => $referralRecord->id,
                    'action' => 'add super admin referral code used',
                    'details' => $referralRecord->toArray(),
                ]);

            } elseif ($referralRecord) {
                // Referral code me naye user ki pending entry (reward first quiz play karne par cron job se milega)
                $referralOldDetails = $referralRecord->toArray();
                
                $referralRecord->update([
                    'referral_user_id' => $user->id,
                    'used'             => 'pending',
                    'updated_at'       => now(),
                ]);

                $this->actontrail->create([
                    'tenant_id'    => $referralRecord->tenant_id,
                    'table_name'   => 'referral_codes',
                    'reference_id' => $referralRecord->id,
                    'action'       => 'link user to pending referral code',
                    'details'      => $referralRecord->toArray(),
                    'oldDetails'   => $referralOldDetails,
                ]);
            }

            $previousProfileStatus = $user->profile_status ?? 'pending';
            $newProfileStatus = $profileCompletionStatus($user);
            $user->profile_status = $newProfileStatus;

            if ($previousProfileStatus !== 'complete' && $newProfileStatus === 'complete') {
                $oldWalletBalance = (float) ($user->wallet_balance ?? 0);
                $rewardAmount = 20;
                $newWalletBalance = $oldWalletBalance + $rewardAmount;

                $user->wallet_balance = $newWalletBalance;
                $user->save();

                WalletHistory::create([
                    'tenant_id' => $user->tenant_id ?? 1,
                    'user_id' => $user->id,
                    'quiz_id' => null,
                    'referral_user_id' => null,
                    'survey_id' => null,
                    'old_wallet_balance' => $oldWalletBalance,
                    'amount' => $rewardAmount,
                    'new_wallet_balance' => $newWalletBalance,
                    'type' => 'credit',
                    'transaction_type' => 'Profile_Completed',
                    'reference_id' => 'profile-completion-'.$user->id,
                    'remarks' => 'Profile Completed Reward',
                ]);
            } else {
                $user->save();
            }

            // ==========================================
            // LOGIN & REDIRECT
            // ==========================================
            Auth::login($user, true);
            session([
                'fsr_authenticated' => true,
                'fsr_user_id' => $user->id,
                'fsr_user_name' => $user->name,
                'fsr_user_mobile' => $user->mobile,
                'is_first_login' => true,
            ]);

            $autoJoinQuizId = session('auto_join_quiz_id');
            $redirectTo = $request->input('redirect') ?? session('redirect_to') ?? session('url.intended');
            session()->forget(['redirect_to', 'url.intended', 'auto_join_quiz_id']);

           if (!$firstUser || (empty($firstUser->name) && empty($firstUser->referral_code))) {
                $this->sendWelcomeMessage($user->mobile ?? $mobile, $request->input('name') ?? $nameStr, $user->tenant_id ?? 1);
            }
            
            if ($autoJoinQuizId) {
                $joinRes = app(\App\Interfaces\api\QuizRepositoryInterface::class)->joinSubmitData($user, (int) $autoJoinQuizId);
                $qModel = \App\Models\Quiz::find($autoJoinQuizId);
                if (!empty($joinRes['success'])) {
                    $targetRoute = $joinRes['redirect'] ?? (($qModel && $qModel->visibility === 'fastest_finger_first') ? route('fsrapp.fff.waiting', $autoJoinQuizId) : route('fsrapp.quizes.waiting', $autoJoinQuizId));
                    return redirect($targetRoute)->with('success', $joinRes['message'] ?? 'Profile completed! Joined quiz successfully!');
                } else {
                    return redirect($joinRes['redirect'] ?? route('fsrapp.home'))->with('warning', $joinRes['message'] ?? 'Could not join quiz.');
                }
            }

            if (FsrAppAuthMiddleware::isValidRedirectUrl($redirectTo)) {
                return redirect()->to($redirectTo)->with('success', 'Profile completed! Joining your quiz...');
            }

            return redirect()->route('fsrapp.home')->with('success', 'Profile completed! Welcome to FSRQuiz.');
        }

        protected function sendWelcomeMessage($mobile, $name, $tenantId = null)
        {
            if (empty($mobile)) {
                return;
            }

            try {
                $whatsAppMobile = str_starts_with($mobile, '91') ? $mobile : '91' . $mobile;
           
                $check = NotificationHistory::where('send_to', $whatsAppMobile)->where('notification_type', 'fsrquiz_welcome')->first();
                if ($check) {
                    return;
                }
                
                $tenant = $tenantId ? Tenant::find($tenantId) : null;
                if (! $tenant) {
                    $tenant = Tenant::first() ?? Tenant::find(1);
                }

                $parameters = [
                    ['name' => 'name', 'value' => $name ?? 'Player']
                ];

                $buttonParameters = [];

                $response = $this->whatsAppRepository->sendWhatsAppMessage(
                    $whatsAppMobile,
                    'FSRQUIZ_WELCOME',
                    $parameters,
                    $buttonParameters,
                    $tenant
                );

                if ($response) {
                    Log::info("WhatsApp Welcome Message sent to {$mobile}");
                }
            } catch (\Throwable $e) {
                Log::error("Failed to send WhatsApp Welcome Message to {$mobile}: " . $e->getMessage());
            }    
        }
    /* ------------------------------------------------------------------ */
    /*  Update Profile — full field set + profile photo (base64) */
    /* ------------------------------------------------------------------ */
    private function normalizeQuizPreferences(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        $items = json_decode($value, true);

        if (! is_array($items)) {
            return null;
        }

        return json_encode(collect($items)
            ->filter(fn ($item) => is_array($item) && isset($item['id'], $item['name']))
            ->map(fn ($item) => [
                'id' => (int) $item['id'],
                'name' => trim((string) $item['name']),
            ])
            ->filter(fn ($item) => $item['name'] !== '')
            ->unique('id')
            ->values()
            ->all());
    }

    private function isProfileComplete(User $user): bool
    {
        // Normal required fields
        $requiredFields = [
            'alternative_mobile',
            'state',
            'city',
            'address',
            'date_of_birth',
            'gender',
            'name',
            'referral_code_to',
            'profile_photo',
        ];

        foreach ($requiredFields as $field) {
            if (blank($user->{$field})) {
                return false;
            }
        }

        // JSON / Array fields
        $arrayFields = [
            'favorite_color',
            'interest',
            'games',
            'hobbies',
        ];

        foreach ($arrayFields as $field) {
            $value = $user->{$field};

            // null / empty string
            if (blank($value)) {
                return false;
            }

            // JSON string "[]"
            if (is_string($value)) {
                $decoded = json_decode($value, true);

                if (json_last_error() === JSON_ERROR_NONE && empty($decoded)) {
                    return false;
                }
            }

            // Actual empty array []
            if (is_array($value) && empty($value)) {
                return false;
            }
        }

        return true;
    }

    private function applyProfileCompletionReward(User $user): void
    {
        $previousStatus = $user->profile_status ?? 'pending';
        if ($previousStatus === 'complete') {
            return;
        }

         // Check complete profile
        $isComplete = $this->isProfileComplete($user);

        // Profile incomplete
        if (! $isComplete) {
            $user->profile_status = 'pending';
            $user->save();

            return;
        }

        $requiredValues = [
            trim((string) ($user->name ?? '')),
            trim((string) ($user->state ?? '')),
            trim((string) ($user->city ?? '')),
            trim((string) ($user->date_of_birth ?? '')),
            trim((string) ($user->gender ?? '')),
            trim((string) ($user->referral_code_to ?? '')),
        ];

        $isComplete = collect($requiredValues)->every(fn ($value) => $value !== '');

        if (! $isComplete) {
            $user->profile_status = 'pending';
            $user->save();
            return;
        }

        $oldWalletBalance = (float) ($user->wallet_balance ?? 0);
        $rewardAmount = 20;
        $newWalletBalance = $oldWalletBalance + $rewardAmount;

        $user->wallet_balance = $newWalletBalance;
        $user->profile_status = 'complete';
        $user->save();

        WalletHistory::create([
            'tenant_id' => $user->tenant_id ?? 1,
            'user_id' => $user->id,
            'quiz_id' => null,
            'referral_user_id' => null,
            'survey_id' => null,
            'old_wallet_balance' => $oldWalletBalance,
            'amount' => $rewardAmount,
            'new_wallet_balance' => $newWalletBalance,
            'type' => 'credit',
            'transaction_type' => 'Profile_Completed',
            'reference_id' => 'profile-completion-'.$user->id,
            'remarks' => 'Profile Completed Reward',
        ]);

        $this->sendWelcomeMessage($user->mobile, $user->name, $user->tenant_id ?? 1);
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Please login first.'], 401);
        }

        $request->validate([
            'name' => 'nullable|string|max:191',
            'city' => 'nullable|string|max:191',
            'email' => 'nullable|email|max:191',
            'alternative_mobile' => 'nullable|string|max:15',
            'state' => 'nullable|string|max:191',
            'address' => 'nullable|string|max:500',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'favorite_color' => 'nullable|json',
            'hobbies' => 'nullable|json',
            'games' => 'nullable|json',
            'interest' => 'nullable|json',
            // 'profile_photo'      => 'nullable|string', // base64 data URL
            'profile_photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:1024',
        ]);

        $oldDetails = $user->toArray();

        $updateData = [
            'name' => $request->name,
            'city' => $request->city,
            'email' => $request->email,
            'alternative_mobile' => $request->alternative_mobile,
            'state' => $request->state,
            'address' => $request->address,
            'date_of_birth' => $request->date_of_birth,
            'gender' => $request->gender,
            'favorite_color' => $this->normalizeQuizPreferences($request->input('favorite_color')),
            'hobbies' => $this->normalizeQuizPreferences($request->input('hobbies')),
            'games' => $this->normalizeQuizPreferences($request->input('games')),
            'interest' => $this->normalizeQuizPreferences($request->input('interest')),
        ];

        // ✅ NEW LOGIC: Agar player_name empty hai, toh auto-generate karo
        if (empty($user->player_name)) {
            $updateData['player_name'] = $this->generateUniquePlayerName($request->name);
        }

        if ($request->hasFile('profile_photo')) {

            // Purani image delete
            if ($user->profile_photo) {
                try {
                    Storage::disk('s3')->delete($user->profile_photo);
                } catch (\Exception $e) {
                    Log::warning(
                        'Old profile photo delete failed: '.$e->getMessage()
                    );
                }
            }

            $file = $request->file('profile_photo');

            if ($file && $file->isValid()) {

                // Unique filename
                $imageName = uniqid(time(), true).'.webp';

                /*
                |--------------------------------------------------------------------------
                | Resize Image
                |--------------------------------------------------------------------------
                | Fixed size: 300 x 300
                | cover() crop karke image ko exactly 300x300 banayega
                */
                $manager = new ImageManager(new Driver);
                $image = $manager->decode($file);

                $image->cover(300, 300);

                // WebP encode
                $encoded = $image->encode(new WebpEncoder(90));

                $path = "users/{$imageName}";

                // Upload resized image to S3
                Storage::disk('s3')->put(
                    $path,
                    (string) $encoded,
                    'public'
                );

                $updateData['profile_photo'] = $path;
            }
        }

        $user->update($updateData);
        $user->refresh();

        $this->applyProfileCompletionReward($user);

        // Update email verification status if email changed
        if ($request->filled('email') && $request->email !== $oldDetails['email']) {
            $user->update(['verify_email' => false]);
            $user->refresh();
        }

        session(['fsr_user_name' => $user->name]);

        $this->actontrail->create([
            'tenant_id' => $user->tenant_id,
            'table_name' => 'users',
            'reference_id' => $user->id,
            'action' => 'update profile',
            'details' => $user->toArray(),
            'oldDetails' => $oldDetails,
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully.',
                'user' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'mobile' => $user->mobile,
                    'alternative_mobile' => $user->alternative_mobile,
                    'city' => $user->city,
                    'state' => $user->state,
                    'address' => $user->address,
                    'date_of_birth' => $user->date_of_birth,
                    'gender' => $user->gender,
                    'favorite_color' => $user->favorite_color,
                    'hobbies' => $user->hobbies,
                    'games' => $user->games,
                    'interest' => $user->interest,
                    'profile_photo' => $user->profile_photo
                        ? asset('storage/'.$user->profile_photo)
                        : null,
                    'verify_email' => (bool) $user->verify_email,
                    'verify_mobile' => (bool) $user->verify_mobile,
                ],
            ]);
        }

        return redirect()->route('fsrapp.profile')->with('success', 'Profile updated successfully.');
    }

    /*  Mark mobile as verified (demo/admin shortcut) */

    public function verifyMobile()
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        $user->update(['verify_mobile' => true]);

        return response()->json(['success' => true, 'message' => 'Mobile verified successfully.']);
    }

    /* ------------------------------------------------------------------ */
    /*  Mark email as verified (demo/admin shortcut) */
    /* ------------------------------------------------------------------ */
    public function sendEmailOtp(Request $request)
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        if (! $user->email) {
            return response()->json(['success' => false, 'message' => 'No email address found.'], 400);
        }

        $otp = rand(100000, 999999);
        Cache::put('email_otp_'.$user->id, $otp, now()->addMinutes(10));

        try {
            // Attempt to send email
            Mail::raw("Your verification OTP is: {$otp}. It is valid for 10 minutes.", function ($message) use ($user) {
                $message->to($user->email)->subject('Email Verification OTP');
            });
        } catch (\Exception $e) {
            Log::error('Failed to send email OTP: '.$e->getMessage());
            // Optionally, handle failure if mail is not configured but for production we should report it.
            // return response()->json(['success' => false, 'message' => 'Failed to send OTP email.'], 500);
        }

        return response()->json(['success' => true, 'message' => 'OTP sent successfully.']);
    }

    /* ------------------------------------------------------------------ */
    /*  Mark email as verified (demo/admin shortcut) */
    /* ------------------------------------------------------------------ */
    public function verifyEmail(Request $request)
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $otp = $request->input('otp');
        $cachedOtp = Cache::get('email_otp_'.$user->id);

        if (! $cachedOtp || $otp != $cachedOtp) {
            return response()->json(['success' => false, 'message' => 'Invalid or expired OTP. Please try again.'], 400);
        }

        $user->update(['verify_email' => true]);
        Cache::forget('email_otp_'.$user->id);

        return response()->json(['success' => true, 'message' => 'Email verified successfully.']);
    }

    public function getCities(Request $request)
    {
        $request->validate([
            'state_id' => 'required|integer',
        ]);

        $cities = City::where('state_id', $request->state_id)
            ->where('status', 'active')
            ->orderBy('name', 'asc')
            ->get(['id', 'name']);

        return response()->json([
            'success' => true,
            'cities' => $cities,
        ]);
    }

    // ***********player name genrate**************

    private function generateUniquePlayerName(string $userName): string
    {
        $prefix = strtoupper(str_pad(substr($userName, 0, 3), 3, 'X'));

        do {
            $randomSuffix = str_pad((string) rand(0, 99999), 5, '0', STR_PAD_LEFT);

            $playerName = $prefix.$randomSuffix;

        } while (User::where('player_name', $playerName)->exists());

        return $playerName;
    }
    // ***********player name genrate**************

    public function updateLanguage(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Please login first.'], 401);
            }
            return redirect()->route('fsrapp.login')->with('error', 'Please login first.');
        }

        $request->validate([
            'language' => 'required|in:en,hi,hinglish',
        ]);

        $newLanguage = $request->input('language');

        $user->update([
            'language' => $newLanguage,
        ]);

        session(['locale' => $newLanguage]);
        app()->setLocale($newLanguage);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Language updated successfully.',
                'language' => $user->language,
            ]);
        }

        return redirect()->back()->with('success', 'Language updated successfully!');
    }

    /**
     * Privacy Policy Page for Mobile Web App
     */
    public function privacyPolicy()
    {
        $cmsPage = \App\Models\CmsPage::where('slug', 'privacy-policy')->where('status', 'published')->first();
        return view('fsrapp.profile.privacy-policy', compact('cmsPage'));
    }

    /**
     * Terms & Conditions Page for Mobile Web App
     */
    public function termsConditions()
    {
        $cmsPage = \App\Models\CmsPage::where(function ($q) {
            $q->where('slug', 'terms-conditions')->orWhere('slug', 'terms-of-use');
        })->where('status', 'published')->first();
        return view('fsrapp.profile.terms-conditions', compact('cmsPage'));
    }

    /**
     * Delete Account View for Mobile Web App
     */
    public function deleteAccountView()
    {
        $user = Auth::user();
        if (!$user && session('fsr_user_id')) {
            $user = User::find(session('fsr_user_id'));
        }
        return view('fsrapp.profile.delete-account', compact('user'));
    }

    /**
     * Handle Delete / Deactivate Account
     */
    public function deleteAccount(Request $request)
    {
        $user = Auth::user();
        if (!$user && session('fsr_user_id')) {
            $user = User::find(session('fsr_user_id'));
        }

        if (!$user) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'User not found.'], 404);
            }
            return redirect()->route('fsrapp.login')->with('error', 'User not found.');
        }

        // Deactivate user account
        $user->is_active = 0;
        $user->can_login = 0;
        $user->session_token = null;
        $user->save();

        // Clear user session & cache
        $userId = $user->id;
        Cache::forget('email_otp_' . $userId);
        Auth::logout();
        session()->forget([
            'pending_mobile', 'otp', 'user_exists',
            'fsr_authenticated', 'fsr_user_id', 'fsr_user_name',
            'fsr_user_mobile', 'is_first_login'
        ]);
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        session()->flush();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => __('app.account_deleted_success'),
                'redirect' => route('fsrapp.login')
            ]);
        }

        return redirect()->route('fsrapp.login')->with('success', __('app.account_deleted_success'));
    }
}
