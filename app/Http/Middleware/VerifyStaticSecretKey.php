<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyStaticSecretKey
{
    /**
     * Handle an incoming request using a static secret API key / Bearer token.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $expectedKey = env('EXTERNAL_API_SECRET_KEY');

        // Extract token from Bearer header, X-API-KEY header, or query parameter
        $token = $request->bearerToken()
            ?: $request->header('X-API-KEY')
            ?: $request->header('X-Access-Token')
            ?: $request->input('api_key')
            ?: $request->input('access_token');

        if (!$token || !hash_equals((string) $expectedKey, (string) $token)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: Invalid or missing API secret key.',
            ], 401);
        }

        return $next($request);
    }
}
