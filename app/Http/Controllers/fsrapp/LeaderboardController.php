<?php

namespace App\Http\Controllers\fsrapp;

use App\Http\Controllers\Controller;
use App\Models\Leaderboard;
use App\Models\User;
use App\Models\Banner;
use App\Models\LeaderPointHistory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;

class LeaderboardController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->get('per_page', 15);
        $user = Auth::user();

        // 1. Leaderboard entries query + LEFT JOIN users (Only required columns)
        $query = Leaderboard::query()
            ->leftJoin('users', 'leaderboards.user_id', '=', 'users.id')
            ->select([
                'leaderboards.id as lb_id',
                'leaderboards.rank as lb_rank',
                'leaderboards.points as lb_points',
                'users.id as user_id',
                'users.name',
                'users.profile_photo',
                'users.leader_board_point',
                'users.wallet_balance as reward_points',
            ])
            ->whereNull('leaderboards.deleted_at');

        // Search Filter (if provided)
        $query->when($request->search, function ($q) use ($request) {
            $q->where(function ($sub) use ($request) {
                $sub->where('users.name', 'like', "%{$request->search}%")
                    ->orWhere('leaderboards.rank', 'like', "%{$request->search}%");
            });
        });

        // Rank Range Filter (top10, top50, top100) (if provided)
        $rankFilter = $request->get('rank_filter');
        if ($rankFilter === 'top10') {
            $query->where('leaderboards.rank', '<=', 10);
        } elseif ($rankFilter === 'top50') {
            $query->where('leaderboards.rank', '<=', 50);
        } elseif ($rankFilter === 'top100') {
            $query->where('leaderboards.rank', '<=', 100);
        }

        $page = max((int) $request->get('page', 1), 1);
        $leaderboard = $query->orderBy('leaderboards.rank', 'asc')
            ->simplePaginate($perPage, ['*'], 'page', $page)
            ->withQueryString();

        // Map has_user flag and calculate fallback points
        $leaderboard->getCollection()->transform(function ($entry) {
            $entry->has_user = !is_null($entry->user_id);
            $entry->lb_points = (!empty($entry->lb_points) && (float)$entry->lb_points > 0)
                ? (float)$entry->lb_points
                : ((int)($entry->leader_board_point ?? 0) > 0 ? (int)$entry->leader_board_point : (int)($entry->reward_points ?? 0));
            return $entry;
        });

        // 2. Early Return for AJAX infinite scroll pagination (skips all non-AJAX queries)
        if ($request->ajax()) {
            return response()->json([
                'html' => view('fsrapp.leaderboard.partials.rows', compact('leaderboard'))->render(),
                'next_page' => $leaderboard->nextPageUrl()
            ]);
        }

        // 3. For Initial Full Page Load:
        $banners = Banner::where('status', 'active')
            ->latest('id')
            ->get();

        // Top 3 Podium (Direct fast query using index)
        $top3 = Leaderboard::whereNotNull('leaderboards.user_id')
            ->whereNull('leaderboards.deleted_at')
            ->join('users', 'leaderboards.user_id', '=', 'users.id')
            ->select([
                'users.id as user_id',
                'users.name',
                'users.profile_photo',
                'users.leader_board_point',
                'users.wallet_balance as reward_points',
                'leaderboards.points as lb_points',
                'leaderboards.rank as lb_rank'
            ])
            ->orderBy('leaderboards.rank', 'asc')
            ->limit(3)
            ->get()
            ->transform(function ($t) {
                $t->lb_points = (!empty($t->lb_points) && (float)$t->lb_points > 0)
                    ? (float)$t->lb_points
                    : ((int)($t->leader_board_point ?? 0) > 0 ? (int)$t->leader_board_point : (int)($t->reward_points ?? 0));
                return $t;
            });

        // Logged-in user's own rank (direct lookup, avoid extra join)
        $myRank = null;
        if ($user) {
            $userLb = Leaderboard::where('user_id', $user->id)
                ->whereNull('deleted_at')
                ->select(['rank as lb_rank', 'points as lb_points'])
                ->first();

            if ($userLb) {
                $userLb->user_id = $user->id;
                $userLb->name = $user->name;
                $userLb->profile_photo = $user->profile_photo;
                $userLb->leader_board_point = $user->leader_board_point;
                $userLb->reward_points = $user->wallet_balance;
                $userLb->lb_points = (!empty($userLb->lb_points) && (float)$userLb->lb_points > 0)
                    ? (float)$userLb->lb_points
                    : ((int)($user->leader_board_point ?? 0) > 0 ? (int)$user->leader_board_point : (int)($user->wallet_balance ?? 0));
                $myRank = $userLb;
            }
        }

        return view('fsrapp.leaderboard.index', compact(
            'leaderboard',
            'top3',
            'myRank',
            'banners'
        ));
    }

    // ********* History data leaderboard **************
    public function myLeaderboardHistory()
    {
        $userId = Auth::id() ?? session('fsr_user_id');
        if (!$userId) {
            return redirect()->route('fsrapp.login');
        }

        // 1. Latest record using indexed id
        $latestHistory = LeaderPointHistory::where('user_id', $userId)
            ->select(['new_point', 'old_point'])
            ->latest('id')
            ->first();

        $newMarks = $latestHistory ? ($latestHistory->new_point ?? 0) : 0;
        $oldMarks = $latestHistory ? ($latestHistory->old_point ?? 0) : 0;

        // 2. Paginated history with specific relation columns
        $history = LeaderPointHistory::with('quiz:id,title')
            ->where('user_id', $userId)
            ->latest('id')
            ->paginate(15);

        // 3. View
        return view('fsrapp.leaderboard.leaderboard-mark-history', compact('history', 'newMarks', 'oldMarks'));
    }
}
