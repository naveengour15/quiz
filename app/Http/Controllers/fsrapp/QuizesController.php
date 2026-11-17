<?php

namespace App\Http\Controllers\fsrapp;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Models\Quiz;
use App\Models\QuizQuestionUserAnswer;
use App\Services\QuizTimeline;
use Carbon\Carbon;

use App\Interfaces\api\QuizRepositoryInterface;
use App\Interfaces\fsrapp\HomeRepositoryInterface;
class QuizesController extends Controller
{
    protected QuizRepositoryInterface $quizRepository;
    protected HomeRepositoryInterface $homeRepository;

    public function __construct(QuizRepositoryInterface $quizRepository, HomeRepositoryInterface $homeRepository)
    {
        $this->quizRepository = $quizRepository;
        $this->homeRepository = $homeRepository;
    }

     // Cache a Quiz model for this request to avoid repeated DB queries
    protected function cachedQuiz(int $quizId): \App\Models\Quiz
    {
        static $cache = [];
        if (!isset($cache[$quizId])) {
            $cache[$quizId] = \App\Models\Quiz::select('id','tenant_id','availability_start','availability_end','scheduled_at','complete_status','visibility','time_limit_minutes','per_que_time_limit_sec','question_limit','no_of_players')
                ->find($quizId);
        }
        return $cache[$quizId];
    }

    /**
     * 1. Join Quiz View
     */
    public function joinView(Request $request, $id)
    {
        $quiz = Quiz::find($id);
        if (!$quiz) {
            return redirect()->route('fsrapp.home')->with('warning', 'Quiz not found.');
        }

        $user = Auth::user();

        $isFinished = false;
        if ($quiz->complete_status === 'completed') {
            $isFinished = true;
        } elseif ($quiz->availability_end && Carbon::parse($quiz->availability_end)->isPast()) {
            $isFinished = true;
        } elseif (!$quiz->availability_end && $quiz->scheduled_at && Carbon::parse($quiz->scheduled_at)->addMinutes(15)->isPast()) {
            $isFinished = true;
        } elseif ($this->quizRepository->isQuizAnswerExpired($quiz, $user)) {
            $isFinished = true;
        }

        if ($isFinished) {
            return redirect()->route('fsrapp.home')->with('warning', '⚠️ Quiz scheduled time has passed. You cannot join this quiz.');
        }

        // 40-Minute Rule: Agar schedule time me 40 minute se jyada bacha hai toh koi user join nahi kar sakega
        if ($quiz->scheduled_at) {
            $secondsUntilStart = Carbon::now()->diffInSeconds(Carbon::parse($quiz->scheduled_at), false);
            if ($secondsUntilStart > 2400) {
                return redirect()->route('fsrapp.home')->with('warning', __('app.quiz_join_40min_warning'));
            }
        }

        $cookieOut = $request->cookie("fsr_quiz_out_{$id}");

        if ($quiz->visibility === 'fastest_finger_first') {
            if ($user) {
                $userEnrollment = \App\Models\QuizUser::where('quiz_id', $id)
                    ->where('user_id', $user->id)
                    ->first();
                $isCompleted = false;
                if (\App\Services\RedisQuizService::isAvailable()) {
                    $redisAnswers = \App\Services\RedisQuizService::getUserAnswers((int) $id, (int) $user->id);
                    if (!empty($redisAnswers)) {
                        $isCompleted = true;
                    }
                }
                if (!$isCompleted) {
                    $isCompleted = \App\Models\QuizQuestionUserAnswer::where('quiz_id', $id)
                        ->where('user_id', $user->id)
                        ->exists();
                }

                if ($userEnrollment && $userEnrollment->status === 'active') {
                    return redirect()->route('fsrapp.fff.waiting', $id);
                }

                if (!$isCompleted && $userEnrollment && $userEnrollment->status === 'inactive') {
                    return redirect()->route('fsrapp.home')->with('warning', '⚠️ Quiz player limit complete try next time');
                }

                if (!$isCompleted && !$userEnrollment && $quiz->no_of_players > 0) {
                    $activeCount = \App\Models\QuizUser::where('quiz_id', $id)->where('status', 'active')->count();
                    if ($activeCount >= (int) $quiz->no_of_players) {
                        \App\Models\QuizUser::firstOrCreate(
                            ['quiz_id' => $id, 'user_id' => $user->id],
                            [
                                'tenant_id'  => $quiz->tenant_id ?? 1,
                                'status'     => 'inactive',
                                'remark'     => 'Player limit exceeded',
                                'out_status' => 'out',
                            ]
                        );
                        return redirect()->route('fsrapp.home')->with('warning', '⚠️ Quiz player limit complete try next time');
                    }
                }

                /*
                if (!$isCompleted && ($cookieOut === 'out' || ($userEnrollment && $userEnrollment->out_status === 'out') || Session::get("fff_quiz_{$id}_out_status") === 'out')) {
                    $outRemark = $userEnrollment->remark ?? Session::get("fff_quiz_{$id}_out_remark") ?? 'Quiz exit';
                    return redirect()->route('fsrapp.home')->with('error', "⚠️ Aap is quiz se out ho chuke hain ({$outRemark}). Aap is quiz mein dobara participate nahi kar sakte.");
                }
                */

                if ($request->query('auto_join') === '1' || $request->boolean('auto_join')) {
                    $joinRes = $this->quizRepository->joinSubmitData($user, (int) $id);
                    if (!empty($joinRes['success'])) {
                        return redirect($joinRes['redirect'] ?? route('fsrapp.fff.waiting', $id))
                            ->with('success', $joinRes['message'] ?? 'Joined quiz successfully!');
                    } else {
                        return redirect($joinRes['redirect'] ?? route('fsrapp.home'))
                            ->with('warning', $joinRes['message'] ?? 'Could not join quiz.');
                    }
                }
            }
            return view('fsrapp.quizes.join', compact('quiz', 'user', 'id'));
        }

        if ($user) {
            $isCompleted = false;
            if (\App\Services\RedisQuizService::isAvailable()) {
                $redisAnswers = \App\Services\RedisQuizService::getUserAnswers((int) $id, (int) $user->id);
                if (!empty($redisAnswers)) {
                    $isCompleted = true;
                }
            }
            if (!$isCompleted) {
                $isCompleted = $this->quizRepository->hasUserAnsweredLastQuestion($user->id, (int) $id);
            }
            if ($isCompleted) {
                \App\Models\QuizUser::where('quiz_id', $id)
                    ->where('user_id', $user->id)
                    ->update(['out_status' => 'in', 'status' => 'active']);
                Session::put("quiz_{$id}_out_status", 'in');
                Session::forget("quiz_{$id}_out_remark");
            }

            // Fast Redis in-memory enrollment check (0ms)
            if (\App\Services\RedisQuizService::isAvailable() && \App\Services\RedisQuizService::isUserEnrolled((int) $id, (int) $user->id)) {
                $targetRoute = ($quiz->visibility === 'fastest_finger_first')
                    ? route('fsrapp.fff.waiting', $id)
                    : route('fsrapp.quizes.waiting', $id);
                return redirect($targetRoute);
            }

            $userEnrollment = \App\Models\QuizUser::where('quiz_id', $id)
                ->where('user_id', $user->id)
                ->first();

            // Agar user pehle se join ho chuka hai toh seedha quiz ke andar (waiting) bhejo
            if ($userEnrollment && $userEnrollment->status === 'active') {
                $targetRoute = ($quiz->visibility === 'fastest_finger_first')
                    ? route('fsrapp.fff.waiting', $id)
                    : route('fsrapp.quizes.waiting', $id);
                return redirect($targetRoute);
            }

            if ($userEnrollment && $userEnrollment->status === 'inactive') {
                return redirect()->route('fsrapp.home')->with('warning', '⚠️ Quiz player limit complete try next time');
            }

            if (!$userEnrollment && $quiz->no_of_players > 0) {
                $activeCount = \App\Models\QuizUser::where('quiz_id', $id)->where('status', 'active')->count();
                if ($activeCount >= (int) $quiz->no_of_players) {
                    \App\Models\QuizUser::firstOrCreate(
                        ['quiz_id' => $id, 'user_id' => $user->id],
                        [
                            'tenant_id'  => $quiz->tenant_id ?? 1,
                            'status'     => 'inactive',
                            'remark'     => 'Player limit exceeded',
                            'out_status' => 'out',
                        ]
                    );
                    return redirect()->route('fsrapp.home')->with('warning', '⚠️ Quiz player limit complete try next time');
                }
            }

            if ($request->query('auto_join') === '1' || $request->boolean('auto_join')) {
                $joinRes = $this->quizRepository->joinSubmitData($user, (int) $id);
                if (!empty($joinRes['success'])) {
                    return redirect($joinRes['redirect'] ?? route('fsrapp.quizes.waiting', $id))
                        ->with('success', $joinRes['message'] ?? 'Joined quiz successfully!');
                } else {
                    return redirect($joinRes['redirect'] ?? route('fsrapp.home'))
                        ->with('warning', $joinRes['message'] ?? 'Could not join quiz.');
                }
            }
        }

        return view('fsrapp.quizes.join', compact('quiz', 'user', 'id'));
    }

