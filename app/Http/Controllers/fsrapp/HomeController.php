<?php

namespace App\Http\Controllers\fsrapp;

use App\Http\Controllers\Controller;
use App\Interfaces\fsrapp\HomeRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Banner;

class HomeController extends Controller
{
    protected HomeRepositoryInterface $homeRepository;

    public function __construct(HomeRepositoryInterface $homeRepository)
    {
        $this->homeRepository = $homeRepository;
    }

    /**
     * Display the FSR App Home screen.
     */
    public function index()
    {
        // Destroy/clear quiz cache and session only if an active quiz was running
        if (session('active_quiz_id') || session('active_fff_quiz_id')) {
            $this->homeRepository->clearActiveQuizSessionAndCache();
        }

        $user = Auth::user();
        if (!$user && session('fsr_user_id')) {
            $user = User::find(session('fsr_user_id'));
        }
        $data = $this->homeRepository->getHomeData($user);

        return view('fsrapp.home.index', $data);
    }

    public function getQuizTimes(Request $request)
    {
        $quizIds = (array) $request->input('quiz_ids', []);
        $tenantId = session('tenant_id') ?? (Auth::user()?->tenant_id ?? 1);
        
        $cacheKey = "fsr_quiz_times_{$tenantId}_" . (empty($quizIds) ? 'all' : md5(implode(',', $quizIds)));
        
        // Cache for 30s to prevent dogpiling/cache stampede under 50k concurrent users
        $times = \Illuminate\Support\Facades\Cache::remember($cacheKey, 30, function () use ($tenantId, $quizIds) {
            if (empty($quizIds)) {
                $quizzes = \App\Models\Quiz::whereIn('tenant_id', [$tenantId, 1])
                    ->where('approve_status', 'approved')
                    ->where('complete_status', 'pending')
                    ->where('scheduled_at', '>=', now()->subMinutes(15))
                    ->orderBy('scheduled_at', 'asc')
                    ->take(20)
                    ->get(['id', 'scheduled_at', 'status', 'complete_status']);
            } else {
                $quizzes = \App\Models\Quiz::whereIn('id', $quizIds)->get(['id', 'scheduled_at', 'status', 'complete_status']);
            }

            $list = [];
            foreach ($quizzes as $quiz) {
                $list[$quiz->id] = [
                    'id' => $quiz->id,
                    'start_time' => $quiz->scheduled_at ? \Carbon\Carbon::parse($quiz->scheduled_at)->timestamp : 0,
                    'status' => $quiz->status,
                    'complete_status' => $quiz->complete_status,
                ];
            }
            return $list;
        });

        $now = \Carbon\Carbon::now()->timestamp;

        // Ensure dynamic server_time is attached to each record
        foreach ($times as $id => $val) {
            $times[$id]['server_time'] = $now;
        }

        return response()->json([
            'success' => true,
            'data' => $times,
            'server_time' => $now
        ]);
    }

    public function howFsrQuizWorks()
    {
        return view('fsrapp.howfsrquizworks.how-fsrquiz-works');
    }

    /**
     * Close/acknowledge an announcement.
     */
    public function closeAnnouncement($id)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $tenantId = $user->tenant_id ?? 1;
        $result = $this->homeRepository->closeAnnouncement($user->id, $tenantId, (int) $id);

        return response()->json($result);
    }

    /**
     * Close/acknowledge an app updation popup.
     */
    public function closeAppUpdation($id)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $tenantId = $user->tenant_id ?? 1;
        $result = $this->homeRepository->closeAppUpdation($user->id, $tenantId, (int) $id);

        // Server-side Session & Cache Cleanup for App Updation (preserving login and locale)
        $preserveKeys = [
            '_token',
            'login_web_',
            'fsr_authenticated',
            'fsr_user_id',
            'fsr_user_name',
            'fsr_user_mobile',
            'locale',
            'lang',
            'language',
            'app_lang',
            'flash',
            '_flash',
            '_previous',
        ];

        $sessionData = session()->all();
        foreach ($sessionData as $key => $val) {
            $preserve = false;
            foreach ($preserveKeys as $pKey) {
                if ($key === $pKey || str_starts_with($key, $pKey) || str_contains($key, 'login_web_')) {
                    $preserve = true;
                    break;
                }
            }
            if (!$preserve) {
                session()->forget($key);
            }
        }

        return response()->json($result);
    }

    /**
     * Track a banner click and return its destination without redirecting the request.
     */
    public function bannerClick($banner, $type)
    {
        $targetUrl = $this->homeRepository->handleBannerClick((int) $banner, (string) $type);

        // if ($targetUrl) {
        //     return redirect()->away($targetUrl);
        // }

        // return back();

        return response()->json([
            'success' => true,
            'url' => $targetUrl,
        ]);
    }

    /**
     * Track a banner only after it has become visible in the browser.
     */
    public function bannerView($banner, $type)
    {
        $bannerData = Banner::where('status', 'active')->find((int) $banner);

        if (!$bannerData) {
            return response()->json(['success' => false], 404);
        }

        $bannerTypes = (array) $bannerData->type;
        if (!in_array('all', $bannerTypes, true) && !in_array((string) $type, $bannerTypes, true)) {
            return response()->json(['success' => false], 404);
        }

        recordBannerView($bannerData->id, (string) $type);

        return response()->json(['success' => true]);
    }

    /**
     * Real-time polling endpoint for latest quiz completion status.
     */
    public function lastQuizStatus(Request $request)
    {
        $tenantId = function_exists('getTenantId') ? (getTenantId() ?: 1) : 1;
        $quizId = $request->query('quiz_id') ? (int) $request->query('quiz_id') : null;
        $status = $this->homeRepository->getLastQuizStatus((int) $tenantId, $quizId);

        return response()->json($status);
    }

    /**
     * Toggle WhatsApp global notification preference
     */
    public function toggleWhatsappNotify(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $user = Auth::user();
        $tenantId = $user->tenant_id ?? 1;

        $record = \App\Models\UsersWhatsappNotify::where('user_id', $user->id)->first();
        
        if ($record) {
            $newStatus = $record->status === 'active' ? 'inactive' : 'active';
            $record->update(['status' => $newStatus]);
            return response()->json(['success' => true, 'status' => $newStatus]);
        } else {
            \App\Models\UsersWhatsappNotify::create([
                'user_id' => $user->id,
                'tenant_id' => $tenantId,
                'status' => 'active'
            ]);
            return response()->json(['success' => true, 'status' => 'active']);
        }
    }

    /**
     * Subscribe user to official WhatsApp channel and record entry.
     */
    public function subscribeWhatsappChannel(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            $userId = session('fsr_user_id');
            if ($userId) {
                $user = User::find($userId);
            }
        }

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $tenantId = $user->tenant_id ?? (session('tenant_id') ?? 1);
        $result = $this->homeRepository->subscribeWhatsappChannel((int) $user->id, (int) $tenantId);

        return response()->json($result);
    }
}
