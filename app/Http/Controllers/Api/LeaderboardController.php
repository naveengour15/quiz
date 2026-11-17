<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Repositories\api\LeaderboardRepository;

class LeaderboardController extends Controller
{

    protected LeaderboardRepository $leaderboardRepository;

    public function __construct(
        LeaderboardRepository $leaderboardRepository
    ) {
        $this->leaderboardRepository = $leaderboardRepository;
    }
    
   
    // 'page' => 'nullable|integer|min:1',
    public function index(Request $request)
    {
        $request->validate([
            'yearly_session' => 'nullable|string|max:20',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $user = $request->user();

        $yearlySession = $request->yearly_session ?? '2026-2027';
        $limit = $request->limit ?? 50;

        $leaderboards = $this->leaderboardRepository
            ->getLeaderboard(
                $user->tenant_id,
                $yearlySession,
                $limit
            );

        $currentUser = $this->leaderboardRepository
            ->getUserRank(
                $user->tenant_id,
                $yearlySession,
                $user->id
            );

        $leaderboardHistories = $this->leaderboardRepository
            ->getLeaderboardHistories(
                $user->tenant_id,
                $yearlySession,
                $user->id
            );

        return response()->json([
            'success' => true,
            'message' => 'Leaderboard fetched successfully.',
            'data' => [
                'yearly_session' => $yearlySession,

                'current_user' => $currentUser,

                'leaderboards' => $leaderboards->items(),

                'pagination' => [
                    'current_page' => $leaderboards->currentPage(),
                    'last_page' => $leaderboards->lastPage(),
                    'per_page' => $leaderboards->perPage(),
                    'total' => $leaderboards->total(),
                ],
                'leaderboardHistories' => $leaderboardHistories,

            ],
        ]);
    }




    /********************* END **********************/ 
}