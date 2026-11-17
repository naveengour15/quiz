<?php

namespace App\Repositories\api\Traits;

use App\Models\Leaderboard;
use App\Models\User;
use App\Models\WalletHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait HandlesQuizLeaderboardAndRewards
{
    public function createWalletHistory($tenantId, $userId, $quizId, $points, ?User $user = null)
    {
        $walletHistory = WalletHistory::query()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->where('quiz_id', $quizId)
            ->where('transaction_type', 'quiz_leaderboard_reward')
            ->lockForUpdate()
            ->first();

        if (!$walletHistory) {
            if (!$user) {
                $user = User::query()->lockForUpdate()->find($userId);
            }

            if (!$user) {
                Log::warning('User not found for wallet reward', [
                    'quiz_id' => $quizId,
                    'tenant_id' => $tenantId,
                    'user_id' => $userId,
                ]);
                return;
            }

            $walletHistory = WalletHistory::create([
                'tenant_id'        => $tenantId,
                'user_id'          => $userId,
                'quiz_id'          => $quizId,
                'amount'           => $points,
                'type'             => 'credit',
                'transaction_type' => 'quiz_leaderboard_reward',
                'reference_id'     => (string) $quizId,
                'remarks'          => 'Reward Win from leaderboard',
            ]);

            $user->increment('wallet_balance', $points);
            $user->refresh();

            Log::info('Wallet reward created', [
                'quiz_id'     => $quizId,
                'tenant_id'   => $tenantId,
                'user_id'     => $userId,
                'points'      => $points,
                'wallet_id'   => $walletHistory->id,
            ]);
        } else {
            Log::info('Wallet reward already exists. Skipping.', [
                'quiz_id'   => $quizId,
                'user_id'   => $userId,
                'wallet_id' => $walletHistory->id,
            ]);
        }
    }

    public function leaderboardUserUpdate(?int $tenantId = null): void
    {
        try {
            Log::info('Global leaderboard update started', [
                'tenant_id' => $tenantId,
            ]);

            $usersQuery = User::query()
                ->where('is_active', 1)
                ->where('leader_board_point', '>', 0)
                ->orderByDesc('leader_board_point')
                ->orderBy('id')
                ->select([
                    'id',
                    'tenant_id',
                    'leader_board_point',
                    'quiz_rank',
                ]);

            if ($tenantId !== null) {
                $usersQuery->where('tenant_id', $tenantId);
            }

            $users = $usersQuery->get();

            if ($users->isEmpty()) {
                Log::info('No users available for global leaderboard', [
                    'tenant_id' => $tenantId,
                ]);
                return;
            }

            $leaderboardQuery = Leaderboard::query()->orderBy('rank');

            if ($tenantId !== null) {
                $leaderboardQuery->where('tenant_id', $tenantId);
            }

            $leaderboards = $leaderboardQuery->get()->keyBy('rank');

            if ($leaderboards->isEmpty()) {
                Log::warning('No leaderboard rank rows found', [
                    'tenant_id' => $tenantId,
                ]);
                return;
            }

            DB::transaction(function () use ($users, $leaderboards, $tenantId) {
                foreach ($users as $index => $user) {
                    $rank = $index + 1;
                    $leaderBoard = $leaderboards->get($rank);

                    if (!$leaderBoard) {
                        break;
                    }

                    if ((int) $leaderBoard->user_id === (int) $user->id && (int) $user->quiz_rank === $rank) {
                        continue;
                    }

                    $leaderBoard->update(['user_id' => $user->id]);
                    $leaderBoard->refresh();

                    $user->update(['quiz_rank' => $rank]);
                    $user->refresh();
                }

                $maxUserRank = $users->count();
                $leaderboards
                    ->filter(function ($leaderBoard) use ($maxUserRank) {
                        return (int) $leaderBoard->rank > $maxUserRank && !empty($leaderBoard->user_id);
                    })
                    ->each(function ($leaderBoard) use ($tenantId) {
                        $leaderBoard->update(['user_id' => null]);
                        $leaderBoard->refresh();
                    });
            });

            Log::info('Global leaderboard update completed', [
                'tenant_id' => $tenantId,
                'user_count' => $users->count(),
            ]);

        } catch (\Throwable $e) {
            Log::error('leaderboardUserUpdate failed', [
                'tenant_id' => $tenantId,
                'message'   => $e->getMessage(),
                'file'      => $e->getFile(),
                'line'      => $e->getLine(),
            ]);
        }
    }

    public function leaderboardUserRankUpdate($startDayDate = null, $endDayDate = null)
    {
        try {
            $yearlySession = $this->getYearlySession();

            // 1. Bulk update all user ranks in ONE single query using MySQL window function (~0.1s)
            DB::statement("
                UPDATE users u
                JOIN (
                    SELECT id, ROW_NUMBER() OVER (ORDER BY leader_board_point DESC, id ASC) as calculated_rank
                    FROM users
                    WHERE is_active = 1 AND leader_board_point >= 0
                ) ranked ON u.id = ranked.id
                SET u.quiz_rank = ranked.calculated_rank
            ");

            // 2. Link top user ranks to Leaderboard session master records (~0.02s)
            DB::statement("
                UPDATE leaderboards l
                JOIN users u ON l.rank = u.quiz_rank
                SET l.user_id = u.id
                WHERE l.yearly_session = ? AND u.is_active = 1
            ", [$yearlySession]);

        } catch (\Throwable $e) {
            Log::error('Quiz leaderboard Rank Update failed', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);
            throw $e;
        }
    }

    public function getYearlySession(?string $date = null)
    {
        $year = now()->year;

        if (now()->month >= 4) {
            $yearlySession = $year . '-' . ($year + 1);
        } else {
            $yearlySession = ($year - 1) . '-' . $year;
        }
        return $yearlySession;
    }
}
