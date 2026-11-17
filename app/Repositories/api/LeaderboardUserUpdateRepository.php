<?php

namespace App\Repositories\api;

use App\Interfaces\api\LeaderboardUserUpdateRepositoryInterface;
use App\Interfaces\ActionTrailRepositoryInterface;
use App\Models\User;
use App\Models\Leaderboard;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class LeaderboardUserUpdateRepository implements LeaderboardUserUpdateRepositoryInterface
{
    public function __construct(protected ActionTrailRepositoryInterface $actontrail)
    {
    }

    // public function leaderboardUserUpdate(?int $tenantId = null): void
    // {
        
    //     try {
 
    //         Log::info('Global leaderboard update started', [
    //             'tenant_id' => $tenantId,
    //         ]);

    //         /** Get users ordered by highest leaderboard points.*/
    //         $usersQuery = User::query()
    //             ->where('is_active', 1)
    //             ->where('leader_board_point', '>', 0)
    //             ->orderByDesc('leader_board_point')
    //             ->orderBy('id')
    //             ->select([
    //                 'id',
    //                 'tenant_id',
    //                 'leader_board_point',
    //                 'quiz_rank',
    //             ]);


    //         if ($tenantId !== null) {
    //             $usersQuery->where('tenant_id', $tenantId);
    //         }

    //         $users = $usersQuery->get();

    //         if ($users->isEmpty()) {

    //             Log::info('No users available for global leaderboard', [
    //                 'tenant_id' => $tenantId,
    //             ]);

    //             return;
    //         }

    //         /* Load all leaderboard positions in ONE query.*/
    //         $leaderboardQuery = Leaderboard::query()
    //             ->orderBy('rank');

    //         if ($tenantId !== null) {
    //             $leaderboardQuery->where('tenant_id', $tenantId);
    //         }

    //         $leaderboards = $leaderboardQuery
    //             ->get()
    //             ->keyBy('rank');

    //         if ($leaderboards->isEmpty()) {

    //             Log::warning('No leaderboard rank rows found', [
    //                 'tenant_id' => $tenantId,
    //             ]);

    //             return;
    //         }

    //         DB::transaction(function () use (
    //             $users,
    //             $leaderboards,
    //             $tenantId
    //         ) {

    //             foreach ($users as $index => $user) {

    //                 $rank = $index + 1;

    //                 $leaderBoard = $leaderboards->get($rank);

    //                 if (!$leaderBoard) {
    //                     Log::warning('Leaderboard rank row not found', [
    //                         'rank'    => $rank,
    //                         'user_id' => $user->id,
    //                     ]);
    //                     break;
    //                 }

    //                 /** Already correct.*/

    //                 if ((int) $leaderBoard->user_id === (int) $user->id && (int) $user->quiz_rank === $rank) {
    //                     continue;
    //                 }

    //                 $oldData = $leaderBoard->toArray();

    //                 $leaderBoard->update([
    //                     'user_id' => $user->id,
    //                 ]);
                   

    //                 $leaderBoard->refresh();

    //                 $this->actionTrailLog(
    //                     $tenantId,
    //                     'leaderboards',
    //                     $leaderBoard->id,
    //                     'update rank',
    //                     $leaderBoard->toArray(),
    //                     $oldData,
    //                     null,
    //                     $user->id
    //                 );
    //                 $oldUserData = $user->toArray();

    //                 $user->update([
    //                     'quiz_rank' => $rank,
    //                 ]);
    //                 $user->refresh();

    //                 $this->actionTrailLog(
    //                     $tenantId,
    //                     'users',
    //                     $user->id,
    //                     'User rank',
    //                     $user->toArray(),
    //                     $oldUserData,
    //                     null,
    //                     $user->id
    //                 );


    //                 Log::debug('Leaderboard rank updated', [
    //                     'tenant_id' => $tenantId,
    //                     'rank'      => $rank,
    //                     'user_id'   => $user->id,
    //                 ]);
    //             }

    //             /*
    //             * Clear old users from remaining ranks.
    //             *
    //             * Example:
    //             * Before = 100 users
    //             * Now    = 90 users
    //             *
    //             * Ranks 91+ should not contain old users.
    //             */
    //             $maxUserRank = $users->count();

    //             $leaderboards
    //                 ->filter(function ($leaderBoard) use ($maxUserRank) {
    //                     return (int) $leaderBoard->rank > $maxUserRank
    //                         && !empty($leaderBoard->user_id);
    //                 })
    //                 ->each(function ($leaderBoard) use ($tenantId) {

    //                     $oldData = $leaderBoard->toArray();

    //                     $leaderBoard->update([
    //                         'user_id' => null,
    //                     ]);

    //                     $leaderBoard->refresh();

    //                     $this->actionTrailLog(
    //                         $tenantId,
    //                         'leaderboards',
    //                         $leaderBoard->id,
    //                         'clear old rank',
    //                         $leaderBoard->toArray(),
    //                         $oldData,
    //                         null,
    //                         null
    //                     );
    //                 });
    //         });

    //         Log::info('Global leaderboard update completed', [
    //             'tenant_id' => $tenantId,
    //             'user_count' => $users->count(),
    //         ]);

    //     } catch (\Throwable $e) {

    //         Log::error('leaderboardUserUpdate failed', [
    //             'tenant_id' => $tenantId,
    //             'message'   => $e->getMessage(),
    //             'file'      => $e->getFile(),
    //             'line'      => $e->getLine(),
    //             'trace'     => $e->getTraceAsString(),
    //         ]);

    //         throw $e;
    //     }
    // }

    public function actionTrailLog(
            $tenantId,
            $tableName,
            $referenceId,
            $action,
            $newDetails = null,
            $oldDetails = null,
            $quizId = null,
            $userId = null,
            $surveyId = null
        ) {
            $newDetailsArr = null;
            if ($newDetails !== null) {
                if (is_object($newDetails) && method_exists($newDetails, 'toArray')) {
                    $newDetailsArr = $newDetails->toArray();
                } elseif (is_array($newDetails)) {
                    $newDetailsArr = $newDetails;
                }
            }

            if (!$quizId) {
                if (is_array($newDetailsArr) && !empty($newDetailsArr['quiz_id'])) {
                    $quizId = $newDetailsArr['quiz_id'];
                } elseif (is_object($newDetails) && !empty($newDetails->quiz_id)) {
                    $quizId = $newDetails->quiz_id;
                } elseif ($tableName === 'quizzes' && $referenceId) {
                    $quizId = $referenceId;
                }
            }

            if (!$userId) {
                if (is_array($newDetailsArr) && !empty($newDetailsArr['user_id'])) {
                    $userId = $newDetailsArr['user_id'];
                } elseif (is_object($newDetails) && !empty($newDetails->user_id)) {
                    $userId = $newDetails->user_id;
                } elseif ($tableName === 'users' && $referenceId) {
                    $userId = $referenceId;
                } elseif (is_array($newDetailsArr) && !empty($newDetailsArr['updated_by'])) {
                    $userId = $newDetailsArr['updated_by'];
                } else {
                    $userId = auth()->id() ?: session('fsr_user_id');
                }
            }

            if (!$surveyId) {
                if (is_array($newDetailsArr) && !empty($newDetailsArr['survey_id'])) {
                    $surveyId = $newDetailsArr['survey_id'];
                } elseif (is_object($newDetails) && !empty($newDetails->survey_id)) {
                    $surveyId = $newDetails->survey_id;
                } elseif ($tableName === 'surveys' && $referenceId) {
                    $surveyId = $referenceId;
                }
            }

            return $this->actontrail->create([
                'tenant_id'    => $tenantId,
                'quiz_id'      => $quizId,
                'user_id'      => $userId,
                'survey_id'    => $surveyId,
                'table_name'   => $tableName,
                'reference_id' => $referenceId,
                'action'       => $action,
                'updated_by'   => $userId ?: (auth()->id() ?: session('fsr_user_id')),
                'details'      => $newDetailsArr ?? $newDetails,
                'old_details'  => $oldDetails
            ]);
    }

        /**
     * ✅ OPTIMIZED & SAFE: Update users with 0 points from their latest history
     */
    public function updateUserPointFromHistory(): array
    {
        try {
            // 1. Sirf un users ko fetch karo jinka point 0 hai (Fast lookup ke liye keyBy('id') use kiya)
            $users = User::where('is_active', 1)
                ->where('leader_board_point', 0)
                ->select(['id', 'tenant_id', 'leader_board_point'])
                ->get()
                ->keyBy('id');

            if ($users->isEmpty()) {
                return [];
            }

            $userIds = $users->keys()->toArray();

            // 2. ✅ SINGLE QUERY: Sabhi users ki latest history ek saath fetch karo (N+1 Problem Solved)
            $latestHistory = DB::table('leader_point_history as lph1')
                ->select('lph1.user_id', 'lph1.new_point')
                ->joinSub(
                    DB::table('leader_point_history')
                        ->select('user_id', DB::raw('MAX(id) as max_id'))
                        ->whereIn('user_id', $userIds)
                        ->groupBy('user_id'),
                    'lph2',
                    'lph1.id',
                    '=',
                    'lph2.max_id'
                )
                ->get()
                ->keyBy('user_id');

            $updatedUsers = [];

            // 3. Process and Update
            foreach ($users as $user) {
                $historyRecord = $latestHistory->get($user->id);
                
                // Agar history mein valid point (> 0) mila, toh update karo
                if ($historyRecord && (int) $historyRecord->new_point > 0) {
                    $point = (int) $historyRecord->new_point;
                    $oldData = $user->toArray();

                    // User table update
                    $user->update(['leader_board_point' => $point]);
                    $user->refresh();

                    // ✅ Action Trail Log
                    $this->actionTrailLog(
                        $user->tenant_id,
                        'users',
                        $user->id,
                        'Update leader board point from history',
                        $user->toArray(),
                        $oldData,
                        null,
                        $user->id
                    );

                    $updatedUsers[] = [
                        'user_id' => $user->id,
                        'tenant_id' => $user->tenant_id,
                        'leader_board_point' => $point,
                    ];
                }
            }

            return $updatedUsers;

        } catch (\Throwable $e) {
            // ✅ ERROR HANDLING: Agar history update fail ho, toh log karo aur empty array return karo
            // Taaki main leaderboard update process ruke na aur aage badh sake
            Log::error('updateUserPointFromHistory failed', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => $e->getTraceAsString(),
            ]);
            
            return []; 
        }
    }

    /**
     * ✅ MAIN: Global leaderboard update with rank assignment
     */
    public function leaderboardUserUpdate(?int $tenantId = null): void
    {
        try {
            Log::info('Global leaderboard update started', [
                'tenant_id' => $tenantId,
            ]);

            // ✅ 1. Pehle 0 points wale users ka history se point update karo (Try-Catch ke sath safe hai)
            $this->updateUserPointFromHistory();

            /** 2. Get users ordered by highest leaderboard points. */
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

            /* 3. Load all leaderboard positions in ONE query. */
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

            // ✅ 4. Transaction ke andar update
            DB::transaction(function () use ($users, $leaderboards, $tenantId) {
                foreach ($users as $index => $user) {
                    $rank = $index + 1;
                    $leaderBoard = $leaderboards->get($rank);

                    if (!$leaderBoard) {
                        Log::warning('Leaderboard rank row not found', [
                            'rank'    => $rank,
                            'user_id' => $user->id,
                        ]);
                        break;
                    }

                    // ✅ Optimization: Agar already sahi hai toh skip karo (Isliye kabhi kabhi update query fire nahi hoti)
                    if ((int) $leaderBoard->user_id === (int) $user->id && (int) $user->quiz_rank === $rank) {
                        continue;
                    }

                    $oldLeaderboardData = $leaderBoard->toArray();

                    // ✅ Leaderboard table me user_id update karo
                    $leaderBoard->update([
                        'user_id' => $user->id,
                    ]);
                    $leaderBoard->refresh();

                    $this->actionTrailLog(
                        $tenantId,
                        'leaderboards',
                        $leaderBoard->id,
                        'update rank',
                        $leaderBoard->toArray(),
                        $oldLeaderboardData,
                        null,
                        $user->id
                    );

                    $oldUserData = $user->toArray();

                    // ✅ User table me quiz_rank update karo
                    $user->update([
                        'quiz_rank' => $rank,
                    ]);
                    $user->refresh();

                    $this->actionTrailLog(
                        $tenantId,
                        'users',
                        $user->id,
                        'User rank',
                        $user->toArray(),
                        $oldUserData,
                        null,
                        $user->id
                    );

                    Log::debug('Leaderboard rank updated', [
                        'tenant_id' => $tenantId,
                        'rank'      => $rank,
                        'user_id'   => $user->id,
                    ]);
                }

                /*
                * 5. Clear old users from remaining ranks.
                * Example: Before = 100 users, Now = 90 users. Ranks 91+ should be cleared.
                */
                $maxUserRank = $users->count();

                $leaderboards
                    ->filter(function ($leaderBoard) use ($maxUserRank) {
                        return (int) $leaderBoard->rank > $maxUserRank
                            && !empty($leaderBoard->user_id);
                    })
                    ->each(function ($leaderBoard) use ($tenantId) {
                        $oldData = $leaderBoard->toArray();

                        $leaderBoard->update([
                            'user_id' => null,
                        ]);
                        $leaderBoard->refresh();

                        $this->actionTrailLog(
                            $tenantId,
                            'leaderboards',
                            $leaderBoard->id,
                            'clear old rank',
                            $leaderBoard->toArray(),
                            $oldData,
                            null,
                            null
                        );
                    });
            });

            Log::info('Global leaderboard update completed', [
                'tenant_id'  => $tenantId,
                'user_count' => $users->count(),
            ]);

        } catch (\Throwable $e) {
            Log::error('leaderboardUserUpdate failed', [
                'tenant_id' => $tenantId,
                'message'   => $e->getMessage(),
                'file'      => $e->getFile(),
                'line'      => $e->getLine(),
                'trace'     => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}