<?php

namespace App\Repositories\androidapi\Traits;

use App\Models\Quiz;
use App\Models\QuizNotification;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionUserAnswer;
use App\Models\QuizUser as QuizUserEnrollment;
use App\Models\QuizWinner;
use App\Models\QuizStatus;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

trait HandlesQuizWebApp
{
    /**
     * Get calculated Quiz Expiry Time (Carbon)
     * Quiz.schedule_time + 10 seconds + (10 * 17 seconds)
     */
    public function getQuizAnswerExpiryTime($quiz): ?Carbon
    {
        if (is_numeric($quiz)) {
            $quiz = Quiz::find((int) $quiz);
        } elseif (!($quiz instanceof Quiz)) {
            $quizId = is_object($quiz) ? ($quiz->id ?? null) : null;
            $quiz = $quizId ? Quiz::find((int) $quizId) : null;
        }

        if (!$quiz) {
            return null;
        }

        $scheduledAt = $quiz->scheduled_at ? Carbon::parse($quiz->scheduled_at) : ($quiz->availability_start ? Carbon::parse($quiz->availability_start) : null);
        if (!$scheduledAt) {
            return null;
        }
        $qLimit = (!empty($quiz->question_limit) ? (int)$quiz->question_limit : null) ?: 10;
        return $scheduledAt->copy()->addSeconds(10 + ($qLimit * 17));
    }

    /**
     * Check if total answer submission time has expired for a user
     */
    public function isQuizAnswerExpired($quiz, $user = null): bool
    {
        if (is_numeric($quiz)) {
            $quiz = Quiz::find((int) $quiz);
        } elseif (!($quiz instanceof Quiz)) {
            $quizId = is_object($quiz) ? ($quiz->id ?? null) : null;
            $quiz = $quizId ? Quiz::find((int) $quizId) : null;
        }

        if (!$quiz) {
            return false;
        }

        $expiryTime = $this->getQuizAnswerExpiryTime($quiz);
        if (!$expiryTime) {
            return false;
        }

        if (Carbon::now()->gte($expiryTime)) {
            if ($user) {
                $userId = is_object($user) ? ($user->id ?? null) : $user;
                if ($userId && $this->hasUserAnsweredLastQuestion($userId, (int) $quiz->id)) {
                    return false;
                }
            }
            return true;
        }

        return false;
    }

    public function preloadQuizQuestionsToSession(int $id): array
    {
        $sessionKey = "quiz_{$id}_questions";
        $switchSessionKey = "quiz_{$id}_switch_questions";

        if (Session::has($sessionKey)) {
            $existing = Session::get($sessionKey);
            if (!empty($existing) && is_array($existing)) {
                return $existing;
            }
            Session::forget($sessionKey);
        }

        $formatted = Cache::get($sessionKey);
        if (empty($formatted) || !is_array($formatted)) {
            Cache::forget($sessionKey);
            $dbQuestions = QuizQuestion::with('question')
                ->where('quiz_id', $id)
                ->where(function ($q) {
                    $q->whereNull('is_switch_question')->orWhere('is_switch_question', 0);
                })
                ->orderBy('order', 'asc')
                ->get();

            $formatted = $this->formatQuestionsArray($dbQuestions);
            if (!empty($formatted)) {
                Cache::put($sessionKey, $formatted, now()->addHours(2));
            }
        }

        $formattedSwitch = Cache::get($switchSessionKey);
        if (empty($formattedSwitch) || !is_array($formattedSwitch)) {
            Cache::forget($switchSessionKey);
            $dbSwitchQuestions = QuizQuestion::with('question')
                ->where('quiz_id', $id)
                ->where('is_switch_question', 1)
                ->orderBy('order', 'asc')
                ->get();

            $formattedSwitch = $this->formatQuestionsArray($dbSwitchQuestions);
            if (!empty($formattedSwitch)) {
                Cache::put($switchSessionKey, $formattedSwitch, now()->addHours(2));
            }
        }

        if (!empty($formatted)) {
            Session::put($sessionKey, $formatted);
            Session::put($switchSessionKey, $formattedSwitch);

            // Preload in Redis for sub-millisecond retrieval
            \App\Services\RedisQuizService::cacheQuizQuestions($id, $formatted, $formattedSwitch);
        }

        return $formatted;
    }

    public function getQuestionsFromSessionData(int $id): array
    {
        $user = Auth::user() ?: auth('web')->user();
        if (!$user && session('fsr_user_id')) {
            $user = \App\Models\User::find(session('fsr_user_id'));
        }

        $quiz = Quiz::find($id);
        if ($quiz && $this->isQuizAnswerExpired($quiz, $user)) {
            if ($user) {
                $userEnrollment = QuizUserEnrollment::where('quiz_id', $id)->where('user_id', $user->id)->first();
                if ($userEnrollment) {
                    $this->markUserOutOfQuizData($user, (int) $id, 'Schedule Time Passed');
                }
            }
            return [
                'success' => false,
                'status'  => 403,
                'out'     => true,
                'quiz_expired' => true,
                'message' => '⚠️ Quiz answer submission time has expired.',
            ];
        }

        /*
        if ($user) {
            $isCompleted = $this->hasUserAnsweredLastQuestion($user->id, $id);
            if (!$isCompleted) {
                $enrollment = QuizUserEnrollment::where('quiz_id', $id)
                    ->where('user_id', $user->id)
                    ->first();
                $cookieOut = request()->cookie("fsr_quiz_out_{$id}");
                if ($cookieOut === 'out' || ($enrollment && $enrollment->out_status === 'out') || Session::get("quiz_{$id}_out_status") === 'out') {
                    return [
                        'success' => false,
                        'status'  => 403,
                        'out'     => true,
                        'message' => '⚠️ Aap is quiz se out ho chuke hain. Aap questions access nahi kar sakte.',
                    ];
                }
            }
        }
        */

        $sessionKey = "quiz_{$id}_questions";
        $switchSessionKey = "quiz_{$id}_switch_questions";

        if (!Session::has($sessionKey) || empty(Session::get($sessionKey))) {
            $redisData = \App\Services\RedisQuizService::getQuizQuestions($id);
            if ($redisData && !empty($redisData['questions'])) {
                Session::put($sessionKey, $redisData['questions']);
                Session::put($switchSessionKey, $redisData['switch_questions'] ?? []);
            } else {
                $cachedQ = Cache::get($sessionKey, []);
                if (!empty($cachedQ)) {
                    Session::put($sessionKey, $cachedQ);
                    $cachedSwitch = Cache::get($switchSessionKey, []);
                    if (!empty($cachedSwitch)) {
                        Session::put($switchSessionKey, $cachedSwitch);
                    }
                } else {
                    $this->preloadQuizQuestionsToSession($id);
                }
            }
        }

        $questions = Session::get($sessionKey, []);
        $switchQuestions = Session::get($switchSessionKey, []);
        if (!$quiz) {
            $quiz = Quiz::find($id);
        }

        return [
            'success' => true,
            'from_session' => true,
            'total' => count($questions),
            'questions' => $questions,
            'switch_questions' => $switchQuestions,
            'quiz_settings' => [
                'fifty_fifty' => (bool) ($quiz->fifty_fifty ?? true),
                'switch_option' => (bool) ($quiz->switch_option ?? true),
            ]
        ];
    }

