<?php

namespace App\Http\Controllers\androidapi;

use App\Http\Controllers\Controller;
use App\Http\Controllers\androidapi\Traits\ResolvesAndroidUser;
use App\Models\Leaderboard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class LeaderboardController extends Controller
{
    use ResolvesAndroidUser;

    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        $user = $this->getAuthUser($request);

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
                'users.wallet_balance as reward_points',
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

        // Rank Range Filter (top10, top50, top100)
        $rankFilter = $request->get('rank_filter');
        if ($rankFilter === 'top10') {
            $query->where('leaderboards.rank', '<=', 10);
        } elseif ($rankFilter === 'top50') {
            $query->where('leaderboards.rank', '<=', 50);
        } elseif ($rankFilter === 'top100') {
            $query->where('leaderboards.rank', '<=', 100);
        }

        $leaderboard = $query->orderBy('leaderboards.rank', 'asc')
            ->paginate($perPage);

        // Mark has_user flag and calculate fallback points
        $leaderboard->getCollection()->transform(function ($entry) {
            $entry->has_user = !is_null($entry->user_id);
            $entry->lb_points = (!empty($entry->lb_points) && (float)$entry->lb_points > 0)
                ? (float)$entry->lb_points
                : ((int)($entry->leader_board_point ?? 0) > 0 ? (int)$entry->leader_board_point : (int)($entry->reward_points ?? 0));
            return $entry;
        });

        // Summary Stats
        $totalPlayers = Cache::remember('lb_total_players', 3600, function () {
            return Leaderboard::whereNotNull('user_id')->whereNull('deleted_at')->count();
        });

        $totalEntries = Cache::remember('lb_total_entries', 3600, function () {
            return Leaderboard::whereNull('deleted_at')->count();
        });

        $totalPointsDistributed = Cache::remember('lb_total_points', 3600, function () {
            return Leaderboard::whereNull('deleted_at')->sum('points');
        });

        // Available Sessions
        $availableSessions = Cache::remember('lb_sessions', 3600, function () {
            return Leaderboard::whereNull('deleted_at')
                ->select('yearly_session')
                ->distinct()
                ->orderBy('yearly_session', 'desc')
                ->pluck('yearly_session');
        });

        // Top 3 Podium
        $top3 = Leaderboard::whereNotNull('leaderboards.user_id')
            ->whereNull('leaderboards.deleted_at')
            ->join('users', 'leaderboards.user_id', '=', 'users.id')
            ->select(
                'users.id',
                'users.name',
                'users.profile_photo',
                'users.leader_board_point',
                'users.wallet_balance as reward_points',
                'leaderboards.points as lb_points',
                'leaderboards.rank as lb_rank'
            )
            ->orderBy('leaderboards.rank', 'asc')
            ->limit(3)
            ->get()
            ->transform(function ($t) {
                $t->lb_points = (!empty($t->lb_points) && (float)$t->lb_points > 0)
                    ? (float)$t->lb_points
                    : ((int)($t->leader_board_point ?? 0) > 0 ? (int)$t->leader_board_point : (int)($t->reward_points ?? 0));
                return $t;
            });

        // Logged-in user own rank
        $myRank = null;
        if ($user) {
            $myRank = Leaderboard::where('leaderboards.user_id', $user->id)
                ->whereNull('leaderboards.deleted_at')
                ->leftJoin('users', 'leaderboards.user_id', '=', 'users.id')
                ->select([
                    'leaderboards.rank as lb_rank',
                    'leaderboards.points as lb_points',
                    'users.id as user_id',
                    'users.name',
                    'users.profile_photo',
                    'users.wallet_balance as reward_points',
                ])
                ->first();
        }

        return response()->json([
            'success' => true,
            'message' => 'Leaderboard fetched successfully.',
            'top3' => $top3,
            'my_rank' => $myRank,
            'leaderboard' => $leaderboard,
            'summary' => [
                'total_players' => $totalPlayers,
                'total_entries' => $totalEntries,
                'total_points_distributed' => $totalPointsDistributed,
                'available_sessions' => $availableSessions
            ]
        ]);
    }

    public function myLeaderboardHistory(Request $request)
    {
        $user = $this->getAuthUser($request);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized user.'
            ], 401);
        }

        $latestHistory = \App\Models\LeaderPointHistory::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->first();

        $newMarks = $latestHistory ? (float)($latestHistory->new_point ?? 0) : (float)($user->leader_board_point ?? 0);
        $oldMarks = $latestHistory ? (float)($latestHistory->old_point ?? 0) : 0;

        $perPage = $request->get('per_page', 15);
        $history = \App\Models\LeaderPointHistory::with('quiz:id,title,visibility')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Leaderboard mark history fetched successfully.',
            'new_marks' => $newMarks,
            'old_marks' => $oldMarks,
            'history' => $history,
        ]);
    }
}

