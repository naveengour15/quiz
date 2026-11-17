<?php

namespace App\Repositories\api;

use App\Models\Leaderboard;
use App\Models\LeaderPointHistory;
use Carbon\Carbon;

class LeaderboardRepository
{
    protected Leaderboard $model;

    public function __construct(Leaderboard $model)
    {
        $this->model = $model;
    }

    /**
     * Get leaderboard with pagination.
     */
    public function getLeaderboard(
        int $tenantId,
        string $yearlySession,
        int $perPage = 20
    ) {
        return $this->model
            ->with([
                'user:id,name,mobile'
            ])
            ->where('tenant_id', $tenantId)
            ->where('yearly_session', $yearlySession)
            ->whereNotNull('user_id')
            ->orderBy('rank', 'asc')
            ->paginate($perPage);
    }

    /**
     * Get logged-in user's leaderboard position.
     */
    public function getUserRank(
        int $tenantId,
        string $yearlySession,
        int $userId
    ) {
        return $this->model
            ->with([
                'user:id,name,mobile'
            ])
            ->where('tenant_id', $tenantId)
            ->where('yearly_session', $yearlySession)
            ->where('user_id', $userId)
            ->first();
    }

    /**
     * Get leaderboard by rank range.
     */
    public function getByRank(
        int $tenantId,
        string $yearlySession,
        int $startRank,
        int $endRank
    ) {
        return $this->model
            ->with([
                'user:id,name,mobile'
            ])
            ->where('tenant_id', $tenantId)
            ->where('yearly_session', $yearlySession)
            ->whereBetween('rank', [$startRank, $endRank])
            ->orderBy('rank', 'asc')
            ->get();
    }

    /**
     * Get leaderboard by rank range.
     */
    public function getLeaderboardHistories(
        int $tenantId,
        string $yearlySession,
        int $userId
    ) {
        [$startYear, $endYear] = explode('-', $yearlySession);

        $startDate = Carbon::create($startYear, 4, 1)->startOfDay();
        $endDate = Carbon::create($endYear, 3, 31)->endOfDay();

        return LeaderPointHistory::with([
                'user:id,name,mobile'
            ])
            ->where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('id', 'asc')
            ->get();
    }
}