    public function joinSubmitData($user, int $id): array
    {
        $quiz = Quiz::find($id);

        if (!$quiz) {
            return [
                'success' => false,
                'status' => 404,
                'message' => 'Quiz not found.',
                'redirect' => route('fsrapp.home')
            ];
        }

        if ($quiz->visibility === 'fastest_finger_first') {
            return app(\App\Services\FffQuizService::class)->joinFffQuiz($user, $id);
        }

        $isFinished = false;
        if ($quiz->complete_status === 'completed') {
            $isFinished = true;
        } elseif ($quiz->availability_end && Carbon::parse($quiz->availability_end)->isPast()) {
            $isFinished = true;
        } elseif (!$quiz->availability_end && $quiz->scheduled_at && Carbon::parse($quiz->scheduled_at)->addMinutes(15)->isPast()) {
            $isFinished = true;
        } elseif ($this->isQuizAnswerExpired($quiz, $user)) {
            $isFinished = true;
        }

        if ($isFinished) {
            return [
                'success' => false,
                'status' => 403,
                'message' => '⚠️ Quiz scheduled time has passed. You cannot join this quiz.',
                'redirect' => route('fsrapp.home')
            ];
        }

        // 40-Minute Rule: Agar schedule time me 40 minute se jyada bacha hai toh koi user join nahi kar sakega
        if ($quiz->scheduled_at && Carbon::now()->diffInSeconds(Carbon::parse($quiz->scheduled_at), false) > 2400) {
            return [
                'success' => false,
                'status' => 403,
                'message' => __('app.quiz_join_40min_warning'),
                'redirect' => route('fsrapp.home')
            ];
        }

        if ($user) {
            if (\App\Services\RedisQuizService::isAvailable()) {
                $isAlreadyEnrolled = \App\Services\RedisQuizService::isUserEnrolled($id, (int) $user->id);

                if (!$isAlreadyEnrolled) {
                    $isKnownEnrolled = Session::get("quiz_{$id}_out_status") !== null;
                    if (!$isKnownEnrolled) {
                        $existing = QuizUserEnrollment::where('quiz_id', $id)
                            ->where('user_id', $user->id)
                            ->first();
                        if ($existing) {
                            $isKnownEnrolled = true;
                            if ($existing->status === 'inactive') {
                                return [
                                    'success'  => false,
                                    'status'   => 403,
                                    'message'  => '⚠️ Quiz player limit complete try next time',
                                    'redirect' => route('fsrapp.home')
                                ];
                            }
                        }
                    }

                    if ($isKnownEnrolled) {
                        \App\Services\RedisQuizService::enrollUser($id, (int) $user->id);
                        Session::put("quiz_{$id}_out_status", 'waiting');
                    } else {
                        if ($quiz->no_of_players > 0) {
                            $enrolledCount = \App\Services\RedisQuizService::getEnrolledPlayersCount($id);
                            $dbActiveCount = QuizUserEnrollment::where('quiz_id', $id)->where('status', 'active')->count();
                            $effectiveActiveCount = max($enrolledCount, $dbActiveCount);

                            if ($effectiveActiveCount >= (int) $quiz->no_of_players) {
                                QuizUserEnrollment::firstOrCreate(
                                    [
                                        'quiz_id'   => $id,
                                        'user_id'   => $user->id,
                                        'tenant_id' => $quiz->tenant_id ?? 1,
                                    ],
                                    [
                                        'status'     => 'inactive',
                                        'remark'     => 'Player limit exceeded',
                                        'out_status' => 'out',
                                    ]
                                );

                                return [
                                    'success'  => false,
                                    'status'   => 403,
                                    'message'  => '⚠️ Quiz player limit complete try next time',
                                    'redirect' => route('fsrapp.home')
                                ];
                            }
                        }

                        // Atomic enqueue into Redis Set & PENDING_ENROLLMENTS_QUEUE for zero-skip MySQL bulk upsert
                        \App\Services\RedisQuizService::enrollUserWithQueue(
                            $id,
                            (int) $user->id,
                            (int) ($quiz->tenant_id ?? 1),
                            'active',
                            'waiting'
                        );

                        try {
                            QuizUserEnrollment::firstOrCreate(
                                [
                                    'quiz_id'   => $id,
                                    'user_id'   => $user->id,
                                    'tenant_id' => $quiz->tenant_id ?? 1,
                                ],
                                [
                                    'status'     => 'active',
                                    'out_status' => 'waiting',
                                ]
                            );
                        } catch (\Throwable $e) {}

                        if (isset($user->available_quiz_limit) && (int) $user->available_quiz_limit > 0) {
                            try {
                                User::where('id', $user->id)
                                    ->where('available_quiz_limit', '>', 0)
                                    ->decrement('available_quiz_limit', 1);
                            } catch (\Throwable $e) {}
                        }

                        Session::put("quiz_{$id}_out_status", 'waiting');
                    }
                } else {
                    Session::put("quiz_{$id}_out_status", 'waiting');
                }
            } else {
                $existing = QuizUserEnrollment::where('quiz_id', $id)
                    ->where('user_id', $user->id)
                    ->first();

                if ($existing) {
                    if ($existing->status === 'inactive') {
                        return [
                            'success'  => false,
                            'status'   => 403,
                            'message'  => '⚠️ Quiz player limit complete try next time',
                            'redirect' => route('fsrapp.home')
                        ];
                    }

                    if ($existing->out_status === 'out') {
                        $existing->update(['out_status' => 'in', 'status' => 'active']);
                        Session::put("quiz_{$id}_out_status", 'in');
                    }
                } else {
                    $status = 'active';
                    $remark = null;

                    if ($quiz->no_of_players > 0) {
                        $activeCount = Cache::remember("quiz_{$id}_active_enroll_count", 3, function () use ($id) {
                            return QuizUserEnrollment::where('quiz_id', $id)
                                ->where('status', 'active')
                                ->count();
                        });

                        if ($activeCount >= (int) $quiz->no_of_players) {
                            $status = 'inactive';
                            $remark = 'Player limit exceeded';
                        }
                    }

                    $enrollment = QuizUserEnrollment::firstOrCreate(
                        [
                            'quiz_id'   => $id,
                            'user_id'   => $user->id,
                            'tenant_id' => $quiz->tenant_id ?? 1,
                        ],
                        [
                            'status'     => $status,
                            'remark'     => $remark,
                            'out_status' => $status === 'active' ? 'waiting' : 'out',
                        ]
                    );

                    if ($enrollment->wasRecentlyCreated && $status === 'active') {
                        if (isset($user->available_quiz_limit) && (int) $user->available_quiz_limit > 0) {
                            try {
                                User::where('id', $user->id)
                                    ->where('available_quiz_limit', '>', 0)
                                    ->decrement('available_quiz_limit', 1);
                            } catch (\Throwable $e) {}
                        }
                    }

                    if ($status === 'inactive' || $enrollment->status === 'inactive') {
                        return [
                            'success'  => false,
                            'status'   => 403,
                            'message'  => '⚠️ Quiz player limit complete try next time',
                            'redirect' => route('fsrapp.home')
                        ];
                    }
                }
            }
        }

        $questions = $this->preloadQuizQuestionsToSession($id);
        Session::put('active_quiz_id', $id);

        return [
            'success' => true,
            'status' => 200,
            'message' => 'Joined successfully',
            'questions_count' => count($questions),
            'redirect' => route('fsrapp.quizes.waiting', $id)
        ];
    }

