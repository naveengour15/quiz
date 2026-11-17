<?php

namespace App\Repositories\androidapi;

use App\Interfaces\androidapi\QuizWinnerRepositoryInterface;
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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
                    
            if ($quiz && $quiz->complete_status !== 'processing' && $quiz->complete_status !== 'completed') {
                \Illuminate\Support\Facades\Cache::lock("quiz_{$quizId}_mark_processing_lock", 10)->get(function () use ($quiz, $quizId) {
                    $freshQuiz = Quiz::find($quizId);
                    if ($freshQuiz && $freshQuiz->complete_status !== 'processing' && $freshQuiz->complete_status !== 'completed') {

                        $freshQuiz->update([
                            'complete_status' => 'processing',
                        ]);
                        
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
            if (!$isCron && !in_array($quiz->complete_status, ['processing', 'completed']) && !$quiz->isAnswerTimeFinished()) {
                return [
                    'success' => false,
                    'message' => 'Quiz play data can only be updated after quiz answer submission time has finished.'
                ];
            }

            if ($quiz->complete_status === 'pending') {
                $quiz->update(['complete_status' => 'processing']);
                $quiz->refresh();
            }

            if (!in_array($quiz->complete_status, ['processing', 'completed'])) {
                return [
                    'success' => false,
                    'message' => 'Quiz play data can only be updated when quiz complete status is processing or completed.'
                ];
            }

            if ($quiz->visibility === 'fastest_finger_first') {
                return $this->updateFffQuizPlayData($quiz);
            }

            if (\App\Services\RedisQuizService::isAvailable()) {
                try {
                    \App\Services\RedisQuizService::persistQuizDataToMySQL($quiz->id);
                } catch (\Throwable $e) {
                    Log::warning("Redis persistQuizDataToMySQL error in quizUpdatePlayData for quiz {$quiz->id}: " . $e->getMessage());
                }
            }

            // Step 0.5: Revert previously awarded points and wallet balances for this quiz if already generated
            $this->revertPreviousQuizRewardsAndPoints($quiz->id, $quiz->tenant_id ?? 1);

            $calculateQuizQuestionUserAnswer = $this->calculateQuizQuestionUserAnswer($quiz->id, $quiz->tenant_id ?? 1);

            if (!empty($calculateQuizQuestionUserAnswer)) {
                $this->updateQuizWinnersData($quiz->id, $quiz->tenant_id ?? 1, $calculateQuizQuestionUserAnswer);
                $this->updateQuizRewards($quiz->id, $quiz->tenant_id ?? 1, $quiz);
                $this->quizStatusUpdate($quiz);
            } else {
                $this->updateQuizWinnersData($quiz->id, $quiz->tenant_id ?? 1, []);
            }

            try {
                QuizWinner::recalculateRanks((int) $quiz->id);
            } catch (\Throwable $e) {}

            try {
                app(\App\Interfaces\androidapi\QuizRepositoryInterface::class)->leaderboardUserRankUpdate();
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

    public function updateFffQuizPlayData(Quiz $quiz): array
    {
        try {
            $quizId = $quiz->id;
            $tenantId = $quiz->tenant_id ?? 1;

            if (\App\Services\RedisQuizService::isAvailable()) {
                try {
                    \App\Services\RedisQuizService::persistQuizDataToMySQL($quiz->id);
                } catch (\Throwable $e) {
                    Log::warning("Redis persistQuizDataToMySQL error in updateFffQuizPlayData for quiz {$quiz->id}: " . $e->getMessage());
                }
            }

            // Step 0.5: Revert previously awarded points and wallet balances for this quiz if already generated
            $this->revertPreviousQuizRewardsAndPoints($quizId, $tenantId);

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
                usort($statsArray, function ($a, $b) {
                    if ($a['correct_answers_count'] != $b['correct_answers_count']) {
                        return $b['correct_answers_count'] <=> $a['correct_answers_count'];
                    }
                    return $a['total_time_taken'] <=> $b['total_time_taken'];
                });

                $previousCorrect = -1;
                $previousTime = -1;
                $currentRank = 1;

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

                    $winner = QuizWinner::updateOrCreate(
                        [
                            'quiz_id' => $quizId,
                            'user_id' => $stat['user_id'],
                        ],
                        [
                            'tenant_id'            => $stat['tenant_id'] ?: $tenantId,
                            'total_question'       => $stat['total_questions_attempted'],
                            'total_correct_answer' => $currentCorrect,
                            'total_time'           => $stat['total_time_taken'],
                            'per_ans_average_time' => $stat['per_ans_average_time'],
                            'rank'                 => $rank,
                            'reward_type'          => 'point',
                            'reward'               => (string) $pointsEarned,
                            'status'               => 'active',
                        ]
                    );

                    $this->updateQuizLeaderboardHistory($quizId, $winner, $quiz);
                }

                $this->updateQuizRewards($quizId, $tenantId, $quiz);
            }

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

    public function updateQuizWinnersData($quizId, $tenant_id, $calculateQuizQuestionUserAnswer)
    {
        try {
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

        $calculateQuizQuestionUserAnswer = array_values(array_filter($calculateQuizQuestionUserAnswer, function ($item) {
            return (int) ($item['total_questions_attempted'] ?? 0) >= 1;
        }));

        if (empty($calculateQuizQuestionUserAnswer)) {
            QuizWinner::where('quiz_id', $quizId)->where('tenant_id', $tenant_id)->delete();
            return;
        }

        $attemptedUserIds = array_column($calculateQuizQuestionUserAnswer, 'user_id');
        QuizWinner::where('quiz_id', $quizId)
            ->where('tenant_id', $tenant_id)
            ->whereNotIn('user_id', $attemptedUserIds)
            ->delete();

        DB::beginTransaction();

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

                QuizWinner::updateOrCreate($searchCriteria, $updateData);
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
                                }
                            }
                        }

                        if ($quiz->visibility !== 'leaderboard' && $rewardType === 'voucher' && $primaryRewardRule->voucher_id && $primaryRewardRule->voucher_histories_id) {
                            $voucherPayload = [
                                'tenant_id'            => $tenant_id,
                                'quiz_id'              => $quizId,
                                'winner'               => $winner,
                                'voucher_id'           => $primaryRewardRule->voucher_id,
                                'voucher_histories_id' => $primaryRewardRule->voucher_histories_id,
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

    public function assignVoucherToWinner(array $data)
    {
        try {
            $tenant_id              = $data['tenant_id'] ?? null;
            $quizId                 = $data['quiz_id'] ?? null;
            $winner                 = $data['winner'] ?? null;
            $voucher_histories_json = $data['voucher_histories_id'] ?? null;

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

    public function updateLeaderboardQuizPoint($quizId, $winner, $quiz)
    {
        try {
            $tenantId = $quiz->tenant_id ?? 1;
            $userId   = $winner->user_id;
            $earnedPoints = (float) ($winner->reward ?? 0);

            $user = User::select(['id', 'leader_board_point'])->find($userId);
            if (!$user) return false;

            $currentUserLeaderPoints = (float) ($user->leader_board_point ?? 0);

            $leaderboardPoints = LeaderPointHistory::query()
                ->where('tenant_id', $tenantId)
                ->where('user_id', $userId)
                ->where('quiz_id', $quizId)
                ->first();

            if ($leaderboardPoints) {
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
            } else {
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
            }

            $user->update(['leader_board_point' => $newUserPoints]);
            return true;

        } catch (\Throwable $e) {
            Log::error('Quiz leaderboard point update failed', ['quiz_id' => $quizId, 'error' => $e->getMessage()]);
            return false;
        }
    }

    public function updateQuizLeaderboardHistory($quizId, $winner, $quiz)
    {
        try {
            $tenantId    = $quiz->tenant_id ?? 1;
            $userId      = $winner->user_id;
            $totalCorrect = (int) ($winner->total_correct_answer ?? 0);

            if ($totalCorrect <= 0) return false;

            $pointsPerCorrect = ($quiz->visibility === 'mahaquiz') ? 10 : 1;
            $earnedPoints = (float) ($pointsPerCorrect * $totalCorrect);

            $user = User::select(['id', 'leader_board_point'])->find($userId);
            if (!$user) return false;

            $currentUserLeaderPoints = (float) ($user->leader_board_point ?? 0);

            $leaderboardPoints = LeaderPointHistory::query()
                ->where('tenant_id', $tenantId)
                ->where('user_id', $userId)
                ->where('quiz_id', $quizId)
                ->where('type', 'credit')
                ->lockForUpdate()
                ->first();

            if ($leaderboardPoints) {
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
            } else {
                $newUserPoints = $currentUserLeaderPoints + $earnedPoints;

                LeaderPointHistory::create([
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
            }

            return true;

        } catch (\Throwable $e) {
            Log::error('Quiz leaderboard history update failed', ['quiz_id' => $quizId, 'error' => $e->getMessage()]);
            return false;
        }
    }

    public function quizStatusUpdate($quiz)
    {
        $tenantId   = $quiz->tenant_id ?? 1;
        $quizId     = $quiz->id;

        $quiz->update(['complete_status' => 'completed']);
        $quiz->refresh();

        try {
            Cache::put("last_quiz_status_t{$tenantId}_q{$quizId}", ['id' => $quiz->id, 'complete_status' => 'completed'], 3600);
            Cache::forget("last_quiz_status_t{$tenantId}_latest");
            Cache::forget("android_last_quiz_status_t{$tenantId}_q{$quizId}");
            Cache::forget("android_last_quiz_status_t{$tenantId}_latest");
        } catch (\Throwable $e) {}

        QuizStatus::query()->where('quiz_id', $quizId)->update(['status' => 'completed']);

        QuizCronJobHistory::updateOrCreate(
            ['quiz_id' => $quizId, 'cron_type' => 'quiz_winner_leaderboard'],
            [
                'tenant_id' => $tenantId,
                'scheduled_at' => $quiz->availability_end ? \Carbon\Carbon::parse($quiz->availability_end) : now(),
                'status' => 'success',
                'updated_at' => now(),
            ]
        );

        if (\App\Services\RedisQuizService::isAvailable()) {
            try {
                \App\Services\RedisQuizService::clearQuizData((int) $quizId);
            } catch (\Throwable $e) {}
        }
    }

    public function generateQuizWinner(): array
    { 
        try {
            $cronJobs = QuizCronJobHistory::whereIn('status', ['pending', 'failed'])
                ->where(function ($q) {
                    $q->whereNull('scheduled_at')->orWhere('scheduled_at', '<=', now()->addMinutes(1));
                })
                ->orderBy('scheduled_at', 'asc')
                ->get();

            if ($cronJobs->isEmpty()) {
                return ['success' => true, 'message' => 'No pending or failed quiz cron jobs found.', 'processed' => 0];
            }

            $processed = 0;
            $failed = 0;

            foreach ($cronJobs as $cronJob) {
                $quiz = Quiz::find($cronJob->quiz_id);
                if (!$quiz) {
                    $cronJob->update(['status' => 'failed']);
                    $failed++;
                    continue;
                }

                if ($quiz->complete_status === 'completed') {
                    $cronJob->update(['status' => 'success', 'updated_at' => now()]);
                    $processed++;
                    continue;
                }

                try {
                    $result = $this->quizUpdatePlayData($quiz, true);
                    if ($result['success'] ?? false) {
                        $cronJob->update(['status' => 'success', 'updated_at' => now()]);
                        $processed++;
                    } else {
                        $cronJob->update(['status' => 'failed', 'updated_at' => now()]);
                        $failed++;
                    }
                } catch (\Throwable $e) {
                    $cronJob->update(['status' => 'failed', 'updated_at' => now()]);
                    $failed++;
                }
            }

            return ['success' => true, 'processed' => $processed, 'failed' => $failed];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}