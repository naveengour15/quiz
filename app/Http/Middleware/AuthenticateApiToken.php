<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthenticateApiToken
{
    public function handle(Request $request, Closure $next)
    {
        // 1. Try Bearer token, header, or query input
        $token = $request->bearerToken() ?: $request->header('X-Access-Token') ?: $request->header('session_token') ?: $request->input('access_token');

        if ($token) {
            $tokenHash = md5($token);
            $user = \Illuminate\Support\Facades\Cache::remember("api_tok_u_{$tokenHash}", 60, function () use ($token) {
                return User::where('session_token', $token)->first();
            });

            if ($user) {
                if (!$user->isAllowedToLogin()) {
                    return response()->json([
                        'status' => false,
                        'success' => false,
                        'message' => 'Your account is not activated for login. Please contact administrator.',
                    ], 403);
                }

                Auth::setUser($user);
                $request->setUserResolver(fn () => $user);
                return $next($request);
            }
        }

        // 2. Try web session authentication (Auth::check() / Auth::user() / guard 'web')
        $sessionUser = Auth::user() ?: Auth::guard('web')->user();
        if ($sessionUser) {
            if (!$sessionUser->isAllowedToLogin()) {
                return response()->json([
                    'status' => false,
                    'success' => false,
                    'message' => 'Your account is not activated for login. Please contact administrator.',
                ], 403);
            }

            Auth::setUser($sessionUser);
            $request->setUserResolver(fn () => $sessionUser);
            return $next($request);
        }

        // 3. Try user ID stored in session if web session is present
        if ($request->hasSession() && $request->session()->has('user_id')) {
            $userId = $request->session()->get('user_id');
            $user = User::find($userId);
            if ($user) {
                if (!$user->isAllowedToLogin()) {
                    return response()->json([
                        'status' => false,
                        'success' => false,
                        'message' => 'Your account is not activated for login. Please contact administrator.',
                    ], 403);
                }

                Auth::setUser($user);
                $request->setUserResolver(fn () => $user);
                return $next($request);
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'Authorization token not provided or session expired',
        ], 401);
    }
}
