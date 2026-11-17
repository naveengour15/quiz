<?php

namespace App\Http\Controllers\fsrapp;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionUserAnswer;
use App\Models\QuizUser;
use App\Models\QuizWinner;
use App\Services\FffQuizService;
use App\Services\RedisQuizService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;

class FffQuizController extends Controller
{
    public function __construct(protected FffQuizService $fffService)
    {
    }

    /**
     * Render Master SPA View for Fastest Finger First
     */
    private function renderFffSpaView($id, $stage = 'waiting', Request $request = null)
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

        $hasAnyAnswer = false;
        if (RedisQuizService::isAvailable()) {
            $cachedAnswers = RedisQuizService::getUserAnswers((int) $id, (int) $user->id);
            if ($cachedAnswers !== null && !empty($cachedAnswers)) {
                $hasAnyAnswer = true;
            }
        }
        if (!$hasAnyAnswer) {
            $hasAnyAnswer = QuizQuestionUserAnswer::where('quiz_id', $id)
                ->where('user_id', $user->id)
                ->exists();
        }

        if ($hasAnyAnswer) {
            QuizUser::where('quiz_id', $id)
                ->where('user_id', $user->id)
                ->update(['out_status' => 'in', 'status' => 'active']);
            Session::put("fff_quiz_{$id}_out_status", 'in');
            Session::forget("fff_quiz_{$id}_out_remark");
        }

        $isEnrolledInRedis = RedisQuizService::isAvailable() && RedisQuizService::isUserEnrolled((int) $id, (int) $user->id);
        $userEnrollment = QuizUser::where('quiz_id', $id)
            ->where('user_id', $user->id)
            ->first();

        // Agar user ne quiz join nahi kiya hai (quiz_users me entry nahi hai aur redis me bhi nahi hai)
        if (!$userEnrollment && !$isEnrolledInRedis) {
            return redirect()->route('fsrapp.home')->with('warning', __('app.please_join_quiz_first'));
        }

        if (!$hasAnyAnswer && $userEnrollment && $userEnrollment->status === 'inactive') {
            return redirect()->route('fsrapp.home')->with('warning', '⚠️ Quiz player limit complete try next time');
        }

        if ($this->fffService->isFffQuizExpired($quiz, $user)) {
            if ($user) {
                $this->fffService->markUserOut($user, (int) $id, 'Schedule Time Passed');
            }
            return redirect()->route('fsrapp.home')->with('warning', '⚠️ Quiz ka samay samapt ho gaya hai. Aap quiz se exit ho chuke hain.');
        }

        // Jab quiz time pura ho gaya ho ya user all answers submit kar chuka ho, start-counter/question/review-counter par nahi jana chahiye -> direct home
        if (in_array($stage, ['start-counter', 'question', 'review-counter'])) {
            $schedTime = !empty($quiz->scheduled_at) ? Carbon::parse($quiz->scheduled_at) : (!empty($quiz->availability_start) ? Carbon::parse($quiz->availability_start) : null);
            $isPastQuestionTime = $schedTime && Carbon::now()->gt($schedTime->copy()->addSeconds(85));

            $totalQCount = Cache::remember("fff_quiz_{$id}_qcount", 600, function () use ($id) {
                return QuizQuestion::where('quiz_id', $id)->count();
            });
            $userAnsCount = 0;
            if ($user) {
                if (RedisQuizService::isAvailable()) {
                    $cAns = RedisQuizService::getUserAnswers((int) $id, (int) $user->id);
                    $userAnsCount = $cAns !== null ? count($cAns) : QuizQuestionUserAnswer::where('quiz_id', $id)->where('user_id', $user->id)->count();
                } else {
                    $userAnsCount = QuizQuestionUserAnswer::where('quiz_id', $id)->where('user_id', $user->id)->count();
                }
            }
            $isAllAns = ($totalQCount > 0 && $userAnsCount >= $totalQCount);

            if ($isAllAns || $isPastQuestionTime || in_array($quiz->complete_status, ['completed', 'processing'])) {
                return redirect()->route('fsrapp.home');
            }
        }

        // Jab tak quiz answer submit time complete nahi ho jata, answer-sheet direct access nahi honi chahiye -> direct home
        if ($stage === 'answer-sheet') {
            if (!$quiz->isAnswerTimeFinished()) {
                return redirect()->route('fsrapp.home');
            }
        }

