<?php

namespace App\Interfaces\api;

use Illuminate\Http\Request;

interface AuthUserRepositoryInterface
{
    public function signup(Request $request);

    public function sendOtp(Request $request);

    public function verifyOtp(Request $request);
}