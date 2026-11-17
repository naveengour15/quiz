<?php

namespace App\Http\Controllers\androidapi;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Interfaces\androidapi\HomeRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class HomeController extends Controller
{
    protected HomeRepositoryInterface $homeRepository;

    public function __construct(HomeRepositoryInterface $homeRepository)
    {
        $this->homeRepository = $homeRepository;
    }

    /**
     * Get Home Screen Data API
     * GET /androidapi/home
     */
    public function index(Request $request)
    {
        $user = $this->getAuthenticatedUser($request);
        $data = $this->homeRepository->getHomeData($user);

        return response()->json([
            'status' => true,
            'message' => 'Home data fetched successfully.',
            'data' => $data
        ]);
    }

    public function categories(Request $request)
    {
        return response()->json(['success' => true, 'data' => $this->homeRepository->getHomeData($this->getAuthenticatedUser($request))['categories']]);
    }

    public function banners(Request $request)
    {
        return response()->json(['success' => true, 'data' => $this->homeRepository->getHomeData($this->getAuthenticatedUser($request))['banners']]);
    }

    public function dashboard(Request $request)
    {
        $data = $this->homeRepository->getHomeData($this->getAuthenticatedUser($request));
        return response()->json(['success' => true, 'data' => $data['user_stats'] ?? []]);
    }

    public function lastQuizStatus(Request $request)
    {
        $user = $this->getAuthenticatedUser($request);
        $tenantId = $user ? ($user->tenant_id ?? 1) : 1;
        $quizId = $request->query('quiz_id') ? (int) $request->query('quiz_id') : null;
        $status = $this->homeRepository->getLastQuizStatus($tenantId, $quizId);
        return response()->json($status);
    }

    /**
     * Get real-time server-synced quiz countdown times
     * POST /androidapi/home/quiz-times
     */
    public function getQuizTimes(Request $request)
    {
        $user = $this->getAuthenticatedUser($request);
        $tenantId = $user ? ($user->tenant_id ?? 1) : 1;
        $quizIds = (array) $request->input('quiz_ids', []);
        $result = $this->homeRepository->getQuizTimes($quizIds, $tenantId);
        return response()->json($result);
    }

    /**
     * Subscribe user to WhatsApp channel
     * POST /androidapi/home/whatsapp-subscribe
     */
    public function subscribeWhatsappChannel(Request $request)
    {
        $user = $this->getAuthenticatedUser($request);
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthenticated user.'
            ], 401);
        }

        $tenantId = $user->tenant_id ?? 1;
        $result = $this->homeRepository->subscribeWhatsappChannel($user->id, $tenantId);
        return response()->json($result);
    }

    /**
     * Close Announcement API
     * POST /androidapi/home/close-announcement/{id}
     */
    public function closeAnnouncement(Request $request, $id)
    {
        $user = $this->getAuthenticatedUser($request);
        $token = $request->bearerToken() ?: $request->input('session_token');
        \Illuminate\Support\Facades\Log::info("Close Announcement Attempt. Token received: " . ($token ?: 'NULL'));

        $user = $this->getAuthenticatedUser($request);
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthenticated user.'
            ], 401);
        }

        \Illuminate\Support\Facades\Log::info("Close Announcement Hit! UserID: {$user->id}, AnnouncementID: {$id}");
        $result = $this->homeRepository->closeAnnouncement($user->id, (int) $id);
        return response()->json($result);
    }

    /**
     * Close App Updation API
     * POST /androidapi/home/close-app-updation/{id}
     */
    public function closeAppUpdation(Request $request, $id)
    {
        $user = $this->getAuthenticatedUser($request);
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthenticated user.'
            ], 401);
        }

        $result = $this->homeRepository->closeAppUpdation($user->id, (int) $id);
        return response()->json($result);
    }

    /**
     * Track a banner click and return its destination URL
     * POST /androidapi/banner/click/{banner}/{type}
     */
    public function bannerClick(Request $request, $banner, $type)
    {
        $user = $this->getAuthenticatedUser($request);
        $targetUrl = $this->homeRepository->handleBannerClick((int) $banner, (string) $type, $user);

        return response()->json([
            'success' => true,
            'url' => $targetUrl,
        ]);
    }

    /**
     * Track a banner view
     * POST /androidapi/banner/view/{banner}/{type}
     */
    public function bannerView(Request $request, $banner, $type)
    {
        $user = $this->getAuthenticatedUser($request);
        $recorded = $this->homeRepository->recordBannerView((int) $banner, (string) $type, $user);

        return response()->json([
            'success' => $recorded,
        ]);
    }

    /**
     * Helper to resolve authenticated user from Bearer Token, Session Token, or user_id
     */
    /**
     * Check App Update API
     * GET /androidapi/check-update
     */
    public function checkAppUpdate(Request $request)
    {
        $settingVersion = \App\Models\Setting::where('key', 'android_app_version')->value('value') ?? '1.0.0';
        $settingVersionCode = (int) (\App\Models\Setting::where('key', 'android_app_version_code')->value('value') ?? 1);
        $updateUrl = \App\Models\Setting::where('key', 'android_app_update_url')->value('value') 
            ?? 'https://play.google.com/store/apps/details?id=com.chaloonline.fsrquiz';
        $forceUpdate = (bool) (\App\Models\Setting::where('key', 'android_app_force_update')->value('value') ?? false);
        $notes = \App\Models\Setting::where('key', 'android_app_update_notes')->value('value') 
            ?? 'A new version of FSRQuiz is available with new features and improvements!';

        $latestUpdation = \App\Models\AppUpdation::where(function ($q) {
            $q->whereNull('status')->orWhereIn('status', ['send', 'active', 'published', '1']);
        })->latest('id')->first();

        $user = $this->getAuthenticatedUser($request);
        $hasUnclosedUpdation = false;
        if ($latestUpdation && $user) {
            $isClosed = \App\Models\AppUpdationHistory::where('user_id', $user->id)
                ->where('app_updation_id', $latestUpdation->id)
                ->exists();
            $hasUnclosedUpdation = !$isClosed;
        } elseif ($latestUpdation) {
            $hasUnclosedUpdation = true;
        }

        return response()->json([
            'success' => true,
            'has_update' => $settingVersionCode > (int) $request->input('version_code', 1),
            'latest_version' => $settingVersion,
            'latest_version_code' => $settingVersionCode,
            'update_url' => $updateUrl,
            'play_store_url' => 'https://play.google.com/store/apps/details?id=com.chaloonline.fsrquiz',
            'apk_url' => 'https://fsrquiz.com/downloads/fsrquiz.apk',
            'force_update' => $forceUpdate,
            'title' => $latestUpdation ? ($latestUpdation->subject ?? 'App Update') : 'New Version Available!',
            'message' => $latestUpdation ? ($latestUpdation->message ?: $notes) : $notes,
            'updation_id' => $latestUpdation?->id,
        ]);
    }

    private function getAuthenticatedUser(Request $request): ?User
    {
        if (Auth::check()) {
            return Auth::user();
        }

        $token = $request->bearerToken();
        if (!$token) {
            $token = $request->header('session_token') ?: $request->input('session_token');
        }

        if ($token) {
            $user = User::where('session_token', $token)->first();
            if ($user) return $user;
        }

        $userId = $request->input('user_id') ?: $request->header('user_id');
        if ($userId) {
            return User::find($userId);
        }

        return null;
    }
}
