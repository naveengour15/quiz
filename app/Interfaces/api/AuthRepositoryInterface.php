<?php

namespace App\Interfaces\api;

use Illuminate\Http\Request;

interface AuthRepositoryInterface
{

    public function signup(Request $request);

    /**
     * Generate and send an OTP to the given mobile number.
     *
     * @param array $data ['mobile' => string, 'tenant_id' => int|null]
     * @return array
     */


    public function sendOtp(array $data): array;

    /**
     * Verify OTP and login/register the user.
     *
     * @param array $data ['mobile' => string, 'otp' => string, 'token' => string, 'name' => string|null, 'tenant_id' => int|null]
     * @return array
     */
    public function verifyOtp(array $data): array;

    /**
     * Resend a fresh OTP to the same mobile number.
     *
     * @param array $data ['mobile' => string, 'token' => string]
     * @return array
     */
    public function resendOtp(array $data): array;

    /**
     * Logout the currently authenticated user (clears session token).
     *
     * @param \App\Models\User $user
     * @return array
     */
    public function logout($user): array;
}
