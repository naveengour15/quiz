<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class FsrAppAuthMiddleware
{
    /**
     * Check if a URL is a valid human-navigable page to redirect users to after auth.
     */
    public static function isValidRedirectUrl(?string $url): bool
    {
        if (empty($url) || !is_string($url)) {
            return false;
        }

        $url = trim($url);

        $invalidPatterns = [
            '/api/',
            'quizes/api',
            'androidapi',
            '/sounds',
            'sound-settings',
            '/login',
            '/otp',
            '/verify-otp',
            '/send-otp',
            '/resend-otp',
            '/complete-profile',
            '/save-profile',
            '/logout',
            'quiz-times',
            'last-quiz-status',
            'mark-out',
            '.json',
            '.mp3',
            '.png',
            '.jpg',
        ];

        foreach ($invalidPatterns as $pattern) {
            if (stripos($url, $pattern) !== false) {
                return false;
            }
        }

        if (str_starts_with($url, '/')) {
            return true;
        }

        $parsed = parse_url($url);
        if (!empty($parsed['path'])) {
            return true;
        }

        return false;
    }

    /**
     * Handle an incoming request for FSR App.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = null;
        if (Auth::check()) {
            $user = Auth::user();
        } elseif (session('fsr_authenticated') && session('fsr_user_id')) {
            $user = User::find(session('fsr_user_id'));
        }


        // Check if user is inactive (is_active == 0)
        if ($user && (!$user->is_active || (int) $user->is_active === 0)) {
            $userId = $user->id;

            // Clear cache related to the user
            Cache::forget('email_otp_' . $userId);

            // Log out user
            Auth::logout();

            // Clear and invalidate session
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            session()->flush();

            if ($request->expectsJson() || $request->ajax() || $request->is('*/api/*') || $request->is('api/*')) {
                return response()->json([
                    'status' => false,
                    'message' => 'Your account has been deactivated. Please contact administrator.',
                ], 403);
            }

            return redirect()->route('fsrapp.login')->with('error', 'Your account has been deactivated. Please contact administrator.');
        }

        // If user is valid and active
        if ($user && $user->is_active) {
            if (!Auth::check()) {
                Auth::login($user, true);
            }

            if (!session('fsr_authenticated')) {
                session([
                    'fsr_authenticated' => true,
                    'fsr_user_id' => $user->id,
                    'fsr_user_name' => $user->name,
                    'fsr_user_mobile' => $user->mobile,
                ]);
            }
        }

        // If no authenticated / active user
        if (!Auth::check() || !session('fsr_authenticated') || !session('fsr_user_id')) {
            session()->forget(['fsr_authenticated', 'fsr_user_id', 'fsr_user_name', 'fsr_user_mobile']);

            // Any API request, AJAX, JSON, or sounds/times/status route MUST return 401 JSON and never set intended URL
            if ($request->expectsJson() || $request->ajax() || $request->is('*/api/*') || $request->is('api/*') || str_contains($request->path(), '/api') || str_contains($request->path(), 'sounds') || str_contains($request->path(), 'quiz-times') || str_contains($request->path(), 'status')) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthenticated. Please log in to continue.',
                ], 401);
            }

            $intendedUrl = $request->fullUrl();

            // Only remember intended URL if it is a valid human-navigable page
            if (self::isValidRedirectUrl($intendedUrl)) {
                session([
                    'url.intended' => $intendedUrl,
                    'redirect_to' => $intendedUrl,
                ]);

                return redirect()->guest(route('fsrapp.login', ['redirect' => $intendedUrl]))->with('info', 'Please log in to continue.');
            } else {
                session()->forget(['url.intended', 'redirect_to']);
                return redirect()->guest(route('fsrapp.login'))->with('info', 'Please log in to continue.');
            }
        }

        return $next($request);
    }
}

