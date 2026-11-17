<?php

namespace App\Repositories\api;

use App\Interfaces\api\QuizPracticeRepositoryInterface;
use App\Models\User;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionUserAnswer;
use App\Models\QuizRankReward;
use App\Models\QuizStatus;
use App\Models\PracticeQuizUser as QuizUserEnrollment;
use App\Models\QuizWinner;
use App\Models\VoucherHistory;
use App\Models\WalletHistory;
use App\Models\LeaderPointHistory;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use App\Interfaces\ActionTrailRepositoryInterface;

class QuizPracticeRepository implements QuizPracticeRepositoryInterface
{ 
    public function __construct(protected ActionTrailRepositoryInterface $actontrail) {
       
    }

    
    public function availableList($user): array
    {
        $query = Quiz::whereIn('status', ['published', 'scheduled', 'completed'])
            ->where('approve_status', 'approved')
            ->with([
                 'practiceUserJoin as userJoin' => function ($q) use ($user) {
                    $q->select([
                        'id',
                        'quiz_id',
                        'user_id',
                        'remark',
                        'status',
                    ])
                    ->where('user_id', $user->id);
                }
            ])
            ->withExists([
                'practiceQuizUsers as is_joined' => function ($q) use ($user) {
                    $q->where('user_id', $user->id)
                    ->where('status', 'active');
                }
            ]);

        $quizzes = $query
            ->where(function ($q) {
                $q->whereNull('availability_end')
                    ->orWhere('availability_end', '>=', now());
            })
            ->latest('id')
            ->paginate(20);

        return ['success' => true, 'data' => $quizzes, 'available_quiz_limit' => $user->available_quiz_limit];
    }

    public function detail($user, int $quizId): array
    {
        $quiz = Quiz::where('id', $quizId)
             ->with([
                 'practiceUserJoin as userJoin' => function ($q) use ($user) {
                    $q->select([
                        'id',
                        'quiz_id',
                        'user_id',
                        'remark',
                        'status',
                    ])
                    ->where('user_id', $user->id);
                }
            ])
            ->withExists([
                'practiceQuizUsers as is_joined' => function ($q) use ($user) {
                    $q->where('user_id', $user->id)
                    ->where('status', 'active');
                }
            ])
            ->first();
        if (! $quiz) {
            return ['success' => false, 'message' => 'Quiz not found'];
        }

        return [
            'success'      => true,
            'quiz'         => $quiz,
            'rank_rewards' => QuizRankReward::where('quiz_id', $quizId)
            ->select(
                [
                    "id",
                    "tenant_id",
                    "quiz_id",
                    "start_rank",
                    "end_rank",
                    "type",
                    "reward",
                    "voucher_id",
                    "voucher_histories_id",
                    "expire_duration",
                    "status"
                ])
            ->get()
        ];
    }


