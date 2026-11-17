<?php

namespace App\Http\Controllers\androidapi;

use App\Http\Controllers\Controller;
use App\Http\Controllers\androidapi\Traits\ResolvesAndroidUser;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionUserAnswer;
use App\Models\QuizUser;
use App\Models\QuizWinner;
use App\Models\QuizNotification;
use App\Models\ActionTrail;
use Carbon\Carbon;
use App\Interfaces\androidapi\QuizRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class QuizesController extends Controller
{
    use ResolvesAndroidUser;

    public function __construct(protected QuizRepositoryInterface $quizRepository)
    {
    }

    /**
     * Mark quiz as processing and schedule winner cron if needed
     */
    protected function markQuizProcessing(Quiz $quiz): void
    {
        if ($quiz->complete_status === 'pending') {

            $second = 207; // 11 question + 20 second conter 
            if($quiz->visibility === 'fastest_finger_first'){
                $second = 80;
            }
             if ($quiz->scheduled_at && \Carbon\Carbon::parse($quiz->scheduled_at)->addSeconds($second)->isPast()) {
                       
                $quiz->update(['complete_status' => 'processing']);

                $scheduledAt = Carbon::parse($quiz->scheduled_at)->addSeconds($second);
                \App\Models\QuizCronJobHistory::firstOrCreate(
                    [
                        'quiz_id' => (int) $quiz->id,
                        'cron_type' => 'quiz_winner_leaderboard',
                    ],
                    [
                        'tenant_id' => $quiz->tenant_id ?? 1,
                        'scheduled_at' => $scheduledAt,
                        'status' => 'pending',
                    ]
                );
            }
        }
    }

    /**
     * Auto check if quiz time has passed (sched + 10s + (17*11)s + 10s = 207s) and update to processing
     */
    protected function checkAndUpdateQuizProcessingStatus(?Quiz $quiz): void
    {
        if (!$quiz || $quiz->complete_status === 'processing' || $quiz->complete_status === 'completed') {
            return;
        }

        $now = Carbon::now();
        $schedTime = null;
        if ($quiz->scheduled_at) {
            $schedTime = Carbon::parse($quiz->scheduled_at);
        } elseif ($quiz->availability_start) {
            $schedTime = Carbon::parse($quiz->availability_start);
        }

        $shouldProcess = false;

        if ($schedTime) {
            if ($quiz->visibility === 'fastest_finger_first') {
                $totalDurationSec = 10 + 60 + 10; // 10s start + 60s questions (1 min) + 10s review = 80s
            } else {
                $qLimit = (!empty($quiz->question_limit) ? (int) $quiz->question_limit : 10) + 1; // 10 base + 1 switch = 11
                $totalDurationSec = 10 + ($qLimit * 17) + 10; // 10s start + (17*11)s questions + 10s review = 207s
            }
            $quizEndTime = $schedTime->copy()->addSeconds($totalDurationSec);

            if ($now->gte($quizEndTime)) {
                $shouldProcess = true;
            }
        }

        if ($quiz->availability_end && Carbon::parse($quiz->availability_end)->isPast()) {
            $shouldProcess = true;
        }

        if ($shouldProcess) {
            $this->markQuizProcessing($quiz);
        }
    }

    /**
     * Get single next upcoming quiz API (lightweight)
     * GET /androidapi/quizzes/next-quiz?current_quiz_id=...
     */
    public function nextQuiz(Request $request)
    {
        $user = $this->getAuthUser($request);
        $tenantId = $user ? ($user->tenant_id ?? 1) : 1;
        $userId = $user ? $user->id : null;
        $currentQuizId = (int) $request->input('current_quiz_id', 0);

        // Current quiz info
        $currentQuiz = $currentQuizId ? Quiz::find($currentQuizId) : null;
        $currentScheduledAt = $currentQuiz && $currentQuiz->scheduled_at 
            ? Carbon::parse($currentQuiz->scheduled_at) 
            : null;

        if (!$currentScheduledAt && $request->filled('current_quiz_time')) {
            try {
                $currentScheduledAt = Carbon::parse($request->input('current_quiz_time'));
            } catch (\Exception $e) {}
        }

        // Base query for approved and pending quizzes
        $baseQuery = function () use ($tenantId, $currentQuizId) {
            $q = Quiz::with(['rankRewards.voucher', 'quizType', 'category'])
                ->whereIn('tenant_id', [$tenantId, 1])
                ->where('approve_status', 'approved')
                ->where('complete_status', 'pending');

            if ($currentQuizId) {
                $q->where('id', '!=', $currentQuizId);
            }
            return $q;
        };

        $next = null;

        // Next quiz MUST be scheduled after current quiz AND in the future (never show expired/past quizzes)
        $threshold = ($currentScheduledAt && $currentScheduledAt->gt(now())) ? $currentScheduledAt : now();

        $next = $baseQuery()
            ->where('scheduled_at', '>', $threshold)
            ->orderBy('scheduled_at', 'asc')
            ->orderBy('id', 'asc')
            ->first();

        // If current quiz is upcoming, also check if another quiz exists at the exact same time with higher ID
        if (!$next && $currentScheduledAt && $currentScheduledAt->gte(now())) {
            $next = $baseQuery()
                ->where('scheduled_at', '=', $currentScheduledAt)
                ->where('id', '>', $currentQuizId)
                ->orderBy('id', 'asc')
                ->first();
        }

        // User specific arrays
        $joinedQuizIds = [];
        $notifiedQuizIds = [];
        $inactiveQuizIds = [];
        $userLimit = 0;

        if ($user) {
            $userLimit = (int) ($user->available_quiz_limit ?? $user->remaining_quizzes ?? 0);
            if ($userLimit <= 0) {
                $statLimit = DB::table('user_stats')->where('user_id', $user->id)->value('available_quiz_limit');
                if ($statLimit !== null) {
                    $userLimit = (int) $statLimit;
                }
            }

            if ($next) {
                $joined = QuizUser::where('user_id', $user->id)->where('quiz_id', $next->id)->exists();
                if ($joined) $joinedQuizIds[] = $next->id;

                $notified = QuizNotification::where('user_id', $user->id)->where('quiz_id', $next->id)->exists();
                if ($notified) $notifiedQuizIds[] = $next->id;
            }
        }

        $formattedQuiz = null;
        if ($next) {
            $maxPoints = ($next->rankRewards && $next->rankRewards->count() > 0)
                ? $next->rankRewards->max('reward')
                : ($next->no_of_points ?? 500);

            $bannerImage = $next->banner_image
                ? (str_starts_with($next->banner_image, 'http') ? $next->banner_image : asset($next->banner_image))
                : null;

            $rankRewards = ($next->rankRewards && $next->rankRewards->count() > 0)
                ? $next->rankRewards->sortBy('start_rank')->map(function ($r) {
                    return [
                        'id' => $r->id,
                        'start_rank' => (int) $r->start_rank,
                        'end_rank' => (int) ($r->end_rank ?: $r->start_rank),
                        'reward' => (float) $r->reward,
                        'voucher_id' => $r->voucher_id,
                        'voucher_name' => $r->voucher?->name ?? null,
                    ];
                })->values()
                : [];

            $startTime = $next->scheduled_at ? (int) (Carbon::parse($next->scheduled_at)->getTimestamp() * 1000) : 0;
            $endTime = $next->availability_end
                ? (int) (Carbon::parse($next->availability_end)->getTimestamp() * 1000)
                : ($startTime ? $startTime + 15 * 60 * 1000 : 0);

            $isJoinedThis = in_array($next->id, $joinedQuizIds);
            $isNotifiedThis = in_array($next->id, $notifiedQuizIds);

            $formattedQuiz = [
                'id' => $next->id,
                'title' => $next->title ?? 'Quiz',
                'description' => $next->description ?? '',
                'category_name' => $next->category?->name ?? 'General',
                'quiz_type_name' => $next->quizType?->name ?? 'Quiz',
                'banner_image' => $bannerImage,
                'visibility' => $next->visibility ?? 'public',
                'entry_fee' => (float) ($next->entry_fee ?? 0),
                'prize_points' => (int) $maxPoints,
                'no_of_players' => (int) ($next->no_of_players ?? 0),
                'joined_count' => (int) ($next->joined_count ?? 0),
                'scheduled_at' => $next->scheduled_at ? Carbon::parse($next->scheduled_at)->toIso8601String() : null,
                'scheduled_timestamp' => $startTime,
                'availability_end' => $next->availability_end ? Carbon::parse($next->availability_end)->toIso8601String() : null,
                'availability_end_timestamp' => $endTime,
                'end_time' => $endTime,
                'time_str' => $next->scheduled_at ? Carbon::parse($next->scheduled_at)->format('h:i A') : null,
                'is_joined' => $isJoinedThis,
                'is_inactive' => in_array($next->id, $inactiveQuizIds),
                'is_notified' => $isNotifiedThis,
                'is_private' => (bool) $next->is_private,
                'complete_status' => $next->complete_status ?? 'pending',
                'status' => $next->status ?? 'pending',
                'rank_rewards' => $rankRewards,
            ];
        }

        return response()->json([
            'success' => true,
            'status' => true,
            'message' => $formattedQuiz ? 'Next quiz fetched successfully.' : 'No upcoming quiz found.',
            'next_quiz' => $formattedQuiz,
            'quiz' => $formattedQuiz,
            'user_limit' => $userLimit,
            'joined_quiz_ids' => $joinedQuizIds,
            'notified_quiz_ids' => $notifiedQuizIds,
            'inactive_quiz_ids' => $inactiveQuizIds,
            'joinedQuizIds' => $joinedQuizIds,
            'notifiedQuizIds' => $notifiedQuizIds,
            'inactiveQuizIds' => $inactiveQuizIds,
        ]);
    }

    /**
     * Get details of a quiz before joining
     */
    public function getDetails(Request $request, $id)
    {
        $quiz = Quiz::with(['rankRewards.voucher', 'quizType', 'category'])->find($id);

        if (!$quiz) {
            return response()->json([
                'success' => false,
                'message' => 'Quiz not found.'
            ], 404);
        }

        $this->checkAndUpdateQuizProcessingStatus($quiz);

        $user = $this->getAuthUser($request);
        $isJoined = false;
        $isNotified = false;
        $isInactive = false;
        $userLimit = 0;

        if ($user) {
            $userLimit = (int) ($user->available_quiz_limit ?? 0);
            $enrollment = QuizUser::where('quiz_id', $id)->where('user_id', $user->id)->first();

            if ($enrollment) {
                $isJoined = ($enrollment->status === 'active');
                $isInactive = ($enrollment->status === 'inactive');
            }

            $isNotified = QuizNotification::where('quiz_id', $id)->where('user_id', $user->id)->exists();
        }

        $banners = \App\Models\Banner::where('status', 'active')->latest()->get();

        return response()->json([
            'success' => true,
            'quiz' => $quiz,
            'is_joined' => $isJoined,
            'is_inactive' => $isInactive,
            'is_notified' => $isNotified,
            'user_limit' => $userLimit,
            'banners' => $banners
        ]);
    }

    /**
     * Join Quiz Action
     */
    public function joinQuiz(Request $request, $id)
    {
        $quiz = Quiz::find($id);
        $user = $this->getAuthUser($request);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Authorization token missing or invalid. Please login.'], 401);
        }

        if (!$quiz) {
            return response()->json(['success' => false, 'message' => 'Quiz not found.'], 404);
        }

        // 1. Password check for private quizzes
        if ($quiz->is_private && !empty($quiz->password)) {
            $submittedPassword = $request->input('password');
            if (empty($submittedPassword) || (string)$submittedPassword !== (string)$quiz->password) {
                return response()->json([
                    'success' => false,
                    'message' => 'Incorrect quiz password. Please try again.'
                ], 403);
            }
        }

        // 2. Scheduled / Finished check
        $isFinished = false;
        if ($quiz->complete_status === 'completed') {
            $isFinished = true;
        } elseif ($quiz->availability_end && Carbon::parse($quiz->availability_end)->isPast()) {
            $isFinished = true;
        } elseif (!$quiz->availability_end && $quiz->scheduled_at && Carbon::parse($quiz->scheduled_at)->addMinutes(15)->isPast()) {
            $isFinished = true;
        }

        if ($isFinished) {
            return response()->json([
                'success' => false,
                'message' => '⚠️ Quiz scheduled time has passed. You cannot join this quiz.'
            ], 403);
        }

        // 3. FFF Quiz routing
        if ($quiz->visibility === 'fastest_finger_first') {
            $fffService = app(\App\Services\FffQuizService::class);
            if ($user) {
                $res = $fffService->joinFffQuiz($user, (int) $id);
                if (empty($res['success'])) {
                    return response()->json([
                        'success' => false,
                        'message' => $res['message'] ?? 'Could not join quiz.'
                    ], $res['status'] ?? 403);
                }
            }
            return response()->json([
                'success' => true,
                'message' => 'Joined Fastest Finger First quiz successfully',
                'quiz_id' => (int) $id,
                'redirect' => '/fff/' . $id . '/waiting',
                'visibility' => 'fastest_finger_first'
            ]);
        }

        // Record interested user whenever join button is clicked
        \App\Models\QuizStatus::recordInterestedUser($id, $quiz->tenant_id ?? 1);

        if ($user) {
            $activeCount = Cache::remember("quiz_{$id}_active_user_count", 10, function () use ($id) {
                return QuizUser::where('quiz_id', $id)->where('status', 'active')->count();
            });
            $existing = QuizUser::where('quiz_id', $id)->where('user_id', $user->id)->first();

            if ($existing && $existing->status === 'inactive') {
                return response()->json([
                    'success' => false,
                    'message' => '⚠️ Is quiz mein player limit full ho gayi hai. Aap is quiz mein join nahi kar sakte. Aap next quiz mein apply/join kar sakte hain.'
                ], 403);
            }

            $status = 'active';
            $remark = null;

            if ($existing) {
                $status = $existing->status ?? 'active';
                $remark = $existing->remark;
                if ($existing->out_status === 'out') {
                    $existing->update(['out_status' => 'in', 'status' => 'active']);
                }
            } elseif ($quiz->no_of_players > 0 && $activeCount >= (int) $quiz->no_of_players) {
                $status = 'inactive';
                $remark = 'Player limit exceeded';
            }

            $userLimit = (int) ($user->available_quiz_limit ?? 0);
            if (!$existing && $userLimit <= 0 && $status === 'active') {
                return response()->json([
                    'success' => false,
                    'message' => 'Aapki quiz limit khatam ho gayi hai! 🎯.'
                ], 403);
            }

            $enrollment = QuizUser::firstOrCreate(
                ['quiz_id' => $id, 'user_id' => $user->id],
                [
                    'tenant_id' => $quiz->tenant_id ?? 1,
                    'status'    => $status,
                    'remark'    => $remark,
                ]
            );

            if ($status === 'active') {
                if ($enrollment->wasRecentlyCreated && isset($user->available_quiz_limit) && (int) $user->available_quiz_limit > 0) {
                    try {
                        \App\Models\User::where('id', $user->id)
                            ->where('available_quiz_limit', '>', 0)
                            ->decrement('available_quiz_limit', 1);
                    } catch (\Throwable $e) {}
                }
                \App\Models\QuizStatus::recordEnrolledUser($id, $quiz->tenant_id ?? 1);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => '⚠️ Is quiz mein player limit full ho gayi hai. Aap is quiz mein join nahi kar sakte. Aap next quiz mein apply/join kar sakte hain.'
                ], 403);
            }
        }

        $questionsCount = QuizQuestion::where('quiz_id', $id)->count();

        return response()->json([
            'success' => true,
            'message' => 'Joined quiz successfully',
            'quiz_id' => (int)$id,
            'questions_count' => $questionsCount,
            'redirect' => '/quizzes/' . $id . '/waiting',
            'visibility' => $quiz->visibility ?? 'normal'
        ]);
    }

    /**
     * Toggle Notify Me for Quiz
     */
    public function toggleNotification(Request $request, $id)
    {
        $user = $this->getAuthUser($request);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Please login to set notification.'], 401);
        }
        
        $quiz = Quiz::find($id);
        if (!$quiz) {
            return response()->json(['success' => false, 'message' => 'Quiz not found.'], 404);
        }

        $existing = QuizNotification::withTrashed()
            ->where('quiz_id', $id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing && !$existing->trashed()) {
            $existing->delete();
            return response()->json([
                'success' => true,
                'notified' => false,
                'message' => 'Notification removed for this quiz.'
            ]);
        }

        $scheduledAt = $quiz->scheduled_at ? Carbon::parse($quiz->scheduled_at) : null;
        $activTime = $scheduledAt ? $scheduledAt->copy()->subMinutes(20) : now();
        $userName = $user->name ?? $user->mobile ?? 'User';

        if ($existing && $existing->trashed()) {
            $existing->restore();
            $existing->update([
                'user_name' => $userName,
                'quiz_name' => $quiz->title ?? $quiz->name ?? null,
                'quiz_schedule_time' => $scheduledAt,
                'notificationactiv_date_time' => $activTime,
                'status' => 'pending',
                'msg' => null,
                'sent_date_time' => null,
            ]);
        } else {
            QuizNotification::updateOrCreate(
                [
                    'quiz_id' => $id,
                    'user_id' => $user->id,
                ],
                [
                    'user_name' => $userName,
                    'quiz_name' => $quiz->title ?? $quiz->name ?? null,
                    'quiz_schedule_time' => $scheduledAt,
                    'notificationactiv_date_time' => $activTime,
                    'status' => 'pending',
                    'msg' => null,
                    'sent_date_time' => null,
                ]
            );
        }

        return response()->json([
            'success' => true,
            'notified' => true,
            'message' => 'We’ll notify you when the quiz is ready to join!'
        ]);
    }

    /**
     * Record lifeline usage (50:50, Audience Poll, Skip, Switch Question)
     */
    public function useLifeline(Request $request, $id)
    {
        $user = $this->getAuthUser($request);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $request->validate(['lifeline' => 'required|string']);

        $lifeline = $request->input('lifeline');
        $questionId = $request->input('question_id');

        $quizUser = QuizUser::firstOrCreate(
            ['quiz_id' => $id, 'user_id' => $user->id],
            ['tenant_id' => $user->tenant_id ?? 1, 'status' => 'active']
        );

        if ($lifeline === 'switch_option' && (bool) $quizUser->switch_option) {
            return response()->json([
                'success' => false,
                'message' => 'Switch question lifeline has already been used in this quiz.',
                'already_used' => true,
            ], 400);
        }
        if ($lifeline === 'fifty_fifty' && (bool) $quizUser->fifty_fifty) {
            return response()->json([
                'success' => false,
                'message' => '50:50 lifeline has already been used in this quiz.',
                'already_used' => true,
            ], 400);
        }

        if ($lifeline === 'switch_option') {
            $quizUser->update([
                'switch_option' => 1,
                'question_id'   => $questionId,
            ]);
            $request->session()->put("quiz_{$id}_user_{$user->id}_lifeline_used", 'switch_option');
            $request->session()->put("quiz_{$id}_switched_question_id", $questionId);
        } elseif ($lifeline === 'fifty_fifty') {
            $quizUser->update([
                'fifty_fifty' => 1,
                'question_id' => $questionId,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Lifeline used successfully.',
            'lifeline' => $lifeline,
            'question_id' => $questionId
        ]);
    }

    /**
     * Pre-quiz waiting room status & remaining timer calculation
     * Optimized with micro-caching to absorb thundering herd polling from thousands of devices
     */
    public function getWaitingStatus(Request $request, $id)
    {
        $waitingData = \Illuminate\Support\Facades\Cache::remember("fsr_quiz_waiting_data_{$id}", 2, function () use ($id) {
            $quiz = Quiz::find($id);
            if (!$quiz) {
                return null;
            }

            $now = Carbon::now();
            $scheduledTimeMs = null;
            $pendingSeconds = 0;

            if ($quiz->scheduled_at) {
                $scheduledTime = Carbon::parse($quiz->scheduled_at);
                $scheduledTimeMs = (int) ($scheduledTime->getTimestamp() * 1000);
                if ($scheduledTime->gt($now)) {
                    $pendingSeconds = (int) $now->diffInSeconds($scheduledTime);
                }
            } elseif ($quiz->availability_start) {
                $scheduledTime = Carbon::parse($quiz->availability_start);
                $scheduledTimeMs = (int) ($scheduledTime->getTimestamp() * 1000);
                if ($scheduledTime->gt($now)) {
                    $pendingSeconds = (int) $now->diffInSeconds($scheduledTime);
                }
            }

            $banners = \App\Models\Banner::where('status', 'active')->latest()->get();

            return [
                'quiz'              => $quiz,
                'scheduled_time_ms' => $scheduledTimeMs,
                'pending_seconds'   => $pendingSeconds,
                'banners'           => $banners,
            ];
        });

        if (!$waitingData || empty($waitingData['quiz'])) {
            return response()->json(['success' => false, 'message' => 'Quiz not found.'], 404);
        }

        $quiz = $waitingData['quiz'];
        if ($quiz->complete_status !== 'completed' && \Illuminate\Support\Facades\Cache::add("quiz_proc_check_{$id}", 1, 5)) {
            $this->checkAndUpdateQuizProcessingStatus($quiz);
        }

        $serverNowMs = (int) (microtime(true) * 1000);
        $pendingSeconds = 0;
        if (!empty($waitingData['scheduled_time_ms'])) {
            $pendingSeconds = max(0, (int) round(($waitingData['scheduled_time_ms'] - $serverNowMs) / 1000));
        }

        return response()->json([
            'success'           => true,
            'quiz'              => $quiz,
            'pending_seconds'   => $pendingSeconds,
            'scheduled_time_ms' => $waitingData['scheduled_time_ms'],
            'server_now_ms'     => $serverNowMs,
            'banners'           => $waitingData['banners']
        ])->header('Cache-Control', 'private, max-age=1, stale-while-revalidate=1');
    }

    /**
     * Get quiz questions array for gameplay
     */
    public function getQuestions(Request $request, $id)
    {
        $quiz = \Illuminate\Support\Facades\Cache::remember("fsr_quiz_obj_{$id}", 60, function () use ($id) {
            return Quiz::find($id);
        });
        if (!$quiz) {
            return response()->json(['success' => false, 'message' => 'Quiz not found.'], 404);
        }

        $this->checkAndUpdateQuizProcessingStatus($quiz);

        $isFff = ($quiz->visibility === 'fastest_finger_first');

        // Cache base questions & switch questions for 5 minutes (identical for all players)
        $baseQuizData = \Illuminate\Support\Facades\Cache::remember("fsr_quiz_questions_base_{$id}", 300, function () use ($id, $quiz, $isFff) {
            if ($isFff) {
                $qList = QuizQuestion::where('quiz_id', $id)
                    ->with('question')
                    ->orderBy('order', 'asc')
                    ->orderBy('id', 'asc')
                    ->get();
                $sList = collect([]);
            } else {
                $qList = QuizQuestion::where('quiz_id', $id)
                    ->with('question')
                    ->where(function ($q) {
                        $q->whereNull('is_switch_question')->orWhere('is_switch_question', 0);
                    })
                    ->orderBy('order', 'asc')
                    ->orderBy('id', 'asc')
                    ->get();

                $sList = QuizQuestion::where('quiz_id', $id)
                    ->with('question')
                    ->where('is_switch_question', 1)
                    ->orderBy('order', 'asc')
                    ->orderBy('id', 'asc')
                    ->get();

                $limit = (int) ($quiz->question_limit ?? 0);
                if ($limit > 0 && $qList->count() > $limit) {
                    $qList = $qList->take($limit)->values();
                }
            }

            return [
                'questions'        => $qList,
                'switch_questions' => $sList,
                'total_questions'  => $qList->count(),
            ];
        });

        $questions = $baseQuizData['questions'];
        $switchQuestions = $baseQuizData['switch_questions'];

        $serverNowMs = (int) (microtime(true) * 1000);
        $scheduledTimeMs = null;
        if ($quiz->scheduled_at) {
            $scheduledTimeMs = (int) (Carbon::parse($quiz->scheduled_at)->getTimestamp() * 1000);
        } elseif ($quiz->availability_start) {
            $scheduledTimeMs = (int) (Carbon::parse($quiz->availability_start)->getTimestamp() * 1000);
        }

        $user = $this->getAuthUser($request);
        $userFiftyFifty = false;
        $userSwitchOption = false;
        if ($user) {
            $qu = QuizUser::where('quiz_id', $id)->where('user_id', $user->id)->first();
            if ($qu) {
                $userFiftyFifty = (bool) $qu->fifty_fifty;
                $userSwitchOption = (bool) $qu->switch_option;
            }
        }

        return response()->json([
            'success'           => true,
            'quiz_id'           => (int) $id,
            'quiz_title'        => $quiz->title ?? $quiz->name ?? 'Quiz',
            'quiz_type'         => $isFff ? 'fff' : 'standard',
            'visibility'        => $quiz->visibility ?? 'normal',
            'total_questions'   => $baseQuizData['total_questions'] ?? $questions->count(),
            'scheduled_time_ms' => $scheduledTimeMs,
            'server_now_ms'     => $serverNowMs,
            'questions'         => $questions,
            'switch_questions'  => $switchQuestions,
            'user_lifelines'    => [
                'fifty_fifty_used' => $userFiftyFifty,
                'switch_used'      => $userSwitchOption,
            ],
            'quiz_settings'     => [
                'fifty_fifty'   => (bool) ($quiz->fifty_fifty ?? true),
                'switch_option' => (bool) ($quiz->switch_option ?? true),
            ]
        ]);
    }

    public function start(Request $request, $id)
    {
        $question = QuizQuestion::where('quiz_id', $id)->orderBy('id')->first();
        if (!$question) {
            return response()->json(['success' => false, 'message' => 'Question not found.'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'question' => ['id' => $question->id, 'question' => $question->question_text ?? ''],
                'total_questions' => QuizQuestion::where('quiz_id', $id)->count(),
            ],
        ]);
    }

    public function getQuestionOptions(Request $request, $id)
    {
        $request->validate(['question_id' => 'required|integer']);
        $question = QuizQuestion::where('quiz_id', $id)->find($request->question_id);
        if (!$question) {
            return response()->json(['success' => false, 'message' => 'Question not found.'], 404);
        }

        return response()->json(['success' => true, 'data' => $question->options ?? []]);
    }

    public function getQuestion(Request $request, $id)
    {
        $query = QuizQuestion::where('quiz_id', $id)->orderBy('id');
        if ($request->filled('last_question_id')) {
            $lastId = (int) $request->input('last_question_id');
            $query->where('id', '>', $lastId);
        }

        $question = $query->first();
        if (!$question) {
            return response()->json(['success' => true, 'data' => []]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'question' => ['id' => $question->id, 'question' => $question->question_text ?? ''],
                'total_questions' => QuizQuestion::where('quiz_id', $id)->count(),
            ],
        ]);
    }

    public function winners(Request $request, $id)
    {
            return $this->getQuizLeaderboard($request, $id);
        }

        public function leave(Request $request, $id)
        {
            $user = $this->getAuthUser($request);
            if ($user) {
                QuizUser::where('quiz_id', $id)->where('user_id', $user->id)->update(['status' => 'left']);
            }
            return response()->json(['success' => true, 'message' => 'Left quiz']);
        }

        public function myResult(Request $request, $id)
    {
        $user = $this->getAuthUser($request);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $userAnswers = QuizQuestionUserAnswer::where('quiz_id', $id)->where('user_id', $user->id)->get();
        $totalCorrect = $userAnswers->where('status', 1)->count();
        $totalAttempted = $userAnswers->count();
        $score = $totalCorrect * 10;
        $totalQuestions = QuizQuestion::where('quiz_id', $id)->where('is_switch_question', false)->count();
        if ($totalQuestions <= 0) {
            $totalQuestions = QuizQuestion::where('quiz_id', $id)->count();
        }

        $winnerRecord = QuizWinner::where('quiz_id', $id)->where('user_id', $user->id)->first();

        return response()->json([
            'success' => true,
            'data' => [
                'score' => (string) $score,
                'correct_answers' => (string) $totalCorrect,
                'total_questions' => (string) $totalQuestions,
                'rank' => (string) ($winnerRecord?->rank ?? '-'),
                'total_time' => (string) round((float) ($winnerRecord?->total_time ?? $userAnswers->sum('total_time')), 3),
            ],
        ]);
    }

    public function getSounds(Request $request)
    {
        if ($request->has('refresh') || $request->has('force')) {
            \Illuminate\Support\Facades\Cache::forget('global_quiz_sound_urls');
        }

        $sounds = \Illuminate\Support\Facades\Cache::remember('global_quiz_sound_urls', 3600, function () {
            $setting = \App\Models\QuizSetting::first();
            return $setting ? $setting->getSoundUrls() : [];
        });

        return response()->json(['success' => true, 'data' => $sounds ?? []]);
    }

    public function status(Request $request, $id)
    {
        return $this->getWaitingStatus($request, $id);
    }

    public function clearSession(Request $request, $id)
    {
        $request->session()->forget(['quiz_id', 'quiz_questions', 'quiz_answers', 'quiz_started_at']);
        return response()->json(['success' => true, 'message' => 'Quiz session cleared.']);
    }

    public function processResult(Request $request, $id)
    {
        $quiz = Quiz::find($id);
        if ($quiz) {
            $this->markQuizProcessing($quiz);
        }
        return response()->json([
            'success' => true,
            'message' => 'Quiz result processing requested.',
            'quiz_id' => (int) $id,
            'complete_status' => $quiz ? ($quiz->fresh()->complete_status ?? 'processing') : null
        ]);
    }

    /**
     * Helper to verify if an answer is correct
     */
    protected function isAnswerCorrect($question, $userAns, $optionId = null): bool
    {
        if (!$question) return false;

        $correctAns = trim((string) ($question->correct_answer ?? $question->hindi_correct_answer ?? ''));
        $userAnsStr = trim((string) $userAns);

        if ($correctAns !== '' && $userAnsStr !== '') {
            $normCorrect = mb_strtolower(preg_replace('/\s+/u', ' ', $correctAns), 'UTF-8');
            $normUser = mb_strtolower(preg_replace('/\s+/u', ' ', $userAnsStr), 'UTF-8');
            if ($normCorrect === $normUser) {
                return true;
            }

            // Strip prefix/parenthesis like "(A) " or "A. "
            $cleanCorrect = preg_replace('/^\s*(\([A-Za-z0-9]+\)|[A-Za-z0-9]+[\.\)])\s*/u', '', $normCorrect);
            $cleanUser = preg_replace('/^\s*(\([A-Za-z0-9]+\)|[A-Za-z0-9]+[\.\)])\s*/u', '', $normUser);
            if ($cleanCorrect !== '' && $cleanUser !== '' && $cleanCorrect === $cleanUser) {
                return true;
            }
        }

        if ($question->correct_option_id !== null && $optionId !== null) {
            if ((string) $question->correct_option_id === (string) $optionId) {
                return true;
            }
        }

        $options = is_array($question->options) ? $question->options : (is_string($question->options) ? json_decode($question->options, true) : []);
        if (is_array($options) && !empty($options) && $correctAns !== '') {
            if ($optionId !== null && isset($options[$optionId])) {
                $optVal = is_array($options[$optionId]) ? ($options[$optionId]['option_text'] ?? $options[$optionId]['text'] ?? '') : (string) $options[$optionId];
                if (mb_strtolower(trim($optVal), 'UTF-8') === mb_strtolower(trim($correctAns), 'UTF-8')) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Submit answer for a single question
     */
    public function submitAnswer(Request $request, $id)
    {
        $request->validate([
            'question_id' => 'required|integer',
            'option_id'   => 'nullable',
            'answer'      => 'nullable|string',
            'time_taken'  => 'nullable|numeric'
        ]);

        $user = $this->getAuthUser($request);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $quiz = \Illuminate\Support\Facades\Cache::remember("fsr_quiz_obj_{$id}", 60, function () use ($id) {
            return Quiz::find($id);
        });
        $isFff = ($quiz && $quiz->visibility === 'fastest_finger_first');

        $quizQuestions = \Illuminate\Support\Facades\Cache::remember("fsr_quiz_q_map_{$id}", 300, function () use ($id) {
            return QuizQuestion::where('quiz_id', $id)->get()->keyBy('id');
        });

        $question = $quizQuestions->get($request->question_id)
            ?: $quizQuestions->firstWhere('question_id', $request->question_id)
            ?: QuizQuestion::where('quiz_id', $id)->where('id', $request->question_id)->first()
            ?: QuizQuestion::where('quiz_id', $id)->where('question_id', $request->question_id)->first();

        if (!$question) {
            return response()->json(['success' => false, 'message' => 'Question not found.'], 404);
        }

        $canonicalQuestionId = (int) $question->id;
        $isCorrect = $this->isAnswerCorrect($question, $request->answer, $request->option_id);
        $totalTime = (float) ($request->time_taken ?? $request->total_time ?? 0);
        $savedAnswer = (string) ($request->answer ?? $request->option_id ?? '');
        $showTime = $this->getQuestionShowTime($quiz, $question, $canonicalQuestionId);
        $correctAnswerStr = $question->correct_answer ?? $question->hindi_correct_answer ?? '';

        // FAST PATH: If Redis is available, write to Redis buffer in 0.5ms with background MySQL batch sync
        if (\App\Services\RedisQuizService::isAvailable()) {
            $recordResult = \App\Services\RedisQuizService::recordAnswer(
                (int) $id,
                (int) $user->id,
                $canonicalQuestionId,
                $savedAnswer,
                (bool) $isCorrect,
                (float) $totalTime,
                null,
                $correctAnswerStr,
                $user->tenant_id ?? 1,
                $showTime,
                true
            );
            if ($recordResult['recorded'] ?? false) {
                return response()->json([
                    'success'          => true,
                    'correct_answer'   => $correctAnswerStr,
                    'points_awarded'   => $isCorrect ? 10 : 0,
                    'submitted_answer' => $savedAnswer,
                ]);
            }
        }

        // FALLBACK PATH: Standard MySQL upsert (runs if Redis not available)
        $userAnswerRecord = QuizQuestionUserAnswer::updateOrCreate(
            [
                'tenant_id'   => $user->tenant_id ?? 1,
                'quiz_id'     => (int) $id,
                'question_id' => $canonicalQuestionId,
                'user_id'     => $user->id,
            ],
            [
                'answer'     => $savedAnswer,
                'status'     => $isCorrect ? 1 : 0,
                'show_time'  => $showTime,
                'ans_time'   => now()->format('H:i:s.v'),
                'total_time' => $totalTime,
            ]
        );

        $userActiveKey = "qu_{$id}_u_{$user->id}_act";
        if (!\Illuminate\Support\Facades\Cache::has($userActiveKey)) {
            QuizUser::where('quiz_id', $id)->where('user_id', $user->id)->update([
                'out_status' => 'in',
                'status'     => 'active',
            ]);
            \Illuminate\Support\Facades\Cache::put($userActiveKey, 1, 300);
        }

        \Illuminate\Support\Facades\Cache::forget("quiz_{$id}_user_{$user->id}_compiled_sheet_response");
        \Illuminate\Support\Facades\Cache::forget("quiz_{$id}_user_{$user->id}_anssheet");
        \Illuminate\Support\Facades\Cache::forget("quiz_{$id}_user_{$user->id}_user_answers");

        return response()->json([
            'success'          => true,
            'correct_answer'   => $correctAnswerStr,
            'points_awarded'   => $isCorrect ? 10 : 0,
            'submitted_answer' => $savedAnswer,
        ]);
    }

    /**
     * Post-game leaderboard ranking for the specific quiz
     */
    public function getQuizLeaderboard(Request $request, $id)
    {
        try {
            $quiz = \Illuminate\Support\Facades\Cache::remember("fsr_quiz_obj_{$id}", 5, function () use ($id) {
                return Quiz::find($id);
            });
            if (!$quiz) {
                return response()->json(['success' => false, 'message' => 'Quiz not found.'], 404);
            }

            if ($quiz->complete_status === 'pending') {
                $lockKey = "fsr_quiz_proc_lock_{$id}";
                if (\Illuminate\Support\Facades\Cache::add($lockKey, 1, 10)) {
                    $this->checkAndUpdateQuizProcessingStatus($quiz);
                }
            }

            try {
                // If quiz is completed and ranks are already finalized, skip recalculating completely
                $recalcFinalKey = "fsr_quiz_ranks_final_{$id}";
                if (!\Illuminate\Support\Facades\Cache::has($recalcFinalKey)) {
                    $hasUnranked = QuizWinner::where('quiz_id', $id)->whereNull('rank')->exists();
                    if ($hasUnranked || $quiz->complete_status !== 'completed') {
                        $recalcLockKey = "fsr_quiz_recalc_lock_{$id}";
                        if (\Illuminate\Support\Facades\Cache::add($recalcLockKey, 1, 30)) {
                            QuizWinner::recalculateRanks((int) $id);
                            if ($quiz->complete_status === 'completed') {
                                \Illuminate\Support\Facades\Cache::put($recalcFinalKey, 1, 3600);
                            }
                        }
                    } else {
                        \Illuminate\Support\Facades\Cache::put($recalcFinalKey, 1, 3600);
                    }
                }
            } catch (\Throwable $e) {}

            $user = $this->getAuthUser($request);
            $isLeaderboardQuiz = ($quiz->visibility === 'leaderboard');

            $page = (int) $request->input('page', 1);
            $perPage = (int) ($request->input('per_page') ?? $request->input('limit') ?? 0);

            // High Concurrency Cache: Cache global top winners list for 5 seconds to eliminate millions of redundant queries
            $cacheKey = "fsr_quiz_winners_page_{$id}_{$page}_{$perPage}";
            $cachedWinnersData = \Illuminate\Support\Facades\Cache::remember($cacheKey, 5, function () use ($id, $page, $perPage, $isLeaderboardQuiz, $quiz) {
                $histories = collect();
                if ($isLeaderboardQuiz) {
                    $histories = \App\Models\LeaderPointHistory::where('quiz_id', $quiz->id)
                        ->get()
                        ->keyBy('user_id');
                }

                $winnersQuery = QuizWinner::where('quiz_id', $id)
                    ->where('total_question', '>=', 1)
                    ->where(function ($q) {
                        $q->whereNull('rank')
                          ->orWhere('rank', '>=', 1);
                    })
                    ->with('user:id,name,profile_photo')
                    ->orderBy('rank', 'asc');

                $totalWinnersCount = (clone $winnersQuery)->count();

                if ($perPage > 0) {
                    $offset = max(0, ($page - 1) * $perPage);
                    $winnersQuery->skip($offset)->take($perPage);
                }

                $winnersList = $winnersQuery->get()
                    ->map(function ($winner) use ($isLeaderboardQuiz, $histories, $quiz) {
                        $rawReward = $winner->reward ?? 0;
                        $rewardNum = is_numeric($rawReward) ? (float) $rawReward : 0;
                        $formattedReward = ($rewardNum == (int) $rewardNum) ? (int) $rewardNum : (float) $rewardNum;
                        $userName = $winner->user?->name ?? 'Player';

                        $item = $winner->toArray();
                        $item['user_name'] = $userName;
                        $item['name'] = $userName;
                        $item['total_correct_answer'] = (int) ($winner->total_correct_answer ?? 0);
                        $item['correct'] = (int) ($winner->total_correct_answer ?? 0);
                        $item['reward'] = $formattedReward;
                        $item['points'] = $formattedReward;
                        $item['score'] = $formattedReward;

                        $item['point_history'] = null;
                        if ($isLeaderboardQuiz) {
                            $uId = (int) $winner->user_id;
                            $ph = $histories->get($uId) ?? $histories->get((string) $uId);
                            if ($ph) {
                                $item['point_history'] = [
                                    'old_point' => (float) ($ph->old_point ?? 0),
                                    'point'     => (float) ($ph->point ?? 0),
                                    'new_point' => (float) ($ph->new_point ?? 0),
                                    'type'      => $ph->type ?? 'none',
                                ];
                            } else {
                                $item['point_history'] = [
                                    'old_point' => 0.0,
                                    'point'     => (float) $formattedReward,
                                    'new_point' => (float) $formattedReward,
                                    'type'      => 'none',
                                ];
                            }
                        }

                        return $item;
                    });

                return [
                    'total_count' => $totalWinnersCount,
                    'winners'     => $winnersList,
                ];
            });

            $totalWinnersCount = $cachedWinnersData['total_count'] ?? 0;
            $winners = collect($cachedWinnersData['winners'] ?? [])->map(function ($item) use ($user) {
                $isCurrentUser = ($user && (int) ($item['user_id'] ?? 0) === (int) $user->id);
                $item['is_current_user'] = $isCurrentUser;
                $item['is_user'] = $isCurrentUser;
                return $item;
            });

            $isFffQuiz = ($quiz->visibility === 'fastest_finger_first');

            if ($isFffQuiz) {
                $totalQuestions = (int) ($quiz->question_limit ?? 0);
                if ($totalQuestions <= 0) {
                    $totalQuestions = (int) ($quiz->quiz_questions_count ?? 0);
                }
                if ($totalQuestions <= 0) {
                    $totalQuestions = \App\Models\QuizQuestion::where('quiz_id', $quiz->id)->count();
                }
                if ($totalQuestions <= 0 && $winners->isNotEmpty()) {
                    foreach ($winners as $w) {
                        $tq = (int) ($w['total_question'] ?? 0);
                        if ($tq > 0) {
                            $totalQuestions = $tq;
                            break;
                        }
                    }
                }
                if ($totalQuestions <= 0) {
                    $totalQuestions = 10;
                }

                $winners = $winners->map(function ($w) use ($totalQuestions) {
                    if (empty($w['total_question']) || (int) $w['total_question'] <= 0) {
                        $w['total_question'] = $totalQuestions;
                        $w['total_questions'] = $totalQuestions;
                    }
                    return $w;
                });
            } else {
                $totalQuestions = 10;
            }

            $currentUserRank = null;
            try {
                if ($user) {
                    // Check if user actually joined the quiz
                    $isEnrolled = \App\Models\QuizUser::where('quiz_id', $id)
                        ->where('user_id', $user->id)
                        ->where('status', 'active')
                        ->exists();

                    // Check if user actually submitted at least 1 answer
                    $hasAnswers = QuizQuestionUserAnswer::where('quiz_id', $id)
                        ->where('user_id', $user->id)
                        ->exists();

                    // Only users who enrolled AND submitted at least 1 answer qualify for currentUserRank
                    if ($isEnrolled && $hasAnswers) {
                        $userWinner = QuizWinner::where('quiz_id', $id)
                            ->where('user_id', $user->id)
                            ->where('total_question', '>=', 1)
                            ->first();

                        if (!$userWinner && $winners->isNotEmpty()) {
                            $matchedWinner = $winners->first(fn($w) => (int) ($w['user_id'] ?? 0) === (int) $user->id);
                            if ($matchedWinner && (int) ($matchedWinner['total_question'] ?? 0) >= 1) {
                                $userWinner = (object) $matchedWinner;
                            }
                        }

                        if ($userWinner) {
                            $rawReward = $userWinner->reward ?? 0;
                            $rewardNum = is_numeric($rawReward) ? (float) $rawReward : 0;
                            $formattedReward = ($rewardNum == (int) $rewardNum) ? (int) $rewardNum : (float) $rewardNum;

                            $totalCorrectAns = (int) ($userWinner->total_correct_answer ?? 0);
                            if ($totalCorrectAns === 0) {
                                $totalCorrectAns = QuizQuestionUserAnswer::where('quiz_id', $id)
                                    ->where('user_id', $user->id)
                                    ->where('status', 1)
                                    ->count();
                            }

                            $userTotalQ = (int) ($userWinner->total_question ?? 0);
                            if ($userTotalQ <= 0) {
                                $userTotalQ = $totalQuestions;
                            }

                            $currentUserRank = [
                                'rank' => (int) $userWinner->rank,
                                'user_id' => (int) $userWinner->user_id,
                                'user_name' => $user->name,
                                'name' => $user->name,
                                'total_correct_answer' => $totalCorrectAns,
                                'correct' => $totalCorrectAns,
                                'total_correct' => $totalCorrectAns,
                                'total_question' => $userTotalQ,
                                'total_questions' => $userTotalQ,
                                'total_time' => (float) ($userWinner->total_time ?? 0),
                                'reward' => $formattedReward,
                                'points' => $formattedReward,
                                'score' => $formattedReward,
                                'is_current_user' => true,
                                'is_user' => true,
                                'user' => [
                                    'id' => (int) $userWinner->user_id,
                                    'name' => $user->name,
                                ],
                                'point_history' => null,
                            ];

                            if ($isLeaderboardQuiz) {
                                $ph = \App\Models\LeaderPointHistory::where('quiz_id', $quiz->id)->where('user_id', $user->id)->first();
                                if ($ph) {
                                    $currentUserRank['point_history'] = [
                                        'old_point' => (float) ($ph->old_point ?? 0),
                                        'point'     => (float) ($ph->point ?? 0),
                                        'new_point' => (float) ($ph->new_point ?? 0),
                                        'type'      => $ph->type ?? 'none',
                                    ];
                                } else {
                                    $currentUserRank['point_history'] = [
                                        'old_point' => 0.0,
                                        'point'     => (float) $formattedReward,
                                        'new_point' => (float) $formattedReward,
                                        'type'      => 'none',
                                    ];
                                }
                            }
                        }
                    }
                }
            } catch (\Throwable $userRankErr) {
                \Illuminate\Support\Facades\Log::warning("getQuizLeaderboard currentUserRank calculation notice: " . $userRankErr->getMessage());
            }

            $banners = \App\Models\Banner::where('status', 'active')
                ->where(function ($query) {
                    $query->whereJsonContains('type', 'all')
                        ->orWhereJsonContains('type', 'quiz_leaderboard');
                })
                ->latest()
                ->take(5)
                ->get()
                ->map(function ($b) {
                    $imagePath = $b->banner_url ?: ($b->banner_file
                        ? (str_starts_with($b->banner_file, 'http') ? $b->banner_file : rtrim(env('AWS_URL', 'https://chaloonline.s3.ap-south-1.amazonaws.com'), '/') . '/' . ltrim($b->banner_file, '/'))
                        : ($b->image ? (str_starts_with($b->image, 'http') ? $b->image : rtrim(env('AWS_URL', 'https://chaloonline.s3.ap-south-1.amazonaws.com'), '/') . '/' . ltrim($b->image, '/')) : null));
                    return [
                        'id' => $b->id,
                        'banner_url' => $imagePath,
                        'image' => $imagePath,
                        'banner_file' => $imagePath,
                    ];
                });

            $responseData = [
                'success' => true,
                'quiz' => $quiz,
                'visibility' => $quiz->visibility,
                'leaderboard' => $winners,
                'current_user_rank' => $currentUserRank,
                'banners' => $banners,
                'total_participants' => $totalWinnersCount,
                'total_players' => $totalWinnersCount,
                'total_winners' => $totalWinnersCount,
                'current_page' => $page,
                'per_page' => $perPage > 0 ? $perPage : $totalWinnersCount,
                'last_page' => $perPage > 0 ? (int) ceil($totalWinnersCount / $perPage) : 1,
            ];

            if ($isFffQuiz) {
                $quiz->total_questions = $totalQuestions;
                $quiz->total_question = $totalQuestions;
                $quiz->questions_count = $totalQuestions;
                $quiz->question_limit = $totalQuestions;
                $responseData['total_questions'] = $totalQuestions;
                $responseData['total_question'] = $totalQuestions;
                $responseData['questions_count'] = $totalQuestions;
            }

            return response()->json($responseData);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("getQuizLeaderboard Exception for Quiz {$id}: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Unable to load leaderboard. Please try again.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Answer Review Sheet (Always returns all 10 questions with user answers or not attempted)
     */
    public function getAnswerSheet(Request $request, $id)
    {
        try {
            $quiz = \Illuminate\Support\Facades\Cache::remember("fsr_quiz_obj_{$id}", 5, function () use ($id) {
                return Quiz::find($id);
            });
            if (!$quiz) {
                return response()->json(['success' => false, 'message' => 'Quiz not found'], 404);
            }

            if ($quiz->complete_status === 'pending') {
                $lockKey = "fsr_quiz_proc_lock_{$id}";
                if (\Illuminate\Support\Facades\Cache::add($lockKey, 1, 10)) {
                    $this->checkAndUpdateQuizProcessingStatus($quiz);
                    $this->markQuizProcessing($quiz);
                }
            }

            $user = $this->getAuthUser($request);
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
            }

            $res = $this->quizRepository->getAnswerSheetDataForApp($user, (int) $id);
            return response()->json($res);
        } catch (\Throwable $e) {
            Log::error("getAnswerSheet Exception for Quiz {$id}: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Quiz data unavailable',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function getAnswerSheetData(Request $request, $id)
    {
        return $this->getAnswerSheet($request, $id);
    }

    /**
     * Post-Quiz Answer Reconciliation (Reconciles offline/cached client answers)
     */
    public function reconcileAnswers(Request $request, $id)
    {
        $user = $this->getAuthUser($request);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $answers = $request->input('answers', []);
        if (!is_array($answers) || empty($answers)) {
            return response()->json(['success' => true, 'reconciled' => 0, 'message' => 'No answers provided for reconciliation']);
        }

        $quiz = \Illuminate\Support\Facades\Cache::remember("fsr_quiz_obj_{$id}", 60, function () use ($id) {
            return Quiz::find($id);
        });
        $questionsMap = \Illuminate\Support\Facades\Cache::remember("fsr_quiz_q_map_{$id}", 300, function () use ($id) {
            return QuizQuestion::where('quiz_id', $id)->get()->keyBy('id');
        });

        $reconciledCount = 0;
        $bulkRows = [];
        $nowTimeStr = now()->format('H:i:s.v');
        $nowDt = now();

        foreach ($answers as $item) {
            if (!is_array($item)) continue;
            $qId = $item['question_id'] ?? $item['id'] ?? null;
            $ans = $item['answer'] ?? $item['user_answer'] ?? null;
            $timeTaken = (float) ($item['time_taken'] ?? $item['total_time'] ?? 0);

            if (!$qId || $ans === null || $ans === '') continue;

            $question = $questionsMap->get($qId) ?: $questionsMap->firstWhere('question_id', $qId);
            if (!$question) continue;

            $isCorrect = $this->isAnswerCorrect($question, $ans);
            $showTime = $this->getQuestionShowTime($quiz, $question, (int) $qId);

            $bulkRows[] = [
                'tenant_id'   => $user->tenant_id ?? 1,
                'quiz_id'     => (int) $id,
                'question_id' => (int) $qId,
                'user_id'     => (int) $user->id,
                'answer'      => (string) $ans,
                'status'      => $isCorrect ? 1 : 0,
                'show_time'   => $showTime,
                'ans_time'    => $nowTimeStr,
                'total_time'  => $timeTaken,
                'created_at'  => $nowDt,
                'updated_at'  => $nowDt,
            ];
            $reconciledCount++;
        }

        // Single Bulk Upsert (1 fast query instead of 10-20 separate queries per user)
        if (!empty($bulkRows)) {
            try {
                QuizQuestionUserAnswer::upsert(
                    $bulkRows,
                    ['tenant_id', 'quiz_id', 'user_id', 'question_id'],
                    ['answer', 'status', 'show_time', 'ans_time', 'total_time', 'updated_at']
                );
            } catch (\Throwable $ex) {
                // Fallback loop if upsert encounters constraint variation
                foreach ($bulkRows as $row) {
                    QuizQuestionUserAnswer::updateOrCreate(
                        [
                            'tenant_id'   => $row['tenant_id'],
                            'quiz_id'     => $row['quiz_id'],
                            'question_id' => $row['question_id'],
                            'user_id'     => $row['user_id'],
                        ],
                        [
                            'answer'     => $row['answer'],
                            'status'     => $row['status'],
                            'show_time'  => $row['show_time'],
                            'ans_time'   => $row['ans_time'],
                            'total_time' => $row['total_time'],
                        ]
                    );
                }
            }
        }

        // Clean up unused Redis data for this user now that all answers are permanently in MySQL
        if (\App\Services\RedisQuizService::isAvailable() && $user) {
            \App\Services\RedisQuizService::cleanupUserReconciledData((int) $id, (int) $user->id);
        }

        // Invalidate stale compiled answer sheet cache so user's newly reconciled answers appear instantly
        if ($user) {
            \Illuminate\Support\Facades\Cache::forget("quiz_{$id}_user_{$user->id}_compiled_sheet_response");
            \Illuminate\Support\Facades\Cache::forget("quiz_{$id}_user_{$user->id}_anssheet");
            \Illuminate\Support\Facades\Cache::forget("quiz_{$id}_user_{$user->id}_user_answers");
        }

        return response()->json([
            'success'          => true,
            'reconciled_count' => $reconciledCount,
            'message'          => 'Answers reconciled successfully.'
        ]);
    }

    private function getQuestionShowTime($quiz, $question, $questionId)
    {
        if (!empty($question->start_time)) {
            return $question->start_time;
        }
        if ($quiz) {
            $scheduledAt = !empty($quiz->scheduled_at) ? \Carbon\Carbon::parse($quiz->scheduled_at) : (!empty($quiz->availability_start) ? \Carbon\Carbon::parse($quiz->availability_start) : null);
            if ($scheduledAt) {
                $allQIds = \Illuminate\Support\Facades\Cache::remember("quiz_{$quiz->id}_ordered_qids", 3600, function () use ($quiz) {
                    return QuizQuestion::where('quiz_id', $quiz->id)
                        ->where(function ($q) {
                            $q->whereNull('is_switch_question')->orWhere('is_switch_question', 0);
                        })
                        ->orderBy('order', 'asc')
                        ->pluck('id')
                        ->toArray();
                });
                $idx = array_search((int) $questionId, $allQIds);
                if ($idx === false) $idx = 0;
                return $scheduledAt->copy()->addSeconds(10 + ($idx * 17))->format('H:i:s');
            }
        }
        return now()->format('H:i:s');
    }
}


