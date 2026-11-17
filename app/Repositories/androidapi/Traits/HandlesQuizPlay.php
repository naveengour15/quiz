<?php

namespace App\Repositories\androidapi\Traits;

use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionUserAnswer;
use App\Models\QuizRankReward;
use App\Models\QuizSetting;
use App\Models\QuizStatus;
use App\Models\QuizUser as QuizUserEnrollment;
use App\Models\QuizWinner;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait HandlesQuizPlay
{
    public function start($user, int $quizId): array
    {
        try {
            if (!$user || !isset($user->id)) {
                return [
                    'success' => false,
                    'message' => 'Invalid user'
                ];
            }

            $quiz = Quiz::find($quizId);

            if (!$quiz) {
                return [
                    'success' => false,
                    'message' => 'Quiz not found'
                ];
            }

            if ($this->hasStartTimePassed($quiz)) {
                return [
                    'success' => false,
                    'message' => 'This quiz has already started. Late access is not allowed.',
                    'redirect' => '/home',
                ];
            }

            $joined = QuizUserEnrollment::where('quiz_id', $quizId)
                ->where('user_id', $user->id)
                ->exists();

            if (!$joined) {
                if (($user->available_quiz_limit ?? 0) <= 0) {
                    return [
                        'success' => false,
                        'message' => 'Your available quiz limit has expired.'
                    ];
                }

                QuizUserEnrollment::create([
                    'tenant_id' => $quiz->tenant_id ?? 1,
                    'quiz_id'   => $quizId,
                    'user_id'   => $user->id,
                    'status'    => 'inactive',
                ]);

                User::where('id', $user->id)
                    ->where('available_quiz_limit', '>', 0)
                    ->decrement('available_quiz_limit');
                $user->refresh();
            }

            $quizStatus = QuizStatus::where('quiz_id', $quizId)->first();

            if (!$quizStatus) {
                $quizStatus = $this->quizStatus($quiz);
            }

            $question = $this->getCurrentQuestion($quizId);

            if (!$question) {
                return [
                    'success' => false,
                    'message' => 'No questions found for this quiz.',
                    'start_time' => Carbon::parse($quiz->availability_start)
                        ->format('H:i:s')
                ];
            }

            $hasAnswer = QuizQuestionUserAnswer::where('quiz_id', $quizId)
                ->where('user_id', $user->id)
                ->exists();

            if (!$hasAnswer) {
                $quizStatus->increment('total_played_user');

                $quizStatus->update([
                    'current_question_id' => $question->question_id,
                ]);
            }
            $hasQuestionAnswer = QuizQuestionUserAnswer::where('quiz_id', $quizId)
                ->where('user_id', $user->id)
                ->where('question_id', $question->id ?? $question->question_id)
                ->exists();
            if ($hasQuestionAnswer) {
                $question = $this->getCurrentQuestion($quizId);
            }

            if ($question && !empty($question->options)) {
                $question->options = $this->shuffleOptions($question->options, ($user->id ?? 0) . '_' . ($question->id ?? $question->question_id ?? 0));
            }

            $remainingTime = $this->getQuestionRemainingTime($question->end_time);

            return [
                'success' => true,
                'quiz_id' => $quizId,
                'time_limit_minutes' => $quiz->time_limit_minutes,
                'question' => $question,
                'end_time' => $question->end_time,
                'start_time' => $question->start_time,
                'pending_time' => $remainingTime,
                'lifelines' => $this->getLifelinesStatus($quiz, $user),
            ];

        } catch (\Exception $e) {
            Log::error('Quiz start failed', [
                'quiz_id' => $quizId,
                'user_id' => $user->id ?? null,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return [
                'success' => false,
                'message' => 'Something went wrong while starting the quiz. Please try again.'
            ];
        }
    }

    public function getQuestion($user, int $quizId, $data): array
    {
        try {
            $quiz = Quiz::find($quizId);

            if (!$quiz) {
                return [
                    'success' => false,
                    'message' => 'Quiz not found',
                ];
            }

            $joined = QuizUserEnrollment::where('quiz_id', $quizId)
                ->where('user_id', $user->id)
                ->exists();

            if (!$joined) {
                return [
                    'success' => false,
                    'message' => 'Please join the quiz before starting',
                ];
            }

            $question = $this->getCurrentQuestion($quizId);

            if ($question) {
                $ans = QuizQuestionUserAnswer::where([
                    'quiz_id'     => $quizId,
                    'question_id' => $question->id ?? $question->question_id,
                    'user_id'     => $user->id,
                ])->first();

                if ($ans) {
                    $question = $this->getCurrentQuestion($quizId);
                }

                $question->time_limit_seconds = $quiz->per_que_time_limit_sec;

                QuizStatus::where('quiz_id', $quizId)
                    ->update([
                        'current_question_id' => $question->question_id,
                    ]);
            }

            if ($question && !empty($question->options)) {
                $question->options = $this->shuffleOptions($question->options, ($user->id ?? 0) . '_' . ($question->id ?? $question->question_id ?? 0));
            }

            $remainingTime = $this->getQuestionRemainingTime($question->end_time);

            return [
                'success'            => true,
                'quiz_id'            => $quizId,
                'time_limit_minutes' => $quiz->time_limit_minutes,
                'question'           => $question,
                'end_time'           => $question->end_time,
                'start_time'         => $question->start_time,
                'pending_time'       => $remainingTime,
                'lifelines'          => $this->getLifelinesStatus($quiz, $user),
            ];

        } catch (\Exception $e) {
            Log::error('Error fetching quiz question', [
                'user_id' => $user->id ?? null,
                'quiz_id' => $quizId,
                'question_id' => $data['question_id'] ?? null,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return [
                'success' => false,
                'message' => 'Something went wrong while fetching the question.',
            ];
        }
    }

    public function getQuestionOptions($user, int $quizId, $data): array
    {
        $quiz = Quiz::find($quizId);

        if (!$quiz) {
            return ['success' => false, 'message' => 'Quiz not found'];
        }

        $joined = QuizUserEnrollment::where('quiz_id', $quizId)->where('user_id', $user->id)->exists();

        if (!$joined) {
            return ['success' => false, 'message' => 'Please join the quiz before starting'];
        }

        $questionId = $data['question_id'] ?? null;
        if (!$questionId) {
            $currQ = $this->getCurrentQuestion($quizId);
            $questionId = $currQ ? ($currQ->id ?? $currQ->question_id) : null;
        }

        $question = QuizQuestion::where('quiz_id', $quizId)
            ->where('id', $questionId)
            ->orderBy('order', 'asc')
            ->first([
                'id as question_id',
                'question_text',
                'hindi_question',
                'options',
                'hindi_options',
                'time_limit_seconds',
                'type',
                'media_url',
                'question_audio',
                'audio_file',
            ]);

        if ($question) {
            $question->time_limit_seconds = $quiz->per_que_time_limit_sec;
            $awsUrl = env('AWS_URL', 'https://chaloonline.s3.ap-south-1.amazonaws.com');

            if (!empty($question->media_url)) {
                $mUrl = trim((string) $question->media_url);
                $cleanPath = ltrim(preg_replace('/^https?:\/\/[^\/]+\//i', '', $mUrl), '/');
                $question->media_url = !empty($awsUrl) ? (rtrim($awsUrl, '/') . '/' . $cleanPath) : asset($mUrl);
            }

            $qAudio = $question->question_audio ?: $question->audio_file;
            if (!empty($qAudio)) {
                $aUrl = trim((string) $qAudio);
                $cleanPath = ltrim(preg_replace('/^https?:\/\/[^\/]+\//i', '', $aUrl), '/');
                $question->question_audio = !empty($awsUrl) ? (rtrim($awsUrl, '/') . '/' . $cleanPath) : asset($aUrl);
            }

            if (!empty($question->options)) {
                $rawOpts = is_array($question->options) ? $question->options : (json_decode($question->options, true) ?: []);
                $hindiOpts = is_array($question->hindi_options) ? $question->hindi_options : (json_decode($question->hindi_options, true) ?: []);
                if (empty($hindiOpts) && is_object($question->question)) {
                    $hRel = $question->question->hindi_options;
                    $hindiOpts = is_array($hRel) ? $hRel : (json_decode($hRel, true) ?: []);
                }
                $mergedOpts = [];
                foreach ($rawOpts as $optIdx => $opt) {
                    $optText = is_array($opt) ? ($opt['option_text'] ?? $opt['text'] ?? $opt['label'] ?? '') : (string) $opt;
                    $optText = trim($optText);

                    $hOptText = '';
                    if (is_array($hindiOpts) && isset($hindiOpts[$optIdx])) {
                        $hOpt = $hindiOpts[$optIdx];
                        $hOptText = is_array($hOpt) ? ($hOpt['option_text'] ?? $hOpt['text'] ?? $hOpt['label'] ?? '') : (string) $hOpt;
                        $hOptText = trim($hOptText);
                    }

                    if ($hOptText !== '' && strcasecmp($optText, $hOptText) !== 0) {
                        $mergedOpts[] = $hOptText . ' (' . $optText . ')';
                    } elseif ($hOptText !== '') {
                        $mergedOpts[] = $hOptText;
                    } else {
                        $mergedOpts[] = $optText;
                    }
                }
                $question->options = $this->shuffleOptions($mergedOpts, ($user->id ?? 0) . '_' . ($question->id ?? $questionId ?? 0));
            }
        }

        return [
            'success'            => true,
            'quiz_id'            => $quizId,
            'time_limit_minutes' => $quiz->time_limit_minutes,
            'question'           => $question,
            'lifelines'          => $this->getLifelinesStatus($quiz, $user),
        ];
    }

    public function submitAnswer($user, int $quizId, array $data): array
    {
        try {
            $quiz = Quiz::find($quizId);
            if ($quiz) {
                $scheduledAt = !empty($quiz->scheduled_at) ? Carbon::parse($quiz->scheduled_at) : (!empty($quiz->availability_start) ? Carbon::parse($quiz->availability_start) : null);
                if ($scheduledAt) {
                    $nowMs = (int) (microtime(true) * 1000);
                    $scheduledTimeMs = (int) ($scheduledAt->getTimestamp() * 1000);
                    $isFff = ($quiz->visibility === 'fastest_finger_first');
                    $offsetSec = $isFff ? 11 : 15;
                    $minSubmitTimeMs = $scheduledTimeMs + ($offsetSec * 1000);
                    if ($nowMs < $minSubmitTimeMs) {
                        return [
                            'success' => false,
                            'status'  => 422,
                            'message' => "⚠️ Quiz start nahi hua hai. Answer schedule time + {$offsetSec} seconds ke baad hi submit ho sakta hai.",
                        ];
                    }
                }
            }

            return DB::transaction(function () use ($user, $quizId, $data) {
                $questionId = $data['question_id'] ?? null;

                if (!$questionId) {
                    $currentQuestion = $this->getCurrentQuestion($quizId);
                    $questionId = $currentQuestion
                        ? ($currentQuestion->id ?? $currentQuestion->question_id ?? null)
                        : null;
                }

                if (!$questionId) {
                    return [
                        'success' => false,
                        'message' => 'Question ID is required.',
                    ];
                }

                $quizQuestion = QuizQuestion::where('quiz_id', $quizId)
                    ->where('id', $questionId)
                    ->first();

                if (!$quizQuestion) {
                    return [
                        'success' => false,
                        'message' => 'Question not part of this quiz.',
                    ];
                }

                $existingAnswer = QuizQuestionUserAnswer::where([
                    'quiz_id'     => $quizId,
                    'question_id' => $questionId,
                    'user_id'     => $user->id,
                    'tenant_id'   => $quizQuestion->tenant_id ?? 1,
                ])
                    ->lockForUpdate()
                    ->first();

                if ($existingAnswer) {
                    return [
                        'success'  => false,
                        'message'  => 'Answer already submitted successfully.',
                        'end_time' => $quizQuestion->end_time,
                    ];
                }

                $totalQuizQuestionsCount = QuizQuestion::where('quiz_id', $quizId)->count();
                $userAnsweredCount = QuizQuestionUserAnswer::where('quiz_id', $quizId)
                    ->where('user_id', $user->id)
                    ->count();

                if ($totalQuizQuestionsCount > 0 && $userAnsweredCount >= $totalQuizQuestionsCount) {
                    return [
                        'success' => false,
                        'message' => 'Maximum allowed questions limit reached for this quiz.',
                    ];
                }

                $submittedAnswer = $data['answer'] ?? null;

                if ($submittedAnswer === null || trim((string) $submittedAnswer) === '') {
                    $options = $quizQuestion->options;
                    if (!is_array($options)) {
                        $options = json_decode($options, true) ?: [];
                    }
                    $submittedAnswer = $options[0] ?? null;
                }

                $options = $quizQuestion->options;
                if (is_string($options)) {
                    $options = json_decode($options, true) ?: [];
                }
                if (!is_array($options)) {
                    $options = [];
                }

                $correctAnswer = trim((string) $quizQuestion->correct_answer);
                $submittedAnswerString = trim((string) $submittedAnswer);

                $correctAnsStr = $this->resolveMatchingOptionStr($correctAnswer, $options);
                $userAnsStr = $this->resolveMatchingOptionStr($submittedAnswerString, $options);

                $isCorrect = $this->checkAnswersMatch(
                    $correctAnsStr ?: $correctAnswer,
                    $userAnsStr ?: $submittedAnswerString,
                    $options
                );

                $answerTime = now();
                $questionStartTime = Carbon::today()->setTimeFromTimeString($quizQuestion->start_time);

                if ($questionStartTime->greaterThan($answerTime)) {
                    $questionStartTime->subDay();
                }

                $diffInMilliseconds = max(0, $questionStartTime->diffInMilliseconds($answerTime));
                $diffInSeconds = intdiv($diffInMilliseconds, 1000);
                $milliseconds = $diffInMilliseconds % 1000;
                $totalTime = sprintf('%d.%03d', $diffInSeconds, $milliseconds);

                $oldAnswerDetails = QuizQuestionUserAnswer::where([
                    'tenant_id'   => $quizQuestion->tenant_id ?? 1,
                    'quiz_id'     => $quizId,
                    'question_id' => $questionId,
                    'user_id'     => $user->id,
                ])->first();

                if (!$oldAnswerDetails) {
                    $userAnswer = QuizQuestionUserAnswer::create([
                        'tenant_id'   => $quizQuestion->tenant_id ?? 1,
                        'quiz_id'     => $quizId,
                        'question_id' => $questionId,
                        'user_id'     => $user->id,
                        'answer'      => $submittedAnswer,
                        'status'      => $isCorrect ? 1 : 0,
                        'show_time'   => $quizQuestion->start_time,
                        'ans_time'    => $answerTime->format('H:i:s.v'),
                        'total_time'  => $totalTime,
                    ]);

                  
                    QuizUserEnrollment::where('quiz_id', $quizId)
                        ->where('user_id', $user->id)
                        ->update(['out_status' => 'in', 'status' => 'active']);
                    Session::put("quiz_{$quizId}_out_status", 'in');
                }

                $nextQuestion = QuizQuestion::where('quiz_id', $quizId)
                    ->where('tenant_id', $quizQuestion->tenant_id)
                    ->where(function ($query) {
                        $query->where('is_switch_question', 0)
                            ->orWhereNull('is_switch_question');
                    })
                    ->where('order', '>', $quizQuestion->order)
                    ->orderBy('order')
                    ->first(['id', 'order']);

                $lastQuestionId = QuizQuestion::where('quiz_id', $quizId)
                    ->where('tenant_id', $quizQuestion->tenant_id)
                    ->where(function ($query) {
                        $query->where('is_switch_question', 0)
                            ->orWhereNull('is_switch_question');
                    })
                    ->max('order');

                $complete = ((int) $quizQuestion->order === (int) $lastQuestionId)
                    ? 'completed'
                    : 'pending';

                $remainingTime = $this->getQuestionRemainingTime($quizQuestion->end_time);

                return [
                    'success'                 => true,
                    'is_correct'              => $isCorrect,
                    'correct_answer'         => $quizQuestion->correct_answer,
                    'remaining_quiz_question' => $complete,
                    'message'                 => 'Answer submitted successfully.',
                    'next_question_id'        => $nextQuestion?->id,
                    'end_time'                => $quizQuestion->end_time,
                    'pending_time'            => $remainingTime,
                ];
            });

        } catch (\Throwable $e) {
            Log::error('Quiz answer submission failed', [
                'user_id'     => $user->id ?? null,
                'quiz_id'     => $quizId,
                'question_id' => $data['question_id'] ?? null,
                'answer'      => $data['answer'] ?? null,
                'message'     => $e->getMessage(),
                'file'        => $e->getFile(),
                'line'        => $e->getLine(),
            ]);

            return [
                'success' => false,
                'message' => 'Something went wrong while submitting the answer. Please try again.',
            ];
        }
    }

    public function finalSubmit($quizId, $user, $tenantId)
    {
        $answers = QuizQuestionUserAnswer::where('tenant_id', $tenantId)
            ->where('quiz_id', $quizId)
            ->where('user_id', $user->id)
            ->get();

        $totalCorrect = 0;
        $totalTime    = 0;
        foreach ($answers as $ans) {
            $quizQuestion = QuizQuestion::where('quiz_id', $quizId)
                ->where('id', $ans['question_id'])
                ->first();

            if (!$quizQuestion) {
                continue;
            }

            $isCorrect = strcasecmp(
                trim((string) $quizQuestion->correct_answer),
                trim((string) ($ans['answer'] ?? ''))
            ) === 0;

            if ($isCorrect) {
                $totalCorrect++;
            }

            $totalTime += (float) ($ans['total_time'] ?? 0);
        }

        $totalQuestions = count($answers);
        $avgTime = $totalQuestions > 0 ? round($totalTime / $totalQuestions, 3) : 0;

        if ($totalQuestions >= 1) {
            $rank = QuizWinner::where('quiz_id', $quizId)
                ->where('user_id', '!=', $user->id)
                ->where(function ($q) use ($totalCorrect, $totalTime) {
                    $q->where('total_correct_answer', '>', $totalCorrect)
                        ->orWhere(function ($q2) use ($totalCorrect, $totalTime) {
                            $q2->where('total_correct_answer', $totalCorrect)
                                ->where('total_time', '<', $totalTime);
                        });
                })
                ->count() + 1;

            $rankReward = QuizRankReward::where('quiz_id', $quizId)
                ->where('start_rank', '<=', $rank)
                ->where('end_rank', '>=', $rank)
                ->first();

            $rewardPoints = $rankReward ? (float) $rankReward->reward : ($totalCorrect * 10);
            $rewardType = $rankReward->type ?? 'point';
            $rewardId = $rankReward->id ?? null;

            $quizWinner = QuizWinner::where([
                'quiz_id' => $quizId,
                'user_id' => $user->id,
            ])->first();

            $oldWinnerDetails = $quizWinner ? $quizWinner->toArray() : null;

            $winner = QuizWinner::updateOrCreate(
                [
                    'quiz_id' => $quizId,
                    'user_id' => $user->id,
                ],
                [
                    'tenant_id'            => $tenantId ?? 1,
                    'total_question'       => $totalQuestions,
                    'total_correct_answer' => $totalCorrect,
                    'total_time'           => round($totalTime, 3),
                    'per_ans_average_time' => round($avgTime, 3),
                    'rank'                 => $rank,
                    'reward'               => $rewardPoints,
                    'reward_type'          => $rewardType,
                    'reward_id'            => $rewardId,
                    'reward_detail'        => $rewardType . ' type award',
                    'status'               => 'active',
                ]
            );
            QuizWinner::recalculateRanks((int) $quizId);
           
        } else {
            QuizWinner::where('quiz_id', $quizId)->where('user_id', $user->id)->delete();
            QuizWinner::recalculateRanks((int) $quizId);
        }

        $quizStatus = QuizStatus::where('quiz_id', $quizId)->first();
        if ($quizStatus) {
            $oldQuizStatusDetails = $quizStatus->toArray();
            $quizStatus->increment('total_complete_quiz_user');
            $quizStatus->increment('total_correct_ans', $totalCorrect);
            $quizStatus->refresh();
            QuizStatus::syncToSessionAndCache($quizId, $quizStatus->toArray());
           
        }

        return [
            'success' => true,
            'message' => 'Quiz submitted successfully',
        ];
    }

    public function submitBulk($user, int $quizId, array $answers): array
    {
        try {
            $quiz = Quiz::find($quizId);
            if ($quiz) {
                $scheduledAt = !empty($quiz->scheduled_at) ? Carbon::parse($quiz->scheduled_at) : (!empty($quiz->availability_start) ? Carbon::parse($quiz->availability_start) : null);
                if ($scheduledAt) {
                    $nowMs = (int) (microtime(true) * 1000);
                    $scheduledTimeMs = (int) ($scheduledAt->getTimestamp() * 1000);
                    $isFff = ($quiz->visibility === 'fastest_finger_first');
                    $offsetSec = $isFff ? 11 : 15;
                    $minSubmitTimeMs = $scheduledTimeMs + ($offsetSec * 1000);
                    if ($nowMs < $minSubmitTimeMs) {
                        return [
                            'success' => false,
                            'status'  => 422,
                            'message' => "⚠️ Quiz start nahi hua hai. Answer schedule time + {$offsetSec} seconds ke baad hi submit ho sakta hai.",
                        ];
                    }
                }
            }

            return DB::transaction(function () use ($user, $quizId, $answers) {
                $totalCorrect = 0;
                $totalTime    = 0;

                foreach ($answers as $ans) {
                    $quizQuestion = QuizQuestion::where('quiz_id', $quizId)
                        ->where('id', $ans['question_id'])
                        ->first();

                    if (!$quizQuestion) {
                        continue;
                    }

                    $isCorrect = strcasecmp(
                        trim((string) $quizQuestion->correct_answer),
                        trim((string) ($ans['answer'] ?? ''))
                    ) === 0;

                    if ($isCorrect) {
                        $totalCorrect++;
                    }

                    $totalTime += (float) ($ans['total_time'] ?? 0);

                    QuizQuestionUserAnswer::updateOrCreate(
                        [
                            'tenant_id'   => $quizQuestion->tenant_id ?? 1,
                            'quiz_id'     => $quizId,
                            'question_id' => $ans['question_id'],
                            'user_id'     => $user->id,
                        ],
                        [
                            'answer'      => $ans['answer'] ?? null,
                            'status'      => $isCorrect ? 1 : 0,
                            'total_time'  => $ans['total_time'] ?? null,
                        ]
                    );
                }

                QuizUserEnrollment::where('quiz_id', $quizId)
                    ->where('user_id', $user->id)
                    ->update(['out_status' => 'in', 'status' => 'active']);
                Session::put("quiz_{$quizId}_out_status", 'in');

                $totalQuestions = count($answers);
                $avgTime = $totalQuestions > 0 ? round($totalTime / $totalQuestions, 3) : 0;

                $winner = null;
                if ($totalQuestions >= 1) {
                    $rank = QuizWinner::where('quiz_id', $quizId)
                        ->where(function ($q) use ($totalCorrect, $totalTime) {
                            $q->where('total_correct_answer', '>', $totalCorrect)
                                ->orWhere(function ($q2) use ($totalCorrect, $totalTime) {
                                    $q2->where('total_correct_answer', $totalCorrect)
                                        ->where('total_time', '<', $totalTime);
                                });
                        })
                        ->count() + 1;

                    $rankReward = QuizRankReward::where('quiz_id', $quizId)
                        ->where('start_rank', '<=', $rank)
                        ->where('end_rank', '>=', $rank)
                        ->first();

                    $rewardPoints = $rankReward ? (float) $rankReward->reward : ($totalCorrect * 10);
                    $rewardType = $rankReward->type ?? 'point';
                    $rewardId = $rankReward->id ?? null;

                    $winner = QuizWinner::updateOrCreate(
                        [
                            'quiz_id' => $quizId,
                            'user_id' => $user->id,
                        ],
                        [
                            'tenant_id'            => 1,
                            'total_question'       => $totalQuestions,
                            'total_correct_answer' => $totalCorrect,
                            'total_time'           => round($totalTime, 3),
                            'per_ans_average_time' => round($avgTime, 3),
                            'rank'                 => $rank,
                            'reward'               => $rewardPoints,
                            'reward_type'          => $rewardType,
                            'reward_id'            => $rewardId,
                            'reward_detail'        => $rewardType . ' type award',
                            'status'               => 'active',
                        ]
                    );
                    QuizWinner::recalculateRanks((int) $quizId);
                } else {
                    QuizWinner::where('quiz_id', $quizId)->where('user_id', $user->id)->delete();
                    QuizWinner::recalculateRanks((int) $quizId);
                }

                QuizStatus::where('quiz_id', $quizId)->increment('total_complete_quiz_user');
                QuizStatus::where('quiz_id', $quizId)->increment('total_correct_ans', $totalCorrect);

                return [
                    'success' => true,
                    'message' => 'Quiz submitted successfully',
                    'result'  => $winner,
                ];
            });
        } catch (\Throwable $e) {
            Log::error('Quiz bulk submission failed', [
                'user_id'       => $user->id ?? null,
                'quiz_id'       => $quizId,
                'answers_count' => count($answers),
                'message'       => $e->getMessage(),
                'file'          => $e->getFile(),
                'line'          => $e->getLine(),
            ]);

            return [
                'success' => false,
                'message' => 'Something went wrong while submitting the quiz. Please try again.',
            ];
        }
    }

    public function myResult($user, int $quizId): array
    {
        try {
            $winner = QuizWinner::where('quiz_id', $quizId)
                ->where('user_id', $user->id)
                ->with('user:id,name')
                ->first();

            if (!$winner) {
                return [
                    'success' => false,
                    'message' => 'No result found for this quiz',
                ];
            }

            $winner->user_name = $winner->user?->name;
            $winner->correct_answers = (int) $winner->total_correct_answer;
            $winner->correct = (int) $winner->total_correct_answer;
            $winner->total_questions = (int) ($winner->total_question ?: 10);
            $winner->total = (int) ($winner->total_question ?: 10);
            $winner->score = (int) ($winner->total_correct_answer * 10);
            $winner->total_score = (int) ($winner->total_correct_answer * 10);

            $val = (float) ($winner->reward ?? 0);
            $calculatedReward = ($val > 0) ? (floor($val) == $val ? (int) $val : round($val, 2)) : (int) ($winner->total_correct_answer * 10);
            $winner->reward = $calculatedReward;
            $winner->coins_earned = $calculatedReward;
            $winner->coins = $calculatedReward;
            $winner->reward_type = $winner->reward_type ?? 'point';
            unset($winner->user);

            return [
                'success' => true,
                'result'  => $winner,
            ];

        } catch (\Exception $e) {
            Log::error('Fetch Quiz Result Failed', [
                'quiz_id' => $quizId,
                'user_id' => $user->id ?? null,
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);

            return [
                'success' => false,
                'message' => 'Something went wrong while fetching the quiz result.',
            ];
        }
    }

    public function winners(int $quizId): array
    {
        try {
            QuizWinner::recalculateRanks($quizId);

            $winners = QuizWinner::with([
                'user:id,name',
                'voucherHistory:id,voucher_id,status',
                'voucherHistory.voucher:id,name',
            ])
                ->where('quiz_id', $quizId)
                ->where('status', 'active')
                ->orderBy('rank')
                ->get()
                ->unique('user_id')
                ->values()
                ->transform(function ($winner) {
                    $winner->user_name = $winner->user?->name;
                    $winner->correct_answers = (int) $winner->total_correct_answer;
                    $winner->correct = (int) $winner->total_correct_answer;
                    $winner->total_questions = (int) ($winner->total_question ?: 10);
                    $winner->total = (int) ($winner->total_question ?: 10);
                    $winner->score = (int) ($winner->total_correct_answer * 10);
                    $winner->total_score = (int) ($winner->total_correct_answer * 10);

                    $val = (float) ($winner->reward ?? 0);
                    $calculatedReward = ($val > 0) ? (floor($val) == $val ? (int) $val : round($val, 2)) : (int) ($winner->total_correct_answer * 10);
                    $winner->reward = $calculatedReward;
                    $winner->coins_earned = $calculatedReward;
                    $winner->coins = $calculatedReward;
                    $winner->reward_type = $winner->reward_type ?? 'point';

                    unset($winner->user);
                    return $winner;
                });

            return [
                'success' => true,
                'quiz_id' => $quizId,
                'winners' => $winners,
            ];

        } catch (\Exception $e) {
            Log::error('Failed to fetch quiz winners', [
                'quiz_id' => $quizId,
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);

            return [
                'success' => false,
                'quiz_id' => $quizId,
                'message' => 'Unable to fetch quiz winners.',
                'winners' => [],
            ];
        }
    }

    public function myHistory($user): array
    {
        try {
            $history = QuizUserEnrollment::with(['quiz:id,title'])->where('user_id', $user->id)
                ->latest('id')
                ->paginate(20);

            return [
                'success' => true,
                'data'    => $history,
            ];

        } catch (\Exception $e) {
            Log::error('Failed to fetch quiz history', [
                'user_id' => $user->id ?? null,
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);

            return [
                'success' => false,
                'message' => 'Unable to fetch quiz history.',
                'data'    => [],
            ];
        }
    }

    public function leave($user, int $quizId): array
    {
        return $this->markUserOutOfQuizData($user, $quizId, 'User left the quiz');
    }

    public function answerSheet($user, int $quizId): array
    {
        try {
            $enrollment = QuizUserEnrollment::where('quiz_id', $quizId)
                ->where('user_id', $user->id)
                ->first();
            $quizUser = $enrollment;

            $hasSwitched = (bool) (($enrollment->switch_option ?? false) || ($quizUser->switch_option ?? false));
            $switchedReplacedQId = $hasSwitched ? ($enrollment->question_id ?? $quizUser->question_id ?? null) : null;

            $standardQuestions = QuizQuestion::where('quiz_id', $quizId)
                ->where(function ($q) {
                    $q->where('is_switch_question', 0)->orWhereNull('is_switch_question');
                })
                ->orderBy('order')
                ->get();

            $switchQuestion = QuizQuestion::where('quiz_id', $quizId)
                ->where('is_switch_question', 1)
                ->first();

            $questionsToUse = [];
            foreach ($standardQuestions as $sq) {
                if ($hasSwitched && $switchedReplacedQId && $sq->id == $switchedReplacedQId && $switchQuestion) {
                    continue;
                } else {
                    $questionsToUse[] = $sq;
                }
            }
            if ($hasSwitched && $switchQuestion) {
                $questionsToUse[] = $switchQuestion;
            }

            $userAnswers = QuizQuestionUserAnswer::where([
                'quiz_id' => $quizId,
                'user_id' => $user->id,
            ])->get()->keyBy('question_id');

            $data = [];
            $correct = 0;

            foreach ($questionsToUse as $question) {
                $userAnswer = $userAnswers->get($question->id);
                $selectedAnswer = $userAnswer->answer ?? null;

                $isCorrect = false;

                if ($selectedAnswer !== null) {
                    $isCorrect = strcasecmp(
                        trim((string) $question->correct_answer),
                        trim((string) $selectedAnswer)
                    ) === 0;
                }

                if ($isCorrect) {
                    $correct++;
                }

                $data[] = [
                    'question_id'     => $question->id,
                    'question'        => $question->question_text,
                    'options'         => $question->options,
                    'selected_answer' => $selectedAnswer,
                    'correct_answer'  => $question->correct_answer,
                    'is_correct'      => $isCorrect,
                    'explanation'     => $question->explanation,
                ];
            }

            $totalSheetCount = count($data);
            return [
                'success' => true,
                'message' => 'Answer sheet fetched successfully.',
                'result'  => [
                    'total_questions' => $totalSheetCount,
                    'correct_answers' => $correct,
                    'wrong_answers'   => $totalSheetCount - $correct,
                    'answers'         => $data,
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Answer Sheet API Failed', [
                'quiz_id' => $quizId,
                'user_id' => $user->id,
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);

            return [
                'success' => false,
                'message' => 'Unable to fetch answer sheet.'
            ];
        }
    }

    public function quizStatus($quiz)
    {
        $quizStatus = QuizStatus::where('quiz_id', $quiz->id)->first();
        if (!$quizStatus) {
            $quizStatus = QuizStatus::create([
                'tenant_id' => $quiz->tenant_id ?? 1,
                'quiz_id' => $quiz->id,
                'total_enroll_user' => 0
            ]);
        }
        return $quizStatus;
    }

    public function delayResponse($endTime): void
    {
        if (!$endTime) {
            return;
        }

        if ($endTime instanceof Carbon) {
            $endTime = $endTime->format('H:i:s');
        }

        $endDateTime = Carbon::today()->setTimeFromTimeString($endTime);

        if ($endDateTime->isPast()) {
            return;
        }

        $delaySeconds = now()->diffInSeconds($endDateTime);

        if ($delaySeconds > 0) {
            sleep($delaySeconds);
        }
    }

    public function getCurrentQuestion($quizId)
    {
        $currentTime = now()->format('H:i:s');

        $question = QuizQuestion::where('quiz_id', $quizId)
            ->where(function ($q) {
                $q->where('is_switch_question', 0)->orWhereNull('is_switch_question');
            })
            ->where('start_time', '<=', $currentTime)
            ->where('end_time', '>', $currentTime)
            ->first([
                'id as question_id',
                'order',
                'question_text',
                'hindi_question',
                'type',
                'time_limit_seconds',
                'start_time',
                'end_time'
            ]);

        if (!$question) {
            $question = QuizQuestion::where('quiz_id', $quizId)
                ->where(function ($q) {
                    $q->where('is_switch_question', 0)->orWhereNull('is_switch_question');
                })
                ->orderBy('order', 'asc')
                ->first([
                    'id as question_id',
                    'order',
                    'question_text',
                    'hindi_question',
                    'type',
                    'time_limit_seconds',
                    'start_time',
                    'end_time'
                ]);
        }

        return $question;
    }

    public function getQuestionRemainingTime($startTime)
    {
        $now = now();
        $target = $startTime instanceof Carbon ? $startTime : Carbon::parse($startTime);
        $difference = $now->diffInSeconds($target, false);

        return $difference > 0
            ? gmdate('H:i:s', (int) $difference)
            : '00:00:00';
    }

    public function getLifelinesStatus($quiz, $user): array
    {
        $quizUser = QuizUserEnrollment::where('quiz_id', $quiz->id)
            ->where('user_id', $user->id)
            ->first();

        $fiftyFiftyUsed = $quizUser ? (bool) $quizUser->fifty_fifty : false;
        $switchOptionUsed = $quizUser ? (bool) $quizUser->switch_option : false;
        $hasUsedAny = $fiftyFiftyUsed || $switchOptionUsed;

        $quizFiftyFifty = (bool) ($quiz->fifty_fifty ?? false);
        $quizSwitchOption = (bool) ($quiz->switch_option ?? false);

        return [
            'quiz_fifty_fifty'      => $quizFiftyFifty,
            'quiz_switch_option'    => $quizSwitchOption,
            'user_fifty_fifty'      => $fiftyFiftyUsed,
            'user_switch_option'    => $switchOptionUsed,
            'has_used_any'          => $hasUsedAny,
            'can_use_fifty_fifty'   => $quizFiftyFifty && !$hasUsedAny,
            'can_use_switch_option' => $quizSwitchOption && !$hasUsedAny,
        ];
    }

    public function useLifeline($user, int $quizId, array $data): array
    {
        $quiz = Quiz::find($quizId);
        if (!$quiz) {
            return ['success' => false, 'message' => 'Quiz not found'];
        }

        $lifeline = $data['lifeline'] ?? null;
        $questionId = $data['question_id'] ?? null;

        if (!in_array($lifeline, ['fifty_fifty', 'switch_option'])) {
            return ['success' => false, 'message' => 'Invalid lifeline type'];
        }

        $quizUser = QuizUserEnrollment::firstOrCreate([
            'quiz_id' => $quizId,
            'user_id' => $user->id,
        ], [
            'tenant_id' => $quiz->tenant_id ?? 1,
            'status'    => 'active',
        ]);

        if ((bool) $quizUser->fifty_fifty || (bool) $quizUser->switch_option) {
            return [
                'success'   => false,
                'message'   => 'You have already used a lifeline in this quiz.',
                'lifelines' => $this->getLifelinesStatus($quiz, $user),
            ];
        }

        if ($lifeline === 'fifty_fifty') {
            if (!(bool) $quiz->fifty_fifty) {
                return ['success' => false, 'message' => '50:50 lifeline is not enabled for this quiz'];
            }

            $question = QuizQuestion::where('quiz_id', $quizId)
                ->where('id', $questionId)
                ->first();

            if (!$question) {
                $question = QuizQuestion::where('quiz_id', $quizId)
                    ->where('question_id', $questionId)
                    ->first();
            }

            if (!$question) {
                return ['success' => false, 'message' => 'Question not found'];
            }

            $rawOptions = is_array($question->options) ? $question->options : json_decode($question->options, true);
            if (!is_array($rawOptions) || count($rawOptions) < 3) {
                return ['success' => false, 'message' => 'Cannot apply 50:50 on less than 3 options'];
            }

            $options = $this->shuffleOptions($rawOptions, ($user->id ?? 0) . '_' . ($question->id ?? $questionId ?? 0));
            $correctAnswer = $question->correct_answer;

            $correctIndex = null;
            foreach ($options as $idx => $opt) {
                $optVal = is_array($opt) ? ($opt['value'] ?? $opt['text'] ?? $opt['option'] ?? '') : $opt;
                if (trim(strtolower((string) $optVal)) === trim(strtolower((string) $correctAnswer)) || (string) $idx === (string) $correctAnswer) {
                    $correctIndex = $idx;
                    break;
                }
            }

            if ($correctIndex === null) {
                $correctIndex = 0;
            }

            $incorrectIndices = [];
            foreach ($options as $idx => $opt) {
                if ($idx != $correctIndex) {
                    $incorrectIndices[] = $idx;
                }
            }

            shuffle($incorrectIndices);
            array_pop($incorrectIndices);
            $removedIndices = array_values($incorrectIndices);

            $removedOptions = [];
            foreach ($removedIndices as $rIdx) {
                $optVal = is_array($options[$rIdx]) ? ($options[$rIdx]['value'] ?? $options[$rIdx]['text'] ?? '') : $options[$rIdx];
                $removedOptions[] = $optVal;
            }

            $quizUser->update([
                'fifty_fifty' => true,
                'question_id' => $questionId,
                'updated_at'  => Carbon::now(),
            ]);

            return [
                'success'         => true,
                'message'         => '50:50 lifeline applied successfully',
                'lifeline'        => 'fifty_fifty',
                'removed_indices' => $removedIndices,
                'removed_options' => $removedOptions,
                'lifelines'       => $this->getLifelinesStatus($quiz, $user),
            ];
        }

        if ($lifeline === 'switch_option') {
            if (!(bool) $quiz->switch_option) {
                return ['success' => false, 'message' => 'Switch Option lifeline is not enabled for this quiz'];
            }

            $switchQuestion = QuizQuestion::where('quiz_id', $quizId)
                ->where('is_switch_question', 1)
                ->first();

            if (!$switchQuestion || $switchQuestion->id == $questionId) {
                $switchQuestion = QuizQuestion::where('quiz_id', $quizId)
                    ->where('id', '!=', $questionId)
                    ->latest()
                    ->first();
            }

            if (!$switchQuestion) {
                return ['success' => false, 'message' => 'No replacement question available'];
            }

            $quizUser->update([
                'switch_option' => true,
                'question_id'   => $questionId,
                'updated_at'    => Carbon::now(),
            ]);

            return [
                'success'      => true,
                'message'      => 'Switch Option lifeline applied successfully',
                'lifeline'     => 'switch_option',
                'new_question' => [
                    'id'                 => $switchQuestion->id,
                    'question_id'        => $switchQuestion->question_id,
                    'question_text'      => $switchQuestion->question_text,
                    'text'               => $switchQuestion->question_text,
                    'options'            => $this->shuffleOptions(
                        $switchQuestion->options,
                        ($user->id ?? 0) . '_' . ($switchQuestion->id ?? 0)
                    ),
                    'time_limit_seconds' => $quiz->per_que_time_limit_sec,
                ],
                'lifelines'    => $this->getLifelinesStatus($quiz, $user),
            ];
        }

        return ['success' => false, 'message' => 'Unknown lifeline'];
    }

    protected function shuffleOptions($options, $seed = null)
    {
        if (!is_array($options)) {
            $options = json_decode($options, true) ?: [];
        }
        if (empty($options) || count($options) <= 1) {
            return $options;
        }

        if ($seed !== null) {
            mt_srand((int) crc32((string) $seed));
            $shuffled = array_values($options);
            for ($i = count($shuffled) - 1; $i > 0; $i--) {
                $j = mt_rand(0, $i);
                $tmp = $shuffled[$i];
                $shuffled[$i] = $shuffled[$j];
                $shuffled[$j] = $tmp;
            }
            mt_srand();
            return $shuffled;
        }

        $shuffled = array_values($options);
        shuffle($shuffled);
        return $shuffled;
    }

    public function getSoundsData(): array
    {
        $sounds = \Illuminate\Support\Facades\Cache::remember('global_quiz_sound_urls', 3600, function () {
            $setting = QuizSetting::first();
            return $setting ? $setting->getSoundUrls() : [];
        });

        return [
            'success' => true,
            'data' => $sounds ?? []
        ];
    }
} 
