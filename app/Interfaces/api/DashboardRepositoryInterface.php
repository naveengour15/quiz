<?php

namespace App\Interfaces\api;

use Illuminate\Http\Request;

interface DashboardRepositoryInterface
{
    public function dashboard($user);
    
    public function profile(Request $request);

    public function completeProfile(Request $request);

    public function updateProfile(Request $request);
    public function updateAltMobile(Request $request);
    public function sendOtpVerifyEmail(Request $request);
    public function verifyEmail(Request $request);
}