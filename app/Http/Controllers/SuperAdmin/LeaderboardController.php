<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Leaderboard;
use App\Interfaces\api\QuizRepositoryInterface;
use App\Interfaces\api\LeaderboardUserUpdateRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LeaderboardController extends Controller
{
    protected QuizRepositoryInterface $quizRepository;
    protected LeaderboardUserUpdateRepositoryInterface $leaderboardUserUpdateRepository;

    public function __construct(QuizRepositoryInterface $quizRepository , LeaderboardUserUpdateRepositoryInterface $leaderboardUserUpdateRepository)
    {
        $this->quizRepository = $quizRepository;
        $this->leaderboardUserUpdateRepository = $leaderboardUserUpdateRepository;
    }

    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 10);

        // Leaderboard entries + LEFT JOIN users
        $query = Leaderboard::query()
            ->leftJoin('users', 'leaderboards.user_id', '=', 'users.id')
            ->select([
                'leaderboards.id as lb_id',
                'leaderboards.rank as lb_rank',
                'leaderboards.points as lb_points',
                'leaderboards.yearly_session',
                'leaderboards.sponsored_by',
                'users.id as user_id',
                'users.name',
                'users.email',
                'users.mobile',
                'users.profile_photo',
                'users.created_at',
                'users.leader_board_point',
            ])
            ->whereNull('leaderboards.deleted_at');

        // Search Filter
        $query->when($request->search, function ($q) use ($request) {
            $q->where(function ($query) use ($request) {
                $query->where('users.name', 'like', "%{$request->search}%")
                    ->orWhere('users.email', 'like', "%{$request->search}%")
                    ->orWhere('users.mobile', 'like', "%{$request->search}%")
                    ->orWhere('leaderboards.rank', 'like', "%{$request->search}%");
            });
        });

        // Session Filter
        $query->when($request->session, function ($q) use ($request) {
            $q->where('leaderboards.yearly_session', $request->session);
        });

        $leaderboard = $query->orderBy('leaderboards.rank', 'asc')
            ->paginate($perPage)
            ->withQueryString();

        // Mark has_user flag
        $leaderboard->getCollection()->transform(function ($entry) {
            $entry->has_user = !is_null($entry->user_id);
            return $entry;
        });

        // Summary Stats
        $totalPlayers = Leaderboard::whereNotNull('user_id')
            ->whereNull('deleted_at')
            ->count();

        $totalEntries = Leaderboard::whereNull('deleted_at')
            ->count();

        $totalPointsDistributed = Leaderboard::whereNull('deleted_at')
            ->sum('points');

        // Available Sessions for Dropdown
        $availableSessions = Leaderboard::whereNull('deleted_at')
            ->select('yearly_session')
            ->distinct()
            ->orderBy('yearly_session', 'desc')
            ->pluck('yearly_session');

        // Top 3 Podium (only with users)
        $top3 = Leaderboard::whereNotNull('user_id')
            ->whereNull('deleted_at')
            ->join('users', 'leaderboards.user_id', '=', 'users.id')
            ->select(
                'users.id',
                'users.name',
                'users.profile_photo',
                'users.leader_board_point as lb_points',
                'leaderboards.rank as lb_rank'
            )
            ->orderBy('leaderboards.rank', 'asc')
            ->limit(3)
            ->get();

        return view('superadmin.leaderboard.index', compact(
            'leaderboard',
            'totalPlayers',
            'totalEntries',
            'totalPointsDistributed',
            'top3',
            'availableSessions'
        ));
    }


    public function updateSponsor(Request $request)
    {
        try {
            $request->validate([
                'entry_id' => 'required|exists:leaderboards,id',
                'sponsored_by' => 'required|string|max:255',
            ]);

            $leaderboard = Leaderboard::findOrFail($request->entry_id);
            $leaderboard->update([
                'sponsored_by' => $request->sponsored_by
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Sponsor updated successfully',
                'sponsored_by' => $leaderboard->sponsored_by
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update sponsor: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateUserLeaderboard(Request $request)
    {
        try {
            $tenantId = $request->get('tenant_id');
            // $this->quizRepository->leaderboardUserUpdate($tenantId ? (int)$tenantId : null);
            $this->leaderboardUserUpdateRepository->leaderboardUserUpdate($tenantId ? (int)$tenantId : null);

            return response()->json([
                'success' => true,
                'message' => 'User leaderboard updated successfully!',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update leaderboard user rankings: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update user leaderboard: ' . $e->getMessage(),
            ], 500);
        }
    }
}