    public function toggleNotificationData($user, int $id): array
    {
        if (!$user) {
            return [
                'success' => false,
                'status' => 401,
                'message' => 'Please login to set notification.'
            ];
        }

        $quiz = Quiz::find($id);
        if (!$quiz) {
            return [
                'success' => false,
                'status' => 404,
                'message' => 'Quiz not found.'
            ];
        }

        $existing = QuizNotification::withTrashed()
            ->where('quiz_id', $id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing && !$existing->trashed()) {
            $existing->delete();
            return [
                'success' => true,
                'notified' => false,
                'message' => 'Notification removed for this quiz.'
            ];
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

        return [
            'success' => true,
            'notified' => true,
            'message' => 'We’ll notify you when the quiz is ready to join!'
        ];
    }

    public function submitQuizAnswer($user, int $id, array $data): array
    {
        if (!$user) {
            return [
                'success' => false,
                'status' => 401,
                'message' => 'Unauthenticated user. Please login to submit answers.',
            ];
        }

        $enrollment = QuizUserEnrollment::where('quiz_id', $id)
            ->where('user_id', $user->id)
            ->first();

        $cookieOut = request()->cookie("fsr_quiz_out_{$id}");
        /*
        $isUserOut = ($enrollment && ($enrollment->out_status === 'out' || $enrollment->status === 'inactive')) ||
                     Session::get("quiz_{$id}_out_status") === 'out' ||
                     $cookieOut === 'out';

        if ($isUserOut) {
            if ($this->hasUserAnsweredLastQuestion($user->id, $id)) {
                if ($enrollment) {
                    $enrollment->update(['out_status' => 'in', 'status' => 'active']);
                }
                Session::put("quiz_{$id}_out_status", 'in');
                Session::forget("quiz_{$id}_out_remark");
            } else {
                $outRemark = ($enrollment ? $enrollment->remark : null) ?: Session::get("quiz_{$id}_out_remark") ?: 'Quiz exit';
                return [
                    'success' => false,
                    'status' => 403,
                    'message' => '⚠️ Aap is quiz se out ho chuke hain (' . $outRemark . '). Answer submit nahi kiya ja sakta.',
                    'out_status' => 'out',
                    'remark' => $outRemark,
                ];
            }
        }
        */

        $quiz = Quiz::find($id);

        if ($quiz) {
            $scheduledAt = !empty($quiz->scheduled_at) ? Carbon::parse($quiz->scheduled_at) : (!empty($quiz->availability_start) ? Carbon::parse($quiz->availability_start) : null);
            if ($scheduledAt) {
                $nowMs = (int) (microtime(true) * 1000);
                $scheduledTimeMs = (int) ($scheduledAt->getTimestamp() * 1000);
                if ($nowMs < ($scheduledTimeMs - 5000)) {
                    return [
                        'success' => false,
                        'status'  => 422,
                        'message' => '⚠️ Quiz start nahi hua hai.',
                    ];
                }
            }
        }

        if ($quiz && $this->isQuizAnswerExpired($quiz, $user)) {
            $this->markUserOutOfQuizData($user, (int) $id, 'Schedule Time Passed');
            return [
                'success' => false,
                'quiz_expired' => true,
                'out_status' => 'out',
                'remark' => 'Schedule Time Passed',
                'status' => 403,
                'message' => '⚠️ Quiz answer submission time has expired.',
            ];
        }

        $questionId = $data['question_id'] ?? null;
        $answer = $data['answer'] ?? null;

        if (!$questionId) {
            return [
                'success' => false,
                'status' => 422,
                'message' => 'Question ID is required.',
            ];
        }

        $questions = Session::get("quiz_{$id}_questions", Cache::get("quiz_{$id}_questions", []));
        $switchQuestions = Session::get("quiz_{$id}_switch_questions", Cache::get("quiz_{$id}_switch_questions", []));

        if (empty($questions)) {
            $this->preloadQuizQuestionsToSession($id);
            $questions = Session::get("quiz_{$id}_questions", []);
            $switchQuestions = Session::get("quiz_{$id}_switch_questions", []);
        }

        $allQuestions = array_merge(
            is_array($questions) ? $questions : [],
            is_array($switchQuestions) ? $switchQuestions : []
        );

        $questionData = null;
        foreach ($allQuestions as $q) {
            if (isset($q['id']) && $q['id'] == $questionId) {
                $questionData = $q;
                break;
            }
        }

        $quizQuestion = null;
        if (!$questionData) {
            $quizQuestion = QuizQuestion::where('quiz_id', $id)
                ->where('id', $questionId)
                ->first();

            if (!$quizQuestion) {
                return [
                    'success' => false,
                    'status' => 422,
                    'message' => 'Question is not part of this quiz',
                ];
            }

            $opts = $quizQuestion->options;
            if (is_string($opts)) {
                $opts = json_decode($opts, true) ?? [];
            }
            if (!is_array($opts)) {
                $opts = [];
            }
            $questionData = [
                'id' => $quizQuestion->id,
                'question' => $quizQuestion->question_text ?? 'Question',
                'hindi_question' => $quizQuestion->hindi_question ?? null,
                'options' => $opts,
                'correct_answer' => $quizQuestion->correct_answer ?? '',
            ];
        }

        $totalQuizQuestionsCount = !empty($questions) ? count($questions) : QuizQuestion::where('quiz_id', $id)->count();

        if ($answer === null || trim((string) $answer) === '' || trim((string) $answer) === 'null') {
            return [
                'success' => false,
                'status' => 422,
                'message' => 'No answer provided.',
            ];
        }

        $isCorrect = false;
        $correctAnsStr = '';
        $userAnsStr = is_string($answer ?? '') ? $answer : (is_null($answer) ? null : json_encode($answer));

        if ($questionData) {
            $rawCorrect = is_string($questionData['correct_answer'] ?? '')
                ? $questionData['correct_answer']
                : json_encode($questionData['correct_answer'] ?? '');

            $options = $questionData['options'] ?? [];
            $correctAnsStr = $this->resolveMatchingOptionStr($rawCorrect, $options);
            if (empty($correctAnsStr)) {
                $correctAnsStr = (string) $rawCorrect;
            }

            if ($userAnsStr !== null && $userAnsStr !== '') {
                $resolvedUser = $this->resolveMatchingOptionStr($userAnsStr, $options);
                if (!empty($resolvedUser)) {
                    $userAnsStr = $resolvedUser;
                }
            }

            if ($correctAnsStr !== null && trim((string) $correctAnsStr) !== ''
                && $userAnsStr !== null && trim((string) $userAnsStr) !== '') {
                $isCorrect = $this->checkAnswersMatch($correctAnsStr, $userAnsStr, $options);
            }
        }

        $finalSavedAnswer = is_string($userAnsStr) && trim($userAnsStr) !== ''
            ? trim($userAnsStr)
            : (is_string($answer) ? trim($answer) : (string) ($answer ?? ''));

        $totalTime = '0.000';
        $rawTotalTime = $data['total_time'] ?? null;
        if ($rawTotalTime !== null && is_numeric($rawTotalTime) && (float) $rawTotalTime >= 0) {
            $totalTime = number_format((float) $rawTotalTime, 3, '.', '');
        }
        $answerTime = now();

        // High-Speed Redis in-memory answer recording & ZSET Leaderboard update + buffer queue
        $redisRes = \App\Services\RedisQuizService::recordAnswer(
            (int) $id,
            (int) $user->id,
            (int) $questionId,
            (string) $finalSavedAnswer,
            (bool) $isCorrect,
            (float) $totalTime,
            $questionData,
            $correctAnsStr,
            (int) ($user->tenant_id ?? 1),
            $quizQuestion?->start_time
        );

        if (!empty($redisRes['already_answered'])) {
            return [
                'success' => true,
                'status' => 200,
                'user_answer' => $redisRes['answer'] ?? $finalSavedAnswer,
                'total_time' => $totalTime,
                'message' => 'You have already answered this question',
            ];
        }

        $userTenantId = $user->tenant_id ?? 1;

        // Compute show_time if null
        $computedShowTime = $quizQuestion?->start_time;
        if (empty($computedShowTime)) {
            $quizObj = Quiz::find($id);
            $scheduledAt = $quizObj?->scheduled_at ? Carbon::parse($quizObj->scheduled_at) : ($quizObj?->availability_start ? Carbon::parse($quizObj->availability_start) : null);
            if ($scheduledAt) {
                $allQIds = QuizQuestion::where('quiz_id', $id)
                    ->where(function ($q) {
                        $q->whereNull('is_switch_question')->orWhere('is_switch_question', 0);
                    })
                    ->orderBy('order', 'asc')
                    ->pluck('id')
                    ->toArray();
                $idx = array_search((int) $questionId, $allQIds);
                if ($idx === false) $idx = 0;
                $computedShowTime = $scheduledAt->copy()->addSeconds(10 + ($idx * 17))->format('H:i:s');
            } else {
                $computedShowTime = $answerTime->format('H:i:s');
            }
        }

        // Direct bulk upsert into MySQL to ensure real-time persistence and admin panel visibility
        try {
            QuizQuestionUserAnswer::upsert(
                [
                    [
                        'tenant_id'   => $userTenantId,
                        'quiz_id'     => $id,
                        'question_id' => $questionId,
                        'user_id'     => $user->id,
                        'answer'      => $finalSavedAnswer,
                        'status'      => $isCorrect ? 1 : 0,
                        'show_time'   => $computedShowTime,
                        'ans_time'    => $answerTime->format('H:i:s.v'),
                        'total_time'  => $totalTime,
                        'created_at'  => $answerTime->toDateTimeString(),
                        'updated_at'  => $answerTime->toDateTimeString(),
                    ]
                ],
                ['tenant_id', 'quiz_id', 'user_id', 'question_id'],
                ['answer', 'status', 'show_time', 'ans_time', 'total_time', 'updated_at']
            );
        } catch (\Throwable $e) {
            Log::error("submitQuizAnswer DB direct upsert failed for quiz {$id}, user {$user->id}, question {$questionId}: " . $e->getMessage());
        }

        // Always update cache answer sheet
        $this->updateAnsSheetSessionData($id, $user, $questionId, $finalSavedAnswer, $isCorrect, $totalTime, $questionData, $correctAnsStr);

        // Quiz me ek bhi ans submit hua chahe koi sa bhi ho uska quiz_users.out_status = 'in' hona chahiye
        // Cache guard: Only run DB update once when user transitions to 'in', eliminating redundant queries on Q2-Q10
        $userActiveCacheKey = "quiz_{$id}_user_{$user->id}_active";
        if (Session::get("quiz_{$id}_out_status") !== 'in' && !\Illuminate\Support\Facades\Cache::has($userActiveCacheKey)) {
            try {
                QuizUserEnrollment::where('quiz_id', $id)
                    ->where('user_id', $user->id)
                    ->where('out_status', '!=', 'in')
                    ->update([
                        'out_status' => 'in',
                        'status'     => 'active',
                    ]);

                \Illuminate\Support\Facades\Cache::put($userActiveCacheKey, true, 3600);
            } catch (\Throwable $e) {}
        }

        Session::put("quiz_{$id}_out_status", 'in');
        Session::forget("quiz_{$id}_out_remark");

        return [
            'success'          => true,
            'status'           => 200,
            'question_id'      => $questionId,
            'submitted_answer' => $finalSavedAnswer,
            'total_time'       => $totalTime,
            'message'          => 'Answer submitted successfully',
        ];
    }

    public function recordLifelineUseData($user, int $id, string $lifeline, $questionId): array
    {
        if (!$user) {
            return ['success' => false, 'status' => 401, 'message' => 'Unauthorized'];
        }

        $enrollment = QuizUserEnrollment::firstOrCreate(
            ['quiz_id' => $id, 'user_id' => $user->id],
            ['tenant_id' => 1, 'status' => 'active']
        );

        $alreadyUsed = (bool) ($enrollment->fifty_fifty || $enrollment->switch_option);
        if ($alreadyUsed) {
            return [
                'success' => false,
                'status' => 422,
                'message' => 'Lifeline already used in this quiz',
            ];
        }

        if ($lifeline === 'fifty_fifty') {
            $enrollment->update([
                'fifty_fifty' => 1,
                'question_id' => $questionId,
            ]);
        } elseif ($lifeline === 'switch_option') {
            $enrollment->update([
                'switch_option' => 1,
                'question_id' => $questionId,
            ]);
        }

        Session::put("quiz_{$id}_lifeline_used", $lifeline);
        Session::put("quiz_{$id}_user_{$user->id}_lifeline_used", $lifeline);

        return [
            'success' => true,
            'status' => 200,
            'message' => 'Lifeline recorded successfully',
            'lifeline' => $lifeline,
        ];
    }

    public function getAnswerSheetDataForApp($user, int $id): array
    {
        $userId = is_object($user) ? ($user->id ?? null) : $user;
        if (!$userId && session('fsr_user_id')) {
            $userId = session('fsr_user_id');
        }

        // Fast path: Return compiled answer sheet from Cache if already calculated for this user
        $userSheetCacheKey = $userId ? "quiz_{$id}_user_{$userId}_compiled_sheet_response" : null;
        if ($userSheetCacheKey) {
            $cachedSheet = Cache::get($userSheetCacheKey);
            if (!empty($cachedSheet) && is_array($cachedSheet) && !empty($cachedSheet['sheet'])) {
                // Verify if cached sheet has valid user answers
                $cachedAttemptCount = 0;
                foreach ($cachedSheet['sheet'] as $sRow) {
                    if (!empty($sRow['user_answer'])) {
                        $cachedAttemptCount++;
                    }
                }

                // If cached sheet had 0 attempts, check if DB now actually has answers (meaning answers arrived after sheet was cached)
                if ($cachedAttemptCount === 0 && $userId) {
                    $hasDbAns = QuizQuestionUserAnswer::where('quiz_id', $id)
                        ->where('user_id', $userId)
                        ->whereNotNull('answer')
                        ->where('answer', '!=', '')
                        ->exists();
                    if ($hasDbAns) {
                        Cache::forget($userSheetCacheKey);
                        $cachedSheet = null;
                    }
                }
            }

            if (!empty($cachedSheet) && is_array($cachedSheet) && !empty($cachedSheet['sheet'])) {
                if (($cachedSheet['complete_status'] ?? '') === 'completed') {
                    return $cachedSheet;
                }

                // If currently processing, check whether quiz has completed using short-lived cached status
                $freshStatus = Cache::remember("quiz_{$id}_status_quick", 3, function () use ($id) {
                    return Quiz::where('id', $id)->value('complete_status');
                });

                if ($freshStatus === 'completed') {
                    $cachedSheet['complete_status'] = 'completed';
                    $cachedSheet['winners'] = QuizWinner::where('quiz_id', $id)
                        ->orderBy('rank')
                        ->take(5)
                        ->get()
                        ->toArray();
                    Cache::put($userSheetCacheKey, $cachedSheet, now()->addMinutes(30));
                    return $cachedSheet;
                }

                // Still processing: return the pre-compiled answer sheet immediately without re-querying questions/answers
                $cachedSheet['complete_status'] = $freshStatus ?: 'processing';
                return $cachedSheet;
            }
        }

        $quiz = Quiz::find($id);
        if ($quiz && $quiz->complete_status !== 'processing' && $quiz->complete_status !== 'completed') {
            try {
                Cache::lock("quiz_{$id}_mark_processing_lock", 10)->get(function () use ($quiz, $id) {
                    $q = Quiz::find($id);
                    if ($q && $q->complete_status !== 'processing' && $q->complete_status !== 'completed') {
                        $q->update(['complete_status' => 'processing']);
                        $scheduledAt = $q->availability_end ? Carbon::parse($q->availability_end) : now();
                        \App\Models\QuizCronJobHistory::firstOrCreate(
                            [
                                'quiz_id' => (int) $id,
                                'cron_type' => 'quiz_winner_leaderboard',
                            ],
                            [
                                'tenant_id' => $q->tenant_id ?? 1,
                                'scheduled_at' => $scheduledAt,
                                'status' => 'pending',
                            ]
                        );
                    }
                });
            } catch (\Throwable $e) {}
        }

        $questionLimit = ($quiz && !empty($quiz->question_limit) && (int) $quiz->question_limit > 0)
            ? (int) $quiz->question_limit
            : 10;

        $enrollment = $userId ? QuizUserEnrollment::where('quiz_id', $id)->where('user_id', $userId)->first() : null;

        if ($enrollment && $quiz && $this->isQuizAnswerExpired($quiz, $userId)) {
            $this->markUserOutOfQuizData($userId, (int) $id, 'Schedule Time Passed');
            // Do not abort with empty answer sheet. Allow compiling questions so user can review the quiz questions and answers.
        }
        $hasSwitched = (bool) ($enrollment && $enrollment->switch_option);
        $switchedReplacedQId = $hasSwitched ? ($enrollment->question_id ?? null) : null;

        // 1. Get official quiz questions
        $questions = Session::get("quiz_{$id}_questions", Cache::get("quiz_{$id}_questions", []));
        $switchQuestions = Session::get("quiz_{$id}_switch_questions", Cache::get("quiz_{$id}_switch_questions", []));

        if (empty($questions)) {
            $questions = $this->preloadQuizQuestionsToSession($id);
            $switchQuestions = Session::get("quiz_{$id}_switch_questions", []);
        }

        // 2. Build map of all available questions (handling switched question if applicable)
        $questionsMap = [];
        foreach ($questions as $q) {
            if ($hasSwitched && $switchedReplacedQId && $q['id'] == $switchedReplacedQId) {
                continue;
            }
            $questionsMap[$q['id']] = $q;
        }

        if ($hasSwitched && !empty($switchQuestions)) {
            foreach ($switchQuestions as $sq) {
                $questionsMap[$sq['id']] = $sq;
            }
        }

        // Always slice to exact question limit (e.g. exactly 10 questions in sequence)
        $usedQuestions = array_slice(array_values($questionsMap), 0, $questionLimit);

        // 3. Gather answer sources: Session/Cache anssheet, session answers, DB answers
        $userKey = $userId ? "quiz_{$id}_user_{$userId}_anssheet" : "quiz_{$id}_anssheet";
        $sessionAnssheet = Session::get($userKey, Cache::get($userKey, []));
        if (!is_array($sessionAnssheet)) $sessionAnssheet = [];

        $userAnsKey = $userId ? "quiz_{$id}_user_{$userId}_user_answers" : "quiz_{$id}_user_answers";
        $sessionAnswers = Session::get($userAnsKey, Cache::get($userAnsKey, []));
        if (!is_array($sessionAnswers)) $sessionAnswers = [];

        $dbAnswers = [];
        if ($userId) {
            $redisAnswers = null;
            if (\App\Services\RedisQuizService::isAvailable()) {
                $redisAnswers = \App\Services\RedisQuizService::getUserAnswers($id, (int) $userId);
            }

            if (!empty($redisAnswers)) {
                $dbAnswers = collect($redisAnswers)->map(function ($item) {
                    return (object) [
                        'question_id' => $item['question_id'] ?? null,
                        'answer'      => $item['answer'] ?? null,
                        'status'      => !empty($item['is_correct']) ? 1 : ($item['status'] ?? 0),
                        'total_time'  => $item['total_time'] ?? 0,
                        'ans_time'    => $item['ans_time'] ?? null,
                    ];
                })->keyBy('question_id');
            } else {
                $dbAnswers = QuizQuestionUserAnswer::where('quiz_id', $id)
                    ->where('user_id', $userId)
                    ->get()
                    ->keyBy('question_id');
            }
        }

        // Batch load any missing question rows from DB to avoid N+1 queries
        $missingQIds = [];
        foreach ($usedQuestions as $q) {
            $qId = $q['id'] ?? null;
            if ($qId && (empty($q['correct_answer']) || empty($q['question_audio']) || empty($q['media_url']))) {
                $missingQIds[] = $qId;
            }
        }
        $missingQuestionsMap = collect();
        if (!empty($missingQIds)) {
            $missingQuestionsMap = QuizQuestion::with('question')
                ->whereIn('id', array_unique($missingQIds))
                ->get()
                ->keyBy('id');
        }

        $sheet = [];
        $allQIds = null;
        foreach ($usedQuestions as $q) {
            $qId = $q['id'];
            $opts = $q['options'] ?? [];
            $cRaw = $q['correct_answer'] ?? '';

            // Check if db question has more metadata if needed
            $dbQ = (is_object($missingQuestionsMap) && method_exists($missingQuestionsMap, 'get'))
                ? $missingQuestionsMap->get($qId)
                : null;
            if ($dbQ) {
                if (empty($cRaw)) {
                    $cRaw = $dbQ->correct_answer ?: ($dbQ->hindi_correct_answer ?: ($dbQ->question?->correct_answer ?: $dbQ->question?->hindi_correct_answer));
                }
                $formattedDb = $this->formatQuestionsArray([$dbQ]);
                if (!empty($formattedDb[0])) {
                    if (empty($q['question_audio'])) $q['question_audio'] = $formattedDb[0]['question_audio'] ?? null;
                    if (empty($q['media_url'])) $q['media_url'] = $formattedDb[0]['media_url'] ?? null;
                }
            }

            $correctAns = $this->resolveMatchingOptionStr($cRaw, $opts) ?: (string) $cRaw;

            $userAns = null;
            $isCorrect = false;
            $totalTime = 0.0;

            $masterQId = $q['question_id'] ?? ($dbQ ? ($dbQ->question_id ?? null) : null);

            $item = $sessionAnssheet[$qId] 
                ?? ($sessionAnssheet[(string) $qId] 
                ?? ($sessionAnssheet[(int) $qId] 
                ?? ($masterQId ? ($sessionAnssheet[$masterQId] ?? ($sessionAnssheet[(string) $masterQId] ?? null)) : null)));

            if ($item) {
                $userAns = $item['user_answer'] ?? ($item['answer'] ?? null);
                $isCorrect = !empty($item['is_correct']);
                $totalTime = (float) ($item['total_time'] ?? ($item['time_taken'] ?? 0));
                if (!empty($item['question_audio']) && empty($q['question_audio'])) {
                    $q['question_audio'] = $item['question_audio'];
                }
                if (!empty($item['media_url']) && empty($q['media_url'])) {
                    $q['media_url'] = $item['media_url'];
                }
            } else {
                $sAns = $sessionAnswers[$qId]
                    ?? ($sessionAnswers[(string) $qId]
                    ?? ($sessionAnswers[(int) $qId]
                    ?? ($masterQId ? ($sessionAnswers[$masterQId] ?? ($sessionAnswers[(string) $masterQId] ?? null)) : null)));

                if ($sAns) {
                    $userAns = $sAns['answer'] ?? null;
                    $isCorrect = (bool) ($sAns['is_correct'] ?? false);
                    $totalTime = (float) ($sAns['total_time'] ?? 0);
                } else {
                    $ansRow = null;
                    if ($dbAnswers instanceof \Illuminate\Support\Collection) {
                        $ansRow = $dbAnswers->get($qId)
                            ?: $dbAnswers->get((string) $qId)
                            ?: $dbAnswers->get((int) $qId)
                            ?: ($masterQId ? ($dbAnswers->get($masterQId) ?: $dbAnswers->get((string) $masterQId) ?: $dbAnswers->get((int) $masterQId)) : null)
                            ?: $dbAnswers->first(function ($val) use ($qId, $masterQId) {
                                $vQId = $val->question_id ?? null;
                                return $vQId && ((string) $vQId === (string) $qId || ($masterQId && (string) $vQId === (string) $masterQId));
                            });
                    } elseif (is_array($dbAnswers)) {
                        $ansRow = $dbAnswers[$qId] ?? ($dbAnswers[(string) $qId] ?? ($dbAnswers[(int) $qId] ?? null));
                        if (!$ansRow && $masterQId) {
                            $ansRow = $dbAnswers[$masterQId] ?? ($dbAnswers[(string) $masterQId] ?? ($dbAnswers[(int) $masterQId] ?? null));
                        }
                    }

                    if ($ansRow) {
                        $userAns = $ansRow->answer ?? null;
                        $isCorrect = (bool) $ansRow->status;
                        $totalTime = (float) ($ansRow->total_time ?? 0);
                    }
                }
            }

            if ($userAns !== null && $userAns !== '') {
                $userAns = $this->resolveMatchingOptionStr($userAns, $opts) ?: (string) $userAns;
                if (!$isCorrect && !empty($correctAns)) {
                    $isCorrect = $this->checkAnswersMatch($correctAns, $userAns, $opts);
                }

                // Backfill into DB if user had answered (in session/cache) but DB insert had failed during high traffic
                if ($userId && (!isset($dbAnswers[$qId]) || empty($dbAnswers[$qId]->answer))) {
                    try {
                        $qModel = QuizQuestion::find($qId);
                        $showTime = $qModel?->start_time;
                        if (empty($showTime) && $quiz) {
                            $scheduledAt = $quiz->scheduled_at ? Carbon::parse($quiz->scheduled_at) : ($quiz->availability_start ? Carbon::parse($quiz->availability_start) : null);
                            if ($scheduledAt) {
                                if ($allQIds === null) {
                                    $allQIds = QuizQuestion::where('quiz_id', $id)
                                        ->where(function ($query) {
                                            $query->whereNull('is_switch_question')->orWhere('is_switch_question', 0);
                                        })
                                        ->orderBy('order', 'asc')
                                        ->pluck('id')
                                        ->toArray();
                                }
                                $idx = array_search((int) $qId, $allQIds);
                                if ($idx === false) $idx = 0;
                                $showTime = $scheduledAt->copy()->addSeconds(10 + ($idx * 17))->format('H:i:s');
                            }
                        }
                        if (empty($showTime)) {
                            $showTime = now()->format('H:i:s');
                        }

                        $savedRow = QuizQuestionUserAnswer::updateOrCreate(
                            [
                                'tenant_id'   => is_object($user) ? ($user->tenant_id ?? 1) : (session('tenant_id') ?? 1),
                                'quiz_id'     => $id,
                                'question_id' => $qId,
                                'user_id'     => $userId,
                            ],
                            [
                                'answer'     => $userAns,
                                'status'     => $isCorrect ? 1 : 0,
                                'show_time'  => $showTime,
                                'ans_time'   => now()->format('H:i:s.v'),
                                'total_time' => number_format((float) $totalTime, 3, '.', ''),
                            ]
                        );
                        $dbAnswers[$qId] = $savedRow;
                    } catch (\Throwable $e) {
                        Log::warning("Backfill quiz answer in getAnswerSheetDataForApp failed: " . $e->getMessage());
                    }
                }
            }

            $qText = $q['question'] ?? $q['question_text'] ?? 'Question';
            if (is_array($qText)) {
                $qText = $qText['question_text'] ?? $qText['question'] ?? 'Question';
            }
            $hindiText = $q['hindi_question'] ?? null;
            if (is_array($hindiText)) {
                $hindiText = $hindiText['hindi_question'] ?? null;
            }

            $sheet[] = [
                'question_id'    => $qId,
                'question'       => is_string($qText) ? $qText : 'Question',
                'hindi_question' => is_string($hindiText) ? $hindiText : null,
                'question_audio' => $q['question_audio'] ?? null,
                'media_url'      => $q['media_url'] ?? null,
                'options'        => $opts,
                'correct_answer' => $correctAns,
                'user_answer'    => $userAns,
                'is_correct'     => $isCorrect,
                'total_time'     => $totalTime,
            ];
        }

        $totalQuestions = count($sheet);
        $correctCount = 0;
        $totalTimeSum = 0;

        foreach ($sheet as $row) {
            if (!empty($row['is_correct'])) {
                $correctCount++;
            }
            $totalTimeSum += (float) ($row['total_time'] ?? 0);
        }

        $wrongCount = max(0, $totalQuestions - $correctCount);

        // Cache completed sheet for instant retrieval on reloads
        if ($userId && !empty($sheet)) {
            $sheetKeyed = [];
            foreach ($sheet as $s) {
                $sheetKeyed[$s['question_id']] = $s;
            }
            Session::put($userKey, $sheetKeyed);
            Cache::put($userKey, $sheetKeyed, now()->addHours(2));
        }

        $response = [
            'success' => true,
            'complete_status' => $quiz?->complete_status ?? 'processing',
            'title' => $quiz?->title ?? 'Live Quiz',
            'winners' => $quiz && $quiz->complete_status === 'completed'
                ? QuizWinner::where('quiz_id', $id)->orderBy('rank')->take(5)->get()->toArray()
                : [],
            'total_questions' => $totalQuestions,
            'correct_count' => $correctCount,
            'wrong_count' => $wrongCount,
            'total_time' => round($totalTimeSum, 3),
            'sheet' => $sheet,
        ];

        if ($userSheetCacheKey && !empty($sheet)) {
            Cache::put($userSheetCacheKey, $response, now()->addMinutes(10));
        }

        return $response;
    }

    public function reconcileQuizAnswers($user, int $id, array $clientAnswers): array
    {
        if (!$user) {
            return ['success' => false, 'message' => 'Invalid parameters'];
        }

        if (empty($clientAnswers)) {
            return ['success' => true, 'reconciled_count' => 0, 'message' => 'No pending answers to reconcile'];
        }

        $quiz = Quiz::find($id);
        if ($quiz) {
            $scheduledAt = !empty($quiz->scheduled_at) ? Carbon::parse($quiz->scheduled_at) : (!empty($quiz->availability_start) ? Carbon::parse($quiz->availability_start) : null);
            if ($scheduledAt) {
                $nowMs = (int) (microtime(true) * 1000);
                $scheduledTimeMs = (int) ($scheduledAt->getTimestamp() * 1000);
                if ($nowMs < ($scheduledTimeMs - 5000)) {
                    return [
                        'success' => false,
                        'status'  => 422,
                        'message' => '⚠️ Quiz start nahi hua hai.',
                    ];
                }
            }
        }

        $userId = is_object($user) ? $user->id : $user;
        $tenantId = is_object($user) ? ($user->tenant_id ?? 1) : (session('tenant_id') ?? 1);
        $synced = 0;

        $questions = Session::get("quiz_{$id}_questions", Cache::get("quiz_{$id}_questions", []));
        $switchQuestions = Session::get("quiz_{$id}_switch_questions", Cache::get("quiz_{$id}_switch_questions", []));
        if (empty($questions)) {
            $questions = $this->preloadQuizQuestionsToSession($id);
            $switchQuestions = Session::get("quiz_{$id}_switch_questions", []);
        }

        $allQuestions = array_merge(
            is_array($questions) ? $questions : [],
            is_array($switchQuestions) ? $switchQuestions : []
        );
        $qMap = collect($allQuestions)->keyBy('id');

        // Check existing answers in Redis/DB to skip redundant writes
        $isRedisAvailable = \App\Services\RedisQuizService::isAvailable();
        $existingDbAnswers = collect();

        if ($isRedisAvailable) {
            $cachedAnswers = \App\Services\RedisQuizService::getUserAnswers($id, (int) $userId);
            if (!empty($cachedAnswers)) {
                $existingDbAnswers = collect($cachedAnswers)->keyBy('question_id');
            }
        }

        if ($existingDbAnswers->isEmpty()) {
            $existingDbAnswers = QuizQuestionUserAnswer::where('quiz_id', $id)
                ->where('user_id', $userId)
                ->get()
                ->keyBy('question_id');
        }

        $nowTimeStr = now()->format('H:i:s.v');
        $nowDateTimeStr = now()->toDateTimeString();
        $rowsToUpsert = [];

        foreach ($clientAnswers as $item) {
            $qId = $item['question_id'] ?? null;
            $ans = $item['user_answer'] ?? ($item['answer'] ?? null);
            if (!$qId || $ans === null || trim((string) $ans) === '' || trim((string) $ans) === 'null') {
                continue;
            }

            // If already present in Redis/DB with valid answer, skip redundant update
            if ($existingDbAnswers->has($qId) && !empty($existingDbAnswers[$qId]->answer ?? $existingDbAnswers[$qId]['answer'] ?? null)) {
                continue;
            }

            $timeTaken = (float) ($item['total_time'] ?? ($item['time_taken'] ?? 0));
            $isCorrect = !empty($item['is_correct']);
            $ansStr = (string) $ans;

            $qData = $qMap->get($qId);
            $rawCorrect = '';
            if ($qData) {
                $rawCorrect = $qData['correct_answer'] ?? '';
                $opts = $qData['options'] ?? [];
                $correctAnsStr = $this->resolveMatchingOptionStr($rawCorrect, $opts) ?: (string) $rawCorrect;
                $userAnsStr = $this->resolveMatchingOptionStr($ansStr, $opts) ?: $ansStr;
                if (!$isCorrect && !empty($correctAnsStr) && !empty($userAnsStr)) {
                    $isCorrect = $this->checkAnswersMatch($correctAnsStr, $userAnsStr, $opts);
                }
                $ansStr = $userAnsStr;
            }

            $computedShowTime = $qData['start_time'] ?? null;
            if (empty($computedShowTime) && $quiz) {
                $scheduledAt = $quiz->scheduled_at ? Carbon::parse($quiz->scheduled_at) : ($quiz->availability_start ? Carbon::parse($quiz->availability_start) : null);
                if ($scheduledAt) {
                    $allQIds = QuizQuestion::where('quiz_id', $id)
                        ->where(function ($query) {
                            $query->whereNull('is_switch_question')->orWhere('is_switch_question', 0);
                        })
                        ->orderBy('order', 'asc')
                        ->pluck('id')
                        ->toArray();
                    $idx = array_search((int) $qId, $allQIds);
                    if ($idx === false) $idx = 0;
                    $computedShowTime = $scheduledAt->copy()->addSeconds(10 + ($idx * 17))->format('H:i:s');
                }
            }
            if (empty($computedShowTime)) {
                $computedShowTime = now()->format('H:i:s');
            }

            $rowsToUpsert[] = [
                'tenant_id'   => $tenantId,
                'quiz_id'     => $id,
                'question_id' => $qId,
                'user_id'     => $userId,
                'answer'      => $ansStr,
                'status'      => $isCorrect ? 1 : 0,
                'show_time'   => $computedShowTime,
                'ans_time'    => $nowTimeStr,
                'total_time'  => number_format($timeTaken, 3, '.', ''),
                'created_at'  => $nowDateTimeStr,
                'updated_at'  => $nowDateTimeStr,
            ];

            // Record in Redis and push to PENDING_ANSWERS_QUEUE if available
            if ($isRedisAvailable) {
                \App\Services\RedisQuizService::recordAnswer(
                    (int) $id,
                    (int) $userId,
                    (int) $qId,
                    $ansStr,
                    $isCorrect,
                    $timeTaken,
                    $qData,
                    $rawCorrect,
                    $tenantId,
                    $computedShowTime
                );
            }

            // Also update cache answer sheet
            $this->updateAnsSheetSessionData($id, $user, $qId, $ansStr, $isCorrect, number_format($timeTaken, 3, '.', ''), $qData, $rawCorrect);
        }

        if (!empty($rowsToUpsert)) {
            try {
                QuizQuestionUserAnswer::upsert(
                    $rowsToUpsert,
                    ['tenant_id', 'quiz_id', 'user_id', 'question_id'],
                    ['answer', 'status', 'show_time', 'ans_time', 'total_time', 'updated_at']
                );
                $synced = count($rowsToUpsert);
            } catch (\Throwable $e) {
                Log::warning("Reconcile bulk upsert failed for quiz {$id}, user {$userId}: " . $e->getMessage());
            }
        }

        // Invalidate compiled cache if any new answer was synced
        if ($synced > 0 && $userId) {
            Cache::forget("quiz_{$id}_user_{$userId}_compiled_sheet_response");
        }

        return [
            'success'      => true,
            'synced_count' => $synced,
            'message'      => "Reconciled {$synced} answers successfully.",
        ];
    }

    public function getLeaderboardDataForApp($user, int $id): array
    {
        try {
            if (\App\Services\RedisQuizService::isAvailable()) {
                $redisLb = \App\Services\RedisQuizService::getLeaderboard($id, $user?->id, 0);
                if ($redisLb && !empty($redisLb['winners'])) {
                    return [
                        'success' => true,
                        'winners' => $redisLb['winners'],
                        'user_rank' => $redisLb['user_rank'],
                        'total_participants' => $redisLb['total_participants'] ?? count($redisLb['winners']),
                        'from_redis' => true,
                    ];
                }
            }

            $cachedWinners = Cache::remember("quiz_{$id}_leaderboard_winners_cache", 15, function () use ($id) {
                $winners = QuizWinner::where('quiz_id', $id)
                    ->with('user:id,name')
                    ->orderByDesc('total_correct_answer')
                    ->orderBy('total_time', 'asc')
                    ->orderBy('rank', 'asc')
                    ->get();

                if ($winners->isEmpty()) {
                    // Use atomic lock so only 1 process computes fallback aggregation under high concurrency
                    Cache::lock("quiz_{$id}_leaderboard_compute_lock", 15)->get(function () use ($id) {
                        $exists = QuizWinner::where('quiz_id', $id)->exists();
                        if ($exists) return;

                        $userStats = QuizQuestionUserAnswer::where('quiz_id', $id)
                            ->select([
                                'user_id',
                                DB::raw('COUNT(id) as total_q'),
                                DB::raw('SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as correct_q'),
                                DB::raw('SUM(COALESCE(total_time, 0)) as total_t')
                            ])
                            ->groupBy('user_id')
                            ->havingRaw('COUNT(id) > 0')
                            ->orderByDesc('correct_q')
                            ->orderBy('total_t', 'asc')
                            ->get();

                        if ($userStats->isNotEmpty()) {
                            $upsertRows = [];
                            $now = now();
                            foreach ($userStats as $idx => $uAns) {
                                $cCount = (int) $uAns->correct_q;
                                $tCount = (int) $uAns->total_q;
                                $tTime = round((float) $uAns->total_t, 3);
                                $pts = $cCount * 10;

                                $upsertRows[] = [
                                    'quiz_id' => $id,
                                    'user_id' => $uAns->user_id,
                                    'tenant_id' => 1,
                                    'total_question' => $tCount,
                                    'total_correct_answer' => $cCount,
                                    'total_time' => $tTime,
                                    'per_ans_average_time' => $tCount > 0 ? round($tTime / $tCount, 3) : 0,
                                    'rank' => $idx + 1,
                                    'reward' => $pts,
                                    'reward_type' => 'point',
                                    'reward_detail' => 'point type award',
                                    'status' => 'active',
                                    'created_at' => $now,
                                    'updated_at' => $now,
                                ];
                            }
                            // Bulk upsert to avoid thousands of individual updateOrCreate queries
                            foreach (array_chunk($upsertRows, 500) as $chunk) {
                                QuizWinner::upsert($chunk, ['quiz_id', 'user_id'], [
                                    'total_question', 'total_correct_answer', 'total_time',
                                    'per_ans_average_time', 'rank', 'reward', 'reward_type', 'updated_at'
                                ]);
                            }
                        }
                    });

                    $validAnswerUserIds = QuizQuestionUserAnswer::where('quiz_id', $id)->distinct()->pluck('user_id')->toArray();
                    $activeEnrolledUserIds = QuizUser::where('quiz_id', $id)->where('status', 'active')->pluck('user_id')->toArray();
                    $qualifyingUserIds = array_values(array_intersect($validAnswerUserIds, $activeEnrolledUserIds));

                    $winners = QuizWinner::where('quiz_id', $id)
                        ->where('total_question', '>=', 1)
                        ->whereIn('user_id', $qualifyingUserIds)
                        ->with('user:id,name')
                        ->orderByDesc('total_correct_answer')
                        ->orderBy('total_time', 'asc')
                        ->orderBy('rank', 'asc')
                        ->get();
                }

                return $winners->map(function ($w, $idx) {
                    $rewardVal = (float) ($w->reward ?? (($w->total_correct_answer ?? 0) * 10));
                    $name = $w->user?->name ?? 'Player #' . $w->user_id;
                    $score = (int) ($w->total_correct_answer ?? 0);
                    return [
                        'rank' => (int) ($idx + 1),
                        'user_id' => (int) $w->user_id,
                        'user_name' => $name,
                        'name' => $name,
                        'score' => $score,
                        'correct' => $score,
                        'total' => (int) ($w->total_question ?? 0),
                        'total_question' => (int) ($w->total_question ?? 0),
                        'reward' => $rewardVal,
                        'points' => $rewardVal,
                        'total_time' => (float) ($w->total_time ?? 0),
                        'status' => $w->status,
                    ];
                })->toArray();
            });

            $currentUserId = $user ? (is_object($user) ? $user->id : (int) $user) : null;
            $data = array_map(function ($row) use ($currentUserId) {
                $row['is_current_user'] = $currentUserId ? ($row['user_id'] == $currentUserId) : false;
                return $row;
            }, $cachedWinners);

            $userRank = null;
            if ($currentUserId) {
                $isEnrolled = QuizUser::where('quiz_id', $id)->where('user_id', $currentUserId)->where('status', 'active')->exists();
                $hasAnswers = QuizQuestionUserAnswer::where('quiz_id', $id)->where('user_id', $currentUserId)->exists();

                if ($isEnrolled && $hasAnswers) {
                    $userRank = Cache::remember("quiz_{$id}_user_{$currentUserId}_lb_rank", 15, function () use ($id, $currentUserId, $user) {
                        $userWinner = QuizWinner::where('quiz_id', $id)
                            ->where('user_id', $currentUserId)
                            ->where('total_question', '>=', 1)
                            ->with('user:id,name')
                            ->first();

                        if ($userWinner) {
                            $rewardVal = (float) ($userWinner->reward ?? (($userWinner->total_correct_answer ?? 0) * 10));
                            $userName = $userWinner->user?->name ?? (is_object($user) ? ($user->name ?? "Player #{$currentUserId}") : "Player #{$currentUserId}");
                            $score = (int) ($userWinner->total_correct_answer ?? 0);
                            $cCount = $score;
                            $tTime = (float) ($userWinner->total_time ?? 0);

                            $calcRank = QuizWinner::where('quiz_id', $id)
                                ->where('total_question', '>=', 1)
                                ->where(function ($q) use ($cCount, $tTime) {
                                    $q->where('total_correct_answer', '>', $cCount)
                                        ->orWhere(function ($q2) use ($cCount, $tTime) {
                                            $q2->where('total_correct_answer', $cCount)
                                                ->where('total_time', '<', $tTime);
                                        });
                                })->count() + 1;

                            return [
                                'rank' => (int) ($userWinner->rank ?: $calcRank),
                                'user_id' => (int) $currentUserId,
                                'user_name' => $userName,
                                'name' => $userName,
                                'score' => $score,
                                'correct' => $score,
                                'total_correct_answer' => $score,
                                'total_correct' => $score,
                                'total' => (int) ($userWinner->total_question ?? 0),
                                'total_question' => (int) ($userWinner->total_question ?? 0),
                                'reward' => $rewardVal,
                                'points' => $rewardVal,
                                'total_time' => $tTime,
                                'status' => $userWinner->status ?? 'active',
                                'is_current_user' => true,
                            ];
                        }

                        return null;
                    });
                }
            }

            return [
                'success' => true,
                'winners' => $data,
                'data' => $data,
                'current_user_rank' => $userRank,
                'user_rank' => $userRank
            ];
        } catch (\Throwable $e) {
            Log::error('getLeaderboardData error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => []
            ];
        }
    }

    public function getStatusFromSessionData(int $id): array
    {
        try {
            Cache::forget("quiz_{$id}_model");
        } catch (\Throwable $e) {}

        $quiz = Quiz::find($id);
        $pendingTime = 0;
        $nowMs = (int) (microtime(true) * 1000);
        $scheduledTimeMs = Cache::get("quiz_{$id}_counter_start_ms");

        if ($quiz && !($quiz instanceof \__PHP_Incomplete_Class)) {
            $sched = null;
            if (!empty($quiz->scheduled_at)) {
                $sched = Carbon::parse($quiz->scheduled_at);
            } elseif (!empty($quiz->availability_start)) {
                $sched = Carbon::parse($quiz->availability_start);
            }

            if ($sched) {
                $now = Carbon::now();
                if ($sched->gt($now)) {
                    $pendingTime = (int) $now->diffInSeconds($sched);
                }
                if (!$scheduledTimeMs) {
                    $scheduledTimeMs = (int) ($sched->getTimestamp() * 1000);
                }
            }
        }

        $status = QuizStatus::getStatusFromSession($id);

        return [
            'success' => true,
            'from_session' => session()->has("quiz_{$id}_status"),
            'pending_time' => $pendingTime,
            'server_now_ms' => $nowMs,
            'scheduled_time_ms' => $scheduledTimeMs,
            'status' => $status
        ];
    }

    public function clearQuizCacheAndSessionData(int $id): void
    {
        if (!$id) return;

        $userId = Auth::id() ?: (session('fsr_user_id') ?? null);
        if ($userId) {
            Session::forget("quiz_{$id}_user_{$userId}_user_answers");
            Cache::forget("quiz_{$id}_user_{$userId}_user_answers");
            Cache::forget("quiz_{$id}_user_{$userId}_anssheet");
            Cache::forget("quiz_{$id}_user_{$userId}_compiled_sheet_response");
            Cache::forget("quiz_{$id}_user_{$userId}_lb_rank");
            Cache::forget("quiz_{$id}_user_{$userId}_enrollment");
        }

        Session::forget("quiz_{$id}_counter_start_ms");
        Session::forget("quiz_{$id}_status");
        Session::forget("quiz_{$id}_user_answers");
        Session::forget("quiz_{$id}_answers");

        // NOTE: We NEVER purge global quiz questions, model, or RedisQuizService data here!
        // Doing so would wipe the active quiz for all other concurrent players.

        if (Session::get('active_quiz_id') == $id) {
            Session::forget('active_quiz_id');
        }
    }

    public function hasUserAnsweredLastQuestion($user, int $quizId): bool
    {
        $userId = is_object($user) ? ($user->id ?? null) : $user;
        if (!$userId) return false;

        // Primary source: check Redis user answer cache before querying MySQL database
        if (\App\Services\RedisQuizService::isAvailable()) {
            $redisAnswers = \App\Services\RedisQuizService::getUserAnswers($quizId, (int) $userId);
            if (!empty($redisAnswers)) {
                return true;
            }
        }

        $hasAnswers = QuizQuestionUserAnswer::where('quiz_id', $quizId)
            ->where('user_id', $userId)
            ->whereNotNull('answer')
            ->where('answer', '!=', '')
            ->exists();

        if ($hasAnswers) {
            return true;
        }

        return false;
    }

    public function markUserOutOfQuizData($user, int $id, string $reason): array
    {
        try {
            $userId = is_object($user) ? ($user->id ?? null) : $user;
            if (!$userId) {
                return [
                    'success' => false,
                    'status' => 401,
                    'message' => 'Unauthenticated user.',
                ];
            }

            // CRITICAL RULE: If user has answered 10 or last question, NEVER mark out under any condition!
            if ($this->hasUserAnsweredLastQuestion($userId, $id)) {
                QuizUserEnrollment::where('quiz_id', $id)
                    ->where('user_id', $userId)
                    ->update([
                        'out_status' => 'in',
                        'status'     => 'active',
                    ]);
                Session::put("quiz_{$id}_out_status", 'in');
                Session::forget("quiz_{$id}_out_remark");

                return [
                    'success' => true,
                    'status' => 200,
                    'message' => 'User has completed all quiz questions and cannot be marked out under any condition.',
                    'out_status' => 'in',
                    'remark' => 'Completed',
                ];
            }

            $rawReason = trim($reason);
            $cleanReason = $rawReason;
            if (stripos($rawReason, 'internet') !== false || stripos($rawReason, 'offline') !== false || stripos($rawReason, 'net off') !== false || stripos($rawReason, 'disconnected') !== false || stripos($rawReason, 'network') !== false || stripos($rawReason, 'connection') !== false || stripos($rawReason, 'wifi') !== false) {
                $cleanReason = 'Internet Off';
            } elseif (stripos($rawReason, 'minimize') !== false || stripos($rawReason, 'minimise') !== false || stripos($rawReason, 'switch') !== false || stripos($rawReason, 'tab') !== false || stripos($rawReason, 'visibility') !== false || stripos($rawReason, 'pagehide') !== false || stripos($rawReason, 'blur') !== false || stripos($rawReason, 'background') !== false) {
                $cleanReason = 'Screen Minimize';
            } elseif (stripos($rawReason, 'refresh') !== false || stripos($rawReason, 'reload') !== false || stripos($rawReason, 'f5') !== false) {
                $cleanReason = 'Screen Refresh';
            } elseif (stripos($rawReason, 'back') !== false || stripos($rawReason, 'popstate') !== false) {
                $cleanReason = 'Back Button';
            } elseif (stripos($rawReason, 'expire') !== false || stripos($rawReason, 'samay') !== false || stripos($rawReason, 'schedule') !== false || stripos($rawReason, 'passed') !== false || stripos($rawReason, 'missed') !== false || stripos($rawReason, 'time out') !== false || stripos($rawReason, 'timeout') !== false || stripos($rawReason, 'time ended') !== false) {
                $cleanReason = 'Schedule Time Passed';
            } elseif (stripos($rawReason, 'limit') !== false) {
                $cleanReason = 'Player limit exceeded';
            } elseif (empty($cleanReason) || $cleanReason === 'User exited quiz') {
                $cleanReason = 'Screen Minimize';
            }

            $quiz = Quiz::find($id);
            $tenantId = $quiz?->tenant_id ?? (is_object($user) ? ($user->tenant_id ?? 1) : 1);

            $quizUser = QuizUserEnrollment::where('quiz_id', $id)
                ->where('user_id', $userId)
                ->first();

            if ($quizUser) {
                $quizUser->update([
                    'out_status' => 'out',
                    'remark'     => $cleanReason,
                    'status'     => 'inactive',
                ]);
            } else {
                // CRITICAL RULE: User never joined this quiz! Never create a quiz_users record for unenrolled users.
                return [
                    'success' => false,
                    'status'  => 404,
                    'message' => 'User is not enrolled in this quiz.',
                ];
            }

            \App\Services\RedisQuizService::unenrollUser($id, (int) $userId);
            $this->clearQuizCacheAndSessionData($id);

            Session::put("quiz_{$id}_out_status", 'out');
            Session::put("quiz_{$id}_out_remark", $cleanReason);

            return [
                'success' => true,
                'status' => 200,
                'message' => 'User marked out of quiz successfully.',
                'out_status' => 'out',
                'remark' => $cleanReason,
            ];
        } catch (\Throwable $e) {
            Log::error("Quiz markUserOutOfQuizData failed for quiz {$id}, user {$userId}: " . $e->getMessage());
            return [
                'success' => false,
                'status' => 500,
                'message' => 'Failed to mark out: ' . $e->getMessage(),
            ];
        }
    }

    protected function updateAnsSheetSessionData($id, $user, $questionId, $finalSavedAnswer, $isCorrect, $totalTime, $questionData = null, $correctAnsStr = '')
    {
        try {
            $userId = is_object($user) ? ($user->id ?? null) : $user;
            if (!$userId) return;

            $userAnsKey = "quiz_{$id}_user_{$userId}_user_answers";
            $userAnswers = Cache::get($userAnsKey, Session::get($userAnsKey, []));
            $userAnswers[$questionId] = [
                'question_id' => $questionId,
                'answer'      => $finalSavedAnswer,
                'is_correct'  => $isCorrect,
                'total_time'  => $totalTime,
            ];
            // Store in Cache only - avoids PHP Session bloat for 50,000 concurrent players
            Cache::put($userAnsKey, $userAnswers, now()->addHours(2));

            if ($questionData) {
                $ansSheetKey = "quiz_{$id}_user_{$userId}_anssheet";
                $anssheet = Cache::get($ansSheetKey, Session::get($ansSheetKey, []));
                $qText = $questionData['question'] ?? '';
                if (is_array($qText)) {
                    $qText = $qText['question_text'] ?? $qText['question'] ?? $qText['title'] ?? implode(' ', $qText);
                }

                $anssheet[$questionId] = [
                    'question_id'    => $questionId,
                    'question'       => (string) $qText,
                    'hindi_question' => !empty($questionData['hindi_question']) ? (string) $questionData['hindi_question'] : null,
                    'question_audio' => $questionData['question_audio'] ?? null,
                    'media_url'      => $questionData['media_url'] ?? null,
                    'options'        => $questionData['options'] ?? [],
                    'correct_answer' => $correctAnsStr,
                    'user_answer'    => $finalSavedAnswer,
                    'is_correct'     => $isCorrect,
                    'total_time'     => (float) $totalTime,
                ];
                // Store in Cache only
                Cache::put($ansSheetKey, $anssheet, now()->addHours(2));
            }
        } catch (\Throwable $e) {
            Log::error('Quiz session/cache save error', [
                'user_id' => is_object($user) ? ($user->id ?? null) : $user,
                'quiz_id' => $id,
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function formatQuestionsArray($collection)
    {
        $formatted = [];
        foreach ($collection as $index => $q) {
            $options = $q->options;
            if (is_string($options)) {
                $options = json_decode($options, true) ?: [];
            }
            if (!is_array($options)) {
                $options = [];
            }

            $hindiOptions = $q->hindi_options;
            if (is_string($hindiOptions)) {
                $hindiOptions = json_decode($hindiOptions, true) ?: [];
            }
            if (empty($hindiOptions)) {
                $rel = $q->question;
                if (is_object($rel) && !empty($rel->hindi_options)) {
                    $hRel = $rel->hindi_options;
                    $hindiOptions = is_string($hRel) ? (json_decode($hRel, true) ?: []) : (is_array($hRel) ? $hRel : []);
                }
            }
            if (!is_array($hindiOptions)) {
                $hindiOptions = [];
            }

            $cleanOptions = [];
            $maxOptCount = max(count($options), count($hindiOptions));
            for ($optIdx = 0; $optIdx < $maxOptCount; $optIdx++) {
                $opt = $options[$optIdx] ?? '';
                $optText = is_array($opt) ? ($opt['option_text'] ?? $opt['text'] ?? $opt['label'] ?? '') : (string) $opt;
                $optText = trim(preg_replace('/[\x{00A0}\x{1680}\x{180E}\x{2000}-\x{200B}\x{202F}\x{205F}\x{3000}]/u', ' ', $optText));

                $hOpt = $hindiOptions[$optIdx] ?? '';
                $hOptText = is_array($hOpt) ? ($hOpt['option_text'] ?? $hOpt['text'] ?? $hOpt['label'] ?? '') : (string) $hOpt;
                $hOptText = trim(preg_replace('/[\x{00A0}\x{1680}\x{180E}\x{2000}-\x{200B}\x{202F}\x{205F}\x{3000}]/u', ' ', $hOptText));

                $hasH = ($hOptText !== '');
                $hasE = ($optText !== '');

                if ($hasH && $hasE && strcasecmp($optText, $hOptText) !== 0) {
                    $cleanOptions[] = $hOptText . ' (' . $optText . ')';
                } elseif ($hasH) {
                    $cleanOptions[] = $hOptText;
                } elseif ($hasE) {
                    $cleanOptions[] = $optText;
                }
            }

            $rawCorrect = $q->correct_answer;
            if ($rawCorrect === null || trim((string) $rawCorrect) === '') {
                $rawCorrect = $q->hindi_correct_answer;
            }
            if (($rawCorrect === null || trim((string) $rawCorrect) === '') && is_object($q->question)) {
                $rawCorrect = $q->question->correct_answer ?: $q->question->hindi_correct_answer;
            }
            if (($rawCorrect === null || trim((string) $rawCorrect) === '') && !empty($q->bot_correct_answer) && strtolower((string) $q->bot_correct_answer) !== 'no') {
                $rawCorrect = $q->bot_correct_answer;
            }

            $resolvedCorrect = '';
            if ($rawCorrect !== null && trim((string) $rawCorrect) !== '') {
                $resolvedCorrect = $this->resolveMatchingOptionStr($rawCorrect, $cleanOptions);
                if ($resolvedCorrect === '' && !empty($options)) {
                    $resolvedEng = $this->resolveMatchingOptionStr($rawCorrect, $options);
                    if ($resolvedEng !== '') {
                        $resolvedCorrect = $this->resolveMatchingOptionStr($resolvedEng, $cleanOptions);
                    }
                }
                if ($resolvedCorrect === '' && !empty($hindiOptions)) {
                    $resolvedHindi = $this->resolveMatchingOptionStr($rawCorrect, $hindiOptions);
                    if ($resolvedHindi !== '') {
                        $resolvedCorrect = $this->resolveMatchingOptionStr($resolvedHindi, $cleanOptions);
                    }
                }
                if ($resolvedCorrect === '') {
                    $resolvedCorrect = trim((string) $rawCorrect);
                }
            }

            if (count($cleanOptions) > 1) {
                shuffle($cleanOptions);
            }

            $qText = $q->question_text;
            $hindiText = $q->hindi_question;
            $questionAudio = $q->question_audio ?: $q->audio_file;
            $mediaUrl = $q->media_url;
            $qType = $q->type;

            if (empty($qText) || empty($hindiText) || empty($questionAudio) || empty($mediaUrl) || empty($qType)) {
                $rel = $q->question;
                if (is_string($rel)) {
                    if (empty($qText)) $qText = $rel;
                } elseif (is_object($rel)) {
                    if (empty($qText)) {
                        $qText = $rel->question_text ?? $rel->question ?? $rel->title ?? '';
                    }
                    if (empty($hindiText)) {
                        $hindiText = $rel->hindi_question ?? null;
                    }
                    if (empty($questionAudio)) {
                        $questionAudio = $rel->question_audio ?? $rel->audio_file ?? null;
                    }
                    if (empty($mediaUrl)) {
                        $mediaUrl = $rel->media_url ?? null;
                    }
                    if (empty($qType)) {
                        $qType = $rel->type ?? null;
                    }
                }
            }

            if (!empty($questionAudio)) {
                $questionAudio = trim((string) $questionAudio);
                $cleanPath = preg_replace('/^https?:\/\/[^\/]+\//i', '', $questionAudio);
                $cleanPath = ltrim($cleanPath, '/');
                $awsUrl = env('AWS_URL', 'https://chaloonline.s3.ap-south-1.amazonaws.com');
                if (!empty($awsUrl)) {
                    $questionAudio = rtrim($awsUrl, '/') . '/' . $cleanPath;
                } else {
                    $questionAudio = asset($questionAudio);
                }
            } else {
                $questionAudio = null;
            }

            if (!empty($mediaUrl)) {
                $mediaUrl = trim((string) $mediaUrl);
                $cleanPath = preg_replace('/^https?:\/\/[^\/]+\//i', '', $mediaUrl);
                $cleanPath = ltrim($cleanPath, '/');
                $awsUrl = env('AWS_URL', 'https://chaloonline.s3.ap-south-1.amazonaws.com');
                if (!empty($awsUrl)) {
                    $mediaUrl = rtrim($awsUrl, '/') . '/' . $cleanPath;
                } else {
                    $mediaUrl = asset($mediaUrl);
                }
            } else {
                $mediaUrl = null;
            }

            $formatted[] = [
                'id' => $q->id,
                'order' => $q->order ?? ($index + 1),
                'type' => $qType ?: 'mcq',
                'question' => (string) ($qText ?: ('Question ' . ($index + 1))),
                'hindi_question' => !empty($hindiText) ? (string) $hindiText : null,
                'question_audio' => $questionAudio,
                'media_url' => $mediaUrl,
                'options' => $cleanOptions,
                'correct_answer' => $resolvedCorrect,
            ];
        }
        return $formatted;
    }

    public function checkAnswersMatch(?string $ans1, ?string $ans2, array $options = []): bool
    {
        if ($ans1 === null || $ans2 === null) return false;

        $str1 = trim(preg_replace('/[\x{00A0}\x{1680}\x{180E}\x{2000}-\x{200B}\x{202F}\x{205F}\x{3000}]/u', ' ', (string) $ans1));
        $str2 = trim(preg_replace('/[\x{00A0}\x{1680}\x{180E}\x{2000}-\x{200B}\x{202F}\x{205F}\x{3000}]/u', ' ', (string) $ans2));

        if ($str1 === '' || $str2 === '') return false;

        $norm1 = preg_replace('/\s+/u', ' ', mb_strtolower($str1, 'UTF-8'));
        $norm2 = preg_replace('/\s+/u', ' ', mb_strtolower($str2, 'UTF-8'));

        if ($norm1 === $norm2) return true;

        $base1 = trim(preg_replace('/\s*\(.*?\)\s*$/u', '', $norm1));
        $base2 = trim(preg_replace('/\s*\(.*?\)\s*$/u', '', $norm2));
        if ($base1 !== '' && $base2 !== '' && $base1 === $base2) return true;

        preg_match('/\((.*?)\)/u', $norm1, $m1);
        preg_match('/\((.*?)\)/u', $norm2, $m2);
        $inner1 = isset($m1[1]) ? trim($m1[1]) : '';
        $inner2 = isset($m2[1]) ? trim($m2[1]) : '';

        if ($inner1 !== '' && $base2 !== '' && $inner1 === $base2) return true;
        if ($inner2 !== '' && $base1 !== '' && $inner2 === $base1) return true;
        if ($inner1 !== '' && $inner2 !== '' && $inner1 === $inner2) return true;
        if ($inner1 !== '' && $norm2 !== '' && $inner1 === $norm2) return true;
        if ($inner2 !== '' && $norm1 !== '' && $inner2 === $norm1) return true;

        if (!empty($options)) {
            $res1 = $this->resolveMatchingOptionStr($str1, $options);
            $res2 = $this->resolveMatchingOptionStr($str2, $options);
            if (!empty($res1) && !empty($res2)) {
                $normRes1 = preg_replace('/\s+/u', ' ', mb_strtolower($res1, 'UTF-8'));
                $normRes2 = preg_replace('/\s+/u', ' ', mb_strtolower($res2, 'UTF-8'));
                if ($normRes1 === $normRes2) return true;

                $bRes1 = trim(preg_replace('/\s*\(.*?\)\s*$/u', '', $normRes1));
                $bRes2 = trim(preg_replace('/\s*\(.*?\)\s*$/u', '', $normRes2));
                if ($bRes1 !== '' && $bRes2 !== '' && $bRes1 === $bRes2) return true;
            }
        }

        return false;
    }

    public function resolveMatchingOptionStr($targetStr, array $options): string
    {
        if ($targetStr === null || trim((string) $targetStr) === '') {
            return '';
        }

        $targetStr = trim(preg_replace('/[\x{00A0}\x{1680}\x{180E}\x{2000}-\x{200B}\x{202F}\x{205F}\x{3000}]/u', ' ', (string) $targetStr));
        if (empty($options)) {
            return $targetStr;
        }

        $stringOptions = [];
        foreach ($options as $k => $v) {
            if (is_array($v)) {
                $stringOptions[$k] = trim(preg_replace('/[\x{00A0}\x{1680}\x{180E}\x{2000}-\x{200B}\x{202F}\x{205F}\x{3000}]/u', ' ', (string) ($v['option_text'] ?? $v['text'] ?? $v['label'] ?? reset($v) ?? '')));
            } else {
                $stringOptions[$k] = trim(preg_replace('/[\x{00A0}\x{1680}\x{180E}\x{2000}-\x{200B}\x{202F}\x{205F}\x{3000}]/u', ' ', (string) $v));
            }
        }

        $normTarget = preg_replace('/\s+/u', ' ', mb_strtolower($targetStr, 'UTF-8'));

        // 1. Direct exact or case-insensitive match
        foreach ($stringOptions as $optStr) {
            $normOpt = preg_replace('/\s+/u', ' ', mb_strtolower($optStr, 'UTF-8'));
            if ($normTarget === $normOpt) {
                return $optStr;
            }
        }

        // 2. Option prefix stripped match (e.g. 'A. Option', '1. Option', '(A) Option')
        $cleanedPrefix = preg_replace('/^(?:(?:option|opt)[\s_]*[a-f\d]+[\s:\-\.]*|[a-f\d]+[\.\)\:\-]\s*|\([a-f\d]+\)\s*)/iu', '', $normTarget);
        $cleanedPrefix = trim($cleanedPrefix);
        if ($cleanedPrefix !== '' && $cleanedPrefix !== $normTarget) {
            foreach ($stringOptions as $optStr) {
                $normOpt = preg_replace('/\s+/u', ' ', mb_strtolower($optStr, 'UTF-8'));
                if ($cleanedPrefix === $normOpt) {
                    return $optStr;
                }
            }
        }

        // 3. Match option letters/keys: 'a', 'b', 'c', 'd', 'e', 'f'
        $cleanedLetter = preg_replace('/^(?:option|opt)[\s_]*/u', '', $normTarget);
        $letters = ['a' => 0, 'b' => 1, 'c' => 2, 'd' => 3, 'e' => 4, 'f' => 5];
        if (isset($letters[$cleanedLetter]) && isset($stringOptions[$letters[$cleanedLetter]])) {
            return $stringOptions[$letters[$cleanedLetter]];
        }

        // 4. Numeric index (0, 1, 2, 3...)
        if (is_numeric($cleanedLetter)) {
            $idx = (int) $cleanedLetter;
            if (isset($stringOptions[$idx])) {
                return $stringOptions[$idx];
            }
            if ($idx > 0 && isset($stringOptions[$idx - 1])) {
                return $stringOptions[$idx - 1];
            }
        }

        // 5. Check base (Hindi/English) and inner parenthetical matches
        $targetBase = trim(preg_replace('/\s*\(.*?\)\s*$/u', '', $targetStr));
        $normTargetBase = preg_replace('/\s+/u', ' ', mb_strtolower($targetBase, 'UTF-8'));

        preg_match('/\((.*?)\)/u', $targetStr, $tMatch);
        $targetInner = isset($tMatch[1]) ? trim($tMatch[1]) : '';
        $normTargetInner = preg_replace('/\s+/u', ' ', mb_strtolower($targetInner, 'UTF-8'));

        foreach ($stringOptions as $optStr) {
            $optBase = trim(preg_replace('/\s*\(.*?\)\s*$/u', '', $optStr));
            $normOptBase = preg_replace('/\s+/u', ' ', mb_strtolower($optBase, 'UTF-8'));

            preg_match('/\((.*?)\)/u', $optStr, $oMatch);
            $optInner = isset($oMatch[1]) ? trim($oMatch[1]) : '';
            $normOptInner = preg_replace('/\s+/u', ' ', mb_strtolower($optInner, 'UTF-8'));

            if (($normTarget !== '' && $normTarget === $normOptBase) ||
                ($normOptInner !== '' && $normTarget === $normOptInner) ||
                ($normTargetBase !== '' && $normTargetBase === $normOptBase) ||
                ($normTargetBase !== '' && $normOptInner !== '' && $normTargetBase === $normOptInner) ||
                ($normTargetInner !== '' && $normTargetInner === $normOptBase) ||
                ($normTargetInner !== '' && $normOptInner !== '' && $normTargetInner === $normOptInner) ||
                ($normTargetBase !== '' && $normOpt !== '' && $normTargetBase === $normOpt) ||
                ($normTargetInner !== '' && $normOpt !== '' && $normTargetInner === $normOpt)
            ) {
                return $optStr;
            }
        }

        return $targetStr;
    }
} 
