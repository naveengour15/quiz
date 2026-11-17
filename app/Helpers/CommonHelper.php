<?php
use App\Models\CommunicationConfig;

use Carbon\Carbon;
use App\Models\Quiz;
use App\Models\AdViewHistory;
use App\Models\AdClickHistory;
use Illuminate\Support\Facades\Auth;



// *********hleper new ************

if (!function_exists('getQuestionAvailableTime')) {

    function getQuestionAvailableTime($scheduleTime, $questionNo, $quizId = null , $visibility = 'public')
    {
       
        $scheduleTime = Carbon::parse(
            $scheduleTime,
            'Asia/Kolkata'
        );


        // Configuration (Seconds)
        $preScheduleBuffer = 60;
        $questionInterval  = 17;
        $questionDuration  = 180;

        // Calculate Times
        $availableStartTime = $scheduleTime->copy()->subSeconds($preScheduleBuffer);

        $questionStartTime = $scheduleTime->copy()->addSeconds(
            $questionNo * $questionInterval
        );

        if ($visibility === 'fastest_finger_first') {
            $fff_time = 95;
             $questionStartTime = $scheduleTime->copy()->addSeconds(
                $fff_time
            );
        }

        $availableEndTime = $questionStartTime->copy()->addSeconds($questionDuration);

        return [
            'scheduleTime' => $scheduleTime->format('Y-m-d H:i:s'),
            'questionNo' => $questionNo,
            'available_start_time' => $availableStartTime->format('Y-m-d H:i:s'),
            'available_end_time'   => $availableEndTime->format('Y-m-d H:i:s'),
            'current_time'         => now()->format('Y-m-d H:i:s'),
            'is_available'         => now()->between($availableStartTime, $availableEndTime),
        ];
    }
}


if (!function_exists('checkQuizTimeSlot')) {

    function checkQuizTimeSlot($startTime, $endTime, $ignoreQuizId = null)
    {  
        $query = Quiz::query();

        if ($ignoreQuizId) {
            $query->where('id', '!=', $ignoreQuizId);
        }

        return $query->where(function ($q) use ($startTime, $endTime) {

            // Existing slot overlaps new slot
            $q->whereBetween('availability_start', [$startTime, $endTime])
              ->orWhereBetween('availability_end', [$startTime, $endTime])
              ->orWhere(function ($sub) use ($startTime, $endTime) {
                    $sub->where('availability_start', '<=', $startTime)
                        ->where('availability_end', '>=', $endTime);
              });

        })->first();
    }
}

if (!function_exists('getTenantId')) {
    function getTenantId()
    {
        if (\Illuminate\Support\Facades\Auth::guard('admin')->check()) {
            $user = \Illuminate\Support\Facades\Auth::guard('admin')->user();
            return ($user instanceof \App\Models\Tenant) ? $user->id : ($user->tenant_id ?? $user->id ?? null);
        }
        if (app()->bound('tenant') && app('tenant')) {
            return app('tenant')->id;
        }
        if (session()->has('tenant_id') && session('tenant_id')) {
            return session('tenant_id');
        }
        if (session()->has('user_tenant_id') && session('user_tenant_id')) {
            return session('user_tenant_id');
        }
        if (\Illuminate\Support\Facades\Auth::check()) {
            $user = \Illuminate\Support\Facades\Auth::user();
            return $user->tenant_id ?? $user->id ?? null;
        }
        return null;
    }
}


if (!function_exists('getSuperAdmin')) {
    function getSuperAdmin()
    {
        return \Illuminate\Support\Facades\Auth::guard('superadmin')->user();
    }
}

if (!function_exists('getSuperAdminTenantId')) {
    function getSuperAdminTenantId()
    {
        return (int) (\Illuminate\Support\Facades\Auth::guard('superadmin')->user()->tenant_id ?? 0) ?: 1;
    }
}


if (!function_exists('quizSetting')) {
    function quizSetting($key = null, $default = null)
    {
        static $setting = null;

        if ($setting === null) {
            $setting = \App\Models\QuizSetting::first();
        }

        if (!$setting || $setting instanceof \__PHP_Incomplete_Class) {
            return $default;
        }

        return $key ? ($setting->{$key} ?? $default) : $setting;
    }
}



if (!function_exists('recordBannerView')) {

    function recordBannerView($bannerId, $pageType, $userId = null, $tenantId = null)
    {
        if (!$bannerId || !$pageType) {
            return;
        }

        $tenantId = $tenantId ?: (getTenantId() ?: getSuperAdminTenantId());
        $viewerId = $userId ?: Auth::id();
        $sessionId = (session()->isStarted() ? session()->getId() : null) ?: (request()->header('session_token') ?: request()->ip());

        // De-duplicate views within 60s window to avoid 500k redundant INSERTs during live quiz
        $dedupKey = "fsr_bv_{$bannerId}_{$pageType}_" . ($viewerId ?: $sessionId);
        if (!\Illuminate\Support\Facades\Cache::add($dedupKey, 1, 60)) {
            return;
        }

        try {
            AdViewHistory::create([
                'tenant_id'  => $tenantId,
                'ad_id'      => $bannerId,
                'banner_id'  => $bannerId,
                'page_type'  => $pageType,
                'type'       => 'banner',
                'quiz_id'    => null,
                'viewer_id'  => $viewerId,
                'session_id' => $sessionId,
                'ip_address' => request()->ip(),
                'device'     => request()->header('User-Agent'),
                'browser'    => request()->header('User-Agent'),
                'os'         => php_uname('s'),
                'country'    => null,
                'state'      => null,
                'city'       => null,
                'referrer'   => request()->header('referer') ?: url()->current(),
                'is_unique'  => true,
                'viewed_at'  => now(),
            ]);
        } catch (\Throwable $e) {}
    }
}


if (!function_exists('recordBannerClick')) {

    function recordBannerClick($bannerId, $pageType, $url = null, $userId = null, $tenantId = null)
    {
        if (!$bannerId || !$pageType) {
            return;
        }

        $tenantId = $tenantId ?: (getTenantId() ?: getSuperAdminTenantId());
        $userId = $userId ?: Auth::id();
        if ($userId && !\App\Models\User::where('id', $userId)->exists()) {
            $userId = null;
        }
        $sessionId = (session()->isStarted() ? session()->getId() : null) ?: (request()->header('session_token') ?: request()->ip());

        AdClickHistory::create([
            'tenant_id'       => $tenantId,
            'ad_id'           => $bannerId,
            'banner_id'       => $bannerId,
            'page_type'       => $pageType,
            'type'            => 'banner',
            'quiz_id'         => null,
            'user_id'         => $userId,
            'session_id'      => $sessionId,
            'ip_address'      => request()->ip(),
            'device'          => request()->header('User-Agent'),
            'browser'         => request()->header('User-Agent'),
            'os'              => php_uname('s'),
            'country'         => null,
            'state'           => null,
            'city'            => null,
            'destination_url' => $url ?: url()->current(),       
            'clicked_at'      => now(),
        ]);
    }
}