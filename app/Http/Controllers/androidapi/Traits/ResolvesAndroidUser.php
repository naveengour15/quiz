<?php

namespace App\Http\Controllers\androidapi\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

trait ResolvesAndroidUser
{
    /**
     * Resolve authenticated user from Request, Session, Bearer Token, or Fallback.
     */
    protected function getAuthUser(Request $request): ?User
    {
        $user = $request->user() ?: Auth::user() ?: Auth::guard('web')->user();

        if (!$user) {
            $token = $request->bearerToken() ?: $request->header('X-Access-Token') ?: $request->header('session_token') ?: $request->input('access_token');
            if ($token) {
                $user = User::where('session_token', $token)->first();
            }
        }

        if (!$user && session('fsr_user_id')) {
            $user = User::find(session('fsr_user_id'));
        }

        if ($user && !$user->isAllowedToLogin()) {
            return null;
        }

        return $user;
    }
}
