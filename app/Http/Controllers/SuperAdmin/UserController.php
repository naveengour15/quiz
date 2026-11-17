<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;
use Intervention\Image\Encoders\WebpEncoder;
use App\Models\LeaderPointHistory;
use App\Models\QuizUser;
use App\Models\QuizWinner;
use App\Models\WalletHistory;
use App\Models\UsersWhatsappNotify;
use App\Models\UserWhatsappSubscribe;
use Illuminate\Validation\Rule;
use App\Models\ReferralCode;
use Illuminate\Support\Facades\DB;

use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    // public function index(Request $request)
    // {
    //     $perPage = $request->get('per_page', 10);
    //     $baseQuery = User::query()->where('tenant_id', getSuperAdminTenantId());
    //     $users = User::query()
    //         ->where('role', 'user') // Sirf User
    //         ->where('tenant_id', getSuperAdminTenantId())
    //          ->withCount(['quizUsers', 'practiceQuizUsers'])
    //         ->withCount('referralsAllowed')
    //         //  ->with('referrer') 
    //         ->with([
    //             'referrer',
    //             'leaderboard',
    //         ])
       
    //         ->when($request->search, function ($q) use ($request) {
    //             $q->where(function ($query) use ($request) {
    //                 $query->where('name', 'like', "%{$request->search}%")
    //                     ->orWhere('email', 'like', "%{$request->search}%")
    //                     ->orWhere('mobile', 'like', "%{$request->search}%")
    //                     ->orWhere('referral_code', 'like', "%{$request->search}%");
    //             });
    //         })
    //         ->when($request->status, function ($q) use ($request) {
    //             $q->where('is_active', $request->status === 'active' ? 1 : 0);
    //         })
    //         ->when($request->filled('profile_status'), function ($q) use ($request) {
    //                 $q->where('profile_status', trim($request->profile_status));
    //         })

    //         ->when($request->filled('gender'), function ($q) use ($request) {
    //                 $gender = trim($request->gender);

    //                 if ($gender === 'not_selected') {
    //                     $q->where(function ($query) {
    //                         $query->whereNull('gender')
    //                             ->orWhere('gender', '');
    //                     });
    //                 } else {
    //                     $q->where('gender', $gender);
    //                 }
    //             })

    //         //  Numeric Quiz Limit Sorting
    //         ->when($request->quiz_limit_sort === 'low_high', function ($q) {
    //             $q->orderByRaw('CAST(available_quiz_limit AS UNSIGNED) ASC');
    //         })

    //         ->when($request->quiz_limit_sort === 'high_low', function ($q) {
    //             $q->orderByRaw('CAST(available_quiz_limit AS UNSIGNED) DESC');
    //         })

    //         // Default latest
    //         ->when(!$request->quiz_limit_sort, function ($q) {
    //             $q->latest();
    //         })

    //         // ->latest()
    //         ->paginate($perPage)
    //         ->withQueryString();

    //     // ✅ Accurate Counts (Sirf current tenant ke users ke liye)
    //     $totalUsers = $baseQuery->count();
    //     $activeUsers = (clone $baseQuery)->where('is_active', 1)->count();
    //     $suspendedUsers = (clone $baseQuery)->where('is_active', 0)->count();

    //     $pendingProfiles  = (clone $baseQuery)->where('profile_status', 'pending')->count();
    //     $completeProfiles = (clone $baseQuery)->where('profile_status', 'complete')->count();


    //     $editing = $request->edit ? User::find($request->edit) : null;
    //     $states = \App\Models\State::where('status', 'active')->orderBy('name')->get();

    //     return view('superadmin.users.index', compact('users', 'editing', 'states','totalUsers','activeUsers','suspendedUsers', 'pendingProfiles',
    //     'completeProfiles'));
    // }

    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 10);

        // Common query: Role + Tenant
        $filteredQuery = User::query()
            ->where('role', 'user')
            ->where('tenant_id', getSuperAdminTenantId())

            // Search Filter
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = trim($request->search);

                $q->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('mobile', 'like', "%{$search}%")
                        ->orWhere('referral_code', 'like', "%{$search}%");
                });
            })

            // Active / Suspended Filter
            ->when($request->filled('status'), function ($q) use ($request) {
                $q->where('is_active', $request->status === 'active' ? 1 : 0);
            })

            // Profile Status Filter
            ->when($request->filled('profile_status'), function ($q) use ($request) {
                $q->where('profile_status', trim($request->profile_status));
            })

            // Gender Filter
            ->when($request->filled('gender'), function ($q) use ($request) {
                $gender = trim($request->gender);

                if ($gender === 'not_selected') {
                    $q->where(function ($query) {
                        $query->whereNull('gender')
                            ->orWhere('gender', '');
                    });
                } else {
                    $q->where('gender', $gender);
                }
            });

        // Counts: Pagination se pehle
        $totalUsers = (clone $filteredQuery)->count();

        $activeUsers = (clone $filteredQuery)
            ->where('is_active', 1)
            ->count();

        $suspendedUsers = (clone $filteredQuery)
            ->where('is_active', 0)
            ->count();

        $pendingProfiles = (clone $filteredQuery)
            ->where('profile_status', 'pending')
            ->count();

        $completeProfiles = (clone $filteredQuery)
            ->where('profile_status', 'complete')
            ->count();

        // Users Listing + Pagination
        $usersQuery = (clone $filteredQuery)
            ->withCount(['quizUsers', 'practiceQuizUsers'])
            ->withCount('referralsAllowed')
            ->with([
                'referrer',
                'leaderboard',
            ]);

        // Quiz Limit Sorting
        if ($request->quiz_limit_sort === 'low_high') {
            $usersQuery->orderByRaw('CAST(available_quiz_limit AS UNSIGNED) ASC');
        } elseif ($request->quiz_limit_sort === 'high_low') {
            $usersQuery->orderByRaw('CAST(available_quiz_limit AS UNSIGNED) DESC');
        } else {
            $usersQuery->latest();
        }

        $users = $usersQuery
            ->paginate($perPage)
            ->withQueryString();

        $editing = $request->edit
            ? User::where('tenant_id', getSuperAdminTenantId())
                ->find($request->edit)
            : null;

        $states = \App\Models\State::where('status', 'active')
            ->orderBy('name')
            ->get();

        return view('superadmin.users.index', compact(
            'users',
            'editing',
            'states',
            'totalUsers',
            'activeUsers',
            'suspendedUsers',
            'pendingProfiles',
            'completeProfiles'
        ));
    }

    public function getCities($stateId)
    {
        $cities = \App\Models\City::where('state_id', $stateId)->orderBy('name')->get(['id', 'name']);
        return response()->json($cities);
    }
    public function store(Request $request)
    {
        $data = $this->validateUser($request);

        // if ($request->hasFile('profile_photo')) {
        //     $data['profile_photo'] = $request->file('profile_photo')->store('users', 'public');
        // }

        if ($request->hasFile('profile_photo')) {

            $file = $request->file('profile_photo');

            if ($file && $file->isValid()) {

                $imageName = uniqid(time(), true) . '.webp';

                $img = Image::decode($file);

                $encoded = $img->encode(
                    new WebpEncoder(quality: 90)
                );

                $path = "users/{$imageName}";

                Storage::disk('s3')->put(
                    $path,
                    (string) $encoded,
                    'public'
                );

                $data['profile_photo'] = $path;
            }
        }
        if (empty($data['password'])) {
            $data['password'] = Hash::make('123456');
        } else {
            $data['password'] = Hash::make($data['password']);
        }

         $data['tenant_id'] = getSuperAdminTenantId();
        $data['login_method'] = $data['login_method'] ?? 'otp';
        $data['can_login'] = $data['is_active'] ?? 1;

        if (!empty($data['role'])) {
            $data['role_id'] = \App\Models\Role::where('slug', $data['role'])
                ->orWhere('name', $data['role'])
                ->value('id');
        }

        // Auto Generate Lat Long if address is provided
        if (!empty($data['address']) && empty($data['lat']) && empty($data['long'])) {
            try {
                $response = \Illuminate\Support\Facades\Http::withoutVerifying()
                    ->withHeaders(['User-Agent' => 'QuizHubApp/1.0'])
                    ->get('https://nominatim.openstreetmap.org/search', [
                        'format' => 'json',
                        'q' => $data['address'],
                        'limit' => 1
                    ]);
                $result = $response->json();
                if (!empty($result) && isset($result[0])) {
                    $data['lat'] = round((float) $result[0]['lat'], 8);
                    $data['long'] = round((float) $result[0]['lon'], 8);
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Geocode failed: ' . $e->getMessage());
            }
        } else {
            if (isset($data['lat'])) $data['lat'] = round((float) $data['lat'], 8);
            if (isset($data['long'])) $data['long'] = round((float) $data['long'], 8);
        }

        \Illuminate\Support\Facades\Log::info('Saving User Data:', $data);
        $user = User::create($data);

        if (empty($user->referral_code)) {
            $user->referral_code = 'T' . ($user->tenant_id ?? 1) . 'U' . $user->id . strtoupper(Str::random(4));
            $user->save();
        }

        AuditLog::log('Created user', 'Users', $user->name);

        return redirect()->route('superadmin.users.index')->with('success', 'User created successfully.');
    }

    public function update(Request $request, User $user)
    {
        $data = $this->validateUser($request, $user->id);

        // if ($request->hasFile('profile_photo')) {
        //     if ($user->profile_photo && Storage::disk('public')->exists($user->profile_photo)) {
        //         Storage::disk('public')->delete($user->profile_photo);
        //     }
        //     $data['profile_photo'] = $request->file('profile_photo')->store('users', 'public');
        // }


        if ($request->hasFile('profile_photo')) {

            // Purani image delete
            if ($user->profile_photo) {
                try {
                    Storage::disk('s3')->delete($user->profile_photo);
                } catch (\Exception $e) {
                    \Log::warning($e->getMessage());
                }
            }

            $file = $request->file('profile_photo');

            if ($file && $file->isValid()) {

                $imageName = uniqid(time(), true) . '.webp';

                $img = Image::decode($file);

                $encoded = $img->encode(
                    new WebpEncoder(quality: 90)
                );

                $path = "users/{$imageName}";

                Storage::disk('s3')->put(
                    $path,
                    (string) $encoded,
                    'public'
                );

                $data['profile_photo'] = $path;
            }
        }
        if (empty($data['password'])) {
            unset($data['password']);
        } else {
            $data['password'] = Hash::make($data['password']);
        }

        // Referral code is fixed once generated - do not overwrite
        unset($data['referral_code']);

        $data['tenant_id'] = getSuperAdminTenantId();
        $data['can_login'] = $data['is_active'] ?? 1;

        if (!empty($data['role'])) {
            $data['role_id'] = \App\Models\Role::where('slug', $data['role'])
                ->orWhere('name', $data['role'])
                ->value('id');
        }

        // 3. ✅ FIX: Agar wallet_balance null ya empty hai, toh usse update mat karo (purani value rahegi)
        if (array_key_exists('wallet_balance', $data) && (is_null($data['wallet_balance']) || $data['wallet_balance'] === '')) {
            unset($data['wallet_balance']);
        } else {
            // Agar value aayi hai, toh ensure karo ki wo numeric ho (default 0)
            $data['wallet_balance'] = (float) ($data['wallet_balance'] ?? 0);
        }

        // Auto Generate Lat Long if address is provided and changed
        if (!empty($data['address']) && empty($data['lat']) && empty($data['long'])) {
            try {
                $response = \Illuminate\Support\Facades\Http::withoutVerifying()
                    ->withHeaders(['User-Agent' => 'QuizHubApp/1.0'])
                    ->get('https://nominatim.openstreetmap.org/search', [
                        'format' => 'json',
                        'q' => $data['address'],
                        'limit' => 1
                    ]);
                $result = $response->json();
                if (!empty($result) && isset($result[0])) {
                    $data['lat'] = round((float) $result[0]['lat'], 8);
                    $data['long'] = round((float) $result[0]['lon'], 8);
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Geocode failed in update: ' . $e->getMessage());
            }
        } else {
            if (isset($data['lat'])) $data['lat'] = round((float) $data['lat'], 8);
            if (isset($data['long'])) $data['long'] = round((float) $data['long'], 8);
        }

        \Illuminate\Support\Facades\Log::info('Updating User Data:', $data);
        $user->update($data);

        if (empty($user->referral_code)) {
            $user->referral_code = 'T' . ($user->tenant_id ?? 1) . 'U' . $user->id . strtoupper(Str::random(4));
            $user->save();
        }

        AuditLog::log('Updated user', 'Users', 'U-' . $user->id);

        return redirect()->route('superadmin.users.index')->with('success', 'User updated successfully.');
    }

    private function validateUser(Request $request, $id = null)
    {
        return $request->validate([
            'name' => 'required|string|max:191',
            // 'email' => 'nullable|email:rfc|max:191',
            'email' => [
                'nullable',
                'regex:/^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.(com|in|org|net|co\.in)$/i',
                'max:191',
            ],
            'mobile' => 'nullable|digits:10',
            // 'alternative_mobile' => 'nullable|digits:10|different:mobile',
             'alternative_mobile' => 'nullable|digits:10',
            'password' => 'nullable|string|min:6',
            'role' => 'required|string',
            'login_method' => 'nullable|string|in:password,otp,inactive',
            'gender' => 'nullable|string|in:male,female,other',
            'date_of_birth' => 'nullable|date',
            'favorite_color' => 'nullable|string|max:191',
            'hobbies' => 'nullable|string',
            'games' => 'nullable|string',
            'interest' => 'nullable|string',
            'state' => 'nullable|string|max:191',
            'city' => 'nullable|string|max:191',
            'address' => 'nullable|string',
            'lat' => 'nullable|numeric',
            'long' => 'nullable|numeric',
            'create_quiz_limit' => 'nullable|integer|min:0',
            'available_quiz_limit' => 'nullable|integer|min:0',
            'quiz_leave' => 'nullable|integer|min:0',
            'quiz_rank' => 'nullable|integer|min:0',
            'wallet_balance' => 'nullable|numeric|min:0',
            'verify_mobile' => 'nullable|boolean',
            'verify_email' => 'nullable|boolean',
            'is_active' => 'required|boolean',
            'profile_photo' => 'nullable|image|max:4096',
        ]);
    }

    public function destroy(User $user)
    {
        $user->update(['is_active' => 0, 'can_login' => 0]);
        AuditLog::log('Suspended user', 'Users', 'U-' . $user->id);

        return back()->with('success', 'User suspended.');
    }

    public function changeStatus(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:users,id',
            'status' => 'required|boolean',
        ]);

        $user = User::findOrFail($request->id);

        $user->update([
            'is_active' => $request->status,
            'can_login' => $request->status,
        ]);

        AuditLog::log(
            'Updated User Status',
            'Users',
            $user->name.' ('.($request->status ? 'Active' : 'Suspended').')'
        );

        return response()->json([
            'success' => true
        ]);
    }

    

     public function leaderPointHistory(User $user)
    {
        $history = LeaderPointHistory::where('user_id', $user->id)
            ->where('tenant_id', getSuperAdminTenantId())
            ->with('quiz')
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'history' => $history,
        ]);
    }

    public function walletHistory(User $user)
    {
        $tenantId = getSuperAdminTenantId();

        $pointHistory = QuizWinner::query()
            ->where('user_id', $user->id)
            ->where('tenant_id', $tenantId)
            ->with([
                'quiz:id,title',
            ])
            ->latest()
            ->get([
                'id',
                'tenant_id',
                'quiz_id',
                'user_id',
                'reward',
                'reward_type',
                'created_at',
            ]);

      
        $voucherHistory = QuizWinner::query()
            ->where('user_id', $user->id)
            ->where('tenant_id', $tenantId)
            ->whereNotNull('voucher_history_id')
            ->with([
                'voucherHistory:id,voucher_id,voucher_type,voucher_point,code,assign_to_user_id,assign_date,expire_date,used_date,status',
                'voucherHistory.voucher:id,name,partner',
            ])
            ->latest()
            ->get([
                'id',
                'tenant_id',
                'user_id',
                'voucher_history_id',
                'created_at',
            ]);

        $walletHistory = WalletHistory::query()
            ->where('user_id', $user->id)
            ->where('tenant_id', $tenantId)
            ->latest()
            ->get([
                'id',
                'tenant_id',
                'user_id',
                'amount',
                'type',
                'transaction_type',
                'remarks',
                'created_at',
            ]);


        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
            ],
            'points' => $pointHistory,
            'vouchers' => $voucherHistory,
            'wallet' => $walletHistory,
            
        ]);
    }

    // *********update play limit add**********

    public function bulkUpdatePlayLimit(Request $request)
    {
        $request->validate([
            'add_limit' => 'required|integer|min:1',
            'max_current_limit' => 'nullable|integer|min:0', // ✅ NEW Validation
        ]);

        $addLimit = $request->add_limit;
        $maxLimit = $request->max_current_limit;

        // 1. Base Query: ONLY Active AND ON users
        $query = User::where('is_active', 1)
                    ->where('on_off', 'on');

        // 2. Conditional Query: Agar max_current_limit diya gaya hai, toh filter lagao
        if ($maxLimit !== null) {
            $query->where('available_quiz_limit', '<=', $maxLimit);
        }

        // 3. Execute Bulk Increment
        $updatedCount = $query->increment('available_quiz_limit', $addLimit);

        // 4. Audit Log
        $logMessage = "Added {$addLimit} limits to {$updatedCount} targeted users";
        if ($maxLimit !== null) {
            $logMessage .= " (Max current limit was: {$maxLimit})";
        }
        
        AuditLog::log('Bulk Updated Play Limits', 'Users', $logMessage);

        return response()->json([
            'success' => true,
            'message' => "Successfully added {$addLimit} limits to {$updatedCount} targeted users."
        ]);
    }
    // ****** Get Referral History of a User ********
    public function getReferralHistory($id)
    { 
        $user = User::findOrFail($id);

        // Referred users ko fetch karte waqt dono possible referral signals ko support karein
        // 1) referral_user_id = current user id
        // 2) referral_code_to = current user's referral_code
        // Isse data mismatch ke case me bhi referral history load ho sake.
        $referrals = User::where('tenant_id', $user->tenant_id)
            ->where(function ($query) use ($id, $user) {
                $query->where('referral_user_id', $id);

                if (!empty($user->referral_code)) {
                    $query->orWhere('referral_code_to', $user->referral_code);
                }
            })
            ->select('id', 'name', 'mobile', 'date_of_birth', 'gender', 'created_at')
            ->latest('created_at')
            ->get()
            ->map(function($referredUser) {
                // Date of Birth se Age calculate karein
                $age = '—';
                if ($referredUser->date_of_birth) {
                    $age = \Carbon\Carbon::parse($referredUser->date_of_birth)->age . ' Years';
                }

                $quizCount = QuizUser::where('user_id', $referredUser->id)->count();

                return [
                    'id' => $referredUser->id,
                    'name' => $referredUser->name,
                    'mobile' => $referredUser->mobile ?? '—',
                    // 'age' => $age,
                    'gender' => ucfirst($referredUser->gender ?? '—'),
                    'created_at' => $referredUser->created_at->format('d M Y, h:i A'),
                    'quiz_count' => (int) $quizCount,
                ];
            });

        return response()->json([
            'success' => true,
            'count' => $referrals->count(),
            'data' => $referrals
        ]);
    }
    // ***********************************************


    public function whatsappNotifiedUsers(Request $request)
    {
        $perPage = $request->get('per_page', 10);
        $tenantId = getSuperAdminTenantId(); // Current tenant ID

        // Base scope for valid users in current tenant
        $validUserScope = function ($q) use ($tenantId) {
            $q->where('tenant_id', $tenantId)->where('role', 'user');
        };

        // ==========================================
        // 1. STATS BOXES COUNTS (Sirf unka count jinki entry UsersWhatsappNotify me hai)
        // ==========================================

         // Total Unique Notified Users
        $totalNotifiedUsers = UsersWhatsappNotify::whereHas('user', $validUserScope)
            ->distinct('user_id')
            ->count('user_id');
        
        // 1. Active Users Count (Notification table me entry hai + User active hai)
        $activeUsers = UsersWhatsappNotify::whereHas('user', function ($q) use ($tenantId) {
            $q->where('tenant_id', $tenantId)
            ->where('role', 'user')
            ->where('is_active', 1);
        })->distinct('user_id')->count('user_id');

        // 2. Suspended Users Count (Notification table me entry hai + User suspended hai)
        $suspendedUsers = UsersWhatsappNotify::whereHas('user', function ($q) use ($tenantId) {
            $q->where('tenant_id', $tenantId)
            ->where('role', 'user')
            ->where('is_active', 0);
        })->distinct('user_id')->count('user_id');

        // 3. WhatsApp Active Count (Notification table me entry hai + status 'active' hai)
        $waActiveUsers = UsersWhatsappNotify::whereHas('user', $validUserScope)
            ->where('status', 'active')
            ->distinct('user_id')->count('user_id');

        // 4. WhatsApp Inactive Count (Notification table me entry hai + status 'active' nahi hai)
        $waInactiveUsers = UsersWhatsappNotify::whereHas('user', $validUserScope)
            ->where('status', '!=', 'active') // Ya 'inactive' agar aapke DB me exactly yeh string hai
            ->distinct('user_id')->count('user_id');


        // ==========================================
        // 2. MAIN DATA TABLE QUERY
        // ==========================================
        $query = UsersWhatsappNotify::query()
            ->with(['user' => function ($q) {
                // Sirf zaroori user columns hi load karein (Performance boost)
                $q->select('id', 'name', 'mobile', 'is_active', 'created_at', 'tenant_id', 'role');
            }])
            ->select('id', 'user_id', 'status', 'updated_at') 
            ->whereHas('user', $validUserScope);

        // 1. Search Filter (User ke name ya mobile par)
        if ($request->filled('search')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                ->orWhere('mobile', 'like', "%{$request->search}%");
            });
        }

        // 2. User Status Filter (User ke is_active par)
        if ($request->filled('user_status')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('is_active', $request->user_status === 'active' ? 1 : 0);
            });
        }

        // 3. WhatsApp Status Filter (Direct is model ke status column par)
        if ($request->filled('whatsapp_status')) {
            $query->where('status', $request->whatsapp_status);
        }

        // 4. Date Filter (User ki joining date par)
        if ($request->filled('date')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->whereDate('created_at', $request->date);
            });
        }

        // Latest notification ke hisaab se sort karein
        $notifiedUsers = $query->latest('updated_at')->paginate($perPage)->withQueryString();

        // ==========================================
        // 3. RETURN VIEW WITH ALL VARIABLES
        // ==========================================
        return view('superadmin.users.whatsapp-notified-users', compact(
            'notifiedUsers',
            'activeUsers',
            'suspendedUsers',
            'waActiveUsers',
            'waInactiveUsers',
            'totalNotifiedUsers'
        ));
    }


    public function toggleWhatsappStatus($id): JsonResponse
    {
        $tenantId = getSuperAdminTenantId();

        // Security: Sirf current tenant ka valid record hi update hoga
        $notify = UsersWhatsappNotify::where('id', $id)
            ->whereHas('user', function ($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId)->where('role', 'user');
            })->first();

        if (!$notify) {
            return response()->json(['success' => false, 'message' => 'Record not found or unauthorized.'], 404);
        }

        // Status Toggle Logic
        $newStatus = ($notify->status === 'active') ? 'inactive' : 'active';
        $notify->status = $newStatus;
        $notify->save();

        return response()->json([
            'success' => true,
            'message' => 'WhatsApp status updated successfully.',
            'new_status' => $newStatus
        ]);
    }

    public function whatsappSubscribeUsers(Request $request)
    {
        $perPage = $request->get('per_page', 10);
        $tenantId = getSuperAdminTenantId(); // Current tenant ID

        // Base scope for valid users in current tenant
        $validUserScope = function ($q) use ($tenantId) {
            $q->where('tenant_id', $tenantId)->where('role', 'user');
        };

        // ==========================================
        // 1. STATS BOXES COUNTS (Sirf unka count jinki entry UserWhatsappSubscribe me hai)
        // ==========================================

        // Total Unique Subscribed Users
        $totalSubscribedUsers = UserWhatsappSubscribe::whereHas('user', $validUserScope)
            ->distinct('user_id')
            ->count('user_id');

        // 1. Active Users Count (Subscription table me entry hai + User active hai)
        $activeUsers = UserWhatsappSubscribe::whereHas('user', function ($q) use ($tenantId) {
            $q->where('tenant_id', $tenantId)
                ->where('role', 'user')
                ->where('is_active', 1);
        })->distinct('user_id')->count('user_id');

        // 2. Suspended Users Count (Subscription table me entry hai + User suspended hai)
        $suspendedUsers = UserWhatsappSubscribe::whereHas('user', function ($q) use ($tenantId) {
            $q->where('tenant_id', $tenantId)
                ->where('role', 'user')
                ->where('is_active', 0);
        })->distinct('user_id')->count('user_id');

        // 3. WhatsApp Subscribed Count (status 'subscribed' hai)
        $waSubscribedUsers = UserWhatsappSubscribe::whereHas('user', $validUserScope)
            ->where('status', 'subscribed')
            ->distinct('user_id')->count('user_id');

        // 4. WhatsApp Unsubscribed Count (status != 'subscribed' hai)
        $waUnsubscribedUsers = UserWhatsappSubscribe::whereHas('user', $validUserScope)
            ->where('status', '!=', 'subscribed')
            ->distinct('user_id')->count('user_id');

        // ==========================================
        // 2. MAIN DATA TABLE QUERY
        // ==========================================
        $query = UserWhatsappSubscribe::query()
            ->with(['user' => function ($q) {
                $q->select('id', 'name', 'mobile', 'is_active', 'created_at', 'tenant_id', 'role');
            }])
            ->select('id', 'user_id', 'status', 'created_at', 'updated_at')
            ->whereHas('user', $validUserScope);

        // 1. Search Filter (User name or mobile)
        if ($request->filled('search')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                    ->orWhere('mobile', 'like', "%{$request->search}%");
            });
        }

        // 2. User Status Filter (User is_active)
        if ($request->filled('user_status')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('is_active', $request->user_status === 'active' ? 1 : 0);
            });
        }

        // 3. WhatsApp Subscription Status Filter (status)
        if ($request->filled('whatsapp_status')) {
            $query->where('status', $request->whatsapp_status);
        }

        // 4. Date Filter (Subscription date)
        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        // Latest subscription sort
        $subscribedUsers = $query->latest('updated_at')->paginate($perPage)->withQueryString();

        // ==========================================
        // 3. RETURN VIEW WITH ALL VARIABLES
        // ==========================================
        return view('superadmin.users.whatsapp-subscribe-users', compact(
            'subscribedUsers',
            'activeUsers',
            'suspendedUsers',
            'waSubscribedUsers',
            'waUnsubscribedUsers',
            'totalSubscribedUsers'
        ));
    }

    

    public function updateReferralCode(Request $request, User $user)
{
    try {
        $tenantId = getSuperAdminTenantId();

        // Current tenant ke user ko hi allow karein
        $user = User::where('tenant_id', $tenantId)
            ->where('role', 'user')
            ->whereKey($user->id)
            ->firstOrFail();

        // JS se referral_code_to aa raha hai
        $newCode = strtoupper(trim($request->input('referral_code_to')));

        $validated = $request->merge([
            'referral_code_to' => $newCode,
        ])->validate([
            'referral_code_to' => [
                'required',
                'string',
                'min:3',
                'max:50',
                'regex:/^[A-Z0-9]+$/',
            ],
        ]);

        $oldCode = $user->referral_code_to;
        $newCode = $validated['referral_code_to'];

        if ($oldCode === $newCode) {
            return response()->json([
                'success' => false,
                'message' => 'New referral code is same as the old referral code.',
            ], 422);
        }

        $referrer = User::where('tenant_id', $tenantId)
            ->where('role', 'user')
            ->where('referral_code', $newCode)
            ->first();

        if (!$referrer) {
            return response()->json([
                'success' => false,
                'message' => 'Referral code does not belong to any valid user.',
            ], 422);
        }

        if ($referrer->id === $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'User cannot use their own referral code.',
            ], 422);
        }

        DB::transaction(function () use ($user, $oldCode, $newCode, $tenantId, $referrer) {
            $user->update([
                'referral_code_to' => $newCode,
                'referral_user_id' => $referrer->id,
            ]);

            $currentReferralRow = ReferralCode::where('code', $oldCode)
                ->where('referral_user_id', $user->id)
                ->orderByDesc('id')
                ->first();

            if ($currentReferralRow) {
                $currentReferralRow->update([
                    'tenant_id' => $tenantId,
                    'user_id' => $referrer->id,
                    'code' => $newCode,
                    'referral_point_provide' => 'yes',
                    'points' => 10,
                    'referral_user_id' => $user->id,
                    'used' => 'pending',
                    'used_date' => null,
                    'status' => 'active',
                ]);

                return;
            }

            $newReferralRow = ReferralCode::where('code', $newCode)
                ->where('user_id', $referrer->id)
                ->where('referral_user_id', $user->id)
                ->orderByDesc('id')
                ->first();

            if ($newReferralRow) {
                $newReferralRow->update([
                    'tenant_id' => $tenantId,
                    'referral_point_provide' => 'yes',
                    'points' => 10,
                    'used' => 'pending',
                    'used_date' => null,
                    'status' => 'active',
                ]);

                return;
            }

            ReferralCode::create([
                'tenant_id' => $tenantId,
                'user_id' => $referrer->id,
                'code' => $newCode,
                'referral_point_provide' => 'yes',
                'points' => 10,
                'referral_user_id' => $user->id,
                'used' => 'pending',
                'used_date' => null,
                'status' => 'active',
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Referral code updated successfully.',
            'old_code' => $oldCode,
            'referral_code_to' => $newCode,

            // Current UI ke liye
            'referrer_name' => 'Superadmin',
            'referrer_mobile' => null,
        ]);

    } catch (\Illuminate\Validation\ValidationException $e) {

        return response()->json([
            'success' => false,
            'message' => $e->validator->errors()->first(),
            'errors' => $e->validator->errors(),
        ], 422);

    } catch (\Throwable $e) {

        Log::error('Referral Code Update Error', [
            'user_id' => $user->id ?? null,
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Something went wrong while updating referral code.',
        ], 500);
    }
}

    //   ***************update on off status***************

    public function toggleOnOff(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:users,id',
        ]);

        $user = User::findOrFail($request->id);

        // Logic: Agar 'on' hai to 'off' karo, agar 'off' hai to 'on' karo
        $newStatus = $user->on_off === 'on' ? 'off' : 'on';

        $user->update([
            'on_off' => $newStatus,
        ]);

        AuditLog::log(
            'Toggled User On/Off Status',
            'Users',
            $user->name . ' switched to ' . ucfirst($newStatus)
        );

        return response()->json([
            'success' => true,
            'message' => 'User successfully switched to ' . ucfirst($newStatus)
        ]);
    }
    // *****************on off status update end**************

}
