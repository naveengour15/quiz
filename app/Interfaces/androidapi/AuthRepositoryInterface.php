<?php

namespace App\Interfaces\androidapi;

interface AuthRepositoryInterface
{
    /**
     * Send OTP to the given mobile number.
     */
    public function sendOtp(string $mobile): array;

    /**
     * Verify the 4-digit OTP for the given mobile number.
     */
    public function verifyOtp(string $mobile, string $otp, string $loginType = 'android'): array;

    /**
     * Resend OTP to the given mobile number.
     */
    public function resendOtp(string $mobile): array;

    /**
     * Complete or update profile for a user.
     */
    public function completeProfile(int $userId, array $data): array;

    /**
     * Get profile data for the authenticated user.
     */
    public function getProfile(int $userId): array;

    /**
     * Logout user and revoke tokens/session.
     */
    public function logout(int $userId): array;
}
