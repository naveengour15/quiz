<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\PageVisitor;
use App\Models\Quiz;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TrackPageVisitors
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    /**
     * Handle tasks after the response has been sent to the browser.
     * This ensures visitor tracking causes ZERO latency for front users.
     */
    public function terminate(Request $request, Response $response): void
    {
        try {
            // 1. Only track GET requests (Skip POST/PUT/DELETE API hits & forms)
            if (!$request->isMethod('GET')) {
                return;
            }

            // 2. Skip AJAX/JSON polling endpoints
            if ($request->ajax() || $request->wantsJson()) {
                return;
            }

            $path = $request->path();

            // 3. Skip backend admin panels, APIs, and android routes
            if (
                $request->is('superadmin*') ||
                $request->is('admin*') ||
                $request->is('api/*') ||
                $request->is('androidapi/*') ||
                $request->is('*/api/*')
            ) {
                return;
            }

            // 4. Skip static assets, service workers, or debug bars
            if (preg_match('/\.(js|css|png|jpg|jpeg|gif|svg|ico|woff|woff2|ttf|eot|mp3|wav|ogg|json)$/i', $path) ||
                str_contains($path, 'firebase-messaging-sw') ||
                str_contains($path, '_debugbar')) {
                return;
            }

            // 5. ⛔ ZERO-LOAD QUIZ SAFETY CHECK:
            // "jab koi bhi quiz time ho us time visit entry band hona chahiye jisse server per load na bade"
            if (Quiz::isAnyQuizPlayingNow()) {
                return;
            }

            // 6. Anti-flood debounce per IP + Path (10 seconds) to avoid database spam on rapid reloads
            $ip = $request->ip();
            $debounceKey = 'pv_deb_' . md5(($ip ?? 'unknown') . '_' . $path);
            if (Cache::has($debounceKey)) {
                return;
            }
            Cache::put($debounceKey, 1, 10);

            // 7. Resolve user & tenant
            $user = $request->user('web');
            $userId = $user?->id ?: session('fsr_user_id');
            $tenantId = $user?->tenant_id ?: (session('tenant_id') ?: 1);

            // 8. Resolve page name & route
            $routeName = $request->route() ? $request->route()->getName() : null;
            $pageName = self::resolvePageName($routeName, $path);
            $fullUrl = $request->fullUrl();

            // 9. Parse User-Agent
            $userAgent = $request->userAgent() ?: '';
            $deviceInfo = self::parseUserAgent($userAgent);

            // 10. Record page visit
            PageVisitor::create([
                'tenant_id'   => (int) $tenantId,
                'user_id'     => $userId ? (int) $userId : null,
                'page_name'   => $pageName,
                'page_url'    => substr($fullUrl, 0, 500),
                'route_name'  => $routeName ? substr($routeName, 0, 191) : null,
                'ip_address'  => substr($ip ?? '', 0, 45),
                'device_type' => $deviceInfo['device_type'],
                'browser'     => $deviceInfo['browser'],
                'platform'    => $deviceInfo['platform'],
                'user_agent'  => $userAgent,
                'visit_date'  => now()->toDateString(),
            ]);

        } catch (\Throwable $e) {
            // Silently ignore or log without impacting the user
            Log::warning('TrackPageVisitors error: ' . $e->getMessage());
        }
    }

    /**
     * Map routes / paths to human-friendly page names
     */
    public static function resolvePageName(?string $routeName, string $path): string
    {
        $map = [
            'fsrapp.home'                  => 'Home Page',
            'fsrapp.allquizlist.index'     => 'All Quiz List',
            'fsrapp.quiz.list'             => 'Quiz List',
            'fsrapp.quizes.join'           => 'Quiz Join Page',
            'fsrapp.quizes.waiting'        => 'Quiz Waiting Room',
            'fsrapp.quizes.start-counter'  => 'Quiz Start Counter',
            'fsrapp.quizes.question'       => 'Live Quiz Play',
            'fsrapp.quizes.review-counter' => 'Quiz Review Counter',
            'fsrapp.quizes.answer-sheet'   => 'Quiz Answer Sheet',
            'fsrapp.quizes.final-counter'  => 'Quiz Final Counter',
            'fsrapp.quizes.leaderboard'    => 'Quiz Leaderboard',
            'fsrapp.fff.waiting'           => 'FFF Waiting Room',
            'fsrapp.fff.start-counter'     => 'FFF Start Counter',
            'fsrapp.fff.question'          => 'FFF Live Quiz Play',
            'fsrapp.fff.review-counter'    => 'FFF Review Counter',
            'fsrapp.fff.answer-sheet'      => 'FFF Answer Sheet',
            'fsrapp.fff.leaderboard'       => 'FFF Leaderboard',
            'fsrapp.login'                 => 'Login Page',
            'fsrapp.otp'                   => 'OTP Verification',
            'fsrapp.complete-profile'      => 'Complete Profile',
            'fsrapp.privacy-policy'        => 'Privacy Policy',
            'fsrapp.terms-conditions'      => 'Terms & Conditions',
            'fsrapp.how-fsrquiz-works'     => 'How FSRQuiz Works',
            'fsrapp.leaderboard'           => 'Global Leaderboard',
            'fsrapp.history'               => 'Quiz History',
            'fsrapp.history.leaderboard'   => 'History Leaderboard',
            'fsrapp.history.answersheet'   => 'History Answer Sheet',
            'fsrapp.survey.index'          => 'Survey List',
            'fsrapp.practice-quiz.index'   => 'Practice Quizzes',
        ];

        if ($routeName && isset($map[$routeName])) {
            return $map[$routeName];
        }

        // Clean fallback from path
        $cleanPath = trim(str_replace(['fsrapp/', 'fsrapp'], '', $path), '/');
        if (empty($cleanPath)) {
            return 'Front Home';
        }

        return ucwords(str_replace(['-', '_', '/'], ' ', $cleanPath));
    }

    /**
     * Fast regex user-agent parsing for Device, Browser, and OS Platform
     */
    public static function parseUserAgent(string $ua): array
    {
        $deviceType = 'Desktop';
        $browser = 'Other';
        $platform = 'Other';

        if (empty($ua)) {
            return [
                'device_type' => $deviceType,
                'browser'     => $browser,
                'platform'    => $platform,
            ];
        }

        // 1. Device Type
        if (preg_match('/(tablet|ipad|playbook|silk)|(android(?!.*mobi))/i', $ua)) {
            $deviceType = 'Tablet';
        } elseif (preg_match('/(mobile|iphone|ipod|blackberry|opera mini|iemobile|wpdesktop)/i', $ua)) {
            $deviceType = 'Mobile';
        }

        // 2. Platform / OS
        if (preg_match('/android/i', $ua)) {
            $platform = 'Android';
        } elseif (preg_match('/(iphone|ipad|ipod)/i', $ua)) {
            $platform = 'iOS';
        } elseif (preg_match('/windows/i', $ua)) {
            $platform = 'Windows';
        } elseif (preg_match('/macintosh|mac os x/i', $ua)) {
            $platform = 'macOS';
        } elseif (preg_match('/linux/i', $ua)) {
            $platform = 'Linux';
        }

        // 3. Browser
        if (preg_match('/edg[ea]?\/([0-9.]+)/i', $ua)) {
            $browser = 'Edge';
        } elseif (preg_match('/opr\/([0-9.]+)|opera/i', $ua)) {
            $browser = 'Opera';
        } elseif (preg_match('/chrome\/([0-9.]+)/i', $ua) && !preg_match('/edg/i', $ua)) {
            $browser = 'Chrome';
        } elseif (preg_match('/version\/([0-9.]+).*safari/i', $ua)) {
            $browser = 'Safari';
        } elseif (preg_match('/firefox\/([0-9.]+)/i', $ua)) {
            $browser = 'Firefox';
        }

        return [
            'device_type' => $deviceType,
            'browser'     => $browser,
            'platform'    => $platform,
        ];
    }
}

