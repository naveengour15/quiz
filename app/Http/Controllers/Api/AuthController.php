<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Interfaces\api\AuthRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    protected AuthRepositoryInterface $authRepository;

    public function __construct(AuthRepositoryInterface $authRepository)
    {
        $this->authRepository = $authRepository;
    }

    public function signup(Request $request)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:150',
            'mobile' => [
                'required',
                'regex:/^[6-9][0-9]{9}$/',
            ],
            'state_id' => 'nullable|integer|exists:states,id',
            'city_name' => 'nullable|string|max:100',
            'dob' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'referral_code' => 'nullable|string|max:50',
        ]);

        $response = $this->authRepository->signup($request);

        return response()->json($response, $response['code']);
    }

    /**
     * POST /api/auth/send-otp
     */
    public function sendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mobile'    => 'required|digits_between:10,15',
            'tenant_id' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $result = $this->authRepository->sendOtp($validator->validated());

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * POST /api/auth/verify-otp
     */
    public function verifyOtp(Request $request)
    {
      
        $validator = Validator::make($request->all(), [
            'mobile' => [
                'required',
                'regex:/^[6-9][0-9]{9}$/',
            ],
            'otp' => 'required|digits:4',
            'token'     => 'required|string',
            'tenant_id' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $result = $this->authRepository->verifyOtp($validator->validated());

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * POST /api/auth/resend-otp
     */
    public function resendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mobile' => 'required|digits_between:10,15',
            'token'  => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $result = $this->authRepository->resendOtp($validator->validated());

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * POST /api/auth/logout  (requires auth)
     */
    public function logout(Request $request)
    {
        $result = $this->authRepository->logout($request->user());

        return response()->json($result);
    }

    /**
     * GET /api/user/profile  (requires auth)
     */
    public function profile(Request $request)
    {
        return response()->json([
            'success' => true,
            'user'    => $request->user(),
        ]);
    }
}
