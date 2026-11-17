<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Interfaces\api\AuthUserRepositoryInterface;
use Illuminate\Http\Request;

class AuthUserController extends Controller
{
    
    public function __construct(protected AuthUserRepositoryInterface $repository)
    {
    }

    public function signup(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'mobile' => [
                'required',
                'regex:/^[6-9][0-9]{9}$/',
            ],
            'state_id' => 'required|integer|exists:states,id',
            'city_name' => 'required|string|max:100',
            'dob' => 'required|date',
            'gender' => 'required|in:male,female,other',
            'referral_code' => 'nullable|string|max:50',
        ]);

        $response = $this->repository->signup($request);

        return response()->json($response, $response['code']);
    }

    public function sendOtp(Request $request)
    {
        $validated = $request->validate([
            'mobile' => [
                'required',
                'regex:/^[6-9][0-9]{9}$/',
            ],
        ]);

        return $this->repository->sendOtp($request);
    }

    public function verifyOtp(Request $request)
    {
        $validated = $request->validate([
            'mobile' => [
                'required',
                'regex:/^[6-9][0-9]{9}$/',
            ],
            'otp' => 'required|digits:6',
        ]);

         return $this->repository->verifyOtp($request);

    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }
}