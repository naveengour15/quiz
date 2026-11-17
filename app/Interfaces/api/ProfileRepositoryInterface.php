<?php

namespace App\Interfaces\api;
use Illuminate\Http\UploadedFile;

interface ProfileRepositoryInterface
{
    public function getProfile($user): array;
    public function updateProfile($user, array $data, ?UploadedFile $profilePhoto = null): array;
    public function updateAlternativeMobile($user, string $mobile): array;
    
    public function sendOtpVerifyEmail($user);
    public function verifyEmailOtp($user, array $data): array;
    public function deleteAccount($user): array;
}