        if ($stage === 'waiting') {
            if ($quiz->complete_status === 'completed') {
                return redirect()->route('fsrapp.fff.leaderboard', $id);
            }
        }

        if (in_array($stage, ['final-counter', 'leaderboard', 'answer-sheet'])) {
            if ($quiz->complete_status !== 'processing' && $quiz->complete_status !== 'completed') {
                try {
                    Cache::lock("quiz_{$id}_mark_processing_lock", 10)->get(function () use ($quiz, $id) {
                        $fresh = Quiz::find($id);
                        if ($fresh->complete_status === 'pending') {

                            $second = 207; // 11 question + 20 second conter 
                            if($fresh->visibility === 'fastest_finger_first'){
                                $second = 95;
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

        Session::put('active_fff_quiz_id', $id);

        if (!in_array($stage, ['answer-sheet', 'final-counter', 'leaderboard'])) {
            if (!Cache::has("fff_quiz_{$id}_questions")) {
                $this->fffService->preloadFffQuestionsToSession((int) $id);
            }
        }

        $pendingTime = 0;
        $scheduledTimeMs = null;
        if (!empty($quiz->scheduled_at)) {
            $scheduledTime = Carbon::parse($quiz->scheduled_at);
            $now = Carbon::now();
            if ($scheduledTime->gt($now)) {
                $pendingTime = (int) $now->diffInSeconds($scheduledTime);
            }
            $scheduledTimeMs = (int) ($scheduledTime->getTimestamp() * 1000);
        } elseif (!empty($quiz->availability_start)) {
            $scheduledTime = Carbon::parse($quiz->availability_start);
            $now = Carbon::now();
            if ($scheduledTime->gt($now)) {
                $pendingTime = (int) $now->diffInSeconds($scheduledTime);
            }
            $scheduledTimeMs = (int) ($scheduledTime->getTimestamp() * 1000);
        } else {
            $pendingTime = 5;
        }

        $banners = Cache::remember('active_banners_list', 300, function () {
            return Banner::where('status', 'active')->latest()->get();
        });

        $serverNowMs = (int) (microtime(true) * 1000);
        $totalFffDurationMs = (10 + 75 + 10) * 1000;

        // If no schedule time, or if scheduled time was more than total duration ago, fallback to cached start time
        if (!$scheduledTimeMs || ($serverNowMs - $scheduledTimeMs) > ($totalFffDurationMs + 120000)) {
            $scheduledTimeMs = Cache::remember("fff_quiz_{$id}_counter_start_ms", 3600, function () use ($serverNowMs) {
                return $serverNowMs;
            });
        } else {
            Cache::put("fff_quiz_{$id}_counter_start_ms", $scheduledTimeMs, now()->addHours(2));
        }

        Session::put("fff_quiz_{$id}_counter_start_ms", $scheduledTimeMs);

        // Answer sheet release calculation: 10s start-counter + 75s questions + 10s review + 30s counter = 125s
        $anssheetReleaseTimeMs = null;
        $scheduledAt = !empty($quiz->scheduled_at) ? Carbon::parse($quiz->scheduled_at) : (!empty($quiz->availability_start) ? Carbon::parse($quiz->availability_start) : null);

        if ($quiz->complete_status === 'completed') {
            $anssheetReleaseTimeMs = null;
        } elseif ($scheduledAt) {
            $releaseTime = $scheduledAt->copy()->addSeconds(125);
            if ($releaseTime->isFuture()) {
                $anssheetReleaseTimeMs = (int) ($releaseTime->getTimestamp() * 1000);
            }
        } elseif ($scheduledTimeMs) {
            $releaseTimeMs = $scheduledTimeMs + 125000;
            if ($releaseTimeMs > $serverNowMs) {
                $anssheetReleaseTimeMs = $releaseTimeMs;
            }
        }

        $totalQuestionsCount = Cache::remember("fff_quiz_{$id}_qcount", 600, function () use ($id) {
            return QuizQuestion::where('quiz_id', $id)->count();
        });

        // Check if current user has answered all questions (only necessary after game starts or if quiz completed)
        $userAnswersCount = 0;
        if ($user && ($quiz->complete_status === 'completed' || !in_array($stage, ['waiting', 'start-counter']))) {
            if (RedisQuizService::isAvailable()) {
                $cachedAns = RedisQuizService::getUserAnswers((int) $id, (int) $user->id);
                if ($cachedAns !== null) {
                    $userAnswersCount = count($cachedAns);
                } else {
                    $userAnswersCount = QuizQuestionUserAnswer::where('quiz_id', $id)->where('user_id', $user->id)->count();
                }
            } else {
                $userAnswersCount = QuizQuestionUserAnswer::where('quiz_id', $id)->where('user_id', $user->id)->count();
            }
        }

        $isAllAnswered = ($totalQuestionsCount > 0 && $userAnswersCount >= $totalQuestionsCount);
        $userFinishTimeMs = null;

        if ($isAllAnswered && $user) {
            $lastAns = QuizQuestionUserAnswer::where('quiz_id', $id)->where('user_id', $user->id)->latest('created_at')->first();
            if ($lastAns && $lastAns->created_at) {
                $userFinishTimeMs = (int) ($lastAns->created_at->getTimestamp() * 1000);
            }
        }

        $initialSheetData = null;
        if ($quiz->complete_status === 'completed' || $quiz->complete_status === 'processing' || in_array($stage, ['answer-sheet', 'final-counter', 'leaderboard'])) {
            try {
                $initialSheetData = $this->fffService->getFffAnswerSheetData($user, (int) $id);
            } catch (\Throwable $e) {
                $initialSheetData = null;
            }
        }

        $winners = [];
        if ($quiz->complete_status === 'completed') {
            $winnersCollection = QuizWinner::with('user:id,name,profile_photo')
                ->where('quiz_id', $quiz->id)
                ->orderBy('rank', 'asc')
                ->take(10)
                ->get();

            $winners = $winnersCollection->map(function ($w) {
                return [
                    'rank'                 => $w->rank,
                    'user_name'            => $w->user->name ?? 'User',
                    'profile_photo'        => $w->user->profile_photo ?? null,
                    'total_correct_answer' => $w->total_correct_answer,
                    'total_question'       => $w->total_question,
                    'total_time'           => round((float) $w->total_time, 2),
                    'reward'               => $w->reward,
                    'reward_type'          => $w->reward_type,
                ];
            })->toArray();
        }

        $lastQuizData = [];
        if ($quiz) {
            $lastQuizData = [
                'id'              => $quiz->id,
                'title'           => $quiz->title,
                'complete_status' => $quiz->complete_status ?? 'processing',
                'completed_at'    => $quiz->completed_at,
                'winners'         => $winners,
            ];
        }

        $rawQuestions = Session::get("fff_quiz_{$id}_questions") ?: $this->fffService->preloadFffQuestionsToSession((int) $id);
        $initialFffQuestions = array_map(function ($q) {
            $copy = $q;
            unset($copy['correct_answer']);
            unset($copy['hindi_correct_answer']);
            unset($copy['explanation']);
            return $copy;
        }, $rawQuestions ?? []);

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

        $encryptedFffPayload = base64_encode(json_encode($rawQuestions ?? []));

        $initialUserAnswers = [];
        if ($user) {
            $dbUserAnswers = QuizQuestionUserAnswer::where('quiz_id', $id)
                ->where('user_id', $user->id)
                ->get();
            foreach ($dbUserAnswers as $ans) {
                $initialUserAnswers[$ans->question_id] = [
                    'question_id' => $ans->question_id,
                    'answer'      => $ans->answer,
                    'total_time'  => (float) $ans->total_time,
                    'show_time'   => $ans->show_time,
                    'ans_time'    => $ans->ans_time,
                ];
            }
        }

        return view('fsrapp.fff.play', compact(
            'quiz',
            'id',
            'pendingTime',
            'banners',
            'serverNowMs',
            'scheduledTimeMs',
            'anssheetReleaseTimeMs',
            'initialStage',
            'totalQuestionsCount',
            'initialSheetData',
            'lastQuizData',
            'winners',
            'userFinishTimeMs',
            'isAllAnswered',
            'initialFffQuestions',
            'encryptedFffPayload',
            'initialUserAnswers'
        ));
    }

    public function waitingView($id)
    {
        return $this->renderFffSpaView($id, 'waiting');
    }

    public function startCounterView($id)
    {
        return $this->renderFffSpaView($id, 'start-counter');
    }

    public function questionView($id)
    {
        return $this->renderFffSpaView($id, 'question');
    }

    public function reviewCounterView($id)
    {
        return $this->renderFffSpaView($id, 'review-counter');
    }

    public function answerSheetView($id)
    {
        return $this->renderFffSpaView($id, 'answer-sheet');
    }

    public function finalCounterView($id)
    {
        return $this->renderFffSpaView($id, 'final-counter');
    }

    public function leaderboardView($id)
    {
        return $this->renderFffSpaView($id, 'leaderboard');
    }

    /**
     * API: Get Questions
     */
    public function getQuestionsFromSession($id)
    {
        $user = Auth::user() ?: auth('web')->user();
        if (!$user && session('fsr_user_id')) {
            $user = \App\Models\User::find(session('fsr_user_id'));
        }
        $res = $this->fffService->getFffQuestionsFromSession((int) $id, $user);
        $status = !empty($res['quiz_expired']) ? 403 : ($res['status'] ?? 200);
        return response()->json($res, $status);
    }

    /**
     * API: Submit Single Answer
     */
    public function submitAnswer(Request $request, $id)
    {
        $user = Auth::user() ?: auth('web')->user();
        if (!$user && session('fsr_user_id')) {
            $user = \App\Models\User::find(session('fsr_user_id'));
        }
        if (!$user && $request->filled('user_id')) {
            $user = \App\Models\User::find($request->input('user_id'));
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
                $minSubmitTimeMs = $scheduledTimeMs + 11000; // schedule_time + 10s start counter + 1s = 11s
                if ($nowMs < $minSubmitTimeMs) {
                    return response()->json([
                        'success' => false,
                        'status'  => 422,
                        'message' => '⚠️ Quiz start nahi hua hai. Answer schedule time + 11 seconds ke baad hi submit ho sakta hai.',
                    ], 422);
                }
            }
        }

        Session::put("fff_quiz_{$id}_started_answering", true);

        $res = $this->fffService->submitFffAnswer($user, (int) $id, $request->all());

        if (!empty($res['quiz_expired'])) {
            return response()->json($res, 403);
        }

        if ($user) {
            if (Session::get("fff_quiz_{$id}_out_status") !== 'in') {
                QuizUser::where('quiz_id', $id)
                    ->where('user_id', $user->id)
                    ->update([
                        'out_status' => 'in',
                        'status'     => 'active',
                    ]);
                Session::put("fff_quiz_{$id}_out_status", 'in');
            }
        }

        return response()->json($res, $res['status'] ?? 200);
    }

    public function getAnswerSheetData(Request $request, $id)
    {
        $user = Auth::user() ?: auth('web')->user();
        if (!$user && session('fsr_user_id')) {
            $user = \App\Models\User::find(session('fsr_user_id'));
        }
        if (!$user && $request->filled('user_id')) {
            $user = \App\Models\User::find($request->input('user_id'));
        }

        $quiz = Quiz::find($id);
        if (!$quiz) {
            return response()->json(['success' => false, 'message' => 'Quiz not found'], 404);
        }

        // Jab tak answer submission time complete nahi hua ho, API se bhi answer data na bhejein
        if (!$quiz->isAnswerTimeFinished()) {
            return response()->json([
                'success' => false,
                'status' => 403,
                'message' => 'Answer sheet is not available yet. Please wait until quiz answer submission time completes.',
                'redirect' => route('fsrapp.home')
            ], 403);
        }

        $nowMs = (int) (microtime(true) * 1000);
        $clientAnswers = $request->input('client_answers') ?: $request->input('answers') ?: [];
        $res = $this->fffService->getFffAnswerSheetData($user, (int) $id, is_array($clientAnswers) ? $clientAnswers : []);

        if (!empty($res['quiz_expired'])) {
            return response()->json($res, 403);
        }

        if ($quiz->complete_status !== 'completed') {
            $scheduledAt = !empty($quiz->scheduled_at) ? Carbon::parse($quiz->scheduled_at) : (!empty($quiz->availability_start) ? Carbon::parse($quiz->availability_start) : null);
            $scheduledTimeMs = $scheduledAt ? (int) ($scheduledAt->getTimestamp() * 1000) : Cache::get("fff_quiz_{$id}_counter_start_ms");

            if ($scheduledTimeMs) {
                $qflowEndMs = $scheduledTimeMs + 95000; // 10s start + 75s questions + 10s review = 95s
                $hasUserAnswers = $user ? QuizQuestionUserAnswer::where('quiz_id', $id)->where('user_id', $user->id)->exists() : false;
                if ($qflowEndMs > $nowMs && !$hasUserAnswers) {
                    $remainingSec = max(1, (int) ceil(($qflowEndMs - $nowMs) / 1000));
                    $res['locked'] = true;
                    $res['remaining_seconds'] = $remainingSec;
                    $res['release_time_ms'] = $qflowEndMs;
                    $res['server_now_ms'] = $nowMs;
                }
            }
        }

        return response()->json($res);
    }

    /**
     * API: Reconcile Answers
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
                $minSubmitTimeMs = $scheduledTimeMs + 11000; // schedule_time + 10s start counter + 1s = 11s
                if ($nowMs < $minSubmitTimeMs) {
                    return response()->json([
                        'success' => false,
                        'status'  => 422,
                        'message' => '⚠️ Quiz start nahi hua hai. Answer schedule time + 11 seconds ke baad hi submit ho sakta hai.',
                    ], 422);
                }
            }
        }

        $clientAnswers = $request->input('answers') ?: $request->input('client_answers') ?: [];
        $res = $this->fffService->reconcileFffAnswers($user, (int) $id, is_array($clientAnswers) ? $clientAnswers : []);
        $status = !empty($res['quiz_expired']) ? 403 : ($res['status'] ?? 200);

        if (is_array($res)) {
            $designatedKey = "fff_quiz_{$id}_process_result_designated";
            $alreadyTriggered = Cache::has("fff_quiz_{$id}_process_result_triggered");
            $res['trigger_process_result'] = !$alreadyTriggered && Cache::add($designatedKey, true, 180);
        }

        return response()->json($res, $status);
    }

    /**
     * API: Process Quiz Result Async
     */
    public function processQuizResultAsync($id)
    {
        $id = (int) $id;
        if (!$id) {
            return response()->json(['success' => false, 'message' => 'Invalid quiz ID'], 400);
        }

        $triggeredKey = "fff_quiz_{$id}_process_result_triggered";

        // Fast-path: If already triggered, exit immediately (0ms)
        if (Cache::has($triggeredKey)) {
            return response()->json([
                'success' => true,
                'already_triggered' => true,
                'message' => 'FFF Quiz result already marked as processing.',
            ]);
        }

        $lockKey = "fff_quiz_{$id}_process_result_api_lock";
        $lock = Cache::lock($lockKey, 30);
        if (!$lock->get()) {
            return response()->json([
                'success' => true,
                'already_triggered' => true,
                'message' => 'FFF Quiz result is currently being initiated.',
            ]);
        }

        try {
            Cache::put($triggeredKey, true, 3600);

            $quiz = Quiz::find($id);
            if ($quiz) {
                if ($quiz->complete_status === 'pending') {

                    $second = 207; // 11 question + 20 second conter 
                    if($quiz->visibility === 'fastest_finger_first'){
                        $second = 80;
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

            return response()->json([
                'success' => true,
                'message' => 'Quiz marked as processing for cron leaderboard declaration.',
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("FffQuizController processQuizResultAsync error for quiz {$id}: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        } finally {
            $lock->release();
        }
    }

    /**
     * API: Get Leaderboard Data
     */
    public function getLeaderboardData($id)
    {
        $user = Auth::user() ?: auth('web')->user();
        if (!$user && session('fsr_user_id')) {
            $user = \App\Models\User::find(session('fsr_user_id'));
        }

        $res = $this->fffService->getFffLeaderboardData($user, (int) $id);
        return response()->json($res);
    }

    /**
     * API: Mark User Out
     */
    public function markUserOut(Request $request, $id)
    {
        $user = Auth::user() ?: auth('web')->user();
        if (!$user && session('fsr_user_id')) {
            $user = \App\Models\User::find(session('fsr_user_id'));
        }

        $reason = $request->input('reason') ?: $request->input('remark') ?: 'Screen Minimize';
        $res = $this->fffService->markUserOut($user, (int) $id, (string) $reason);

        return response()->json($res, $res['status'] ?? 200);
    }

    /**
     * API: Clear FFF Quiz Cache and Session Data Endpoint
     */
    public function clearSessionApi($id)
    {
        $quiz = Quiz::find($id);
        if (!$quiz || $quiz->complete_status !== 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'FFF Quiz session can only be cleared after quiz is completed.'
            ], 400);
        }

        $this->fffService->clearFffQuizCacheAndSessionData((int) $id);
        return response()->json([
            'success' => true,
            'message' => 'FFF Quiz cache and session data cleared successfully.'
        ]);
    }
}
