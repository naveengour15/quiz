<?php

namespace App\Repositories\androidapi\Traits;

use App\Models\Quiz;
use App\Models\QuizUser as QuizUserEnrollment;
use App\Models\QuizRankReward;
use App\Models\QuizStatus;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

trait HandlesQuizLists
{
    public function availableList($user = null): array
    {
        $userId = $user?->id;
        $query = Quiz::whereIn('status', ['published', 'scheduled', 'active'])
            ->where('approve_status', 'approved')
            ->where('complete_status', 'pending');

        if ($userId) {
            $query->with([
                'userJoin' => function ($q) use ($userId) {
                    $q->select([
                        'id',
                        'quiz_id',
                        'user_id',
                        'remark',
                        'status',
                    ])
                    ->where('user_id', $userId);
                }
            ])
            ->withExists([
                'quizUsers as is_joined' => function ($q) use ($userId) {
                    $q->where('user_id', $userId)
                    ->where('status', 'active');
                }
            ]);
        }
        // Do not filter out quizzes when available_quiz_limit is 0 so card remains visible
        // if ($userId && ($user->available_quiz_limit ?? 0) <= 0) {
        //     $query->whereHas('quizUsers', function ($q) use ($userId) {
        //         $q->where('user_id', $userId);
        //     });
        // }

        $quizzes = $query
            ->where(function ($q) {
                $q->whereNull('availability_end')
                    ->orWhere('availability_end', '>=', now());
            })
            ->latest('id')
            ->get();

        return ['success' => true, 'data' => $quizzes, 'available_quiz_limit' => $user?->available_quiz_limit ?? 0];
    }

    public function upcomingList($user = null): array
    {
        $quizzes = Quiz::where('approve_status', 'approved')
            ->where('complete_status', 'pending')
            ->where(function ($q) {
                $q->where('scheduled_at', '>', now())
                  ->orWhere('status', 'scheduled');
            })
            ->orderBy('scheduled_at', 'asc')
            ->take(10)
            ->get();

        return ['success' => true, 'data' => $quizzes];
    }

    public function practiceList($user = null): array
    {
        $quizzes = Quiz::where('approve_status', 'approved')
            ->where(function ($q) {
                $q->where('visibility', 'practice')
                  ->orWhere('status', 'practice');
            })
            ->latest('id')
            ->get();

        return ['success' => true, 'data' => $quizzes];
    }

    public function detail($user, int $quizId): array
    {
        $quiz = Quiz::where('id', $quizId)
            ->with([
                'userJoin' => function ($q) use ($user) {
                    if ($user) {
                        $q->select([
                            'id',
                            'quiz_id',
                            'user_id',
                            'remark',
                            'status',
                        ])
                        ->where('user_id', $user->id);
                    }
                }
            ])
            ->when($user, function ($q) use ($user) {
                $q->withExists([
                    'quizUsers as is_joined' => function ($sq) use ($user) {
                        $sq->where('user_id', $user->id)
                           ->where('status', 'active');
                    }
                ]);
            })
            ->first();

        if (!$quiz) {
            return ['success' => false, 'message' => 'Quiz not found'];
        }

        $joinUsers = QuizUserEnrollment::with('user:id,name')
            ->where('quiz_id', $quizId)
            ->get()
            ->map(function ($enrollment) {
                return [
                    'user_name' => $enrollment->user?->name,
                    'user_id'   => $enrollment->user_id,
                ];
            });

        $totalJoinedUsers = count($joinUsers);

        $scheduledTime = Carbon::parse($quiz->scheduled_at);
        $startDateTime = Carbon::parse($quiz->scheduled_at)->addSeconds(10);
        $startTime     = $startDateTime->format('H:i:s');

        $remainingTime = $this->getQuestionRemainingTime($scheduledTime);

        return [
            'success'      => true,
            'quiz'         => $quiz,
            'total_joined' => $totalJoinedUsers,
            'joined_users' => $joinUsers,
            'scheduled_time' => $scheduledTime,
            'start_time' => $startTime,
            'pending_time' => $remainingTime,
            'rank_rewards' => QuizRankReward::where('quiz_id', $quizId)
                ->select([
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

    private function hasStartTimePassed(?Quiz $quiz): bool
    {
        if (!$quiz) {
            return false;
        }

        $startTime = $quiz->scheduled_at
            ? Carbon::parse($quiz->scheduled_at)
            : ($quiz->availability_start ? Carbon::parse($quiz->availability_start) : null);

        return $startTime ? $startTime->isPast() : false;
    }

    public function join($user, int $quizId, ?string $password): array
    {
        try {
            $quiz = Quiz::where('id', $quizId)->first();

            if (!$quiz) {
                return [
                    'success' => false,
                    'message' => 'Quiz not found.'
                ];
            }

            if ($this->hasStartTimePassed($quiz)) {
                return [
                    'success' => false,
                    'message' => 'This quiz has already started. Late join is not allowed.',
                    'redirect' => '/home',
                ];
            }

            if ($quiz->visibility === 'private' && $quiz->password !== $password) {
                return [
                    'success' => false,
                    'message' => 'Invalid quiz password'
                ];
            }

            $quizStatus = $this->quizStatus($quiz);

            $alreadyJoined = QuizUserEnrollment::where('quiz_id', $quizId)
                ->where('user_id', $user->id)
                ->exists();

            $scheduleTime  = Carbon::parse($quiz->scheduled_at)->format('H:i:s');
            $startDateTime = Carbon::parse($quiz->scheduled_at)->addSeconds(10);
            $startTime     = $startDateTime->format('H:i:s');

            $remainingTime = $this->getQuestionRemainingTime(Carbon::parse($quiz->scheduled_at));

            if ($alreadyJoined) {
                return [
                    'success' => false,
                    'message' => 'You have already joined this quiz',
                    'quiz_id' => $quizId,
                    'schedule_time' => $scheduleTime,
                    'start_time' => $startTime,
                    'pending_time' => $remainingTime,
                    'total_joined' => $quizStatus->total_enroll_user ?? 0,
                    'total_join_pending' => max(0, (int) ($quiz->no_of_players ?? 0) - (int) ($quizStatus->total_enroll_user ?? 0)),
                ];
            }

            if (($user->available_quiz_limit ?? 0) <= 0) {
                return [
                    'success' => false,
                    'message' => 'Your available quiz limit has expired.'
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

            User::where('id', $user->id)
                ->where('available_quiz_limit', '>', 0)
                ->decrement('available_quiz_limit');
            $user->refresh();

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
                    'message' => 'Player limit exist in this quiz.',
                    'total_joined' => $joined,
                    'total_join_pending' => 0,
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
                'schedule_time' => $scheduleTime,
                'start_time' => $startTime,
                'join_time' => Carbon::parse($quiz->login_time_period)->format('H:i:s'),
                'pending_time' => $remainingTime,
                'total_joined' => ($quizStatus->total_enroll_user ?? 0) + 1,
                'total_join_pending' => max(0, (int) ($quiz->no_of_players ?? 0) - (($quizStatus->total_enroll_user ?? 0) + 1)),
            ];

        } catch (\Exception $e) {
            Log::error('Quiz join failed', [
                'user_id'   => $user->id ?? null,
                'quiz_id'   => $quizId,
                'password'  => $password,
                'message'   => $e->getMessage(),
                'file'      => $e->getFile(),
                'line'      => $e->getLine(),
            ]);

            return [
                'success' => false,
                'message' => 'Something went wrong while joining the quiz. Please try again.'
            ];
        }
    }
} 
