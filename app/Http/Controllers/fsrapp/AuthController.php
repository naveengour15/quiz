<?php

namespace App\Http\Controllers\fsrapp;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\User;
use App\Models\Tenant;
use App\Models\MobileVerification;
use App\Models\ReferralCode;
use App\Http\Middleware\FsrAppAuthMiddleware;
use App\Helpers\BuildSmsHelper;
use App\Repositories\WhatsAppRepositoryInterface;
use App\Models\TenantMessageSetting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function login(Request $request, $quiz_id = null)
    {
        $quiz = null;
        if ($quiz_id) {
            $quiz = \App\Models\Quiz::find($quiz_id);
            if ($quiz && $quiz->scheduled_at) {
                $secondsUntilStart = \Carbon\Carbon::now()->diffInSeconds(\Carbon\Carbon::parse($quiz->scheduled_at), false);
                if ($secondsUntilStart > 2400) { // 40 minutes = 2400 seconds
                    session()->forget(['redirect_to', 'url.intended', 'auto_join_quiz_id']);
                    if (Auth::check() || (session('fsr_authenticated') && session('fsr_user_id'))) {
                        return redirect()->route('fsrapp.home')->with('warning', __('app.quiz_join_40min_warning'));
                    }
                    return view('fsrapp.login.index')->with('warning', __('app.quiz_join_40min_warning'));
                }
            }

            $autoJoin = $request->query('auto_join', 1);
            session(['auto_join_quiz_id' => $quiz_id]);
            $targetUrl = route('fsrapp.quizes.join', array_filter([
                'id' => $quiz_id,
                'auto_join' => $autoJoin,
            ]));
            session([
                'redirect_to' => $targetUrl,
                'url.intended' => $targetUrl,
            ]);
        }

        $redirect = $request->query('redirect') ?? session('redirect_to') ?? session('url.intended');
        if (!FsrAppAuthMiddleware::isValidRedirectUrl($redirect)) {
            $redirect = null;
            session()->forget(['redirect_to', 'url.intended']);
        }

        $user = Auth::user();
        if (!$user && session('fsr_user_id')) {
            $user = User::find(session('fsr_user_id'));
        }

        if ($user) {
            // Direct auto-join if quiz_id is provided and user is authenticated
            if ($quiz_id) {
                session()->forget(['redirect_to', 'url.intended', 'auto_join_quiz_id']);
                $joinRes = app(\App\Interfaces\api\QuizRepositoryInterface::class)->joinSubmitData($user, (int) $quiz_id);
                if (!empty($joinRes['success'])) {
                    $targetRoute = $joinRes['redirect'] ?? (($quiz && $quiz->visibility === 'fastest_finger_first') ? route('fsrapp.fff.waiting', $quiz_id) : route('fsrapp.quizes.waiting', $quiz_id));
                    return redirect($targetRoute)->with('success', $joinRes['message'] ?? 'Joined quiz successfully!');
                } else {
                    return redirect($joinRes['redirect'] ?? route('fsrapp.home'))->with('warning', $joinRes['message'] ?? 'Could not join quiz.');
                }
            }

            if ($redirect) {
                session()->forget(['redirect_to', 'url.intended']);
                return redirect()->to($redirect);
            }
            return redirect()->route('fsrapp.home');
        }

        if ($request->filled('redirect') && $redirect) {
            session([
                'redirect_to' => $redirect,
                'url.intended' => $redirect,
            ]);
        }

        return view('fsrapp.login.index');
    }

    public function sendOtp(Request $request)
    {
        $redirect = $request->input('redirect') ?? $request->query('redirect') ?? session('redirect_to') ?? session('url.intended');
        if (!FsrAppAuthMiddleware::isValidRedirectUrl($redirect)) {
            $redirect = null;
            session()->forget(['redirect_to', 'url.intended']);
        }

        if (Auth::check() || (session('fsr_authenticated') && session('fsr_user_id'))) {
            if ($redirect) {
                session()->forget(['redirect_to', 'url.intended']);
                return redirect()->to($redirect)->with('warning', 'You are already logged in.');
            }
            return redirect()->route('fsrapp.home')->with('warning', 'You are already logged in.');
        }

        if ($request->isMethod('get')) {
            return redirect()->route('fsrapp.login', array_filter(['redirect' => $redirect]));
        }

        $request->validate([
            'mobile' => 'required|digits:10',
        ], [
            'mobile.required' => 'Please enter your 10-digit mobile number.',
            'mobile.digits' => 'Mobile number must be exactly 10 digits.',
        ]);

        $mobile = $request->input('mobile');

        // Security: Rate Limit OTP requests (Max 5 attempts per 10 minutes per mobile/IP to prevent SMS flooding)
        $throttleKey = 'send-otp:' . $mobile . '|' . $request->ip();
        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = \Illuminate\Support\Facades\RateLimiter::availableIn($throttleKey);
            return back()->withInput()->with('error', "Too many OTP requests. Please wait {$seconds} seconds before trying again.");
        }
        \Illuminate\Support\Facades\RateLimiter::hit($throttleKey, 600);

         // ✅ NEW: URL se referral code capture karein (agar hai toh)
        $referralCode = $request->input('ref'); 

        // Check if user exists in database
        $user = User::where('mobile', $mobile)->first();

        if ($user && (!$user->is_active || (int) $user->is_active === 0)) {
            return back()->withInput()->with('error', 'Your account has been deactivated. Please contact administrator.');
        }

        // Generate 4-digit OTP
        $otp = (string) rand(1000, 9999);

        // Store in MobileVerification table
        MobileVerification::updateOrCreate(
            ['mobile_number' => $mobile],
            [
                'tenant_id' => 1,
                'otp' => $otp,
                'token' => (string) Str::uuid(),
            ]
        );

        
        // Send OTP via SMS
        /*
            $sentViaSms = false;
            try {
                $tenant = Tenant::first() ?? Tenant::find(1);
                $tenantName = $tenant->name ?? 'FSRQuiz';

                $smsResponse = BuildSmsHelper::sendOtp('FsrQuiz Login verify otp', null, $mobile, $otp, $tenantName);

                if ($smsResponse && !empty($smsResponse['status'])) {
                    $sentViaSms = true;
                    Log::info("SMS OTP sent to {$mobile}: {$otp}");
                } else {
                    Log::error('OTP SMS sending failed to mobile number', ['mobile' => $mobile, 'response' => $smsResponse]);
                }
            } catch (\Throwable $e) {
                Log::error("Failed to send SMS OTP to {$mobile}: " . $e->getMessage());
            }

            // Save session state
            session([
                'pending_mobile' => $mobile,
                'fsr_user_mobile' => $mobile,
                'otp' => $otp,
                'user_exists' => ($user && !empty($user->name)) ? true : false,
                'pending_referral_code' => $referralCode,
                'redirect_to' => $redirect,
                'url.intended' => $redirect,
            ]);

            $msg = $sentViaSms 
                ? "OTP sent successfully via SMS to +91 {$mobile}." 
                : "OTP generated for +91 {$mobile}. Please check your SMS.";
                
        */


            // Get message settings
                $messageSetting = TenantMessageSetting::where('tenant_id', 1)->first();

                if ($messageSetting && $messageSetting->whatsapp === 'active') {

                // Send OTP via WhatsApp =========================
                $sentViaWhatsApp = false;

                try {
                    $whatsAppMobile = str_starts_with($mobile, '91')
                        ? $mobile
                        : '91' . $mobile;

                    $tenant = Tenant::first() ?? Tenant::find(1);

                    $parameters = [
                        ['name' => 'otp', 'value' => $otp],
                        ['name' => 'company_name', 'value' => 'FsrQuiz'],
                    ];

                    $buttonParameters = [
                        ['index' => 0, 'value' => 'verify'],
                    ];

                    $whatsAppRepo = app(WhatsAppRepositoryInterface::class);

                    $response = $whatsAppRepo->sendWhatsAppMessage(
                        $whatsAppMobile,
                        'CHALO_OTP',
                        $parameters,
                        $buttonParameters,
                        $tenant
                    );

                    if ($response) {
                        $sentViaWhatsApp = true;

                        Log::info("WhatsApp OTP sent to {$mobile}: {$otp}");
                    }

                } catch (\Throwable $e) {
                    Log::error(
                        "Failed to send WhatsApp OTP to {$mobile}: " . $e->getMessage()
                    );
                }

                $msg = $sentViaWhatsApp
                    ? "OTP sent successfully via WhatsApp to +91 {$mobile}."
                    : "OTP generated for +91 {$mobile}. Please check your WhatsApp.";

                } else {

                    //  Send OTP via SMS
                    $sentViaSms = false;

                    try {
                        $tenant = Tenant::first() ?? Tenant::find(1);
                        $tenantName = $tenant->name ?? 'FSRQuiz';

                        $smsResponse = BuildSmsHelper::sendOtp(
                            'FsrQuiz Login verify otp',
                            null,
                            $mobile,
                            $otp,
                            $tenantName
                        );

                        if ($smsResponse && !empty($smsResponse['status'])) {
                            $sentViaSms = true;

                            Log::info("SMS OTP sent to {$mobile}: {$otp}");
                        } else {
                            Log::error(
                                'OTP SMS sending failed to mobile number',
                                [
                                    'mobile' => $mobile,
                                    'response' => $smsResponse
                                ]
                            );
                        }

                    } catch (\Throwable $e) {
                        Log::error(
                            "Failed to send SMS OTP to {$mobile}: " . $e->getMessage()
                        );
                    }

                    $msg = $sentViaSms
                        ? "OTP sent successfully via SMS to +91 {$mobile}."
                        : "OTP generated for +91 {$mobile}. Please check your SMS.";
                }


                // Save session state
                session([
                    'pending_mobile' => $mobile,
                    'fsr_user_mobile' => $mobile,
                    'otp' => $otp,
                    'user_exists' => ($user && !empty($user->name)) ? true : false,
                    'pending_referral_code' => $referralCode,
                    'redirect_to' => $redirect,
                    'url.intended' => $redirect,
                ]);

        return redirect()->route('fsrapp.otp', array_filter(['redirect' => $redirect]))->with('success', $msg);
    }

    public function otp(Request $request)
    {
       
        $redirect = $request->query('redirect') ?? session('redirect_to') ?? session('url.intended');
        if (!FsrAppAuthMiddleware::isValidRedirectUrl($redirect)) {
            $redirect = null;
            session()->forget(['redirect_to', 'url.intended']);
        }

        if (Auth::check() || (session('fsr_authenticated') && session('fsr_user_id'))) {
            if ($redirect) {
                session()->forget(['redirect_to', 'url.intended']);
                return redirect()->to($redirect);
            }
            return redirect()->route('fsrapp.home');
        }

        $mobile = session('pending_mobile')
            ?? session('fsr_user_mobile')
            ?? $request->old('mobile');
       
        if (!$mobile) {
            return redirect()->route('fsrapp.login', array_filter(['redirect' => $redirect]))->with('error', 'Please enter your mobile number first.');
        }

        return view('fsrapp.otp.index', compact('mobile'));
    }

    public function verifyOtp(Request $request)
    {
        $redirect = $request->input('redirect') 
            ?? $request->query('redirect') 
            ?? session('redirect_to') 
            ?? session('url.intended');

        if (!FsrAppAuthMiddleware::isValidRedirectUrl($redirect)) {
            $redirect = null;
            session()->forget(['redirect_to', 'url.intended']);
        }

        if (Auth::check() || (session('fsr_authenticated') && session('fsr_user_id'))) {
            if ($redirect) {
                session()->forget(['redirect_to', 'url.intended']);
                return redirect()->to($redirect)->with('warning', 'You are already logged in.');
            }
            return redirect()->route('fsrapp.home')->with('warning', 'You are already logged in.');
        }

        if ($request->isMethod('get')) {
            return redirect()->route('fsrapp.otp', array_filter(['redirect' => $redirect]));
        }

        $request->validate([
            'otp' => 'required',
            'mobile' => 'nullable|digits:10',
        ]);

        $otpInput = $request->input('otp');
    
        if (is_array($otpInput)) {
            $otpInput = implode('', $otpInput);
        }

        if (strlen($otpInput) !== 4) {
            return back()->with('error', 'Please enter a valid 4-digit OTP.');
        }

        $sessionMobile = session('pending_mobile') ?? session('fsr_user_mobile');
        $mobile = $sessionMobile ?? $request->input('mobile');

        if (!$mobile) {
            return redirect()->route('fsrapp.login', array_filter(['redirect' => $redirect]))->with('error', 'Session expired. Please try again.');
        }

        // Security: Brute-Force Protection on OTP verification (Max 5 failed attempts per 15 mins)
        $verifyThrottleKey = 'verify-otp:' . $mobile . '|' . $request->ip();
        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($verifyThrottleKey, 5)) {
            $seconds = \Illuminate\Support\Facades\RateLimiter::availableIn($verifyThrottleKey);
            $minutes = ceil($seconds / 60);
            return back()->with('error', "Too many incorrect attempts. Please wait {$minutes} minutes before trying again.");
        }

        // Verify against DB MobileVerification or Session
        $verification = MobileVerification::where('mobile_number', $mobile)
            ->latest()
            ->first();

        $sessionOtp = session('otp');
        $validOtp = false;

        if ($verification && (string)$verification->otp === (string)$otpInput) {
            $validOtp = true;
            $verification->delete();
        } elseif ($sessionMobile && $sessionMobile === $mobile && $sessionOtp && (string)$sessionOtp === (string)$otpInput) {
            $validOtp = true;
        }

        if (!$validOtp) {
            \Illuminate\Support\Facades\RateLimiter::hit($verifyThrottleKey, 900);
            $attemptsLeft = \Illuminate\Support\Facades\RateLimiter::retriesLeft($verifyThrottleKey, 5);
            return back()
                ->withInput(['mobile' => $mobile])
                ->with('error', "Invalid OTP entered. {$attemptsLeft} attempt(s) remaining.");
        }

        // Clear rate limiters on successful OTP validation
        \Illuminate\Support\Facades\RateLimiter::clear($verifyThrottleKey);
        \Illuminate\Support\Facades\RateLimiter::clear('send-otp:' . $mobile . '|' . $request->ip());
        session([
            'pending_mobile' => $mobile,
            'fsr_user_mobile' => $mobile,
        ]);

        $user = User::where('mobile', $mobile)->first();

        // Check if user is inactive / deactivated
        if ($user && (!$user->is_active || (int) $user->is_active === 0)) {
            $userId = $user->id;
            Cache::forget('email_otp_' . $userId);
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            session()->flush();
            return redirect()->route('fsrapp.login')->with('error', 'Your account has been deactivated. Please contact administrator.');
        }

        if ($user) {
            $user->update(['login_type' => 'web']);
        }

        $pendingRefCode = session('pending_referral_code');

        if ($user) {
            Auth::login($user, true); 
            $request->session()->regenerate(); 
        } else {
            $request->session()->regenerate();
        }

        // If user is already registered (exists and has filled profile/name)
        if ($user && !empty($user->name) && !empty($user->referral_code_to) && ReferralCode::where('code', $user->referral_code_to)->where('referral_user_id', $user->id)->exists() ) {
            Auth::login($user, true);
            session([
                'fsr_authenticated' => true,
                'fsr_user_id' => $user->id,
                'fsr_user_name' => $user->name,
                'fsr_user_mobile' => $user->mobile,
            ]);

            $autoJoinQuizId = session('auto_join_quiz_id');
            session()->forget(['redirect_to', 'url.intended', 'auto_join_quiz_id']);

            if ($autoJoinQuizId) {
                $joinRes = app(\App\Interfaces\api\QuizRepositoryInterface::class)->joinSubmitData($user, (int) $autoJoinQuizId);
                $qModel = \App\Models\Quiz::find($autoJoinQuizId);
                if (!empty($joinRes['success'])) {
                    $targetRoute = $joinRes['redirect'] ?? (($qModel && $qModel->visibility === 'fastest_finger_first') ? route('fsrapp.fff.waiting', $autoJoinQuizId) : route('fsrapp.quizes.waiting', $autoJoinQuizId));
                    return redirect($targetRoute)->with('success', $joinRes['message'] ?? 'Joined quiz successfully!');
                } else {
                    return redirect($joinRes['redirect'] ?? route('fsrapp.home'))->with('warning', $joinRes['message'] ?? 'Could not join quiz.');
                }
            }

            if ($redirect && FsrAppAuthMiddleware::isValidRedirectUrl($redirect)) {
                return redirect()->to($redirect)->with('success', 'Welcome back, ' . $user->name . '!');
            }

            return redirect()->route('fsrapp.home')->with('success', 'Welcome back, ' . $user->name . '!');
        } else {
            // New user or incomplete profile -> go to profile completion view
            if ($user) {
                Auth::login($user, true);
                session([
                    'pending_mobile' => $mobile,
                    'pending_referral_code' => $pendingRefCode ?? 'SANDE687',
                    'fsr_authenticated' => true,
                    'fsr_user_id'   => $user->id,
                    'fsr_user_name' => $user->name ?? 'User',
                    'fsr_user_mobile' => $user->mobile,
                    'redirect_to' => $redirect,
                    'url.intended' => $redirect,
                ]);
            } else {
                session([
                    'pending_mobile' => $mobile,
                    'pending_referral_code' => $pendingRefCode ?? 'SANDE687',
                    'fsr_user_mobile' => $mobile,
                    'redirect_to' => $redirect,
                    'url.intended' => $redirect,
                ]);
            }
            return redirect()->route('fsrapp.complete-profile', array_filter(['redirect' => $redirect]))->with('info', 'Please complete your profile to continue.');
        }
    } 

    public function resendOtp(Request $request)
    {
        if (Auth::check() || (session('fsr_authenticated') && session('fsr_user_id'))) {
            return redirect()->route('fsrapp.home')->with('warning', 'You are already logged in.');
        }

        if ($request->isMethod('get')) {
            $mobile = session('pending_mobile');
            if ($mobile) {
                return redirect()->route('fsrapp.otp')->with('warning', 'Invalid request method.');
            }
            return redirect()->route('fsrapp.login')->with('warning', 'Please log in to continue.');
        }

        $mobile = session('pending_mobile');
        if (!$mobile) {
            return redirect()->route('fsrapp.login');
        }

        // Generate fresh 4-digit OTP & Resend via SMS
        $otp = (string) rand(1000, 9999);

        MobileVerification::updateOrCreate(
            ['mobile_number' => $mobile],
            [
                'tenant_id' => 1,
                'otp' => $otp,
                'token' => (string) Str::uuid(),
            ]
        );

        $sentViaSms = false;
        try {
            $tenant = Tenant::first() ?? Tenant::find(1);
            $tenantName = $tenant->name ?? 'FSRQuiz';

            $smsResponse = BuildSmsHelper::sendOtp('FsrQuiz Login verify otp', null, $mobile, $otp, $tenantName);

            if ($smsResponse && !empty($smsResponse['status'])) {
                $sentViaSms = true;
                Log::info("SMS OTP resent to {$mobile}: {$otp}");
            } else {
                Log::error('Resend OTP SMS sending failed to mobile number', ['mobile' => $mobile, 'response' => $smsResponse]);
            }
        } catch (\Throwable $e) {
            Log::error("Failed to resend SMS OTP to {$mobile}: " . $e->getMessage());
        }

        session(['otp' => $otp]);
        return back()->with('success', "OTP resent successfully via SMS to +91 {$mobile}. ( OTP is {$otp}) ");
    }

    public function logout(Request $request)
    {
        $userId = Auth::id() ?? session('fsr_user_id');
        if ($userId) {
            Cache::forget('email_otp_' . $userId);
        }
        Auth::logout();
        session()->forget(['pending_mobile', 'otp', 'user_exists', 'fsr_authenticated', 'fsr_user_id', 'fsr_user_name', 'fsr_user_mobile', 'is_first_login']);
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        session()->flush();
        return redirect()->route('fsrapp.login')->with('success', 'Logged out successfully.');
    }

}