    public function join($user, int $quizId, ?string $password): array
    {
        try {
            $quiz = Quiz::where('id', $quizId)->first();
            
            if ($quiz->visibility === 'private' && $quiz->password !== $password) {
                return [
                    'success' => false,
                    'message' => 'Invalid quiz password'
                ];
            }

            $quizStatus =  $this->quizStatus($quiz);

            $alreadyJoined = QuizUserEnrollment::where('quiz_id', $quizId)
                ->where('user_id', $user->id)
                ->exists();

            if ($alreadyJoined) {
                return [
                    'success' => false,
                    'message' => 'You have already joined this quiz'
                ];
            }

            $enrollment = QuizUserEnrollment::updateOrCreate(
                [
                    'quiz_id' => $quizId,
                    'user_id' => $user->id,
                ],
                [
                    'tenant_id' => $quiz->tenant_id ?? 1,
                    'status'    => 'inactive',
                ]
            );

            QuizStatus::where('quiz_id', $quizId)
                ->increment('total_intrested_user');

            $joined = QuizUserEnrollment::where('quiz_id', $quizId)->where('status', 'active')->count();

            if ($quiz->no_of_players > 0 && (int) $joined >= (int) $quiz->no_of_players) {
                
                $enrollment->update([
                    'status' => 'inactive',
                    'remark' => 'Player limit existed'
                ]);

                return [
                    'success' => false,
                    'message' => 'Player limit exist in this quiz.'
                ];
            }


            $enrollment->update([
                'status' => 'active'
            ]);

            QuizStatus::where('quiz_id', $quizId)
                ->increment('total_enroll_user');

            return [
                'success' => true,
                'message' => 'Joined quiz successfully',
                'quiz_id' => $quizId,
                'start_time' => Carbon::parse($quiz->availability_start)->format('H:i:s'),
            ];

        } catch (\Exception $e) {
            Log::error('Quiz join failed', [
                'user_id'   => $user->id ?? null,
                'quiz_id'   => $quizId,
                'password'  => $password,
                'message'   => $e->getMessage(),
                'file'      => $e->getFile(),
                'line'      => $e->getLine(),
                'trace'     => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Something went wrong while joining the quiz. Please try again.'
            ];
        }
    }

    public function start($user, int $quizId): array
    {
        try {
            $quiz = Quiz::find($quizId);
            
            if (! $quiz) {
                return [
                    'success' => false,
                    'message' => 'Quiz not found'
                ];
            }

            $joined = QuizUserEnrollment::where('quiz_id', $quizId)
                ->where('user_id', $user->id)
                ->exists();

            if (! $joined) {
                return [
                    'success' => false,
                    'message' => 'Please join the quiz before starting'
                ];
            }
            $currentTime = now()->format('H:i:s');
           
            $question = QuizQuestion::where('quiz_id', $quizId)
                // ->where('start_time', '<=', $currentTime)
                // ->where('end_time', '>', $currentTime)
                ->orderBy('order', 'asc')
                ->first([
                    'question_id',
                    'order',
                    'question_text',
                    'type',
                    'time_limit_seconds',
                    'start_time',
                    'end_time',
                ]);

            if (!$question) {
                return [
                    'success' => false,
                    'message' => 'No questions found for this quiz.'
                ];
            }

            QuizStatus::where('quiz_id', $quizId)->increment('total_played_user');

            if ($question && !empty($question->options)) {
                $question->options = $this->shuffleOptions($question->options, ($user->id ?? 0) . '_' . ($question->id ?? $question->question_id ?? 0));
            }

            return [
                'success' => true,
                'quiz_id' => $quizId,
                'time_limit_minutes' => $quiz->time_limit_minutes,
                'question' => $question,
            ];
        } catch (\Exception $e) {
            Log::error('Quiz start failed', [
                'quiz_id' => $quizId,
                'user_id' => $user->id ?? null,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
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

            if (! $quiz) {
                return [
                    'success' => false,
                    'message' => 'Quiz not found',
                ];
            }

            $joined = QuizUserEnrollment::where('quiz_id', $quizId)
                ->where('user_id', $user->id)
                ->exists();

            if (! $joined) {
                return [
                    'success' => false,
                    'message' => 'Please join the quiz before starting',
                ];
            }

            // Next question
            $nextQuestion = QuizQuestion::where('quiz_id', $quizId)
                ->where('question_id', $data['last_question_id'])
                ->orderBy('order', 'asc')
                ->first([
                    'question_id',
                    'order',
                    'question_text',
                    'type',
                    // 'media_url',
                    'time_limit_seconds',
                ]);
            if ($nextQuestion) {
                $nextQuestion->time_limit_seconds = $quiz->per_que_time_limit_sec;
                if (!empty($nextQuestion->options)) {
                    $nextQuestion->options = $this->shuffleOptions($nextQuestion->options, ($user->id ?? 0) . '_' . ($nextQuestion->id ?? $nextQuestion->question_id ?? 0));
                }
            }

            return [
                'success'            => true,
                'quiz_id'            => $quizId,
                'time_limit_minutes' => $quiz->time_limit_minutes,
                'question'           => $nextQuestion,
            ];

        } catch (\Exception $e) {

            Log::error('Error fetching quiz question', [
                'user_id' => $user->id ?? null,
                'quiz_id' => $quizId,
                'question_id' => $data['question_id'] ?? null,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
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

        if (! $quiz) {
            return ['success' => false, 'message' => 'Quiz not found'];
        }

        $joined = QuizUserEnrollment::where('quiz_id', $quizId)->where('user_id', $user->id)->exists();

        if (! $joined) {
            return ['success' => false, 'message' => 'Please join the quiz before starting'];
        }

        $question = QuizQuestion::where('quiz_id', $quizId)
        ->where('question_id', $data['question_id'])
        ->orderBy('order', 'asc')
        ->first([
            'question_id',
            'options',
            'time_limit_seconds',
        ]);
        if ($question) {
            $question->time_limit_seconds = $quiz->per_que_time_limit_sec;
            if (!empty($question->options)) {
                $question->options = $this->shuffleOptions($question->options, ($user->id ?? 0) . '_' . ($question->id ?? $question->question_id ?? 0));
            }
        }


        return [
            'success'             => true,
            'quiz_id'             => $quizId,
            'time_limit_minutes'  => $quiz->time_limit_minutes,
            'question'           => $question,
        ];
    }


    public function submitAnswer($user, int $quizId, array $data): array
    {
        DB::beginTransaction();

        try {
            $answer = $data['answer'];
            
            $quizQuestion = QuizQuestion::where('quiz_id', $quizId)
                ->where('question_id', $data['question_id'])
                ->first();


            if (! $quizQuestion) {
                return [
                    'success' => false,
                    'message' => 'Question not part of this quiz'
                ];
            }

            if($answer == '' || $answer == null) {
                $answer = $question->options[0] ?? null;
            }
            
            $isCorrect = strcasecmp(
                trim((string) $quizQuestion->correct_answer),
                trim((string) $answer)
            ) === 0;

            $showTime = Carbon::parse($data['show_time']);
            $ansTime  = Carbon::parse($data['ans_time']);

            $diffInSeconds = $showTime->diffInSeconds($ansTime);

            QuizQuestionUserAnswer::updateOrCreate(
                [
                    'tenant_id'   => $quizQuestion->tenant_id ?? 1,
                    'quiz_id'     => $quizId,
                    'question_id' => $data['question_id'],
                    'user_id'     => $user->id,
                ],
                [
                    'answer'      => $answer,
                    'status'      => $isCorrect ? 1 : 0,
                    'show_time'   => $data['show_time'] ?? null,
                    'ans_time'    => $data['ans_time'] ?? null,
                    'total_time'  => $diffInSeconds,
                ]
            );

            $nextQuestion = QuizQuestion::where('quiz_id', $quizId, $quizQuestion->tenant_id)
                ->where('order', '>', $quizQuestion->order)
                ->orderBy('order')
                ->first();

            DB::commit();

            $complete = 'pending';
            $lastQuestionId = QuizQuestion::where('quiz_id', $quizId)
                ->orderByDesc('order')
                ->value('id');
               
           
            if ($quizQuestion->id == $lastQuestionId) {
                $complete = 'completed';
                $this->finalSubmit($quizId, $user, $quizQuestion->tenant_id);
            }

            return [
                'success'          => true,
                'remaining_quiz_question' => $complete,
                'message'          => 'Answer submitted successfully.',
                'next_question_id' => $nextQuestion->question_id ?? null,
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Quiz answer submission failed', [
                'user_id'     => $user->id ?? null,
                'quiz_id'     => $quizId,
                'question_id' => $data['question_id'] ?? null,
                'answer'      => $data['answer'] ?? null,
                'message'     => $e->getMessage(),
                'file'        => $e->getFile(),
                'line'        => $e->getLine(),
                'trace'       => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Something went wrong while submitting the answer. Please try again.'
            ];
        }
    }


    public function finalSubmit($quizId, $user, $tenantId) {
        $answers = QuizQuestionUserAnswer::where('tenant_id', $tenantId)
        ->where('quiz_id', $quizId)
        ->where('user_id', $user->id)
        ->get();

        $totalCorrect = 0;
        $totalTime    = 0;
        foreach ($answers as $ans)
        {
            $quizQuestion = QuizQuestion::where('quiz_id', $quizId)
                ->where('question_id', $ans['question_id'])
                ->first();

            if (! $quizQuestion) {
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
        $avgTime = $totalQuestions > 0
            ? round($totalTime / $totalQuestions, 3)
            : 0;

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

        if ($totalQuestions >= 1) {
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

        $updated = User::where('id', $user->id)
            ->where('available_quiz_limit', '>', 0)
            ->decrement('available_quiz_limit');
        if($updated){

        }

        QuizStatus::where('quiz_id', $quizId)
            ->increment('total_complete_quiz_user');

        QuizStatus::where('quiz_id', $quizId)
            ->increment('total_correct_ans', $totalCorrect);

        return [
            'success' => true,
            'message' => 'Quiz submitted successfully',
            //'result'  => $winner,
        ];
        

    }


    public function submitBulk($user, int $quizId, array $answers): array
    {
        try {
            return DB::transaction(function () use ($user, $quizId, $answers) {

                $totalCorrect = 0;
                $totalTime    = 0;

                foreach ($answers as $ans) {

                    $quizQuestion = QuizQuestion::where('quiz_id', $quizId)
                        ->where('question_id', $ans['question_id'])
                        ->first();

                    if (! $quizQuestion) {
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

                $totalQuestions = count($answers);
                $avgTime = $totalQuestions > 0
                    ? round($totalTime / $totalQuestions, 3)
                    : 0;

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

                $winner = null;
                if ($totalQuestions >= 1) {
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

                QuizStatus::where('quiz_id', $quizId)
                    ->increment('total_complete_quiz_user');

                QuizStatus::where('quiz_id', $quizId)
                    ->increment('total_correct_ans', $totalCorrect);

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
                'trace'         => $e->getTraceAsString(),
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

            // Calculate results if not already calculated
            $this->quizResult($quizId);
            
            $winner = QuizWinner::where('quiz_id', $quizId)
                ->where('user_id', $user->id)
                ->first();

            if (! $winner) {
                return [
                    'success' => false,
                    'message' => 'No result found for this quiz',
                ];
            }

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
                'trace'   => $e->getTraceAsString(),
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

            $this->quizResult($quizId);           

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
                ->values();

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
                'trace'   => $e->getTraceAsString(),
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
                'trace'   => $e->getTraceAsString(),
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
        try {

            DB::transaction(function () use ($user, $quizId) {

                QuizUserEnrollment::where('quiz_id', $quizId)
                    ->where('user_id', $user->id)
                    ->update([
                        'status' => 'inactive'
                    ]);

                $user->increment('quiz_leave');
            });

            return [
                'success' => true,
                'message' => 'You have left the quiz'
            ];

        } catch (\Exception $e) {

            Log::error('Quiz Leave Failed', [
                'quiz_id' => $quizId,
                'user_id' => $user->id ?? null,
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Unable to leave the quiz. Please try again.'
            ];
        }
    }

    public function quizResult($quizId)
    {
        try {        
            $quiz = Quiz::find($quizId);
            if (!$quiz) {
                return;
            }

            $hasActiveWinners = QuizWinner::where('quiz_id', $quizId)
                ->where('status', 'active')
                ->exists();

            $hasRewardedWinners = WalletHistory::where('tenant_id', $quiz->tenant_id ?? 1)
                ->where('transaction_type', 'quiz_reward')
                ->where(function($q) use ($quizId) {
                    $q->where('quiz_id', $quizId)
                      ->orWhere('reference_id', (string) $quizId)
                      ->orWhereIn('reference_id', function($sub) use ($quizId) {
                          $sub->select('id')->from('quiz_rank_rewards')->where('quiz_id', $quizId);
                      });
                })
                ->exists();

            if ($hasActiveWinners && $quiz->complete_status === 'completed' && $hasRewardedWinners) {
                return;
            }

            if ($quiz->complete_status === 'pending') {
                $quiz->update(['complete_status' => 'completed']);
            }

            $quizStatus = $this->quizStatus($quiz);

            if (empty($quizStatus->total_enroll_user) ||
                $quizStatus->total_enroll_user != $quizStatus->total_complete_quiz_user
            ) {
                $this->setDefaultAnsOfQuiz($quiz);
            }

            // Calculate winners
            $this->calculateUser($quiz);
            
            // update status             
            if ($quizStatus) {
                $quizStatus->update(['status' => 'complete']);
            }

        } catch (\Exception $e) {
            // Revert complete_status to 'pending' if calculation failed
            Quiz::where('id', $quizId)->update(['complete_status' => 'pending']);

            Log::error('Quiz Result Error', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);

            throw $e;
        }
    }

    // Calculate winners
    public function calculateUser($quiz)
    {
        try {

            $tenantId = $quiz->tenant_id;
            $quizId   = $quiz->id;
            $quiz = Quiz::where('id', $quizId)->first();
            $quizQuestionLimit = $quiz->question_limit;

            $enrollUserIds = QuizUserEnrollment::where('quiz_id', $quizId)->pluck('user_id')->toArray();
            $answerUserIds = QuizQuestionUserAnswer::where('quiz_id', $quizId)->pluck('user_id')->toArray();
            $winnerUserIds = QuizWinner::where('quiz_id', $quizId)->pluck('user_id')->toArray();
            $userIds       = array_values(array_unique(array_merge($enrollUserIds, $answerUserIds, $winnerUserIds)));

            $results = [];

            foreach ($userIds as $userId) {
                $answers = QuizQuestionUserAnswer::where([
                    'quiz_id' => $quizId,
                    'user_id' => $userId,
                ])->get();

                if ($answers->count() > 0) {
                    $totalCorrect = 0;
                    $totalTime = 0;

                    foreach ($answers as $answer) {
                        $question = QuizQuestion::where('quiz_id', $quizId)
                            ->where(function ($q) use ($answer) {
                                $q->where('id', $answer->question_id)
                                  ->orWhere('question_id', $answer->question_id);
                            })
                            ->first();

                        if (!$question) {
                            continue;
                        }

                        if (
                            strcasecmp(
                                trim($question->correct_answer),
                                trim($answer->answer)
                            ) === 0
                        ) {
                            $totalCorrect++;
                        }

                        $totalTime += (float) $answer->total_time;
                    }
                  
                    $results[] = [
                        'user_id'        => $enrollment->user_id,
                        'total_question' => $answers->count(),
                        'correct'        => $totalCorrect,
                        'time'           => $totalTime,
                        'avg_time'       => $answers->count() > 0
                            ? round($totalTime / $answers->count(), 2)
                            : 0,
                    ];
                }
               
            }

            // Sort by correct answers desc, then time asc
            usort($results, function ($a, $b) {

                if ($a['correct'] != $b['correct']) {
                    return $b['correct'] <=> $a['correct'];
                }

                return $a['time'] <=> $b['time'];
            });
          
            $rank = 1;
            // Set Rank
            $userCount = 0;
            foreach ($results as $result) {
                if ($result['correct'] < 1) {
                    continue;
                }
                $userCount++;
                $winner = QuizWinner::updateOrCreate(
                    [
                        'quiz_id' => $quizId,
                        'user_id' => $result['user_id'],
                    ],
                    [
                        'tenant_id'            => $tenantId,
                        'total_question'       => $quizQuestionLimit,
                        'total_correct_answer' => $result['correct'],
                        'total_time'           => $result['time'],
                        'per_ans_average_time' => $result['avg_time'],
                        'rank'                 => $rank,
                        'status'               => 'active',
                    ]
                );
                // set Reward
                $this->giveReward($quizId, $result['user_id'], $rank, $tenantId);
                $rank++;
            }

            QuizWinner::recalculateRanks((int) $quizId);

            $quizStatus = QuizStatus::where('quiz_id', $quizId)->first();

            if ($quizStatus) {
                $quizStatus->update([
                    'total_complete_quiz_user' => $userCount,
                    'total_played_user' => $userCount,
                ]);
            }

            return true;

        } catch (\Exception $e) {

            Log::error('Quiz Result Calculation Failed', [
                'quiz_id' => $quiz->id ?? null,
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    protected function giveReward($quizId, $userId, $rank, $tenantId)
    {
        try {
            DB::transaction(function () use ($quizId, $userId, $rank, $tenantId) {
                // Minimum 1 correct answer required to receive any reward
                $correctCount = QuizQuestionUserAnswer::where('tenant_id', $tenantId)
                    ->where('quiz_id', $quizId)
                    ->where('user_id', $userId)
                    ->where('status', 1)
                    ->count();

                if ($correctCount < 1) {
                    return;
                }

                // get Rank Reward
                $reward = QuizRankReward::where('tenant_id', $tenantId)
                    ->where('quiz_id', $quizId)
                    ->where('start_rank', '<=', $rank)
                    ->where('end_rank', '>=', $rank)
                    ->first();
             
                if (!$reward) {
                    return;
                }

                // Check if reward was already given to this user for this quiz
                $alreadyRewarded = WalletHistory::where('tenant_id', $tenantId)
                    ->where('user_id', $userId)
                    ->where('transaction_type', 'quiz_reward')
                    ->where(function($q) use ($reward, $quizId) {
                        $q->where('quiz_id', $quizId)
                          ->orWhere('reference_id', (string) $reward->id)
                          ->orWhere('reference_id', (string) $quizId);
                    })
                    ->exists();

                if ($alreadyRewarded) {
                    return;
                }

                $voucherHistoryId = is_array($reward->voucher_histories_id)
                ? $reward->voucher_histories_id
                : json_decode($reward->voucher_histories_id, true);

                if($reward->type == 'voucher') {
                    $voucherHistory =  VoucherHistory::where('tenant_id', $tenantId)
                        ->where('voucher_type', $reward->type)
                        ->whereIn('id', $voucherHistoryId)
                        ->whereNull('assign_to_user_id')
                        ->orderBy('id')
                        ->first();

                    $expireDate = now()->addDays($voucherHistory->expire_duration);

                    // update Rank Reward
                    $voucherHistory->update([       
                            'assign_to_user_id' => $userId,
                            'voucher_point'  => $reward->reward,
                            'assign_date'   => now(),
                            'expire_date'   => $expireDate,
                        ]);
                    
                    QuizWinner::updateOrCreate(
                    [
                        'quiz_id' => $quizId,
                        'user_id' => $userId,
                    ], 
                    [
                    'reward_type' => $reward->type,
                    'voucher_history_id' => $voucherHistory->id
                    ]);
                } elseif ($reward->type === 'point') {

                    $user = User::find($userId);

                    if ($user) {
                        $user->increment('wallet_balance', $reward->reward);

                        WalletHistory::create([
                            'tenant_id'        => $user->tenant_id ?? 1,
                            'user_id'          => $userId,
                            'quiz_id'          => $quizId,
                            'amount'           => $reward->reward,
                            'type'             => 'credit',
                            'transaction_type' => 'quiz_reward',
                            'reference_id'     => (string) $reward->id,
                            'remarks'          => "Reward Win from quiz",
                        ]);
                    }

                    QuizWinner::updateOrCreate(
                    [
                        'quiz_id' => $quizId,
                        'user_id' => $userId,
                    ], 
                    [
                        'reward_type' => $reward->type,
                        'reward' => $reward->reward,
                        'reward_id' =>  $reward->id,
                    ]);
                
                }

            });

        } catch (\Exception $e) {

            Log::error('Reward Assignment Failed', [
                'quiz_id' => $quizId,
                'user_id' => $userId,
                'rank'    => $rank,
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);
        }
    }


    protected function setDefaultAnsOfQuiz($quiz)
    {
        try {
            $tenantId = $quiz->tenant_id;
            $quizId   = $quiz->id;

            $enrollments = QuizUserEnrollment::where('quiz_id', $quizId)->get();
           
            $questions = QuizQuestion::where('quiz_id', $quizId)
                ->orderBy('order')
                ->get();

            foreach ($enrollments as $enrollment) {

                $time = Carbon::parse($quiz->availability_start)->format('H:i:s');
                $startTime = Carbon::createFromFormat('H:i:s', $time);
              
                foreach ($questions as $question) {

                    $exists = QuizQuestionUserAnswer::where([
                        'tenant_id'  => $tenantId,
                        'quiz_id'    => $quizId,
                        'user_id'    => $enrollment->user_id,
                        'question_id'=> $question->question_id,
                    ])->exists();

                    if (!$exists) {
        
                        $showTime = $startTime->copy();
                        $ansTime  = $startTime->copy()->addSeconds(10);

                        $answer = $question->options[0] ?? null;

                        $isCorrect = strcasecmp(
                            trim((string) $question->correct_answer),
                            trim((string) $answer)
                        ) === 0;

                        QuizQuestionUserAnswer::updateOrCreate(
                            [
                                'tenant_id'   => $tenantId,
                                'quiz_id'     => $quizId,
                                'question_id' => $question->question_id,
                                'user_id'     => $enrollment->user_id,
                            ],
                            [
                                'answer'      => $answer,
                                'status'      => $isCorrect ? 1 : 0,
                                'show_time'   => $showTime,
                                'ans_time'    => $ansTime,
                                'total_time'  => 10,
                            ]
                        );
                    }

                    // Move to next question time slot
                    $startTime->addSeconds(10);
                }

            }

            return true;

        } catch (\Exception $e) {

            Log::error('Set Default Quiz Answers Failed', [
                'quiz_id' => $quiz->id ?? null,
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    public function answerSheet($user, int $quizId): array
    {
        try {

            $questions = QuizQuestion::where('quiz_id', $quizId)
                ->orderBy('order')
                ->get();

            $data = [];
            $correct = 0;

            foreach ($questions as $question) {

                $userAnswer = QuizQuestionUserAnswer::where([
                    'quiz_id'     => $quizId,
                    'user_id'     => $user->id,
                    'question_id' => $question->question_id,
                ])->first();

                $selectedAnswer = $userAnswer->answer ?? null;

                $isCorrect = false;

                if ($selectedAnswer !== null) {
                    $isCorrect = strcasecmp(
                        trim((string)$question->correct_answer),
                        trim((string)$selectedAnswer)
                    ) === 0;
                }

                if ($isCorrect) {
                    $correct++;
                }

                $data[] = [
                    'question_id'     => $question->question_id,
                    'question'        => $question->question_text,
                    'options'         => $question->options,
                    'selected_answer' => $selectedAnswer,
                    'correct_answer'  => $question->correct_answer,
                    'is_correct'      => $isCorrect,
                    'explanation'     => $question->explanation,
                ];
            }

            return [
                'success' => true,
                'message' => 'Answer sheet fetched successfully.',
                'result'  => [
                    'total_questions' => $questions->count(),
                    'correct_answers' => $correct,
                    'wrong_answers'   => $questions->count() - $correct,
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
        if(!$quizStatus){
            $quizStatus = QuizStatus::create(
                [
                    'tenant_id' => $quiz->tenant_id,
                    'quiz_id' => $quiz->id,
                    'total_enroll_user' => 0                    
                ]
            );
        }
        return $quizStatus;
    }

    /******************************* END *******************************/ 
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
}
