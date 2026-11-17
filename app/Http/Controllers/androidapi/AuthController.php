<?php

namespace App\Http\Controllers\androidapi;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Interfaces\androidapi\AuthRepositoryInterface;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\NotificationHistory;
use App\Models\Tenant;
use App\Repositories\WhatsAppRepositoryInterface;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    protected AuthRepositoryInterface $authRepository;
      protected WhatsAppRepositoryInterface $whatsAppRepository;

    public function __construct(AuthRepositoryInterface $authRepository)
    {
        $this->authRepository = $authRepository;
    }

    

    /**
     * Send OTP API
     * POST /androidapi/auth/send-otp
     */
    public function sendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mobile' => 'required|digits:10',
        ], [
            'mobile.required' => 'Please enter your 10-digit mobile number.',
            'mobile.digits' => 'Mobile number must be exactly 10 digits.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors()
            ], 422);
        }

        $result = $this->authRepository->sendOtp($request->input('mobile'));
        $statusCode = $result['status'] ? 200 : ($result['code'] ?? 400);

        return response()->json($result, $statusCode);
    }

    /**
     * Verify OTP API
     * POST /androidapi/auth/verify-otp
     */
    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mobile' => 'required|digits:10',
            'otp' => 'required|digits:4',
            'login_type' => 'sometimes|in:android,ios',
        ], [
            'mobile.required' => 'Mobile number is required.',
            'otp.required' => 'Please enter the 4-digit OTP.',
            'otp.digits' => 'OTP must be exactly 4 digits.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors()
            ], 422);
        }

        $result = $this->authRepository->verifyOtp(
            $request->input('mobile'),
            $request->input('otp'),
            $request->input('login_type', 'android')
        );

        $statusCode = $result['status'] ? 200 : ($result['code'] ?? 400);
        return response()->json($result, $statusCode);
    }

    /**
     * Resend OTP API
     * POST /androidapi/auth/resend-otp
     */
    public function resendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mobile' => 'required|digits:10',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors()
            ], 422);
        }

        $result = $this->authRepository->resendOtp($request->input('mobile'));
        $statusCode = $result['status'] ? 200 : ($result['code'] ?? 400);

        return response()->json($result, $statusCode);
    }

    /**
     * Complete or Update Profile API
     * POST /androidapi/auth/complete-profile
     */
    public function completeProfile(Request $request)
    {
        $user = $this->getAuthenticatedUser($request);
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthenticated user. Header bearer token or user_id is required.'
            ], 401);
        }
       

        if (!$user->isAllowedToLogin()) {
            return response()->json([
                'status' => false,
                'message' => 'Your account is not activated for login. Please contact administrator.'
            ], 403);
        }

        $payload = $request->all();
        if (!$request->filled('date_of_birth') && $request->filled('dob')) {
            $payload['date_of_birth'] = $request->input('dob');
        }

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'min:3', 'max:191', 'regex:/^[a-zA-Z\s]+$/'],
            'referral_code' => 'required|string|max:50',
            'language' => 'required|string|in:en,hi,hinglish',
            'email' => 'nullable|email|max:191',
            'state' => 'nullable|string|max:191',
            'city' => 'nullable|string|max:191',
            'gender' => 'nullable|string|in:male,female,other',
            'date_of_birth' => 'nullable|date',
            'dob' => 'nullable|date',
            'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

    

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors()
            ], 422);
        }

        $result = $this->authRepository->completeProfile($user->id, $payload);

        
        $statusCode = $result['status'] ? 200 : ($result['code'] ?? 400);
        // $this->sendWelcomeMessage($user->mobile, $user->name, $user->tenant_id ?? 1);

        if ($statusCode == 200) {
            $this->sendWelcomeMessage(
                $user->mobile,
                $user->name,
                $user->tenant_id ?? 1
            );
        }
     
        return response()->json($result, $statusCode);
    }

    /**
     * Get Profile API
     * GET /androidapi/auth/profile
     */
    public function getProfile(Request $request)
    {
        $user = $this->getAuthenticatedUser($request);
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthenticated user.'
            ], 200); // Changed to 200 to prevent Axios from throwing a console error when auto-logout is disabled
        }

        if (!$user->isAllowedToLogin()) {
            return response()->json([
                'status' => false,
                'message' => 'Your account is not activated for login. Please contact administrator.'
            ], 403);
        }

        $result = $this->authRepository->getProfile($user->id);
        $statusCode = $result['status'] ? 200 : ($result['code'] ?? 400);

        return response()->json($result, $statusCode);
    }

    /**
     * Logout API
     * POST /androidapi/auth/logout
     */
    public function logout(Request $request)
    {
        $user = $this->getAuthenticatedUser($request);
        if (!$user) {
            return response()->json([
                'status' => true,
                'message' => 'Logged out successfully.'
            ]);
        }

        $token = $request->bearerToken() ?: $request->header('X-Access-Token') ?: $request->header('session_token') ?: $request->input('access_token');
        if ($token) {
            \Illuminate\Support\Facades\Cache::forget('api_tok_u_' . md5($token));
        }

        $result = $this->authRepository->logout($user->id);
        return response()->json($result, 200);
    }

    public function signup(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mobile' => 'required|digits:10',
            'name' => 'required|string|max:191',
            'login_type' => 'sometimes|in:android,ios',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first(), 'errors' => $validator->errors()], 422);
        }

        $user = User::where('mobile', $request->mobile)->first();
        if ($user && !$user->isAllowedToLogin()) {
            return response()->json([
                'status' => false,
                'message' => 'Your account is not activated for login. Please contact administrator.'
            ], 403);
        }

        if (!$user) {
            $verified = $this->authRepository->verifyOtp(
                $request->mobile,
                $request->otp ?? '',
                $request->input('login_type', 'android')
            );
            if (!$verified['status']) {
                $statusCode = $verified['code'] ?? 422;
                return response()->json(['status' => false, 'message' => $verified['message'] ?? 'Please verify OTP before signup.'], $statusCode);
            }
            $user = User::where('mobile', $request->mobile)->first();
        }

        if ($user && !$user->isAllowedToLogin()) {
            return response()->json([
                'status' => false,
                'message' => 'Your account is not activated for login. Please contact administrator.'
            ], 403);
        }

        return response()->json($this->authRepository->completeProfile($user->id, $request->all()));
    }

    /**
     * Helper to resolve authenticated user from Bearer Token, Session Token, or user_id
     */
    private function getAuthenticatedUser(Request $request): ?User
    {
        if (Auth::check()) {
            return Auth::user();
        }

        // Check Authorization header or session_token or user_id input
        $token = $request->bearerToken();
        if (!$token) {
            $token = $request->header('session_token') ?: $request->input('session_token');
        }

        if ($token) {
            $user = User::where('session_token', $token)->first();
            if ($user) return $user;
        }

        $userId = $request->input('user_id') ?: $request->header('user_id');
        if ($userId) {
            return User::find($userId);
        }

        return null;
    }

    //*******welecome message******* */
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
    //*******welecome message******* */
}
