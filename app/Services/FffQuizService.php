<?php

namespace App\Services;

use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionUserAnswer;
use App\Models\QuizStatus;
use App\Models\QuizUser;
use App\Models\QuizWinner;
use App\Models\LeaderPointHistory;
use App\Models\User;
use App\Services\RedisQuizService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class FffQuizService
{
    /**
     * Get calculated FFF Quiz Expiry Time (Carbon)
     * quiz.schedule_time + 10 seconds (start counter) + 60 seconds (question time) = 70 seconds
     */
    public function getFffQuizExpiryTime(Quiz $quiz): ?Carbon
    {
        $scheduledAt = $quiz->scheduled_at ? Carbon::parse($quiz->scheduled_at) : ($quiz->availability_start ? Carbon::parse($quiz->availability_start) : null);
        if (!$scheduledAt) {
            return null;
        }
        if ($quiz->availability_end) {
            return Carbon::parse($quiz->availability_end)->copy()->addMinutes(10);
        }
        // At least 20 minutes from schedule time to prevent premature expiration
        return $scheduledAt->copy()->addMinutes(20);
    }

    /**
     * Check if FFF user has submitted answer
     */
    public function hasUserSubmittedFffAnswer($user, int $quizId): bool
    {
        $userId = is_object($user) ? ($user->id ?? null) : $user;
        if (!$userId) return false;

        if (RedisQuizService::isAvailable()) {
            $cachedAns = RedisQuizService::getUserAnswers($quizId, (int) $userId);
            if ($cachedAns !== null && !empty($cachedAns)) {
                return true;
            }
        }

        return QuizQuestionUserAnswer::where('quiz_id', $quizId)->where('user_id', $userId)->exists();
    }

    /**
     * Check if total answer submission time has expired for FFF user
     */
    public function isFffQuizExpired(Quiz $quiz, $user = null): bool
    {
        $expiryTime = $this->getFffQuizExpiryTime($quiz);
        if (!$expiryTime) {
            return false;
        }

        if (Carbon::now()->gte($expiryTime)) {
            if ($user) {
                $userId = is_object($user) ? ($user->id ?? null) : $user;
                if ($userId && $this->hasUserSubmittedFffAnswer($userId, (int) $quiz->id)) {
                    return false;
                }
            }
            return true;
        }

        return false;
    }

    /**
     * Join Fastest Finger First Quiz
     */
    public function joinFffQuiz($user, int $quizId): array
    {
        $quiz = Cache::remember("quiz_entity_{$quizId}", 60, function () use ($quizId) {
            return Quiz::find($quizId);
        });
        if (!$quiz) {
            return [
                'success' => false,
                'status'  => 404,
                'message' => 'Quiz not found.',
                'redirect' => route('fsrapp.home')
            ];
        }

        $isFinished = false;
        if ($quiz->complete_status === 'completed') {
            $isFinished = true;
        } elseif ($quiz->availability_end && Carbon::parse($quiz->availability_end)->isPast()) {
            $isFinished = true;
        } elseif (!$quiz->availability_end && $quiz->scheduled_at && Carbon::parse($quiz->scheduled_at)->addMinutes(15)->isPast()) {
            $isFinished = true;
        } elseif ($this->isFffQuizExpired($quiz, $user)) {
            $isFinished = true;
        }

        if ($isFinished) {
            return [
                'success'  => false,
                'status'   => 403,
                'message'  => '⚠️ Quiz scheduled time has passed. You cannot join this quiz.',
                'redirect' => route('fsrapp.home')
            ];
        }

        // 40-Minute Rule: Agar schedule time me 40 minute se jyada bacha hai toh koi user join nahi kar sakega
        if ($quiz->scheduled_at && Carbon::now()->diffInSeconds(Carbon::parse($quiz->scheduled_at), false) > 2400) {
            return [
                'success'  => false,
                'status'   => 403,
                'message'  => __('app.quiz_join_40min_warning'),
                'redirect' => route('fsrapp.home')
            ];
        }

        if ($user) {
            if (RedisQuizService::isAvailable()) {
                $isAlreadyEnrolled = RedisQuizService::isUserEnrolled($quizId, (int) $user->id);

                if (!$isAlreadyEnrolled) {
                    $isKnownEnrolled = Session::get("fff_quiz_{$quizId}_out_status") !== null;
                    if (!$isKnownEnrolled) {
                        $existing = QuizUser::where('quiz_id', $quizId)
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
                            }else{
                                $isKnownEnrolled = true;
                            }
                        }
                    }

                    if ($isKnownEnrolled) {
                        RedisQuizService::enrollUser($quizId, (int) $user->id);
                        Session::put("fff_quiz_{$quizId}_out_status", 'waiting');
                    } else {
                        if ($quiz->no_of_players > 0) {
                            $enrolledCount = RedisQuizService::getEnrolledPlayersCount($quizId);
                            $dbActiveCount = QuizUser::where('quiz_id', $quizId)->where('status', 'active')->count();
                            $effectiveActiveCount = max($enrolledCount, $dbActiveCount);

                            if ($effectiveActiveCount >= (int) $quiz->no_of_players) {
                                QuizUser::firstOrCreate(
                                    [
                                        'quiz_id'   => $quizId,
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
                        RedisQuizService::enrollUserWithQueue(
                            $quizId,
                            (int) $user->id,
                            (int) ($quiz->tenant_id ?? 1),
                            'active',
                            'waiting'
                        );

                        try {
                            QuizUser::firstOrCreate(
                                [
                                    'quiz_id'   => $quizId,
                                    'user_id'   => $user->id,
                                    'tenant_id' => $quiz->tenant_id ?? 1,
                                ],
                                [
                                    'status'     => 'active',
                                    'out_status' => 'waiting',
                                ]
                            );
                        } catch (\Throwable $e) {
                            Log::warning("joinFffQuiz QuizUser::firstOrCreate error: " . $e->getMessage());
                        }

                        if (isset($user->available_quiz_limit) && (int) $user->available_quiz_limit > 0) {
                            try {
                                User::where('id', $user->id)->decrement('available_quiz_limit', 1);
                                $user->refresh();
                            } catch (\Throwable $e) {}
                        }

                        Session::put("fff_quiz_{$quizId}_out_status", 'waiting');
                    }
                } else {
                    Session::put("fff_quiz_{$quizId}_out_status", 'waiting');
                }
            } else {
                $existing = QuizUser::where('quiz_id', $quizId)
                    ->where('user_id', $user->id)
                    ->first();

                $isCompleted = $this->hasUserSubmittedFffAnswer($user, $quizId);

                if ($existing && $existing->out_status === 'out' && $isCompleted) {
                    $existing->update(['out_status' => 'in', 'status' => 'active']);
                    Session::put("fff_quiz_{$quizId}_out_status", 'in');
                }

                $status = 'active';
                $remark = null;

                if ($existing) {
                    $status = $existing->status ?? 'active';
                    $remark = $existing->remark;
                } elseif ($quiz->no_of_players > 0) {
                    $activeCount = QuizUser::where('quiz_id', $quizId)
                        ->where('status', 'active')
                        ->count();

                    if ($activeCount >= (int) $quiz->no_of_players) {
                        $status = 'inactive';
                        $remark = 'Player limit exceeded';
                    }
                }

                $enrollment = QuizUser::withTrashed()
                    ->where('quiz_id', $quizId)
                    ->where('user_id', $user->id)
                    ->first();

                if ($enrollment) {
                    if ($enrollment->trashed()) {
                        $enrollment->restore();
                    }
                    $enrollment->update([
                        'status'     => $status,
                        'remark'     => $remark,
                        'out_status' => $status === 'active' ? 'waiting' : 'out',
                    ]);
                } else {
                    $enrollment = QuizUser::create([
                        'quiz_id'    => $quizId,
                        'user_id'    => $user->id,
                        'tenant_id'  => $quiz->tenant_id ?? 1,
                        'status'     => $status,
                        'remark'     => $remark,
                        'out_status' => $status === 'active' ? 'waiting' : 'out',
                    ]);
                }

                if ($enrollment->wasRecentlyCreated && $status === 'active') {
                    if (isset($user->available_quiz_limit) && (int) $user->available_quiz_limit > 0) {
                        try {
                            User::where('id', $user->id)->decrement('available_quiz_limit', 1);
                            if (method_exists($user, 'refresh')) {
                                $user->refresh();
                            }
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

        // Ensure QuizStatus record exists (cached to prevent 50k redundant DB queries)
        Cache::remember("fff_quiz_status_initialized_{$quizId}", 3600, function () use ($quizId, $quiz) {
            return QuizStatus::firstOrCreate(
                [
                    'quiz_id'   => $quizId,
                    'tenant_id' => $quiz->tenant_id ?? 1,
                ],
                [
                    'status' => 'pending',
                ]
            );
        });

        $questions = $this->preloadFffQuestionsToSession($quizId);
        Session::put('active_fff_quiz_id', $quizId);

        return [
            'success'         => true,
            'status'          => 200,
            'message'         => 'Joined Fastest Finger First quiz successfully',
            'questions_count' => count($questions),
            'redirect'        => route('fsrapp.fff.waiting', $quizId)
        ];
    }

    /**
     * Preload Fastest Finger First questions from quiz_question
     */
    public function preloadFffQuestionsToSession(int $quizId): array
    {
        $cacheKey = "fff_quiz_{$quizId}_questions";

        $questions = Cache::remember($cacheKey, 3600, function () use ($quizId) {
            $rawQuestions = QuizQuestion::where('quiz_id', $quizId)
                ->orderBy('order', 'asc')
                ->orderBy('id', 'asc')
                ->get();

            $formatted = [];
            foreach ($rawQuestions as $index => $q) {
                $optionsList = $this->parseOptions($q->options, $q->hindi_options);

                $formatted[] = [
                    'id'                   => $q->id,
                    'question_id'          => $q->question_id ?? $q->id,
                    'order'                => $q->order ?? ($index + 1),
                    'question_text'        => $q->question_text,
                    'hindi_question'       => $q->hindi_question,
                    'question_audio'       => $q->question_audio ?: $q->audio_file,
                    'media_url'            => $q->media_url,
                    'type'                 => $q->type ?? 'mcq',
                    'options'              => $optionsList,
                    'hindi_options'        => is_string($q->hindi_options) ? (json_decode($q->hindi_options, true) ?: []) : ($q->hindi_options ?: []),
                    'correct_answer'       => $q->correct_answer,
                    'hindi_correct_answer' => $q->hindi_correct_answer,
                    'explanation'          => $q->explanation,
                ];
            }
            return $formatted;
        });

        Session::put($cacheKey, $questions);

        if (RedisQuizService::isAvailable()) {
            RedisQuizService::cacheQuizQuestions($quizId, $questions);
        }

        return $questions;
    }

    /**
     * Helper to normalize options with bilingual support
     */
    public function parseOptions($optionsRaw, $hindiOptionsRaw = null): array
    {
        if (is_string($optionsRaw)) {
            $decoded = json_decode($optionsRaw, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $optionsRaw = $decoded;
            }
        }
        if (!is_array($optionsRaw)) {
            $optionsRaw = [];
        }

        if (is_string($hindiOptionsRaw)) {
            $decodedH = json_decode($hindiOptionsRaw, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $hindiOptionsRaw = $decodedH;
            }
        }
        if (!is_array($hindiOptionsRaw)) {
            $hindiOptionsRaw = [];
        }

        $list = [];
        $letters = ['A', 'B', 'C', 'D', 'E', 'F'];
        $optValues = array_values($optionsRaw);
        $hOptValues = array_values($hindiOptionsRaw);
        $maxCount = max(count($optValues), count($hOptValues));

        for ($idx = 0; $idx < $maxCount; $idx++) {
            $val = $optValues[$idx] ?? null;
            $hVal = $hOptValues[$idx] ?? null;

            $engText = '';
            $labelStr = '';

            if (is_array($val)) {
                $engText = $val['text'] ?? $val['option_text'] ?? $val['option'] ?? '';
                $labelStr = $val['label'] ?? $val['key'] ?? '';
            } elseif (is_object($val)) {
                $engText = $val->text ?? $val->option_text ?? $val->option ?? '';
                $labelStr = $val->label ?? $val->key ?? '';
            } elseif ($val !== null) {
                $engText = (string) $val;
            }

            $hindiText = '';
            if (is_array($hVal)) {
                $hindiText = $hVal['text'] ?? $hVal['option_text'] ?? $hVal['option'] ?? '';
                if (empty($labelStr)) {
                    $labelStr = $hVal['label'] ?? $hVal['key'] ?? '';
                }
            } elseif (is_object($hVal)) {
                $hindiText = $hVal->text ?? $hVal->option_text ?? $hVal->option ?? '';
                if (empty($labelStr)) {
                    $labelStr = $hVal->label ?? $hVal->key ?? '';
                }
            } elseif ($hVal !== null) {
                $hindiText = (string) $hVal;
            }

            $engText = trim(preg_replace('/[\x{00A0}\x{1680}\x{180E}\x{2000}-\x{200B}\x{202F}\x{205F}\x{3000}]/u', ' ', (string) $engText));
            $hindiText = trim(preg_replace('/[\x{00A0}\x{1680}\x{180E}\x{2000}-\x{200B}\x{202F}\x{205F}\x{3000}]/u', ' ', (string) $hindiText));

            // Check if engText itself is bilingual "Hindi (English)"
            if (empty($hindiText) && preg_match('/^(.*?)\s*\((.*?)\)$/u', $engText, $pm)) {
                $p1 = trim($pm[1]);
                $p2 = trim($pm[2]);
                $hasDev1 = (bool) preg_match('/[\x{0900}-\x{097F}]/u', $p1);
                $hasDev2 = (bool) preg_match('/[\x{0900}-\x{097F}]/u', $p2);
                if ($hasDev1 && !$hasDev2) {
                    $hindiText = $p1;
                    $engText = $p2;
                } elseif (!$hasDev1 && $hasDev2) {
                    $hindiText = $p2;
                    $engText = $p1;
                } else {
                    $hindiText = $p1;
                    $engText = $p2;
                }
            }

            if (empty($labelStr)) {
                $labelStr = $letters[$idx] ?? chr(65 + $idx);
            }

            $hasH = ($hindiText !== '');
            $hasE = ($engText !== '');

            $combinedText = '';
            if ($hasH && $hasE && strcasecmp($engText, $hindiText) !== 0) {
                $combinedText = $hindiText . ' (' . $engText . ')';
            } elseif ($hasH) {
                $combinedText = $hindiText;
            } else {
                $combinedText = $engText;
            }

            if ($combinedText === '') continue;

            $list[] = [
                'key'        => (string) $labelStr,
                'label'      => (string) $labelStr,
                'text'       => $combinedText,
                'english'    => $engText !== '' ? $engText : $combinedText,
                'hindi'      => $hindiText !== '' ? $hindiText : null,
                'hindi_text' => $hindiText !== '' ? $hindiText : null,
            ];
        }

        return $list;
    }

    /**
     * Get Questions for API
     */
    public function getFffQuestionsFromSession(int $quizId, $user = null): array
    {
        if (!$user && session('fsr_user_id')) {
            $user = User::find(session('fsr_user_id'));
        }

        $quiz = Cache::remember("quiz_entity_{$quizId}", 60, function () use ($quizId) {
            return Quiz::find($quizId);
        });
        if ($quiz && $this->isFffQuizExpired($quiz, $user)) {
            if ($user) {
                $this->markUserOut($user, $quizId, 'Schedule Time Passed');
            }
            return [
                'success'      => false,
                'status'       => 403,
                'out'          => true,
                'quiz_expired' => true,
                'message'      => '⚠️ Quiz answer submission time has expired.',
            ];
        }

        $sessionKey = "fff_quiz_{$quizId}_questions";
        $questions = Session::get($sessionKey);

        if (empty($questions)) {
            $questions = $this->preloadFffQuestionsToSession($quizId);
        }

        // Return without revealing correct_answer to frontend
        $safeQuestions = array_map(function ($q) {
            $copy = $q;
            unset($copy['correct_answer']);
            unset($copy['hindi_correct_answer']);
            unset($copy['explanation']);
            return $copy;
        }, $questions);

        $answeredQuestionIds = [];
        if ($user) {
            if (RedisQuizService::isAvailable()) {
                $cachedAns = RedisQuizService::getUserAnswers($quizId, (int) $user->id);
                if ($cachedAns !== null) {
                    $answeredQuestionIds = array_map(fn($a) => (int) ($a['question_id'] ?? 0), array_values($cachedAns));
                } else {
                    $answeredQuestionIds = QuizQuestionUserAnswer::where('quiz_id', $quizId)
                        ->where('user_id', $user->id)
                        ->pluck('question_id')
                        ->map(fn($id) => (int) $id)
                        ->toArray();
                }
            } else {
                $answeredQuestionIds = QuizQuestionUserAnswer::where('quiz_id', $quizId)
                    ->where('user_id', $user->id)
                    ->pluck('question_id')
                    ->map(fn($id) => (int) $id)
                    ->toArray();
            }
        }

        $answeredSet = array_flip($answeredQuestionIds);
        $nextIndex = 0;
        foreach ($safeQuestions as $idx => $q) {
            $qId = (int) ($q['id'] ?? 0);
            if (isset($answeredSet[$qId])) {
                $nextIndex = $idx + 1;
            } else {
                $nextIndex = $idx;
                break;
            }
        }

        return [
            'success'               => true,
            'data'                  => $safeQuestions,
            'answered_question_ids' => $answeredQuestionIds,
            'answered_count'        => count($answeredQuestionIds),
            'next_index'            => $nextIndex,
            'is_all_answered'       => (count($safeQuestions) > 0 && $nextIndex >= count($safeQuestions)),
        ];
    }

    /**
     * Submit Single Question Answer Async
     */
    public function submitFffAnswer($user, int $quizId, array $data): array
    {
        if (!$user && session('fsr_user_id')) {
            $user = User::find(session('fsr_user_id'));
        }

        if (!$user) {
            return [
                'success' => false,
                'status'  => 401,
                'message' => 'User not authenticated',
            ];
        }

        $quiz = Cache::remember("quiz_model_{$quizId}", 120, function () use ($quizId) {
            return Quiz::find($quizId);
        });
        if (!$quiz || !($quiz instanceof Quiz)) {
            Cache::forget("quiz_model_{$quizId}");
            $quiz = Cache::remember("quiz_entity_{$quizId}", 60, function () use ($quizId) {
            return Quiz::find($quizId);
        });
        }
        if (!$quiz) {
            return [
                'success' => false,
                'status'  => 404,
                'message' => 'Quiz not found',
            ];
        }

        // Support batch submissions (e.g. 10-second batch containing multiple answers)
        if (!empty($data['answers']) && is_array($data['answers'])) {
            return $this->reconcileFffAnswers($user, $quizId, $data['answers']);
        }
        if (!empty($data['client_answers']) && is_array($data['client_answers'])) {
            return $this->reconcileFffAnswers($user, $quizId, $data['client_answers']);
        }

        $scheduledAt = !empty($quiz->scheduled_at) ? Carbon::parse($quiz->scheduled_at) : (!empty($quiz->availability_start) ? Carbon::parse($quiz->availability_start) : null);
        if ($scheduledAt) {
            $nowMs = (int) (microtime(true) * 1000);
            $scheduledTimeMs = (int) ($scheduledAt->getTimestamp() * 1000);
            $minSubmitTimeMs = $scheduledTimeMs + 11000; // schedule_time + 10s start counter + 1s = 11s
            if ($nowMs < $minSubmitTimeMs) {
                return [
                    'success' => false,
                    'status'  => 422,
                    'message' => '⚠️ Quiz start nahi hua hai. Answer schedule time + 11 seconds ke baad hi submit ho sakta hai.',
                ];
            }
        }

        if ($this->isFffQuizExpired($quiz, $user)) {
            $this->markUserOut($user, $quizId, 'Schedule Time Passed');
            return [
                'success'      => false,
                'status'       => 403,
                'quiz_expired' => true,
                'out_status'   => 'out',
                'remark'       => 'Schedule Time Passed',
                'message'      => '⚠️ Quiz answer submission time has expired.',
            ];
        }

        $questionId = (int) ($data['question_id'] ?? 0);
        $submittedAnswer = trim((string) ($data['answer'] ?? ''));
        $totalTime = (float) ($data['total_time'] ?? 0.0); // Elapsed seconds on this question

        $cachedQuestions = Cache::remember("fff_quiz_{$quizId}_questions_raw_map", 3600, function () use ($quizId) {
            return QuizQuestion::where('quiz_id', $quizId)->get();
        });

        $question = $cachedQuestions->firstWhere('id', $questionId) ?: $cachedQuestions->firstWhere('question_id', $questionId);

        if (!$question) {
            return [
                'success' => false,
                'status'  => 404,
                'message' => 'Question not found in Fastest Finger First bank.',
            ];
        }

        $canonicalQuestionId = (int) $question->id;

        $cleanSubmitted = trim((string) $submittedAnswer);
        $cleanCorrect = trim((string) $question->correct_answer);
        $cleanHindiCorrect = trim((string) $question->hindi_correct_answer);

        $isCorrect = false;
        if ($cleanSubmitted !== '') {
            if (strcasecmp($cleanSubmitted, $cleanCorrect) === 0 || ($cleanHindiCorrect !== '' && strcasecmp($cleanSubmitted, $cleanHindiCorrect) === 0)) {
                $isCorrect = true;
            }

            if (!$isCorrect) {
                // Check against parsed options
                $opts = $this->parseOptions($question->options, $question->hindi_options);
                
                $matchedSubmittedIndex = null;
                $matchedCorrectIndex = null;

                $subLower = strtolower($cleanSubmitted);
                $subBase = strtolower(trim(preg_replace('/\s*\(.*?\)\s*$/u', '', $subLower)));

                $corLower = strtolower($cleanCorrect);
                $corHindiLower = strtolower($cleanHindiCorrect);
                $corBase = strtolower(trim(preg_replace('/\s*\(.*?\)\s*$/u', '', $corLower)));

                foreach ($opts as $idx => $opt) {
                    $optKey = strtolower($opt['key'] ?? '');
                    $optLabel = strtolower($opt['label'] ?? '');
                    $optText = strtolower(trim($opt['text'] ?? ''));
                    $optEng = strtolower(trim($opt['english'] ?? ''));
                    $optHindi = strtolower(trim($opt['hindi'] ?? ''));
                    $optBase = strtolower(trim(preg_replace('/\s*\(.*?\)\s*$/u', '', $optText)));

                    if ($subLower === $optKey || $subLower === $optLabel || $subLower === $optText || $subLower === $optEng || ($optHindi !== '' && $subLower === $optHindi) || ($subBase !== '' && ($subBase === $optBase || $subBase === $optEng || $subBase === $optHindi))) {
                        $matchedSubmittedIndex = $idx;
                    }

                    if ($corLower === $optKey || $corLower === $optLabel || $corLower === $optText || $corLower === $optEng || ($optHindi !== '' && ($corLower === $optHindi || $corHindiLower === $optHindi)) || ($corBase !== '' && ($corBase === $optBase || $corBase === $optEng || $corBase === $optHindi))) {
                        $matchedCorrectIndex = $idx;
                    }
                }

                if ($matchedSubmittedIndex !== null && $matchedCorrectIndex !== null && $matchedSubmittedIndex === $matchedCorrectIndex) {
                    $isCorrect = true;
                }
            }
        }

        $now = now();
        $showTime = isset($data['show_time']) ? Carbon::parse($data['show_time'])->format('H:i:s') : $now->format('H:i:s');
        $ansTime = isset($data['ans_time']) ? Carbon::parse($data['ans_time'])->format('H:i:s.v') : $now->format('H:i:s.v');

        $tenantId = $quiz->tenant_id ?? 1;

        // High-Speed Redis in-memory answer recording & pipeline
        if (RedisQuizService::isAvailable()) {
            $redisRes = RedisQuizService::recordAnswer(
                $quizId,
                (int) $user->id,
                $canonicalQuestionId,
                $submittedAnswer,
                $isCorrect,
                $totalTime,
                ['id' => $canonicalQuestionId],
                $cleanCorrect,
                $tenantId,
                $showTime
            );

            if (!empty($redisRes['already_answered'])) {
                return [
                    'success'    => true,
                    'is_correct' => $isCorrect,
                    'message'    => 'You have already answered this question',
                ];
            }
        }

        // Direct DB upsert to guarantee MySQL persistence without relying solely on async queue
        try {
            QuizQuestionUserAnswer::upsert(
                [
                    [
                        'tenant_id'   => $tenantId,
                        'quiz_id'     => $quizId,
                        'question_id' => $canonicalQuestionId,
                        'user_id'     => $user->id,
                        'answer'      => $submittedAnswer,
                        'status'      => $isCorrect ? 1 : 0,
                        'show_time'   => $showTime,
                        'ans_time'    => $ansTime,
                        'total_time'  => $totalTime,
                        'created_at'  => $now->toDateTimeString(),
                        'updated_at'  => $now->toDateTimeString(),
                    ]
                ],
                ['tenant_id', 'quiz_id', 'user_id', 'question_id'],
                ['answer', 'status', 'show_time', 'ans_time', 'total_time', 'updated_at']
            );
        } catch (\Throwable $e) {
            Log::error("submitFffAnswer DB upsert error: " . $e->getMessage());
        }

        // Ensure user status is active (cached to prevent locking quiz_users on every question)
        $userActiveCacheKey = "fff_quiz_{$quizId}_user_{$user->id}_active";
        if (Session::get("fff_quiz_{$quizId}_out_status") !== 'in' && !Cache::has($userActiveCacheKey)) {
            try {
                QuizUser::where('quiz_id', $quizId)
                    ->where('user_id', $user->id)
                    ->update([
                        'out_status' => 'in',
                        'status'     => 'active',
                    ]);
                Cache::put($userActiveCacheKey, true, 3600);
            } catch (\Throwable $e) {}
        }

        Session::put("fff_quiz_{$quizId}_out_status", 'in');
        Session::forget("fff_quiz_{$quizId}_out_remark");

        // Invalidate cached answer sheet response so every fresh submission is reflected immediately
        Cache::forget("fff_quiz_{$quizId}_user_{$user->id}_compiled_sheet_response");

        return [
            'success'    => true,
            'is_correct' => $isCorrect,
            'message'    => 'Answer recorded successfully',
        ];
    }

    /**
     * Reconcile FFF answers in bulk from client-side or session directly to quiz_question_user_answers
     */
    public function reconcileFffAnswers($user, int $quizId, array $clientAnswers = []): array
    {
        if (!$user && session('fsr_user_id')) {
            $user = User::find(session('fsr_user_id'));
        }

        if (!$user) {
            return ['success' => false, 'message' => 'User not found'];
        }

        $quiz = Cache::remember("quiz_model_{$quizId}", 120, function () use ($quizId) {
            return Quiz::find($quizId);
        });
        if (!$quiz || !($quiz instanceof Quiz)) {
            Cache::forget("quiz_model_{$quizId}");
            $quiz = Cache::remember("quiz_entity_{$quizId}", 60, function () use ($quizId) {
            return Quiz::find($quizId);
        });
        }
        if (!$quiz) {
            return ['success' => false, 'message' => 'Quiz not found'];
        }

        $scheduledAt = !empty($quiz->scheduled_at) ? Carbon::parse($quiz->scheduled_at) : (!empty($quiz->availability_start) ? Carbon::parse($quiz->availability_start) : null);
        if ($scheduledAt) {
            $nowMs = (int) (microtime(true) * 1000);
            $scheduledTimeMs = (int) ($scheduledAt->getTimestamp() * 1000);
            $minSubmitTimeMs = $scheduledTimeMs + 11000; // schedule_time + 10s start counter + 1s = 11s
            if ($nowMs < $minSubmitTimeMs) {
                return [
                    'success' => false,
                    'status'  => 422,
                    'message' => '⚠️ Quiz start nahi hua hai. Answer schedule time + 11 seconds ke baad hi submit ho sakta hai.',
                ];
            }
        }

        if ($this->isFffQuizExpired($quiz, $user)) {
            return [
                'success'      => false,
                'status'       => 403,
                'out'          => true,
                'quiz_expired' => true,
                'message'      => '⚠️ Quiz answer submission time has expired.',
            ];
        }

        if (empty($clientAnswers)) {
            return [
                'success'          => true,
                'reconciled_count' => 0,
                'total_answers'    => 0,
                'message'          => 'No pending answers to reconcile'
            ];
        }

        $tenantId = $quiz->tenant_id ?? 1;
        $allQuestions = $this->preloadFffQuestionsToSession($quizId);
        $questionsMap = collect($allQuestions)->keyBy('id');
        $questionsByMasterId = collect($allQuestions)->keyBy('question_id');

        // Existing answers: check Redis first if available
        $existingAnswers = collect();
        if (RedisQuizService::isAvailable()) {
            $cachedAns = RedisQuizService::getUserAnswers($quizId, (int) $user->id);
            if (!empty($cachedAns)) {
                $existingAnswers = collect($cachedAns)->keyBy('question_id');
            }
        }

        if ($existingAnswers->isEmpty()) {
            $existingAnswers = QuizQuestionUserAnswer::where('quiz_id', $quizId)
                ->where('user_id', $user->id)
                ->get()
                ->keyBy('question_id');
        }

        // Early return if all questions are already answered in database
        if ($existingAnswers->count() >= count($allQuestions) || empty($clientAnswers)) {
            return [
                'success'          => true,
                'reconciled_count' => 0,
                'total_answers'    => $existingAnswers->count()
            ];
        }

        $reconciledCount = 0;
        $now = now();

        foreach ($clientAnswers as $qId => $ansData) {
            $inputQuestionId = is_numeric($qId) ? (int) $qId : (int) ($ansData['question_id'] ?? 0);
            if (!$inputQuestionId) {
                continue;
            }

            // Find question object by id or master question_id
            $qObj = $questionsMap->get($inputQuestionId) ?? $questionsByMasterId->get($inputQuestionId);
            if (!$qObj) {
                continue;
            }

            $canonicalQuestionId = (int) $qObj['id'];
            if ($existingAnswers->has($canonicalQuestionId)) {
                continue;
            }

            $submittedAnswer = is_array($ansData) ? trim((string) ($ansData['answer'] ?? '')) : trim((string) $ansData);
            if ($submittedAnswer === '') continue;

            $totalTime = is_array($ansData) ? (float) ($ansData['total_time'] ?? 0.0) : 0.0;
            $showTime = (is_array($ansData) && isset($ansData['show_time'])) ? Carbon::parse($ansData['show_time'])->format('H:i:s') : now()->format('H:i:s');
            $ansTime = (is_array($ansData) && isset($ansData['ans_time'])) ? Carbon::parse($ansData['ans_time'])->format('H:i:s.v') : now()->format('H:i:s.v');

            $cleanCorrect = trim((string) ($qObj['correct_answer'] ?? ''));
            $cleanHindiCorrect = trim((string) ($qObj['hindi_correct_answer'] ?? ''));

            $isCorrect = false;
            if (strcasecmp($submittedAnswer, $cleanCorrect) === 0 || ($cleanHindiCorrect !== '' && strcasecmp($submittedAnswer, $cleanHindiCorrect) === 0)) {
                $isCorrect = true;
            }

            if (!$isCorrect && $qObj) {
                $opts = $this->parseOptions($qObj['options'] ?? [], $qObj['hindi_options'] ?? []);
                $matchedSubmittedIndex = null;
                $matchedCorrectIndex = null;
                $subLower = strtolower($submittedAnswer);
                $subBase = strtolower(trim(preg_replace('/\s*\(.*?\)\s*$/u', '', $subLower)));
                $corLower = strtolower($cleanCorrect);
                $corHindiLower = strtolower($cleanHindiCorrect);
                $corBase = strtolower(trim(preg_replace('/\s*\(.*?\)\s*$/u', '', $corLower)));

                foreach ($opts as $idx => $opt) {
                    $optKey = strtolower($opt['key'] ?? '');
                    $optLabel = strtolower($opt['label'] ?? '');
                    $optText = strtolower(trim($opt['text'] ?? ''));
                    $optEng = strtolower(trim($opt['english'] ?? ''));
                    $optHindi = strtolower(trim($opt['hindi'] ?? ''));
                    $optBase = strtolower(trim(preg_replace('/\s*\(.*?\)\s*$/u', '', $optText)));

                    if ($subLower === $optKey || $subLower === $optLabel || $subLower === $optText || $subLower === $optEng || ($optHindi !== '' && $subLower === $optHindi) || ($subBase !== '' && ($subBase === $optBase || $subBase === $optEng || $subBase === $optHindi))) {
                        $matchedSubmittedIndex = $idx;
                    }

                    if ($corLower === $optKey || $corLower === $optLabel || $corLower === $optText || $corLower === $optEng || ($optHindi !== '' && ($corLower === $optHindi || $corHindiLower === $optHindi)) || ($corBase !== '' && ($corBase === $optBase || $corBase === $optEng || $corBase === $optHindi))) {
                        $matchedCorrectIndex = $idx;
                    }
                }

                if ($matchedSubmittedIndex !== null && $matchedCorrectIndex !== null && $matchedSubmittedIndex === $matchedCorrectIndex) {
                    $isCorrect = true;
                }
            }

            if (RedisQuizService::isAvailable()) {
                RedisQuizService::recordAnswer(
                    $quizId,
                    (int) $user->id,
                    $canonicalQuestionId,
                    $submittedAnswer,
                    $isCorrect,
                    $totalTime,
                    ['id' => $canonicalQuestionId],
                    $cleanCorrect,
                    $tenantId,
                    $showTime,
                    false
                );
            }

            $rowsToUpsert[] = [
                'tenant_id'   => $tenantId,
                'quiz_id'     => $quizId,
                'question_id' => $canonicalQuestionId,
                'user_id'     => $user->id,
                'answer'      => $submittedAnswer,
                'status'      => $isCorrect ? 1 : 0,
                'show_time'   => $showTime,
                'ans_time'    => $ansTime,
                'total_time'  => $totalTime,
                'created_at'  => $now->toDateTimeString(),
                'updated_at'  => $now->toDateTimeString(),
            ];

            $existingAnswers->put($canonicalQuestionId, true);
        }

        if (!empty($rowsToUpsert)) {
            // ALWAYS upsert directly into MySQL to guarantee zero data loss
            try {
                QuizQuestionUserAnswer::upsert(
                    $rowsToUpsert,
                    ['tenant_id', 'quiz_id', 'user_id', 'question_id'],
                    ['answer', 'status', 'show_time', 'ans_time', 'total_time', 'updated_at']
                );
                $reconciledCount = count($rowsToUpsert);
            } catch (\Throwable $e) {
                Log::error("reconcileFffAnswers bulk upsert error: " . $e->getMessage());
            }
        }

        // Clean up unused Redis data for this user now that all answers are permanently in MySQL
        if (RedisQuizService::isAvailable() && $user) {
            RedisQuizService::cleanupUserReconciledData((int) $quizId, (int) $user->id);
        }

        if ($reconciledCount > 0) {
            try {
                QuizUser::firstOrCreate(
                    [
                        'quiz_id'   => $quizId,
                        'user_id'   => $user->id,
                        'tenant_id' => $tenantId,
                    ],
                    [
                        'status'     => 'active',
                        'out_status' => 'in',
                    ]
                );
            } catch (\Throwable $e) {}

            try {
                QuizUser::where('quiz_id', $quizId)
                    ->where('user_id', $user->id)
                    ->where('out_status', '!=', 'out')
                    ->where(function ($q) {
                        $q->whereNull('out_status')->orWhere('out_status', '!=', 'in');
                    })
                    ->update(['out_status' => 'in', 'status' => 'active']);
                Session::put("fff_quiz_{$quizId}_out_status", 'in');
            } catch (\Throwable $e) {}

            // Invalidate cached answer sheet response so updated data is served immediately
            Cache::forget("fff_quiz_{$quizId}_user_{$user->id}_compiled_sheet_response");
        }

        return [
            'success'          => true,
            'reconciled_count' => $reconciledCount,
            'total_answers'    => $existingAnswers->count() + $reconciledCount,
        ];
    }

    /**
     * Compile Answer Sheet Data for user
     */
    public function getFffAnswerSheetData($user, int $quizId, array $clientAnswers = []): array
    {
        if (!$user && session('fsr_user_id')) {
            $user = User::find(session('fsr_user_id'));
        }

        $userSheetCacheKey = $user ? "fff_quiz_{$quizId}_user_{$user->id}_compiled_sheet_response" : null;
        if ($userSheetCacheKey && empty($clientAnswers)) {
            $cached = Cache::get($userSheetCacheKey);
            if (!empty($cached) && is_array($cached) && !empty($cached['data'])) {
                $cachedAttempted = $cached['data']['total_attempted'] ?? 0;
                $dbAnswersCount = QuizQuestionUserAnswer::where('quiz_id', $quizId)->where('user_id', $user->id)->count();
                // Only use cache if quiz is fully completed AND cached attempts match or exceed DB count
                $quizModel = Quiz::find($quizId);
                if ($quizModel && $quizModel->complete_status === 'completed' && $cachedAttempted >= $dbAnswersCount) {
                    return $cached;
                } else {
                    Cache::forget($userSheetCacheKey);
                }
            }
        }

        if (!empty($clientAnswers) && $user) {
            try {
                $this->reconcileFffAnswers($user, $quizId, $clientAnswers);
            } catch (\Throwable $e) {}
        }

        $quiz = Cache::remember("quiz_entity_{$quizId}", 60, function () use ($quizId) {
            return Quiz::find($quizId);
        });
        if (!$quiz) {
            return ['success' => false, 'message' => 'Quiz not found'];
        }

        $hasAnswers = $user ? QuizQuestionUserAnswer::where('quiz_id', $quizId)->where('user_id', $user->id)->exists() : false;
        if (!$hasAnswers && $this->isFffQuizExpired($quiz, $user)) {
            return [
                'success'              => false,
                'status'               => 403,
                'out'                  => true,
                'quiz_expired'         => true,
                'message'              => '⚠️ Quiz answer submission time has expired. You did not submit answers.',
                'data'                 => [],
                'total_questions'      => 0,
                'total_correct_answer' => 0,
                'total_time'           => 0,
                'score'                => 0,
            ];
        }

        $dbAnswers = QuizQuestionUserAnswer::where('quiz_id', $quizId)
            ->where('user_id', $user ? $user->id : 0)
            ->get();

        $userAnswers = $dbAnswers;
        if ($user && RedisQuizService::isAvailable()) {
            $redisAnswers = RedisQuizService::getUserAnswers($quizId, (int) $user->id);
            if (!empty($redisAnswers)) {
                $dbKeyed = $dbAnswers->keyBy('question_id');
                foreach ($redisAnswers as $item) {
                    $qId = $item['question_id'] ?? null;
                    if ($qId && !$dbKeyed->has($qId)) {
                        $dbKeyed->put($qId, (object) [
                            'question_id' => $qId,
                            'answer'      => $item['answer'] ?? null,
                            'status'      => !empty($item['is_correct']) ? 1 : ($item['status'] ?? 0),
                            'total_time'  => $item['total_time'] ?? 0,
                        ]);
                    }
                }
                $userAnswers = $dbKeyed->values();
            }
        }

        $allQuestions = QuizQuestion::where('quiz_id', $quizId)
            ->orderBy('order', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $answersMap = $userAnswers->keyBy('question_id');
        $sheetQuestions = [];
        $totalCorrect = 0;
        $totalTime = 0.0;
        $totalAttempted = 0;

        foreach ($allQuestions as $index => $q) {
            $ans = $answersMap->get($q->id) ?? ($q->question_id ? $answersMap->get($q->question_id) : null);
            $hasAttempted = !empty($ans) && ($ans->answer !== null && $ans->answer !== '');
            
            $isCorrect = false;
            $qTime = 0.0;
            $userAnswer = null;

            if ($hasAttempted) {
                $totalAttempted++;
                $userAnswer = $ans->answer;
                $isCorrect = (bool) $ans->status;

                if (!$isCorrect && $userAnswer) {
                    $cleanU = trim((string) $userAnswer);
                    $cleanC = trim((string) $q->correct_answer);
                    $cleanHC = trim((string) $q->hindi_correct_answer);

                    if (strcasecmp($cleanU, $cleanC) === 0 || ($cleanHC !== '' && strcasecmp($cleanU, $cleanHC) === 0)) {
                        $isCorrect = true;
                    } else {
                        $uBase = trim(preg_replace('/\s*\(.*?\)\s*$/u', '', strtolower($cleanU)));
                        $cBase = trim(preg_replace('/\s*\(.*?\)\s*$/u', '', strtolower($cleanC)));
                        $hcBase = trim(preg_replace('/\s*\(.*?\)\s*$/u', '', strtolower($cleanHC)));
                        if (($uBase && $cBase && $uBase === $cBase) || ($uBase && $hcBase && $uBase === $hcBase)) {
                            $isCorrect = true;
                        }
                    }
                }

                if ($isCorrect) $totalCorrect++;
                $qTime = (float) ($ans->total_time ?? 0.0);
                $totalTime += $qTime;
            }

            $sheetQuestions[] = [
                'id'                   => $q->id,
                'question_id'          => $q->question_id ?? $q->id,
                'order'                => $q->order ?? ($index + 1),
                'question_text'        => $q->question_text,
                'hindi_question'       => $q->hindi_question,
                'question_audio'       => $q->question_audio ?? null,
                'media_url'            => $q->media_url ?? null,
                'options'              => $this->parseOptions($q->options, $q->hindi_options),
                'hindi_options'        => is_string($q->hindi_options) ? (json_decode($q->hindi_options, true) ?: []) : ($q->hindi_options ?: []),
                'user_answer'          => $userAnswer,
                'correct_answer'       => $q->correct_answer,
                'hindi_correct_answer' => $q->hindi_correct_answer,
                'is_correct'           => $isCorrect,
                'is_attempted'         => $hasAttempted,
                'explanation'          => $q->explanation,
                'time_taken'           => round($qTime, 2),
                'total_time'           => round($qTime, 2),
            ];
        }

        if ($quiz->complete_status !== 'processing' && $quiz->complete_status !== 'completed') {
            try {
                Cache::lock("quiz_{$quizId}_mark_processing_lock", 10)->get(function () use ($quiz, $quizId) {
                    $freshQuiz = Quiz::find($quizId);
                    if ($freshQuiz && $freshQuiz->complete_status !== 'processing' && $freshQuiz->complete_status !== 'completed') {
                        $freshQuiz->update(['complete_status' => 'processing']);
                        $scheduledAt = $freshQuiz->availability_end ? Carbon::parse($freshQuiz->availability_end) : now();
                        \App\Models\QuizCronJobHistory::firstOrCreate(
                            [
                                'quiz_id' => $quizId,
                                'cron_type' => 'quiz_winner_leaderboard',
                            ],
                            [
                                'tenant_id' => $freshQuiz->tenant_id ?? 1,
                                'scheduled_at' => $scheduledAt,
                                'status' => 'pending',
                            ]
                        );
                    }
                });
            } catch (\Throwable $e) {}
        }

        $res = [
            'success' => true,
            'data'    => [
                'quiz_id'         => $quizId,
                'quiz_title'      => $quiz->title ?? 'Fastest Finger First',
                'total_attempted' => $totalAttempted,
                'total_questions' => $allQuestions->count(),
                'total_correct'   => $totalCorrect,
                'total_time'      => round($totalTime, 2),
                'questions'       => $sheetQuestions,
            ]
        ];

        if ($userSheetCacheKey && !empty($sheetQuestions) && $quiz->complete_status === 'completed') {
            Cache::put($userSheetCacheKey, $res, now()->addMinutes(10));
        }

        return $res;
    }

    /**
     * Process Fastest Finger First winners, leaderboard, and points directly in main tables
     */
    public function processFffQuizResult(int $quizId): array
    {
        try {
            $quiz = Cache::remember("quiz_entity_{$quizId}", 60, function () use ($quizId) {
            return Quiz::find($quizId);
        });
            if (!$quiz) {
                return ['success' => false, 'message' => 'Quiz not found'];
            }

            $tenantId = $quiz->tenant_id ?? 1;

            if (RedisQuizService::isAvailable()) {
                try {
                    RedisQuizService::persistQuizDataToMySQL($quizId);
                } catch (\Throwable $e) {}
            }

            // Step 1: Calculate user scores from QuizQuestionUserAnswer
            $rawStats = QuizQuestionUserAnswer::where('quiz_id', $quizId)
                ->where('tenant_id', $tenantId)
                ->select(
                    'tenant_id',
                    'user_id',
                    DB::raw('COUNT(id) as total_attempted'),
                    DB::raw('SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as total_correct_answer'),
                    DB::raw('SUM(COALESCE(total_time, 0)) as total_time')
                )
                ->groupBy('tenant_id', 'user_id')
                ->get();

            if ($rawStats->isEmpty()) {
                return ['success' => true, 'message' => 'No participants to rank'];
            }

            $statsArray = [];
            foreach ($rawStats as $stat) {
                $attempted = (int) $stat->total_attempted;
                if ($attempted < 1) continue;

                $correct = (int) $stat->total_correct_answer;
                $tTime = (float) $stat->total_time;
                $perAvg = $attempted > 0 ? (float) round($tTime / $attempted, 2) : 0.0;

                $statsArray[] = [
                    'tenant_id'            => (int) $stat->tenant_id,
                    'user_id'              => (int) $stat->user_id,
                    'quiz_id'              => (int) $quizId,
                    'total_attempted'      => $attempted,
                    'total_correct_answer' => $correct,
                    'total_time'           => $tTime,
                    'per_ans_average_time' => $perAvg,
                ];
            }

            // Step 2: Sort by max correct answers, then less total time
            usort($statsArray, function ($a, $b) {
                if ($a['total_correct_answer'] != $b['total_correct_answer']) {
                    return $b['total_correct_answer'] <=> $a['total_correct_answer'];
                }
                return $a['total_time'] <=> $b['total_time'];
            });

            DB::beginTransaction();

            $previousCorrect = -1;
            $previousTime = -1;
            $currentRank = 1;

            foreach ($statsArray as $index => $stat) {
                $currentCorrect = (int) $stat['total_correct_answer'];
                $currentTime = $stat['total_time'];

                if ($currentCorrect === $previousCorrect && $currentTime === $previousTime) {
                    $rank = $currentRank;
                } else {
                    $rank = $index + 1;
                    $currentRank = $rank;
                }

                $previousCorrect = $currentCorrect;
                $previousTime = $currentTime;

                // Points equal to total correct answers
                $pointsEarned = $currentCorrect;

                QuizWinner::updateOrCreate(
                    [
                        'quiz_id' => $quizId,
                        'user_id' => $stat['user_id'],
                    ],
                    [
                        'tenant_id'            => $tenantId,
                        'total_question'       => $stat['total_attempted'],
                        'total_correct_answer' => $currentCorrect,
                        'total_time'           => $stat['total_time'],
                        'per_ans_average_time' => $stat['per_ans_average_time'],
                        'rank'                 => $rank,
                        'reward_type'          => 'point',
                        'reward'               => (string) $pointsEarned,
                        'status'               => 'active',
                    ]
                );

                // Add points into leader_point_history and user's leader_board_point
                if ($pointsEarned > 0) {
                    $targetUser = User::find($stat['user_id']);
                    $oldPoints = $targetUser ? (float) ($targetUser->leader_board_point ?? 0) : 0.0;
                    $newPoints = $oldPoints + $pointsEarned;

                    $history = LeaderPointHistory::firstOrCreate(
                        [
                            'tenant_id' => $tenantId,
                            'quiz_id'   => $quizId,
                            'user_id'   => $stat['user_id'],
                        ],
                        [
                            'old_point' => $oldPoints,
                            'point'     => $pointsEarned,
                            'new_point' => $newPoints,
                            'type'      => 'credit',
                        ]
                    );

                    if ($history->wasRecentlyCreated && $targetUser) {
                        $targetUser->increment('leader_board_point', $pointsEarned);
                    }
                }
            }

            // Mark status complete
            QuizStatus::where('quiz_id', $quizId)->update(['status' => 'completed']);
            $quiz->update(['complete_status' => 'completed']);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Fastest Finger First leaderboard & points processed successfully',
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('FFF processQuizResult failed: ' . $e->getMessage(), [
                'quiz_id' => $quizId,
                'trace'   => $e->getTraceAsString()
            ]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get Leaderboard Data for Frontend
     */
    public function getFffLeaderboardData($user, int $quizId): array
    {
        $winners = Cache::remember("fff_quiz_{$quizId}_leaderboard_winners", 30, function () use ($quizId) {
            $winnerModels = QuizWinner::with('user:id,name,profile_photo')
                ->where('quiz_id', $quizId)
                ->orderBy('rank', 'asc')
                ->get();

            return $winnerModels->map(function ($w) {
                return [
                    'rank'                 => $w->rank,
                    'user_id'              => $w->user_id,
                    'user_name'            => $w->user->name ?? 'User',
                    'profile_photo'        => $w->user->profile_photo ?? null,
                    'total_correct_answer' => $w->total_correct_answer,
                    'total_question'       => $w->total_question,
                    'total_time'           => round((float) $w->total_time, 2),
                    'reward'               => $w->reward,
                    'reward_type'          => $w->reward_type,
                ];
            })->toArray();
        });

        $currentUserRank = null;
        if ($user) {
            $userId = is_object($user) ? ($user->id ?? null) : $user;
            if ($userId) {
                foreach ($winners as $w) {
                    if ($w['user_id'] == $userId) {
                        $currentUserRank = $w;
                        break;
                    }
                }
            }
        }

        return [
            'success'          => true,
            'data'             => $winners,
            'currentUserRank'  => $currentUserRank,
        ];
    }

    /**
     * Mark User Out of Fastest Finger First quiz
     */
    public function markUserOut($user, int $quizId, string $reason): array
    {
        if (!$user) {
            return ['success' => false, 'status' => 401, 'message' => 'User not found'];
        }

        $userId = is_object($user) ? $user->id : $user;

        QuizUser::where('quiz_id', $quizId)
            ->where('user_id', $userId)
            ->update([
                'out_status' => 'out',
                'remark'     => $reason,
            ]);

        Session::put("fff_quiz_{$quizId}_out_status", 'out');
        Session::put("fff_quiz_{$quizId}_out_remark", $reason);

        return [
            'success' => true,
            'message' => 'User marked out',
            'reason'  => $reason,
        ];
    }

    public function clearFffQuizCacheAndSessionData(int $id): void
    {
        if (!$id) return;

        $userId = Auth::id() ?: (session('fsr_user_id') ?? null);
        if ($userId) {
            Session::forget("fff_quiz_{$id}_user_{$userId}_answers");
            Cache::forget("fff_quiz_{$id}_user_{$userId}_answers");
            Cache::forget("fff_quiz_{$id}_user_{$userId}_anssheet");
            Cache::forget("fff_quiz_{$id}_user_{$userId}_compiled_sheet");
            Cache::forget("fff_quiz_{$id}_user_{$userId}_lb_rank");
        }

        Session::forget("fff_quiz_{$id}_counter_start_ms");
        Session::forget("fff_quiz_{$id}_status");
        Session::forget("fff_active_quiz_id");
    }
}