    /**
     * Common method to handle Quiz Join Submission and Redirection
     */
    public function joinSubmit(Request $request, $id)
    {
        $user = Auth::user() ?: auth('web')->user();
        if (!$user && session('fsr_user_id')) {
            $user = \App\Models\User::find(session('fsr_user_id'));
        }

        if (!$user) {
            if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please login to join the quiz.',
                    'redirect' => route('fsrapp.login')
                ], 401);
            }
            return redirect()->route('fsrapp.login')->with('warning', 'Please login to join the quiz.');
        }

        // 40-Minute Rule: Agar schedule time me 40 minute se jyada bacha hai toh koi user join nahi kar sakega
        $quiz = Quiz::find($id);
        if ($quiz && $quiz->scheduled_at) {
            $secondsUntilStart = Carbon::now()->diffInSeconds(Carbon::parse($quiz->scheduled_at), false);
            if ($secondsUntilStart > 2400) {
                if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => __('app.quiz_join_40min_warning'),
                        'redirect' => route('fsrapp.home')
                    ], 403);
                }
                return redirect()->route('fsrapp.home')->with('warning', __('app.quiz_join_40min_warning'));
            }
        }

        $res = $this->quizRepository->joinSubmitData($user, (int) $id);

        if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
            return response()->json($res, $res['status'] ?? 200);
        }

        if (!empty($res['redirect'])) {
            $redirectType = !empty($res['success']) ? 'success' : 'warning';
            return redirect($res['redirect'])->with($redirectType, $res['message'] ?? '');
        }

        if (empty($res['success'])) {
            return redirect()->back()->with('warning', $res['message'] ?? 'Failed to join quiz.');
        }

        return redirect()->back()->with('success', $res['message']);
    }

    /**
     * Toggle Notify Me for Quiz
     */
    public function toggleNotification(Request $request, $id)
    {
        $user = Auth::user();
        $res = $this->quizRepository->toggleNotificationData($user, (int) $id);

        if (!$res['success']) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $res['message']
                ], $res['status'] ?? 400);
            }
            return redirect()->back()->with('warning', $res['message']);
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'notified' => $res['notified'],
                'message' => $res['message']
            ]);
        }

        return redirect()->back()->with('success', $res['message']);
    }

    /**
     * Helper to render SPA Master View with all component datasets
     */
    private function renderSpaView($id, $stage = 'waiting', Request $request = null)
    {
        $quiz = Quiz::find($id);

        if (!$quiz) {
            return redirect()->route('fsrapp.home')->with('warning', 'Quiz not found.');
        }

        // 40-Minute Rule: Agar quiz completed nahi hai aur schedule time me 40 minute se jyada bacha hai,
        // toh koi bhi stage (waiting, question, review-counter, answer-sheet, leaderboard etc.) open nahi hogi -> seedha home redirect
        if ($quiz->complete_status !== 'completed' && $quiz->scheduled_at) {
            $secondsUntilStart = Carbon::now()->diffInSeconds(Carbon::parse($quiz->scheduled_at), false);
            if ($secondsUntilStart > 2400) { // 40 minutes = 2400 seconds
                return redirect()->route('fsrapp.home')->with('warning', __('app.quiz_join_40min_warning'));
            }
        }

        if ($quiz->visibility === 'fastest_finger_first') {
            $fffStage = in_array($stage, ['waiting', 'start-counter', 'question', 'review-counter', 'answer-sheet', 'final-counter', 'leaderboard']) ? $stage : 'waiting';
            return redirect()->route('fsrapp.fff.' . $fffStage, $id);
        }

        $user = Auth::user() ?: auth('web')->user();
        if (!$user && session('fsr_user_id')) {
            $user = \App\Models\User::find(session('fsr_user_id'));
        }
        if (!$user && request()->filled('user_id')) {
            $user = \App\Models\User::find(request()->input('user_id'));
        }

        // Agar user login nahi hai, seedha home page redirect with alert
        if (!$user) {
            return redirect()->route('fsrapp.home')->with('warning', __('app.please_join_quiz_first'));
        }

        $cookieOut = request()->cookie("fsr_quiz_out_{$id}");

        // Additional validation: Check if quiz answer submission time has expired for user
        if ($this->quizRepository->isQuizAnswerExpired($quiz, $user)) {
            $this->quizRepository->markUserOutOfQuizData($user, (int) $id, 'Schedule Time Passed');
            return redirect()->route('fsrapp.home')->with('warning', '⚠️ Quiz ka samay samapt ho gaya hai. Aap quiz se exit ho chuke hain.');
        }

        // Use Redis user answers cache as primary source before querying MySQL database
        $isCompleted = false;
        if (\App\Services\RedisQuizService::isAvailable()) {
            $redisAnswers = \App\Services\RedisQuizService::getUserAnswers((int) $id, (int) $user->id);
            if (!empty($redisAnswers)) {
                $isCompleted = true;
            }
        }
        if (!$isCompleted) {
            $isCompleted = $this->quizRepository->hasUserAnsweredLastQuestion($user->id, (int) $id);
        }

        if ($isCompleted && Session::get("quiz_{$id}_out_status") === 'out') {
            \App\Models\QuizUser::where('quiz_id', $id)
                ->where('user_id', $user->id)
                ->update(['out_status' => 'in', 'status' => 'active']);
            Session::put("quiz_{$id}_out_status", 'in');
            Session::forget("quiz_{$id}_out_remark");
        }

        $isEnrolledInRedis = \App\Services\RedisQuizService::isAvailable() && \App\Services\RedisQuizService::isUserEnrolled((int) $id, (int) $user->id);
        $userEnrollment = \App\Models\QuizUser::where('quiz_id', $id)
            ->where('user_id', $user->id)
            ->first();

        // Agar user ne quiz join nahi kiya hai (quiz_users me entry nahi hai aur redis me bhi nahi hai)
        if (!$userEnrollment && !$isEnrolledInRedis) {
            return redirect()->route('fsrapp.home')->with('warning', __('app.please_join_quiz_first'));
        }

        if (!$isCompleted && $userEnrollment && $userEnrollment->status === 'inactive') {
            return redirect()->route('fsrapp.home')->with('warning', '⚠️ Quiz player limit complete try next time');
        }

        $scheduledAt = $quiz->scheduled_at
            ? Carbon::parse($quiz->scheduled_at)
            : ($quiz->availability_start ? Carbon::parse($quiz->availability_start) : null);

        if ($scheduledAt && in_array($stage, ['waiting', 'start-counter', 'question', 'review-counter', 'answer-sheet'], true)) {
            $questionLimit = (!empty($quiz->question_limit) ? (int) $quiz->question_limit : null)
                ?: \App\Models\QuizQuestion::where('quiz_id', $id)
                ->where(function ($query) {
                    $query->whereNull('is_switch_question')->orWhere('is_switch_question', 0);
                })
                ->count();
            $questionLimit = $questionLimit ?: ((!empty($quiz->question_limit) ? (int) $quiz->question_limit : null) ?: 10);
            $questionSlots = $questionLimit + ($userEnrollment && $userEnrollment->switch_option ? 1 : 0);
            $questionStart = QuizTimeline::questionStartAt($scheduledAt);
            $questionEnd = QuizTimeline::questionEndAt($scheduledAt, $questionSlots);
            $answerSheetAt = QuizTimeline::answerSheetAt($scheduledAt, $questionSlots);
            $now = Carbon::now();

            if ($now->lt($scheduledAt)) {
                $stage = 'waiting';
            } elseif ($now->lt($questionStart)) {
                $stage = 'start-counter';
            } elseif ($now->lt($questionEnd)) {
                $stage = 'question';
            } elseif ($now->lt($answerSheetAt)) {
                $stage = 'review-counter';
            } else {
                $stage = 'answer-sheet';
            }
        }

        if ($stage === 'answer-sheet' && !$quiz->isAnswerTimeFinished($user->id)) {
            $stage = 'review-counter';
        }

        if ($stage === 'waiting') {
            if ($quiz->complete_status === 'completed') {
                return redirect()->route('fsrapp.quizes.leaderboard', $id);
            }
        }

        if (in_array($stage, ['review-counter', 'answer-sheet', 'final-counter', 'leaderboard'])) {
            if ($quiz->complete_status !== 'processing' && $quiz->complete_status !== 'completed') {
                try {
                    Cache::lock("quiz_{$id}_mark_processing_lock", 10)->get(function () use ($quiz, $id) {
                        $fresh = Quiz::find($id);
                        if ($fresh && $fresh->complete_status === 'pending') {
                            $second = 207;
                            if ($fresh->visibility === 'fastest_finger_first') {
                                $second = 80;
                            } else {
                                $questionCount = (!empty($fresh->question_limit) ? (int) $fresh->question_limit : null)
                                    ?: \App\Models\QuizQuestion::where('quiz_id', $id)
                                    ->where(function ($query) {
                                        $query->whereNull('is_switch_question')->orWhere('is_switch_question', 0);
                                    })
                                    ->count();
                                $questionCount = $questionCount ?: ((!empty($fresh->question_limit) ? (int) $fresh->question_limit : null) ?: 10);
                                $maxQuestionSlots = $questionCount + ($fresh->switch_option ? 1 : 0);
                                $second = QuizTimeline::START_COUNTER_SECONDS
                                    + ($maxQuestionSlots * QuizTimeline::QUESTION_SLOT_SECONDS)
                                    + QuizTimeline::REVIEW_COUNTER_SECONDS;
                            }
                            if ($fresh->scheduled_at && \Carbon\Carbon::parse($fresh->scheduled_at)->addSeconds($second)->isPast()) {
                                
                                $fresh->update(['complete_status' => 'processing']);
                                $scheduledAt = $fresh->availability_end ? Carbon::parse($fresh->availability_end) : now();
                                \App\Models\QuizCronJobHistory::firstOrCreate(
                                    [
                                        'quiz_id' => (int) $id,
                                        'cron_type' => 'quiz_winner_leaderboard',
                                    ],
                                    [
                                        'tenant_id' => $fresh->tenant_id ?? 1,
                                        'scheduled_at' => $scheduledAt,
                                        'status' => 'pending',
                                    ]
                                );
                            }
                        }
                    });
                } catch (\Throwable $e) {}
            }
        }

        Session::put('active_quiz_id', $id);

        if (!in_array($stage, ['answer-sheet', 'final-counter', 'leaderboard'])) {
            if (!Cache::has("quiz_{$id}_questions")) {
                $this->quizRepository->preloadQuizQuestionsToSession((int) $id);
            }
        }

        $pendingTime = 0;
        if (!empty($quiz->scheduled_at)) {
            $scheduledTime = Carbon::parse($quiz->scheduled_at);
            $now = Carbon::now();
            if ($scheduledTime->gt($now)) {
                $pendingTime = (int) $now->diffInSeconds($scheduledTime);
            }
        } elseif (!empty($quiz->availability_start)) {
            $scheduledTime = Carbon::parse($quiz->availability_start);
            $now = Carbon::now();
            if ($scheduledTime->gt($now)) {
                $pendingTime = (int) $now->diffInSeconds($scheduledTime);
            }
        } else {
            $pendingTime = 5;
        }

        $banners = Cache::remember('active_banners_list', 300, function () {
            return \App\Models\Banner::where('status', 'active')->latest()->get();
        });

        $nowMs = (int) (microtime(true) * 1000);

        // Cache Question Metadata per quiz to eliminate repeated queries (excluding switch questions)
        $qMeta = Cache::remember("quiz_{$id}_qmeta_v2", 600, function () use ($id, $quiz) {
            $count = (!empty($quiz->question_limit) ? (int) $quiz->question_limit : null)
                ?: \App\Models\QuizQuestion::where('quiz_id', $id)
                ->where(function ($q) {
                    $q->whereNull('is_switch_question')->orWhere('is_switch_question', 0);
                })->count();
            $count = $count ?: ((!empty($quiz->question_limit) ? (int) $quiz->question_limit : null) ?: 10);
            $maxEndTime = \App\Models\QuizQuestion::where('quiz_id', $id)->whereNotNull('end_time')->max('end_time');
            return [
                'count' => $count,
                'max_end_time' => $maxEndTime
            ];
        });

        $qCount = $qMeta['count'];
        $maxQuestionEndTime = $qMeta['max_end_time'];
        $switchQuestionUsed = (bool) ($userEnrollment && $userEnrollment->switch_option);
        $userQuestionCount = $qCount + ($switchQuestionUsed ? 1 : 0);
        $scheduledTimeMs = null;
        if (!empty($quiz->scheduled_at)) {
            $scheduledTimeMs = (int) (Carbon::parse($quiz->scheduled_at)->getTimestamp() * 1000);
        } elseif (!empty($quiz->availability_start)) {
            $scheduledTimeMs = (int) (Carbon::parse($quiz->availability_start)->getTimestamp() * 1000);
        }

        // Use the scheduled timestamp even after the quiz has ended so reloads preserve its timeline.
        if (!$scheduledTimeMs) {
            $scheduledTimeMs = Cache::remember("quiz_{$id}_counter_start_ms", 3600, function () use ($nowMs) {
                return $nowMs;
            });
        } else {
            Cache::put("quiz_{$id}_counter_start_ms", $scheduledTimeMs, now()->addHours(2));
        }

        $serverNowMs = $nowMs;

        $lastQuestionEndTime = null;
        $quizPlayBufferSec = 15 + ($qCount * 17) + 60;

        if ($maxQuestionEndTime) {
            $lastQuestionEndTime = Carbon::parse($maxQuestionEndTime);
        } elseif (!empty($quiz->availability_end)) {
            $lastQuestionEndTime = Carbon::parse($quiz->availability_end)->addSeconds($quizPlayBufferSec);
        } elseif (!empty($quiz->scheduled_at)) {
            $lastQuestionEndTime = Carbon::parse($quiz->scheduled_at)->addSeconds($quizPlayBufferSec);
        }

        $availabilityEndMs = $lastQuestionEndTime ? (int) ($lastQuestionEndTime->getTimestamp() * 1000) : null;
        $quizAvailabilityEndMs = !empty($quiz->availability_end) ? (int) (Carbon::parse($quiz->availability_end)->getTimestamp() * 1000) : null;

        $anssheetReleaseTimeMs = null;
        $releaseTimeMs = null;
        if ($scheduledTimeMs) {
            $releaseTimeMs = $scheduledTimeMs
                + (QuizTimeline::START_COUNTER_SECONDS * 1000)
                + ($userQuestionCount * QuizTimeline::QUESTION_SLOT_SECONDS * 1000)
                + (QuizTimeline::REVIEW_COUNTER_SECONDS * 1000);
        }

        if ($releaseTimeMs && $releaseTimeMs > $serverNowMs) {
            $anssheetReleaseTimeMs = $releaseTimeMs;
        }

        // Guard against premature stage rendering on server side
        if ($scheduledTimeMs) {
            if ($serverNowMs < $scheduledTimeMs) {
                if (in_array($stage, ['start-counter', 'question'])) {
                    $stage = 'waiting';
                }
            } elseif ($serverNowMs < ($scheduledTimeMs + 10000)) {
                if ($stage === 'question') {
                    $stage = 'start-counter';
                }
            }
        }

        $initialStage = $stage;

        $tenantId = ($user ? $user->tenant_id : null) ?? (session('tenant_id') ?? 1);

        $initialSheetData = null;
        if ($stage === 'answer-sheet') {
            try {
                $initialSheetData = $this->quizRepository->getAnswerSheetDataForApp($user, (int) $id);
            } catch (\Throwable $e) {
                $initialSheetData = null;
            }
        }

        $initialUserAnswers = [];
        if ($user) {
            $dbUserAnswers = QuizQuestionUserAnswer::where('quiz_id', $id)
                ->where('user_id', $user->id)
                ->whereNotNull('answer')
                ->where('answer', '!=', '')
                ->get();
            foreach ($dbUserAnswers as $ans) {
                $initialUserAnswers[$ans->question_id] = [
                    'question_id' => $ans->question_id,
                    'answer'      => $ans->answer,
                    'total_time'  => (float) $ans->total_time,
                ];
            }

            $redisAnswers = \App\Services\RedisQuizService::getUserAnswers((int) $id, (int) $user->id) ?? [];
            foreach ($redisAnswers as $questionId => $answer) {
                if (!isset($initialUserAnswers[$questionId]) && !empty($answer['answer'])) {
                    $initialUserAnswers[$questionId] = [
                        'question_id' => $questionId,
                        'answer' => $answer['answer'],
                        'total_time' => (float) ($answer['total_time'] ?? 0),
                    ];
                }
            }
        }

        // Preload questions for instant client-side embedding (eliminates synchronized fetch spike)
        $questionsData = $this->quizRepository->getQuestionsFromSessionData((int) $id);
        $initialQuestions = $questionsData['questions'] ?? [];
        $initialSwitchQuestions = $questionsData['switch_questions'] ?? [];

        if ($quiz) {
            $winners = [];
            if ($quiz->complete_status === 'completed') {
                $winners = Cache::remember("quiz_{$quiz->id}_top_winners", 300, function () use ($quiz) {
                    return \App\Models\QuizWinner::with('user:id,name,profile_photo')
                        ->where('quiz_id', $quiz->id)
                        ->orderBy('rank', 'asc')
                        ->take(5)
                        ->get()
                        ->map(function ($w) {
                            return [
                                'rank' => $w->rank,
                                'user_name' => $w->user->name ?? 'User',
                                'profile_photo' => $w->user->profile_photo ?? null,
                                'total_correct_answer' => $w->total_correct_answer,
                                'total_question' => $w->total_question,
                                'total_time' => $w->total_time,
                                'reward' => $w->reward,
                                'reward_type' => $w->reward_type,
                            ];
                        })->toArray();
                });
            }

            $lastQuizData = [
                'id' => $quiz->id,
                'title' => $quiz->title,
                'completed_at' => $quiz->updated_at ? $quiz->updated_at->toDateTimeString() : null,
                'completed_at_ms' => $quiz->updated_at ? $quiz->updated_at->timestamp * 1000 : null,
                'complete_status' => $quiz->complete_status === 'completed' ? 'completed' : 'processing',
                'winners' => $winners
            ];
        } else {
            $lastQuizData = $this->homeRepository->getLastQuizData((int) $tenantId);
        }

        $qIndex = (int) ($request ? $request->input('qIndex', $request->input('q_index', 0)) : request()->input('qIndex', request()->input('q_index', 0)));

        $fiftyFiftyData = Session::get("quiz_{$id}_5050_data");
        if (!$fiftyFiftyData && $userEnrollment && $userEnrollment->fifty_fifty && $userEnrollment->question_id) {
            $fiftyFiftyData = $this->quizRepository->getFiftyFiftyOptionsForQuestion((int)$id, $userEnrollment->question_id);
        }

        $userLifeline = [
            'fifty_fifty'       => $userEnrollment ? (bool) $userEnrollment->fifty_fifty : false,
            'switch_option'     => $userEnrollment ? (bool) $userEnrollment->switch_option : false,
            'question_id'       => $userEnrollment ? $userEnrollment->question_id : null,
            'fifty_fifty_data'  => $fiftyFiftyData,
        ];

        return view('fsrapp.quizes.play', compact(
            'quiz',
            'id',
            'pendingTime',
            'banners',
            'serverNowMs',
            'scheduledTimeMs',
            'qIndex',
            'initialStage',
            'availabilityEndMs',
            'quizAvailabilityEndMs',
            'anssheetReleaseTimeMs',
            'lastQuizData',
            'initialSheetData',
            'qCount',
            'initialQuestions',
            'initialSwitchQuestions',
            'initialUserAnswers',
            'userLifeline'
        ));
    }

    /**
     * 2. Waiting Counter Page View
     */
    public function waitingView(Request $request, $id)
    {
        return $this->renderSpaView($id, 'waiting', $request);
    }

    /**
     * 3. Start 10-Second Counter View
     */
    public function startCounterView(Request $request, $id)
    {
        return $this->renderSpaView($id, 'start-counter', $request);
    }

    /**
     * 4. Single Question Page View
     */
    public function questionView(Request $request, $id)
    {
        return $this->renderSpaView($id, 'question', $request);
    }

    /**
     * 7. 10-Second Review Counter View
     */
    public function reviewCounterView(Request $request, $id)
    {
        return $this->renderSpaView($id, 'review-counter', $request);
    }

    /**
     * 8. Answer Sheet Page View
     */
    public function answerSheetView(Request $request, $id)
    {
        return $this->renderSpaView($id, 'answer-sheet', $request);
    }

    /**
     * 9. Final 30-Second Counter View
     */
    public function finalCounterView(Request $request, $id)
    {
        return $this->renderSpaView($id, 'final-counter', $request);
    }

    /**
     * 10. Leaderboard Page View
     */
    public function leaderboardView(Request $request, $id)
    {
        return $this->renderSpaView($id, 'leaderboard', $request);
    }

    /**
     * Sound API Endpoint
     */
    public function getSounds()
    {
        $res = $this->quizRepository->getSoundsData();
        return response()->json($res);
    }

    /**
     * Questions & Options API Endpoint
     */
    public function getQuestionsFromSession($id)
    {
        $quiz = Cache::remember("quiz_entity_{$id}", 60, function () use ($id) {
            return Quiz::find($id);
        });
        if ($quiz) {
            $user = Auth::user() ?: auth('web')->user();
            if (!$user && session('fsr_user_id')) {
                $user = \App\Models\User::find(session('fsr_user_id'));
            }
            if ($this->quizRepository->isQuizAnswerExpired($quiz, $user)) {
                if ($user) {
                    $this->quizRepository->markUserOutOfQuizData($user, (int) $id, 'Schedule Time Passed');
                }
                return response()->json([
                    'success' => false,
                    'status' => 403,
                    'out' => true,
                    'quiz_expired' => true,
                    'message' => '⚠️ Quiz answer submission time has expired.',
                ], 403);
            }
        }
        Cache::forget("quiz_{$id}_questions_api_response");
        $res = $this->quizRepository->getQuestionsFromSessionData((int) $id);
        $status = !empty($res['quiz_expired']) ? 403 : ($res['status'] ?? 200);
        return response()->json($res, $status);
    }

    /**
     * Submit single answer
     */
    public function submitAnswer(Request $request, $id)
    {
        $quiz = Cache::remember("quiz_model_{$id}", 120, function () use ($id) {
            return Quiz::find($id);
        });
        if (!$quiz || !($quiz instanceof Quiz)) {
            Cache::forget("quiz_model_{$id}");
            $quiz = Quiz::find($id);
        }
        if (!$quiz) {
            return response()->json([
                'success' => false,
                'message' => 'Quiz not found',
            ], 404);
        }

        $scheduledAt = !empty($quiz->scheduled_at) ? Carbon::parse($quiz->scheduled_at) : (!empty($quiz->availability_start) ? Carbon::parse($quiz->availability_start) : null);
        if ($scheduledAt) {
            $nowMs = (int) (microtime(true) * 1000);
            $scheduledTimeMs = (int) ($scheduledAt->getTimestamp() * 1000);
            $minSubmitTimeMs = $scheduledTimeMs + 15000; // schedule_time + 10s counter + 5s question appear = 15s
            if ($nowMs < $minSubmitTimeMs) {
                return response()->json([
                    'success' => false,
                    'status'  => 422,
                    'message' => '⚠️ Quiz start nahi hua hai. First question ka answer schedule time + 15 seconds ke baad hi submit ho sakta hai.',
                ], 422);
            }
        }

        $user = Auth::user() ?: auth('web')->user();
        if (!$user && session('fsr_user_id')) {
            $user = \App\Models\User::find(session('fsr_user_id'));
        }
        if (!$user && $request->filled('user_id')) {
            $user = \App\Models\User::find($request->input('user_id'));
        }

        if ($this->quizRepository->isQuizAnswerExpired($quiz, $user)) {
            if ($user) {
                $this->quizRepository->markUserOutOfQuizData($user, (int) $id, 'Schedule Time Passed');
            }
            return response()->json([
                'success' => false,
                'quiz_expired' => true,
                'out_status' => 'out',
                'remark' => 'Schedule Time Passed',
                'status' => 403,
                'message' => '⚠️ Quiz answer submission time has expired.',
            ], 403);
        }

        $res = $this->quizRepository->submitQuizAnswer($user, (int) $id, $request->all());
 
        if ($user) {
            $userActiveCacheKey = "quiz_{$id}_user_{$user->id}_active";
            if (Session::get("quiz_{$id}_out_status") !== 'in' && !Cache::has($userActiveCacheKey)) {
                \App\Models\QuizUser::where('quiz_id', $id)
                    ->where('user_id', $user->id)
                    ->update([
                        'out_status' => 'in',
                        'status'     => 'active',
                    ]);
                Session::put("quiz_{$id}_out_status", 'in');
                Cache::put($userActiveCacheKey, true, 3600);
            }
            Session::forget("quiz_{$id}_out_remark");
        }

        if (!$res['success']) {
            return response()->json([
                'success' => false,
                'out_status' => $res['out_status'] ?? null,
                'remark' => $res['remark'] ?? null,
                'quiz_expired' => !empty($res['quiz_expired']),
                'message' => $res['message'],
            ], $res['status'] ?? 400);
        }

        return response()->json($res);
    }

    /**
     * Record Lifeline use
     */
    public function recordLifelineUse(Request $request, $id)
    {
        $user = Auth::user();
        $res = $this->quizRepository->recordLifelineUseData(
            $user,
            (int) $id,
            (string) $request->input('lifeline'),
            $request->input('question_id')
        );

        if (!$res['success']) {
            return response()->json(['success' => false, 'message' => $res['message']], $res['status'] ?? 400);
        }

        return response()->json($res);
    }

    /**
     * Reconcile/bulk submit answers from client session/local storage
     */
    public function reconcileAnswers(Request $request, $id)
    {
        $user = Auth::user() ?: auth('web')->user();
        if (!$user && session('fsr_user_id')) {
            $user = \App\Models\User::find(session('fsr_user_id'));
        }
        if (!$user && $request->filled('user_id')) {
            $user = \App\Models\User::find($request->input('user_id'));
        }

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        $quiz = Cache::remember("quiz_model_{$id}", 120, function () use ($id) {
            return Quiz::find($id);
        });
        if ($quiz && !($quiz instanceof Quiz)) {
            Cache::forget("quiz_model_{$id}");
            $quiz = Quiz::find($id);
        }
        if ($quiz) {
            $scheduledAt = !empty($quiz->scheduled_at) ? Carbon::parse($quiz->scheduled_at) : (!empty($quiz->availability_start) ? Carbon::parse($quiz->availability_start) : null);
            if ($scheduledAt) {
                $nowMs = (int) (microtime(true) * 1000);
                $scheduledTimeMs = (int) ($scheduledAt->getTimestamp() * 1000);
                $minSubmitTimeMs = $scheduledTimeMs + 15000;
                if ($nowMs < $minSubmitTimeMs) {
                    return response()->json([
                        'success' => false,
                        'status'  => 422,
                        'message' => '⚠️ Quiz start nahi hua hai. Answer schedule time + 15 seconds ke baad hi submit ho sakta hai.',
                    ], 422);
                }
            }
        }

        if ($quiz && $this->quizRepository->isQuizAnswerExpired($quiz, $user)) {
            return response()->json([
                'success' => false,
                'status' => 403,
                'out' => true,
                'quiz_expired' => true,
                'message' => '⚠️ Quiz answer submission time has expired.',
            ], 403);
        }

        $clientAnswers = $request->input('answers', $request->input('sheet', []));
        if (is_string($clientAnswers)) {
            $clientAnswers = json_decode($clientAnswers, true) ?: [];
        }

        $res = $this->quizRepository->reconcileQuizAnswers($user, (int) $id, (array) $clientAnswers);

        // Designate exactly one client to trigger /api/process-result when not already processed
        if (is_array($res)) {
            $designatedKey = "quiz_{$id}_process_result_designated";
            $alreadyTriggered = Cache::has("quiz_{$id}_process_result_triggered");
            $res['trigger_process_result'] = !$alreadyTriggered && Cache::add($designatedKey, true, 180);
        }

        return response()->json($res);
    }

    /**
     * Answer Sheet Data Endpoint
     */ 
    public function getAnswerSheetData(Request $request, $id)
    {
        $user = Auth::user() ?: auth('web')->user();
        if (!$user && session('fsr_user_id')) {
            $user = \App\Models\User::find(session('fsr_user_id'));
        }

        $quiz = Quiz::find($id);
        if (!$quiz) {
            return response()->json(['success' => false, 'message' => 'Quiz not found'], 404);
        }

        // Jab tak answer submission time complete nahi hua ho, API se bhi answer data na bhejein
        if (!$quiz->isAnswerTimeFinished($user?->id)) {
            return response()->json([
                'success' => false,
                'status' => 403,
                'message' => 'Answer sheet is not available yet. Please wait until quiz answer submission time completes.',
                'redirect' => route('fsrapp.home')
            ], 403);
        }

        // If client passed sheet in request, reconcile first
        $clientAnswers = $request->input('answers', $request->input('sheet', []));
        if (is_string($clientAnswers)) {
            $clientAnswers = json_decode($clientAnswers, true) ?: [];
        }
        if (!empty($clientAnswers) && $user) {
            try { 
                $this->quizRepository->reconcileQuizAnswers($user, (int) $id, (array) $clientAnswers);
            } catch (\Throwable $e) {}
        }

        $hasAnswers = $user ? QuizQuestionUserAnswer::where('quiz_id', $id)->where('user_id', $user->id)->exists() : false;
        if (!$hasAnswers && $this->quizRepository->isQuizAnswerExpired($quiz, $user)) {
            if ($user) {
                $this->quizRepository->markUserOutOfQuizData($user, (int) $id, 'Schedule Time Passed');
            }
            return response()->json([
                'success' => false,
                'status' => 403,
                'out' => true,
                'quiz_expired' => true,
                'message' => '⚠️ Quiz answer submission time has expired. You did not submit answers.',
            ], 403);
        }

        $res = $this->quizRepository->getAnswerSheetDataForApp($user, (int) $id);

        if ($user && !empty($res['success']) && Session::get("quiz_{$id}_out_status") === 'out') {
            try {
                \App\Models\QuizUser::where('quiz_id', $id)
                    ->where('user_id', $user->id)
                    ->where('out_status', 'out')
                    ->update(['out_status' => 'in', 'status' => 'active']);
                Session::put("quiz_{$id}_out_status", 'in');
                Session::forget("quiz_{$id}_out_remark");
            } catch (\Throwable $e) {}
        }

        return response()->json($res);
    }

    /**
     * Trigger Quiz Result Async Endpoint
     */
    public function processQuizResultAsync($id)
    {
        $id = (int) $id;
        if (!$id) {
            return response()->json(['success' => false, 'message' => 'Invalid quiz ID'], 400);
        }

        $triggeredKey = "quiz_{$id}_process_result_triggered";

        // Fast-path: If already triggered, exit immediately (0ms) without hitting DB or locks
        if (Cache::has($triggeredKey)) {
            return response()->json([
                'success' => true,
                'already_triggered' => true,
                'message' => 'Quiz result already being processed.',
            ]);
        }

        $lockKey = "quiz_{$id}_process_result_api_lock";
        $lock = Cache::lock($lockKey, 30);
        if (!$lock->get()) {
            return response()->json([
                'success' => true,
                'already_triggered' => true,
                'message' => 'Quiz result is currently being initiated.',
            ]);
        }

        try {
            Cache::put($triggeredKey, true, 3600);

            $quiz = Quiz::find($id);
            if ($quiz) {
                if ($quiz->complete_status === 'pending') {
                    $second = 237; // 10s start + 10 questions * 17s + 10s review + 47s counter = 237s
                    if($quiz->visibility === 'fastest_finger_first'){
                        $second = 110; // 10s start + 60s questions + 10s review + 30s counter = 110s
                    }
                    if ($quiz->scheduled_at && \Carbon\Carbon::parse($quiz->scheduled_at)->addSeconds($second)->isPast()) {                        
                        $quiz->update(['complete_status' => 'processing']);
                    }
                }

                $scheduledAt = $quiz->availability_end ? Carbon::parse($quiz->availability_end) : now();
                \App\Models\QuizCronJobHistory::firstOrCreate(
                    [
                        'quiz_id' => $id,
                        'cron_type' => 'quiz_winner_leaderboard',
                    ],
                    [
                        'tenant_id' => $quiz->tenant_id ?? 1,
                        'scheduled_at' => $scheduledAt,
                        'status' => 'pending',
                    ]
                );
            }

            $this->quizRepository->quizResultAsync($id);

            return response()->json([
                'success' => true,
                'message' => 'Quiz complete_status set to processing and result processing triggered.'
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("processQuizResultAsync error for quiz {$id}: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        } finally {
            $lock->release();
        }
    }

    /**
     * Leaderboard Data Endpoint
     */
    public function getLeaderboardData($id)
    {
        $user = Auth::user() ?: auth('web')->user();
        if (!$user && session('fsr_user_id')) {
            $user = \App\Models\User::find(session('fsr_user_id'));
        }
        $res = $this->quizRepository->getLeaderboardDataForApp($user, (int) $id);
        return response()->json($res);
    }

    /**
     * Quiz Status API Endpoint
     */
    public function getStatusFromSession($id)
    {
        $res = $this->quizRepository->getStatusFromSessionData((int) $id);
        return response()->json($res);
    }

    /**
     * Clear Quiz Cache and Session Data Endpoint
     */
    public function clearSessionApi($id)
    {
        $quiz = Quiz::find($id);
        if (!$quiz || $quiz->complete_status !== 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'Quiz session can only be cleared after quiz is completed.'
            ], 400);
        }

        $this->quizRepository->clearQuizCacheAndSessionData((int) $id);
        return response()->json([
            'success' => true,
            'message' => 'Quiz cache and session data cleared successfully.'
        ]);
    }

    /**
     * Mark user out of quiz (screen lock, background, offline, tab switch, navigation away, etc.)
     */
    public function markUserOut(Request $request, $id)
    {
        $user = Auth::user() ?: auth('web')->user();
        if (!$user) {
            $userId = session('fsr_user_id') ?: $request->input('user_id') ?: $request->json('user_id');
            if ($userId) {
                $user = \App\Models\User::find($userId);
            }
        }

        $reason = $request->input('reason') ?: $request->input('remark') ?: $request->json('reason') ?: 'Screen Minimize';
        $res = $this->quizRepository->markUserOutOfQuizData($user, (int) $id, (string) $reason);

        return response()->json($res, $res['status'] ?? 200);
    }

    /**
     * Static helper for clearing session/cache
     */
    public static function clearQuizCacheAndSession($id)
    {
        app(QuizRepositoryInterface::class)->clearQuizCacheAndSessionData((int) $id);
    }
}
