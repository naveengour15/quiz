<?php

namespace App\Repositories\api;

use App\Interfaces\api\QuizWinnerRepositoryInterface;
use App\Interfaces\ActionTrailRepositoryInterface;
use App\Models\Quiz;
use App\Models\QuizQuestionUserAnswer;
use App\Models\QuizWinner;
use App\Models\QuizRankReward;
use App\Models\VoucherHistory;
use App\Models\WalletHistory;
use App\Models\LeaderPointHistory;
use App\Models\QuizStatus;
use App\Models\QuizCronJobHistory;
use App\Models\User;
use App\Models\QuizUser;
use App\Models\QuizQuestion;
use App\Jobs\MigrateFffQuizDataToPermanentJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Exception;

class QuizWinnerRepository implements QuizWinnerRepositoryInterface
{
    public function __construct(protected ActionTrailRepositoryInterface $actionTrail)
    {
    }

    public function actionTrailLog($tenantId, $tableName, $referenceId, $action, $details = null, $oldDetails = null, $quizId = null, $userId = null)
    {
        try {
            $authUserId = auth()->id() ?? 1;
            $roleId     = auth()->user()?->role_id ?? 1;

            return $this->actionTrail->create([
                'tenant_id'          => $tenantId ?? 1,
                'quiz_id'            => $quizId,
                'user_id'            => $userId,
                'survey_id'          => null,
                'table_name'         => $tableName,
                'reference_id'       => $referenceId,
                'action'             => $action,
                'updated_by'         => $authUserId,
                'updated_by_role_id' => $roleId,
                'old_details'        => $oldDetails ? (is_array($oldDetails) ? json_encode($oldDetails) : $oldDetails) : null,
                'details'            => $details ? (is_array($details) ? json_encode($details) : $details) : null,
                'status'             => 'success',
                'created_at'         => now(),
                'updated_at'         => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Action trail logging failed: ' . $e->getMessage());
            return null;
        }
    }

    public function generateQuizWinnerLeaderboard(int $quizId)
    { 
        try {
            $quiz = Quiz::find($quizId);
                    
            if ($quiz && $quiz->complete_status === 'pending') {
                \Illuminate\Support\Facades\Cache::lock("quiz_{$quizId}_mark_processing_lock", 10)->get(function () use ($quiz, $quizId) {
                    $freshQuiz = Quiz::find($quizId);
                    if ($freshQuiz && $freshQuiz->complete_status === 'pending') {
                        //$oldQuizStatusDetails = $freshQuiz->toArray();
                        
                        $second = 207; // 11 question + 20 second conter 
                        if($freshQuiz->visibility === 'fastest_finger_first'){
                            $second = 80;
                        }
                        if ($freshQuiz->scheduled_at && \Carbon\Carbon::parse($freshQuiz->scheduled_at)->addSeconds($second)->isPast()) {
                            $freshQuiz->update(['complete_status' => 'processing']);
                            $freshQuiz->refresh();
                        }
                        
                        
                        $scheduledAt = $freshQuiz->availability_end ? \Carbon\Carbon::parse($freshQuiz->availability_end) : now();
                        
                        $quizCronJob = QuizCronJobHistory::firstOrCreate(
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
            }
           
            return true;

        } catch (\Throwable $e) {
            Log::error('Quiz Result Error', [
                'quiz_id' => $quizId,
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    // ***********************udpate play quizes data start*************************************

    /**
     * Revert previously awarded leaderboard points and wallet balances for this specific quiz.
     */
    public function revertPreviousQuizRewardsAndPoints(int $quizId, int $tenantId = 1): void
    {
        DB::transaction(function () use ($quizId, $tenantId) {
            // 1. Revert previous wallet balances awarded for this quiz
            $walletHistories = WalletHistory::where('quiz_id', $quizId)
                ->where('type', 'credit')
                ->whereIn('transaction_type', ['quiz_reward', 'quiz_leaderboard_reward'])
                ->lockForUpdate()
                ->get();

            foreach ($walletHistories as $wh) {
                $amount = (float) ($wh->amount ?? 0);
                if ($amount > 0) {
                    $user = User::where('id', $wh->user_id)->lockForUpdate()->first();
                    if ($user) {
                        $currentBalance = (float) ($user->wallet_balance ?? 0);
                        $deduct = min($currentBalance, $amount);
                        if ($deduct > 0) {
                            $user->decrement('wallet_balance', $deduct);
                        }
                    }
                }
                $wh->delete();
            }

            // 2. Revert previous leaderboard points awarded for this quiz
            $leaderPointHistories = LeaderPointHistory::where('quiz_id', $quizId)
                ->where('type', 'credit')
                ->lockForUpdate()
                ->get();

            foreach ($leaderPointHistories as $lph) {
                $point = (float) ($lph->point ?? 0);
                if ($point > 0) {
                    $user = User::where('id', $lph->user_id)->lockForUpdate()->first();
                    if ($user) {
                        $currentPoints = (float) ($user->leader_board_point ?? 0);
                        $deduct = min($currentPoints, $point);
                        if ($deduct > 0) {
                            $user->decrement('leader_board_point', $deduct);
                        }
                    }
                }
                $lph->delete();
            }

            // 3. Reset reward fields in existing QuizWinner records for this quiz
            QuizWinner::where('quiz_id', $quizId)
                ->where('tenant_id', $tenantId)
                ->update([
                    'reward_type'   => null,
                    'reward_id'     => null,
                    'reward'        => null,
                    'reward_detail' => null,
                ]);
        });
    }

    public function quizUpdatePlayData(Quiz $quiz, bool $isCron = false): array
    {
        try {
            // Check condition: quiz answer time must be finished before play data can be updated
            if (!$isCron && !in_array($quiz->complete_status, ['processing', 'completed']) && !$quiz->isAnswerTimeFinished()) {
                return [
                    'success' => false,
                    'message' => 'Quiz play data can only be updated after quiz answer submission time has finished.'
                ];
            }

            // Auto-promote pending quiz to processing when SuperAdmin/cron initiates update
            if ($quiz->complete_status === 'pending') {
                if (($quiz->availability_end && \Carbon\Carbon::parse($quiz->availability_end)->isPast()) ||
                 ($quiz->scheduled_at && \Carbon\Carbon::parse($quiz->scheduled_at)->addMinutes(2)->isPast())) {
                    $quiz->update(['complete_status' => 'processing']);
                    $quiz->refresh();
                }
            }

            // Check condition: current quiz complete_status must be processing or completed
            if (!$isCron && !in_array($quiz->complete_status, ['processing', 'completed'])) {
                return [
                    'success' => false,
                    'message' => 'Quiz play data can only be updated when quiz complete status is processing or completed.'
                ];
            }

            // Route FFF (Fastest Finger First) quizzes to dedicated calculation
            if ($quiz->visibility === 'fastest_finger_first') {
                return $this->updateFffQuizPlayData($quiz);
            }

            // Step 0: Flush Redis in-memory answer batches to MySQL if Redis is available
            if (\App\Services\RedisQuizService::isAvailable()) {
                try {
                    \App\Services\RedisQuizService::persistQuizDataToMySQL($quiz->id);
                } catch (\Throwable $e) {
                    Log::warning("Redis persistQuizDataToMySQL error in quizUpdatePlayData for quiz {$quiz->id}: " . $e->getMessage());
                }
            }

            // Step 0.5: Revert previously awarded points and wallet balances for this quiz if already generated
            $this->revertPreviousQuizRewardsAndPoints($quiz->id, $quiz->tenant_id ?? 1);

            // Step 1: User-wise stats calculate karo aur Rank assign karo
            $calculateQuizQuestionUserAnswer = $this->calculateQuizQuestionUserAnswer($quiz->id, $quiz->tenant_id ?? 1);

            if (!empty($calculateQuizQuestionUserAnswer)) {
                // Step 2: Quiz Winners table me Rank update/insert karo
                $this->updateQuizWinnersData($quiz->id, $quiz->tenant_id ?? 1, $calculateQuizQuestionUserAnswer);
                
                // Step 3: Rank ke hisaab se Rewards calculate karke update karo
                $this->updateQuizRewards($quiz->id, $quiz->tenant_id ?? 1, $quiz);
                
                // Step 4: Update quiz status table  
                $this->quizStatusUpdate($quiz);
            } else {
                $this->updateQuizWinnersData($quiz->id, $quiz->tenant_id ?? 1, []);
            }

            try {
                QuizWinner::recalculateRanks((int) $quiz->id);
            } catch (\Throwable $e) {}

            try {
                app(\App\Interfaces\api\QuizRepositoryInterface::class)->leaderboardUserRankUpdate();
            } catch (\Throwable $e) {}

            return [
                'success' => true,
                'message' => "Quiz play data, ranks, and rewards updated successfully for: {$quiz->title}",
                'data' => [
                    'quiz_id' => $quiz->id,
                    'quiz_title' => $quiz->title,
                    'updated_at' => now()->toDateTimeString(),
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Quiz Play Data Update Failed', [
                'quiz_id' => $quiz->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to update quiz play data: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Process Fastest Finger First (FFF) Quiz play data and update leaderboard history & rewards.
     */
    public function updateFffQuizPlayData(Quiz $quiz): array
    {
        try {
            $quizId = $quiz->id;
            $tenantId = $quiz->tenant_id ?? 1;

            Log::info("FFF updateFffQuizPlayData started for quiz_id: {$quizId}");

            // Step 0: Flush Redis in-memory answer batches to MySQL if Redis is available
            if (\App\Services\RedisQuizService::isAvailable()) {
                try {
                    \App\Services\RedisQuizService::persistQuizDataToMySQL($quiz->id);
                } catch (\Throwable $e) {
                    Log::warning("Redis persistQuizDataToMySQL error in updateFffQuizPlayData for quiz {$quiz->id}: " . $e->getMessage());
                }
            }

            // Step 0.5: Revert previously awarded points and wallet balances for this quiz if already generated
            $this->revertPreviousQuizRewardsAndPoints($quizId, $tenantId);

            // Step 1: Calculate user stats from QuizQuestionUserAnswer
            $rawStats = QuizQuestionUserAnswer::where('quiz_id', $quizId)
                ->select(
                    'user_id',
                    DB::raw('MAX(tenant_id) as tenant_id'),
                    DB::raw('COUNT(id) as total_questions_attempted'),
                    DB::raw('SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as correct_answers_count'),
                    DB::raw('SUM(COALESCE(total_time, 0)) as total_time_taken')
                )
                ->groupBy('user_id')
                ->get();

            $statsArray = [];
            foreach ($rawStats as $stat) {
                $attempted = (int) $stat->total_questions_attempted;
                if ($attempted < 1) continue;

                $correct = (int) $stat->correct_answers_count;
                $tTime = (float) $stat->total_time_taken;
                $perAvg = $attempted > 0 ? (float) round($tTime / $attempted, 2) : 0.0;

                $statsArray[] = [
                    'tenant_id'                 => (int) ($stat->tenant_id ?: $tenantId),
                    'user_id'                   => (int) $stat->user_id,
                    'quiz_id'                   => (int) $quizId,
                    'total_questions_attempted' => $attempted,
                    'correct_answers_count'     => $correct,
                    'total_time_taken'          => $tTime,
                    'per_ans_average_time'      => $perAvg,
                ];
            }

            if (!empty($statsArray)) {
                // Step 2: Sort by max correct answers, then less total time
                usort($statsArray, function ($a, $b) {
                    if ($a['correct_answers_count'] != $b['correct_answers_count']) {
                        return $b['correct_answers_count'] <=> $a['correct_answers_count'];
                    }
                    return $a['total_time_taken'] <=> $b['total_time_taken'];
                });

                $previousCorrect = -1;
                $previousTime = -1;
                $currentRank = 1;
                $winnerRows = [];
                $nowStr = now()->toDateTimeString();

                foreach ($statsArray as $index => $stat) {
                    $currentCorrect = (int) $stat['correct_answers_count'];
                    $currentTime = $stat['total_time_taken'];

                    if ($currentCorrect === $previousCorrect && $currentTime === $previousTime) {
                        $rank = $currentRank;
                    } else {
                        $rank = $index + 1;
                        $currentRank = $rank;
                    }

                    $previousCorrect = $currentCorrect;
                    $previousTime = $currentTime;

                    $pointsEarned = $currentCorrect;

                    $winnerRows[] = [
                        'quiz_id'              => $quizId,
                        'user_id'              => $stat['user_id'],
                        'tenant_id'            => $stat['tenant_id'] ?: $tenantId,
                        'total_question'       => $stat['total_questions_attempted'],
                        'total_correct_answer' => $currentCorrect,
                        'total_time'           => $stat['total_time_taken'],
                        'per_ans_average_time' => $stat['per_ans_average_time'],
                        'rank'                 => $rank,
                        'reward_type'          => 'point',
                        'reward'               => (string) $pointsEarned,
                        'status'               => 'active',
                        'created_at'           => $nowStr,
                        'updated_at'           => $nowStr,
                    ];
                }

                // High-performance bulk upsert in chunks of 250
                foreach (array_chunk($winnerRows, 250) as $chunk) {
                    QuizWinner::upsert(
                        $chunk,
                        ['quiz_id', 'user_id'],
                        [
                            'tenant_id',
                            'total_question',
                            'total_correct_answer',
                            'total_time',
                            'per_ans_average_time',
                            'rank',
                            'reward_type',
                            'reward',
                            'status',
                            'updated_at'
                        ]
                    );
                }

                // High-speed bulk update for leaderboard point history
                $this->bulkUpdateFffLeaderboardHistory($quizId, $winnerRows, $quiz);

                // Step 3: Apply Rank Rewards if defined
                $this->updateQuizRewards($quizId, $tenantId, $quiz);

                // Invalidate cached leaderboard so fresh ranks are available immediately
                Cache::forget("fff_quiz_{$quizId}_leaderboard_winners");
                Cache::forget("quiz_{$quizId}_leaderboard_winners_cache");
            }

            // Step 4: Update QuizStatus & Quiz status
            $this->quizStatusUpdate($quiz);
            $quiz->update(['complete_status' => 'completed']);

            return [
                'success' => true,
                'message' => "Fastest Finger First quiz play data updated successfully for: {$quiz->title}",
                'data'    => [
                    'quiz_id'    => $quizId,
                    'quiz_title' => $quiz->title,
                    'updated_at' => now()->toDateTimeString(),
                ]
            ];
        } catch (\Exception $e) {
            Log::error('FFF Quiz Play Data Update Failed', [
                'quiz_id' => $quiz->id,
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to update FFF quiz play data: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * High-performance bulk leaderboard history update for FFF quizzes
     */
    public function bulkUpdateFffLeaderboardHistory(int $quizId, array $winnerRows, Quiz $quiz): void
    {
        try {
            $tenantId = $quiz->tenant_id ?? 1;
            $pointsPerCorrect = ($quiz->visibility === 'mahaquiz') ? 10 : 1;

            $eligibleWinners = array_filter($winnerRows, function ($row) {
                return (int) ($row['total_correct_answer'] ?? 0) > 0;
            });

            if (empty($eligibleWinners)) {
                return;
            }

            $userIds = array_values(array_unique(array_column($eligibleWinners, 'user_id')));

            // Load all users and their existing point histories in 2 batch queries
            $users = User::whereIn('id', $userIds)->select(['id', 'leader_board_point'])->get()->keyBy('id');
            $existingHistories = LeaderPointHistory::where('quiz_id', $quizId)
                ->where('type', 'credit')
                ->whereIn('user_id', $userIds)
                ->get()
                ->keyBy('user_id');

            $newHistories = [];
            $nowStr = now()->toDateTimeString();

            foreach ($eligibleWinners as $row) {
                $uid = (int) $row['user_id'];
                $totalCorrect = (int) ($row['total_correct_answer'] ?? 0);
                $earnedPoints = (float) ($pointsPerCorrect * $totalCorrect);

                $user = $users->get($uid);
                if (!$user) continue;

                $currentLeaderPoints = (float) ($user->leader_board_point ?? 0);
                $existing = $existingHistories->get($uid);

                if ($existing) {
                    $previousEarned = (float) ($existing->point ?? 0);
                    $diff = $earnedPoints - $previousEarned;
                    $oldPoint = (float) ($existing->old_point ?? 0);
                    $newPoint = max(0, $oldPoint + $earnedPoints);

                    $existing->update([
                        'old_point'  => $oldPoint,
                        'point'      => $earnedPoints,
                        'new_point'  => $newPoint,
                        'updated_at' => $nowStr,
                    ]);

                    if ($diff != 0) {
                        User::where('id', $uid)->increment('leader_board_point', $diff);
                    }
                } else {
                    $newPoint = $currentLeaderPoints + $earnedPoints;
                    $newHistories[] = [
                        'tenant_id'  => $tenantId,
                        'user_id'    => $uid,
                        'quiz_id'    => $quizId,
                        'old_point'  => $currentLeaderPoints,
                        'point'      => $earnedPoints,
                        'new_point'  => $newPoint,
                        'type'       => 'credit',
                        'created_at' => $nowStr,
                        'updated_at' => $nowStr,
                    ];

                    User::where('id', $uid)->increment('leader_board_point', $earnedPoints);
                }
            }

            if (!empty($newHistories)) {
                foreach (array_chunk($newHistories, 250) as $chunk) {
                    LeaderPointHistory::insert($chunk);
                }
            }
        } catch (\Throwable $e) {
            Log::error("bulkUpdateFffLeaderboardHistory error: " . $e->getMessage());
        }
    }

    /**
     * Apply Rank Rewards for FFF quizzes (delegates to standard updateQuizRewards).
     */
    public function updateTempQuizRewards($quizId, $tenantId, $quiz)
    {
        return $this->updateQuizRewards($quizId, $tenantId, $quiz);
    }

    /**
     * Permanent migration handler for FFF (all data is already stored in permanent tables).
     */
    public function migrateFffDataToPermanentTables(int $quizId): array
    {
        $quiz = Quiz::withoutGlobalScopes()->find($quizId);
        if (!$quiz) {
            return ['success' => false, 'message' => 'Quiz not found'];
        }

        return $this->updateFffQuizPlayData($quiz);
    }

    // 1. Calculated quiz question user ans
    public function calculateQuizQuestionUserAnswer($quizId, $tenant_id)
    {
        try {
            $rawStats = QuizQuestionUserAnswer::where('quiz_id', $quizId)
                ->where('tenant_id', $tenant_id)
                ->select(
                    'tenant_id',
                    'user_id',
                    DB::raw('COUNT(id) as total_questions_attempted'),
                    DB::raw('SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as correct_answers_count'),
                    DB::raw('SUM(COALESCE(total_time, 0)) as total_time_taken')
                )
                ->groupBy('tenant_id', 'user_id')
                ->get();

            if ($rawStats->isEmpty()) {
                return [];
            }

            $finalStats = [];

            foreach ($rawStats as $stat) {
                $totalAttempted = (int) $stat->total_questions_attempted;

                // Minimum 1 attempted answer required to qualify for quiz_winner & leaderboard
                if ($totalAttempted < 1) {
                    continue;
                }

                $correctCount = (int) $stat->correct_answers_count;
                $totalTime = (float) ($stat->total_time_taken ?? 0);
                
                $perAnsAverageTime = $totalAttempted > 0 
                    ? (float) round(($totalTime / $totalAttempted), 2) 
                    : 0.0;

                $finalStats[] = [
                    'tenant_id'                 => (int) $stat->tenant_id,
                    'user_id'                   => (int) $stat->user_id,
                    'quiz_id'                   => (int) $quizId,
                    'total_questions_attempted' => $totalAttempted,
                    'correct_answers_count'     => $correctCount,
                    'total_time_taken'          => $totalTime,
                    'per_ans_average_time'      => $perAnsAverageTime,
                ];
            }

            return $finalStats;

        } catch (\Exception $e) {
            Log::error('Quiz Question User Answer Calculation Failed', [
                'quiz_id'   => $quizId,
                'tenant_id' => $tenant_id,
                'error_msg' => $e->getMessage(),
                'trace'     => $e->getTraceAsString()
            ]);

            return [];
        }
    }

    // 2. Update Quiz Winner Table data
    public function updateQuizWinnersData($quizId, $tenant_id, $calculateQuizQuestionUserAnswer)
    {
        try {
            // Delete any existing winner records with no questions attempted
        QuizWinner::where('quiz_id', $quizId)
            ->where('tenant_id', $tenant_id)
            ->where(function ($q) {
                $q->where('total_question', '<', 1)
                  ->orWhereNull('total_question');
            })
            ->delete();

        if (empty($calculateQuizQuestionUserAnswer)) {
            QuizWinner::where('quiz_id', $quizId)->where('tenant_id', $tenant_id)->delete();
            return;
        }

        // Filter participants with minimum 1 question attempted
        $calculateQuizQuestionUserAnswer = array_values(array_filter($calculateQuizQuestionUserAnswer, function ($item) {
            return (int) ($item['total_questions_attempted'] ?? 0) >= 1;
        }));

        if (empty($calculateQuizQuestionUserAnswer)) {
            QuizWinner::where('quiz_id', $quizId)->where('tenant_id', $tenant_id)->delete();
            return;
        }

        // Delete any existing winner record whose user is not in the attempted list
        $attemptedUserIds = array_column($calculateQuizQuestionUserAnswer, 'user_id');
        QuizWinner::where('quiz_id', $quizId)
            ->where('tenant_id', $tenant_id)
            ->whereNotIn('user_id', $attemptedUserIds)
            ->delete();

        DB::beginTransaction();

            if (empty($calculateQuizQuestionUserAnswer)) {
                DB::commit();
                return;
            }

            usort($calculateQuizQuestionUserAnswer, function ($a, $b) {
                if ($a['correct_answers_count'] != $b['correct_answers_count']) {
                    return $b['correct_answers_count'] <=> $a['correct_answers_count'];
                }
                return $a['total_time_taken'] <=> $b['total_time_taken'];
            });

            $previousCorrect = -1;
            $previousTime = -1;
            $currentRank = 1;

            foreach ($calculateQuizQuestionUserAnswer as $index => $stat) {
                $currentCorrect = (int) $stat['correct_answers_count'];
                $currentTime = $stat['total_time_taken'];

                if ($currentCorrect === $previousCorrect && $currentTime === $previousTime) {
                    $rank = $currentRank;
                } else {
                    $rank = $index + 1;
                    $currentRank = $rank;
                }

                $previousCorrect = $currentCorrect;
                $previousTime = $currentTime;

                $searchCriteria = [
                    'quiz_id'   => $quizId,
                    'user_id'   => $stat['user_id'],
                    'tenant_id' => $tenant_id,
                ];

                $updateData = [
                    'total_question'       => $stat['total_questions_attempted'],
                    'total_correct_answer' => $stat['correct_answers_count'],
                    'total_time'           => $stat['total_time_taken'],
                    'per_ans_average_time' => $stat['per_ans_average_time'],
                    'rank'                 => $rank,
                    'status'               => 'active',
                ];

                $existingWinner = QuizWinner::where($searchCriteria)->first();
                $oldDetails = $existingWinner ? $existingWinner->toArray() : null;
                
                //$actionType = $existingWinner ? 'update quiz winner status & rank' : 'add new quiz winner status & rank';

                $updatedWinner = QuizWinner::updateOrCreate($searchCriteria, $updateData);
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Quiz Winners Data Update Failed', [
                'quiz_id'     => $quizId,
                'tenant_id'   => $tenant_id,
                'error_msg'   => $e->getMessage(),
                'trace'       => $e->getTraceAsString()
            ]);

            throw $e; 
        }
    }

    // 3. Update Reward Quiz Winner Table
    public function updateQuizRewards($quizId, $tenant_id, $quiz)
    {
        $rankRewards = QuizRankReward::where('quiz_id', $quizId)
            ->where('tenant_id', $tenant_id)
            ->where('status', 'active')
            ->get();

        $winners = QuizWinner::where('quiz_id', $quizId)
            ->where('tenant_id', $tenant_id)
            ->where('total_question', '>=', 1)
            ->get();
        $groupedByRank = $winners->groupBy('rank');

        foreach ($groupedByRank as $rank => $usersInThisRank) {
            $tiedCount = $usersInThisRank->count();
            $totalTiedReward = 0;
            $primaryRewardRule = null;

            for ($r = $rank; $r < $rank + $tiedCount; $r++) {
                $rule = $rankRewards->first(function ($item) use ($r) {
                    return $r >= $item->start_rank && $r <= $item->end_rank;
                });
                if ($rule) {
                    if (!$primaryRewardRule) {
                        $primaryRewardRule = $rule;
                    }
                    $totalTiedReward += (float) $rule->reward;
                }
            }

            if ($primaryRewardRule) {
                $finalRewardPerUser = round($totalTiedReward / $tiedCount, 2);
                $rewardType = $primaryRewardRule->type;
                $rewardId = $primaryRewardRule->id;

                foreach ($usersInThisRank as $winner) {
                    DB::beginTransaction();
                    
                    try {
                        $winner->update([
                            'reward_type'   => $rewardType,
                            'reward_id'     => $rewardId,
                            'reward'        => $finalRewardPerUser,
                            'reward_detail' => $rewardType . ' type award',
                        ]);

                        if ($quiz->visibility !== 'leaderboard' && $rewardType === 'point' && $finalRewardPerUser > 0) {
                            $targetUser = User::find($winner->user_id);
                            
                            if ($targetUser) {
                                $oldWalletDetails = $targetUser->toArray();
                                $old_wallet_balance = (float) ($targetUser->wallet_balance ?? 0);

                                $existingHistory = WalletHistory::where('tenant_id', $tenant_id)
                                    ->where('user_id', $targetUser->id)
                                    ->where('quiz_id', $quizId)
                                    ->latest('id')->first();

                                if ($existingHistory) {
                                    $oldAmount = (float) $existingHistory->amount;
                                    
                                    $targetUser->decrement('wallet_balance', $oldAmount);
                                    $targetUser->increment('wallet_balance', $finalRewardPerUser);
                                    $targetUser->refresh();

                                    $new_wallet_balance = (float) $targetUser->wallet_balance;

                                    $existingHistory->update([
                                        'amount'             => $finalRewardPerUser,
                                        'old_wallet_balance' => $old_wallet_balance,
                                        'new_wallet_balance' => $new_wallet_balance,
                                        'type'               => 'credit',
                                        'transaction_type'   => 'quiz_reward',
                                        'remarks'            => "Reward Win from quiz (Adjusted)",
                                        'updated_at'         => now(),
                                    ]);
                                    
                                    $trailAction = 'update wallet reward (adjusted)';

                                } else {
                                    $targetUser->increment('wallet_balance', $finalRewardPerUser);
                                    $targetUser->refresh();

                                    $new_wallet_balance = (float) $targetUser->wallet_balance;

                                    WalletHistory::create([
                                        'tenant_id'        => $tenant_id,
                                        'user_id'          => $targetUser->id,
                                        'quiz_id'          => $quizId,
                                        'amount'           => $finalRewardPerUser,
                                        'old_wallet_balance'=> $old_wallet_balance,
                                        'new_wallet_balance'=> $new_wallet_balance,
                                        'type'             => 'credit',
                                        'transaction_type' => 'quiz_reward',
                                        'reference_id'     => (string) $quizId,
                                        'remarks'          => "Reward Win from quiz (Add)",
                                        'created_at'       => now(),
                                        'updated_at'       => now(),
                                    ]);

                                    $trailAction = 'add wallet reward';
                                }
                            }
                        }

                        if ($quiz->visibility !== 'leaderboard' && $rewardType === 'voucher' && $rewardRule->voucher_id && $rewardRule->voucher_histories_id) {
                            $voucherPayload = [
                                'tenant_id'            => $tenant_id,
                                'quiz_id'              => $quizId,
                                'winner'               => $winner,
                                'voucher_id'           => $rewardRule->voucher_id,
                                'voucher_histories_id' => $rewardRule->voucher_histories_id,
                                'reward_id'            => $rewardId,
                            ];

                            $this->assignVoucherToWinner($voucherPayload);
                        }

                        if ($quiz->visibility === 'leaderboard'){
                            $this->updateLeaderboardQuizPoint($quizId, $winner, $quiz);
                        } else {
                            $this->updateQuizLeaderboardHistory($quizId, $winner, $quiz);
                        }

                        DB::commit();

                    } catch (\Exception $e) {
                        DB::rollBack();
                        
                        Log::error('Quiz Reward Update Failed for User', [
                            'user_id' => $winner->user_id ?? 'unknown',
                            'quiz_id' => $quizId,
                            'error_message' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        ]);
                    }
                }
            }
        }

        // Ensure all winners have leaderboard history/points updated regardless of rank reward matching
        foreach ($winners as $winner) {
            try {
                if ($quiz->visibility === 'leaderboard') {
                    $this->updateLeaderboardQuizPoint($quizId, $winner, $quiz);
                } else {
                    $this->updateQuizLeaderboardHistory($quizId, $winner, $quiz);
                }
            } catch (\Throwable $e) {
                Log::error('Quiz Winner Leaderboard Point Update Failed for User', [
                    'user_id' => $winner->user_id ?? 'unknown',
                    'quiz_id' => $quizId,
                    'error_message' => $e->getMessage(),
                ]);
            }
        }

        // Send FCM winner notification with points/marks to each winner
        $this->sendWinnerPushNotifications($quiz, $winners);
    }

    /**
     * Send winner notification to each winner with their rank, points and marks
     */
    protected function sendWinnerPushNotifications($quiz, $winners): void
    {
        try {
            $firebaseService = app(\App\Services\FirebaseNotificationService::class);
            if (!$firebaseService->getAccessToken() && empty(config('firebase.server_key'))) {
                return;
            }

            foreach ($winners as $winner) {
                if (empty($winner->user_id)) {
                    continue;
                }

                $tokens = \App\Models\DeviceToken::where('user_id', $winner->user_id)
                    ->where('is_active', true)
                    ->pluck('token')
                    ->filter()
                    ->unique()
                    ->toArray();

                if (empty($tokens)) {
                    continue;
                }

                $rank = $winner->rank ?? 1;
                $reward = (float) ($winner->reward ?? 0);
                $formattedReward = ($reward == (int) $reward) ? (string) (int) $reward : (string) $reward;
                $quizName = trim((string) ($quiz->title ?? '')) ?: ('Quiz #' . $quiz->id);

                $isMarksQuiz = in_array($quiz->visibility, ['leaderboard', 'fastest_finger_first'], true);

                $title = "🏆 बधाई हो! आप Quiz में विजेता रहे!";
                if ($isMarksQuiz) {
                    // Leaderboard and Fastest Finger First Quizzes give Marks according to rank
                    $body = "शानदार प्रदर्शन! आपने '{$quizName}' में Rank #{$rank} हासिल की है! आपको {$formattedReward} Marks मिले हैं।";
                    if ($quiz->visibility == 'leaderboard') {
                        $body = "शानदार प्रदर्शन! आपने '{$quizName}' में Rank #{$rank} हासिल की है! 🎉 आपके Marks अब {$formattedReward} गुना (x{$formattedReward}) हो जाएंगे! 🚀";
    
                    }
                } else {
                    // Public Quiz gives Points according to rank
                    $body = "शानदार प्रदर्शन! आपने '{$quizName}' में Rank #{$rank} हासिल की है! आपको {$formattedReward} Points मिले हैं।";
                }

                $data = [
                    'type'            => 'quiz_winner',
                    'quiz_id'         => (string) $quiz->id,
                    'quiz_title'      => (string) $quizName,
                    'rank'            => (string) $rank,
                    'reward'          => (string) $formattedReward,
                    'points'          => $isMarksQuiz ? '' : (string) $formattedReward,
                    'marks'           => $isMarksQuiz ? (string) $formattedReward : '',
                    'visibility'      => (string) ($quiz->visibility ?? 'public'),
                    'action'          => 'open_leaderboard',
                    'click_action'    => 'FLUTTER_NOTIFICATION_CLICK',
                ];

                $firebaseService->sendToTokens($tokens, $title, $body, $data);

                try {
                    \App\Models\InboxNotification::create([
                        'tenant_id'    => $quiz->tenant_id ?? 1,
                        'user_id'      => $winner->user_id,
                        'audience'     => 'user',
                        'type'         => 'quiz_winner',
                        'title'        => $title,
                        'body'         => $body,
                        'action'       => 'open_leaderboard',
                        'data'         => $data,
                        'related_type' => 'quiz',
                        'related_id'   => $quiz->id,
                        'status'       => 'published',
                        'published_at' => now(),
                    ]);
                } catch (\Throwable $e) {}
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Winner notification send error: ' . $e->getMessage());
        }
    }

    // 4. Assign Voucher To Winner
    public function assignVoucherToWinner(array $data)
    {
        try {
            $tenant_id              = $data['tenant_id'] ?? null;
            $quizId                 = $data['quiz_id'] ?? null;
            $winner                 = $data['winner'] ?? null;
            $voucher_id             = $data['voucher_id'] ?? null;
            $voucher_histories_json = $data['voucher_histories_id'] ?? null;
            $rewardRule_id          = $data['reward_id'] ?? null;

            if (!$tenant_id || !$quizId || !$winner || !$voucher_histories_json) {
                throw new Exception("Missing required data in voucher payload.");
            }

            if (is_string($voucher_histories_json)) {
                $historyIds = json_decode($voucher_histories_json, true);
            } elseif (is_array($voucher_histories_json)) {
                $historyIds = $voucher_histories_json;
            } else {
                $historyIds = [];
            }

            if (empty($historyIds) || !is_array($historyIds)) {
                throw new Exception("Invalid or empty voucher_histories_id provided.");
            }

            $voucherHistory = VoucherHistory::whereIn('id', $historyIds)
                ->where('tenant_id', $tenant_id)
                ->where('status', 'assigned_to_quiz')
                ->lockForUpdate()
                ->first();

            if (!$voucherHistory) {
                Log::warning('No vouchers with "assigned_to_quiz" status available', [
                    'quiz_id'       => $quizId,
                    'user_id'       => $winner->user_id,
                    'attempted_ids' => $historyIds
                ]);
                
                $winner->update([
                    'voucher_history_id' => null,
                    'reward_detail'      => 'Voucher reward (Out of stock)',
                ]);
                
                return false; 
            }

            $expireDuration = (int) ($voucherHistory->expire_duration ?? 0);
            $expireDate = now()->addDays($expireDuration);

            $voucherHistory->update([
                'assign_to_user_id' => $winner->user_id,
                'assign_date'       => now(),
                'expire_date'       => $expireDate,
                'status'            => 'assigned_to_user',
                'updated_at'        => now(),
            ]);

            $winner->update([
                'voucher_history_id' => $voucherHistory->id,
                'reward_detail'      => "Voucher Assigned: {$voucherHistory->code}",
            ]);

            return true;

        } catch (Exception $e) {
            Log::error('assignVoucherToWinner Function Failed', [
                'user_id'       => $data['winner']->user_id ?? 'unknown',
                'quiz_id'       => $data['quiz_id'] ?? 'unknown',
                'error_message' => $e->getMessage(),
                'trace'         => $e->getTraceAsString()
            ]);

            throw $e; 
        }
    }

    // 5. Update Leaderboard Quiz into(*) Points
    public function updateLeaderboardQuizPoint($quizId, $winner, $quiz)
    {
        try {
            $tenantId = $quiz->tenant_id ?? 1;
            $userId   = $winner->user_id;
            $earnedPoints = (float) ($winner->reward ?? 0);

            $user = User::select([
                'id',
                'leader_board_point',
            ])->find($userId);

            if (!$user) {
                Log::warning('Quiz leaderboard: User not found', [
                    'tenant_id' => $tenantId,
                    'quiz_id'   => $quizId,
                    'user_id'   => $userId,
                ]);

                return false;
            }

            //$oldUserData = $user->toArray();
            $currentUserLeaderPoints = (float) ($user->leader_board_point ?? 0);

            $leaderboardPoints = LeaderPointHistory::query()
                ->where('tenant_id', $tenantId)
                ->where('user_id', $userId)
                ->where('quiz_id', $quizId)
                ->first();

            if ($leaderboardPoints) {
                //$oldHistoryData = $leaderboardPoints->toArray();
                $currentUserLeaderPoints = (float) ($leaderboardPoints->old_point ?? 0);
                $newUserPoints = $currentUserLeaderPoints * $earnedPoints;

                $creditType = $newUserPoints >= $currentUserLeaderPoints ? 'credit' : 'debit';

                $leaderboardPoints->update([
                    'old_point'  => $currentUserLeaderPoints,
                    'point'      => $earnedPoints,
                    'new_point'  => $newUserPoints,
                    'type'       => $creditType,
                    'updated_at' => now(),
                ]);

                //$trailAction = 'update quiz points';
            } else {
                //$oldHistoryData = null;
                $currentUserLeaderPoints = (float) ($user->leader_board_point ?? 0);
                $newUserPoints = $currentUserLeaderPoints * $earnedPoints;

                $creditType = $newUserPoints >= $currentUserLeaderPoints ? 'credit' : 'debit';

                $leaderboardPoints = LeaderPointHistory::create([
                    'tenant_id'  => $tenantId,
                    'user_id'    => $userId,
                    'quiz_id'    => $quizId,
                    'old_point'  => $currentUserLeaderPoints,
                    'point'      => $earnedPoints,
                    'new_point'  => $newUserPoints,
                    'type'       => $creditType,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                //$trailAction = 'add quiz points';
            }

            $user->update([
                'leader_board_point' => $newUserPoints,
            ]);

            $user->leader_board_point = $newUserPoints;

            return true;

        } catch (\Throwable $e) {
            Log::error('Quiz leaderboard point update failed', [
                'tenant_id'    => $quiz->tenant_id ?? 1,
                'quiz_id'      => $quizId,
                'user_id'      => $winner->user_id ?? null,
                'earned_point' => $winner->reward ?? null,
                'error'        => $e->getMessage(),
                'file'         => $e->getFile(),
                'line'         => $e->getLine(),
                'trace'        => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    // 6. Update Leaderboard + Points in history table
    public function updateQuizLeaderboardHistory($quizId, $winner, $quiz)
    {
        try {
            $tenantId    = $quiz->tenant_id ?? 1;
            $userId      = $winner->user_id;
            $totalCorrect = (int) ($winner->total_correct_answer ?? 0);

            if ($totalCorrect <= 0) {
                Log::info('Quiz leaderboard: No points earned', [
                    'tenant_id'    => $tenantId,
                    'quiz_id'      => $quizId,
                    'user_id'      => $userId,
                    'total_correct' => $totalCorrect,
                    'total_time'   => $winner->total_time ?? 0,
                ]);

                return false;
            }

            $pointsPerCorrect = ($quiz->visibility === 'mahaquiz') ? 10 : 1;
            $earnedPoints = (float) ($pointsPerCorrect * $totalCorrect);

            $user = User::select([
                'id',
                'leader_board_point',
            ])->find($userId);

            if (!$user) {
                Log::warning('Quiz leaderboard: User not found', [
                    'tenant_id' => $tenantId,
                    'quiz_id'   => $quizId,
                    'user_id'   => $userId,
                ]);

                return false;
            }

            //$oldUserData = $user->toArray();
            $currentUserLeaderPoints = (float) ($user->leader_board_point ?? 0);

            $leaderboardPoints = LeaderPointHistory::query()
                ->where('tenant_id', $tenantId)
                ->where('user_id', $userId)
                ->where('quiz_id', $quizId)
                ->where('type', 'credit')
                ->lockForUpdate()
                ->first();

            if ($leaderboardPoints) {
                //$oldHistoryData = $leaderboardPoints->toArray();
                $previousEarned = (float) ($leaderboardPoints->point ?? 0);
                $diff = $earnedPoints - $previousEarned;
                $currentUserLeaderPoints = (float) ($leaderboardPoints->old_point ?? 0);
                $newUserPoints = max(0, $currentUserLeaderPoints + $earnedPoints);

                $leaderboardPoints->update([
                    'old_point'  => $currentUserLeaderPoints,
                    'point'      => $earnedPoints,
                    'new_point'  => $newUserPoints,
                    'type'       => 'credit',
                    'updated_at' => now(),
                ]);

                if ($diff != 0) {
                    $user->increment('leader_board_point', $diff);
                }

                $user->refresh();
                //$trailAction = 'update quiz points';
            } else {
                //$oldHistoryData = null;
                $newUserPoints = $currentUserLeaderPoints + $earnedPoints;

                $leaderboardPoints = LeaderPointHistory::create([
                    'tenant_id'  => $tenantId,
                    'user_id'    => $userId,
                    'quiz_id'    => $quizId,
                    'old_point'  => $currentUserLeaderPoints,
                    'point'      => $earnedPoints,
                    'new_point'  => $newUserPoints,
                    'type'       => 'credit',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $user->increment('leader_board_point', $earnedPoints);
                $user->leader_board_point = $newUserPoints;
                //$trailAction = 'add quiz points';
            }

            return true;

        } catch (\Throwable $e) {
            Log::error('Quiz leaderboard history update failed', [
                'tenant_id'     => $quiz->tenant_id ?? 1,
                'quiz_id'       => $quizId,
                'user_id'       => $winner->user_id ?? null,
                'total_correct' => $winner->total_correct_answer ?? null,
                'reward'        => $winner->reward ?? null,
                'visibility'    => $quiz->visibility ?? null,
                'error'         => $e->getMessage(),
                'file'          => $e->getFile(),
                'line'          => $e->getLine(),
                'trace'         => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    // 7. Update Quiz Status table
    public function quizStatusUpdate($quiz)
    {
        $tenantId   = $quiz->tenant_id ?? 1;
        $quizId     = $quiz->id;
        //$oldQuizDetails = $quiz->toArray();

        $quiz->update([
            'complete_status' => 'completed',
        ]);

        $quiz->refresh();

        try {
            \Illuminate\Support\Facades\Cache::put("last_quiz_status_t{$tenantId}_q{$quizId}", [
                'id' => $quiz->id,
                'complete_status' => 'completed',
            ], 3600);
            \Illuminate\Support\Facades\Cache::put("last_quiz_status_t1_q{$quizId}", [
                'id' => $quiz->id,
                'complete_status' => 'completed',
            ], 3600);
            \Illuminate\Support\Facades\Cache::forget("last_quiz_status_t{$tenantId}_latest");
            \Illuminate\Support\Facades\Cache::forget("last_quiz_status_t1_latest");
            \Illuminate\Support\Facades\Cache::forget("android_last_quiz_status_t{$tenantId}_q{$quizId}");
            \Illuminate\Support\Facades\Cache::forget("android_last_quiz_status_t{$tenantId}_latest");
        } catch (\Throwable $e) {}

        $quizStatus = QuizStatus::query()
            ->where('quiz_id', $quizId)
            ->first();

        if ($quizStatus) {
            //$oldQuizStatusDetails = $quizStatus->toArray();

            $quizStatus->update([
                'status' => 'completed',
            ]);

            $quizStatus->refresh();
        }
        
        // Guarantee QuizCronJobHistory has status 'success' (even if updated before scheduled time)
        QuizCronJobHistory::updateOrCreate(
            [
                'quiz_id' => $quizId,
                'cron_type' => 'quiz_winner_leaderboard',
            ],
            [
                'tenant_id' => $tenantId,
                'scheduled_at' => $quiz->availability_end ? \Carbon\Carbon::parse($quiz->availability_end) : now(),
                'status' => 'success',
                'updated_at' => now(),
            ]
        );

        QuizCronJobHistory::where('quiz_id', $quizId)
            ->whereIn('status', ['pending', 'failed'])
            ->update([
                'status' => 'success',
                'updated_at' => now(),
            ]);

        // Clean up Redis in-memory data for this finished quiz
        if (\App\Services\RedisQuizService::isAvailable()) {
            try {
                \App\Services\RedisQuizService::clearQuizData((int) $quizId);
            } catch (\Throwable $e) {
                Log::warning("clearQuizData error for quiz {$quizId}: " . $e->getMessage());
            }
        }
    }
    //************udpate play quizes data end */

    // ***********************Cron Job: generateQuizWinner start********************************
    public function generateQuizWinner(): array
    { 
        try {
            $cronJobs = QuizCronJobHistory::whereIn('status', ['pending', 'failed'])
                ->where(function ($q) {
                    $q->whereNull('scheduled_at')
                      ->orWhere('scheduled_at', '<=', now()->addMinutes(1));
                })
                ->orderBy('scheduled_at', 'asc')
                ->orderBy('id', 'asc')
                ->get();

            if ($cronJobs->isEmpty()) {
                return ['success' => true, 'message' => 'No pending or failed quiz cron jobs found.', 'processed' => 0];
            }
            Log::info('generateQuizWinner cron execution started');

            $processed = 0;
            $failed = 0;

            foreach ($cronJobs as $cronJob) {
                $quiz = Quiz::find($cronJob->quiz_id);

                if (!$quiz) {
                    $cronJob->update(['status' => 'failed']);
                    $failed++;
                    continue;
                }

                // Agar quiz already 'completed' ho chuki hai, toh is cron job ko seedha 'success' mark karein
                if ($quiz->complete_status === 'completed') {
                    $cronJob->update([
                        'status' => 'success',
                        'updated_at' => now(),
                    ]);
                    $processed++;
                    continue;
                }

                // Agar quiz ka time nikal chuka hai aur complete_status abhi bhi 'pending' hai, toh ise 'processing' me update karo
                if ($quiz->complete_status === 'pending') {
                    $isPast = false;
                    if ($quiz->availability_end && \Carbon\Carbon::parse($quiz->availability_end)->isPast()) {
                        $isPast = true;
                    } elseif ($quiz->scheduled_at && \Carbon\Carbon::parse($quiz->scheduled_at)->addMinutes(5)->isPast()) {
                        $isPast = true;
                    } elseif ($cronJob->scheduled_at && \Carbon\Carbon::parse($cronJob->scheduled_at)->isPast()) {
                        $isPast = true;
                    }

                    if ($isPast) {
                        $quiz->update(['complete_status' => 'processing']);
                        $quiz->refresh();
                    } else {
                        // Agar quiz abhi tak future me hai, toh is cron job ko abhi skip karo taaki premature fail na ho
                        continue;
                    }
                }

                try {
                    $result = $this->quizUpdatePlayData($quiz, true);

                    if ($result['success'] ?? false) {
                        $cronJob->update([
                            'status' => 'success',
                            'updated_at' => now(),
                        ]);
                        $processed++;
                    } else {
                        $cronJob->update([
                            'status' => 'failed',
                            'updated_at' => now(),
                        ]);
                        $failed++;
                        Log::warning('quizUpdatePlayData failed in cron: ' . ($result['message'] ?? 'Unknown error'), ['quiz_id' => $quiz->id]);
                    }
                } catch (\Throwable $e) {
                    $cronJob->update([
                        'status' => 'failed',
                        'updated_at' => now(),
                    ]);
                    $failed++;

                    Log::error('generateQuizWinner failed for quiz ' . $cronJob->quiz_id, [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            }

            return [
                'success'   => true,
                'processed' => $processed,
                'failed'    => $failed,
            ];

        } catch (\Throwable $e) {
            Log::error('generateQuizWinner cron execution error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
    // ***********************Cron Job: generateQuizWinner end**********************************
}
