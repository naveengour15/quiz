<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\QuizUser;
use App\Models\Leaderboard;
use App\Models\Quiz;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PlayerQuizController extends Controller
{
    public function activity(Request $request)
    {
        $perPage = $request->get('per_page', 20);

        // ==========================================
        // HELPER CLOSURES (Reusable filters)
        // ==========================================
        $applyCommonFilters = function ($query) use ($request) {
            if ($request->filled('search')) {
                $query->where(function ($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->search . '%')
                      ->orWhere('mobile', 'like', '%' . $request->search . '%');
                });
            }
            if ($request->filled('status')) {
                $query->where('is_active', $request->status === 'active' ? 1 : 0);
            }
            if ($request->filled('join_date_start')) {
                $query->whereDate('created_at', '>=', $request->join_date_start);
            }
            if ($request->filled('join_date_end')) {
                $query->whereDate('created_at', '<=', $request->join_date_end);
            }
        };

                
        // ✅ CORRECTED: Sequential Filter Logic (Range Base -> Consistency -> Nested Activity)
        $applyActivityFilters = function ($query) use ($request) {
            
            $rangeFrom = $request->filled('range_from') ? $request->range_from : null;
            $rangeTo = $request->filled('range_to') ? $request->range_to : null;
            $consistency = $request->filled('consistency_filter') ? $request->consistency_filter : null;
            
            $activityStatus = $request->filled('activity_status') ? $request->activity_status : 'all';
            $mainDateStart = $request->filled('activity_date_start') ? $request->activity_date_start : '1970-01-01';
            $mainDateEnd = $request->filled('activity_date_end') ? $request->activity_date_end : '2099-12-31';

            // ==========================================
            // STEP 1 & 2: Range Base + Consistency Filter
            // ==========================================
            if ($rangeFrom && $rangeTo) {
                // Total days in the selected range (e.g., 1 Oct to 10 Oct = 10 days)
                $totalDays = \Carbon\Carbon::parse($rangeFrom)->diffInDays(\Carbon\Carbon::parse($rangeTo)) + 1;
                
                // Subquery to count DISTINCT days played in the range
                $countSql = "(SELECT COUNT(DISTINCT DATE(created_at)) FROM quiz_users WHERE quiz_users.user_id = users.id AND DATE(created_at) BETWEEN ? AND ?)";

                if ($consistency === '100') {
                    // Must have played on ALL days in the range
                    $query->whereRaw("$countSql = ?", [$rangeFrom, $rangeTo, $totalDays]);
                    
                } elseif ($consistency === '75') {
                    // Must have played on >= 75% of days (and inherently >= 1 day)
                    $requiredDays = ceil($totalDays * 0.75);
                    $query->whereRaw("$countSql >= ?", [$rangeFrom, $rangeTo, $requiredDays]);
                    
                } elseif ($consistency === '50') {
                    // Must have played on >= 50% of days (and inherently >= 1 day)
                    $requiredDays = ceil($totalDays * 0.50);
                    $query->whereRaw("$countSql >= ?", [$rangeFrom, $rangeTo, $requiredDays]);
                    
                } elseif ($consistency === 'low') {
                    // MUST have played at least 1 day, BUT less than 50% of days
                    $requiredDays = ceil($totalDays * 0.50);
                    $query->whereRaw("$countSql >= 1", [$rangeFrom, $rangeTo])
                          ->whereRaw("$countSql < ?", [$rangeFrom, $rangeTo, $requiredDays]);
                          
                } else {
                    // If Range is selected but NO consistency dropdown is chosen, 
                    // default to: Played at least once in this range.
                    $query->whereRaw("$countSql >= 1", [$rangeFrom, $rangeTo]);
                }
            }

            // ==========================================
            // STEP 3: Nested Activity Filter (From Date / To Date)
            // Applied on top of the results from Step 1 & 2
            // ==========================================
            if ($activityStatus !== 'all') {
                if ($activityStatus === 'played') {
                    $query->whereExists(function ($q) use ($mainDateStart, $mainDateEnd) {
                        $q->select(DB::raw(1))
                          ->from('quiz_users')
                          ->whereColumn('quiz_users.user_id', 'users.id')
                          ->whereBetween('quiz_users.created_at', [$mainDateStart, $mainDateEnd . ' 23:59:59']);
                    });
                } elseif ($activityStatus === 'not_played') {
                    $query->whereNotExists(function ($q) use ($mainDateStart, $mainDateEnd) {
                        $q->select(DB::raw(1))
                          ->from('quiz_users')
                          ->whereColumn('quiz_users.user_id', 'users.id')
                          ->whereBetween('quiz_users.created_at', [$mainDateStart, $mainDateEnd . ' 23:59:59']);
                    });
                }
            }
        };
        // ✅ NEW: Min/Max Quiz Count Filter (using whereRaw subquery to avoid having+count bug)
        $applyQuizRangeFilter = function ($query) use ($request) {
            if ($request->filled('min_quizzes')) {
                $query->whereRaw('(SELECT COUNT(*) FROM quiz_users WHERE quiz_users.user_id = users.id) >= ?', [(int)$request->min_quizzes]);
            }
            if ($request->filled('max_quizzes')) {
                $query->whereRaw('(SELECT COUNT(*) FROM quiz_users WHERE quiz_users.user_id = users.id) <= ?', [(int)$request->max_quizzes]);
            }
        };

        // ==========================================
        // 1. STATS COUNTS
        // ==========================================
        $statsQuery = User::query()->where('role', 'user');
        $applyCommonFilters($statsQuery);
        $applyActivityFilters($statsQuery);
        $applyQuizRangeFilter($statsQuery); // ✅ Quiz Range applied to stats

        $totalUsers = (clone $statsQuery)->count();
        $activeUsers = (clone $statsQuery)->where('is_active', 1)->count();
        $inactiveUsers = (clone $statsQuery)->where('is_active', 0)->count();
        
        $playedUsers = (clone $statsQuery)->whereRaw('(SELECT COUNT(*) FROM quiz_users WHERE quiz_users.user_id = users.id) > 0')->count();
        $notPlayedUsers = (clone $statsQuery)->whereRaw('(SELECT COUNT(*) FROM quiz_users WHERE quiz_users.user_id = users.id) = 0')->count();

        // ==========================================
        // 2. MAIN QUERY FOR TABLE DATA
        // ==========================================
        $query = User::query()
            ->where('role', 'user')
            ->select('id', 'name', 'mobile', 'created_at', 'is_active', 'wallet_balance', 'leader_board_point')
            ->withCount(['quizUsers as total_quizzes'])
            ->addSelect([
                'last_played_date' => QuizUser::select('created_at')
                    ->whereColumn('user_id', 'users.id')
                    ->latest('created_at')
                    ->limit(1),
                
                'last_quiz_name' => Quiz::select('title') 
                    ->where('id', function ($sub) {
                        $sub->select('quiz_id')
                            ->from('quiz_users')
                            ->whereColumn('user_id', 'users.id')
                            ->latest('created_at')
                            ->limit(1);
                    }),

                'current_rank' => Leaderboard::select('rank')
                    ->whereColumn('user_id', 'users.id')
                    ->orderBy('rank', 'asc')
                    ->limit(1)
            ]);

        $applyCommonFilters($query);
        $applyActivityFilters($query);
        $applyQuizRangeFilter($query); // ✅ Quiz Range applied to table

        // Sorting Logic
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDir = $request->get('sort_dir', 'desc');
        $allowedColumns = ['name', 'created_at', 'total_quizzes', 'last_played_date', 'current_rank'];
        
        if (in_array($sortBy, $allowedColumns)) {
            $query->orderBy($sortBy, $sortDir === 'asc' ? 'asc' : 'desc');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $players = $query->paginate($perPage)->withQueryString();

        return view('superadmin.users.player.activity', compact(
            'players', 'totalUsers', 'activeUsers', 'inactiveUsers', 'playedUsers', 'notPlayedUsers'
        ));
    }

    
    // ==========================================
    // 3. EXPORT TO CSV (Updated with Wallet & Leaderboard Points)
    // ==========================================
    public function export(Request $request)
    {
        $applyCommonFilters = function ($query) use ($request) {
            if ($request->filled('search')) {
                $query->where(function ($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->search . '%')
                      ->orWhere('mobile', 'like', '%' . $request->search . '%');
                });
            }
            if ($request->filled('status')) {
                $query->where('is_active', $request->status === 'active' ? 1 : 0);
            }
            if ($request->filled('join_date_start')) {
                $query->whereDate('created_at', '>=', $request->join_date_start);
            }
            if ($request->filled('join_date_end')) {
                $query->whereDate('created_at', '<=', $request->join_date_end);
            }
        };

        $applyActivityFilters = function ($query) use ($request) {
            $rangeFrom = $request->filled('range_from') ? $request->range_from : null;
            $rangeTo = $request->filled('range_to') ? $request->range_to : null;
            $consistency = $request->filled('consistency_filter') ? $request->consistency_filter : null;
            
            $activityStatus = $request->filled('activity_status') ? $request->activity_status : 'all';
            $mainDateStart = $request->filled('activity_date_start') ? $request->activity_date_start : '1970-01-01';
            $mainDateEnd = $request->filled('activity_date_end') ? $request->activity_date_end : '2099-12-31';

            if ($rangeFrom && $rangeTo) {
                $totalDays = \Carbon\Carbon::parse($rangeFrom)->diffInDays(\Carbon\Carbon::parse($rangeTo)) + 1;
                $countSql = "(SELECT COUNT(DISTINCT DATE(created_at)) FROM quiz_users WHERE quiz_users.user_id = users.id AND DATE(created_at) BETWEEN ? AND ?)";

                if ($consistency === '100') {
                    $query->whereRaw("$countSql = ?", [$rangeFrom, $rangeTo, $totalDays]);
                } elseif ($consistency === '75') {
                    $requiredDays = ceil($totalDays * 0.75);
                    $query->whereRaw("$countSql >= ?", [$rangeFrom, $rangeTo, $requiredDays]);
                } elseif ($consistency === '50') {
                    $requiredDays = ceil($totalDays * 0.50);
                    $query->whereRaw("$countSql >= ?", [$rangeFrom, $rangeTo, $requiredDays]);
                } elseif ($consistency === 'low') {
                    $requiredDays = ceil($totalDays * 0.50);
                    $query->whereRaw("$countSql >= 1", [$rangeFrom, $rangeTo])
                          ->whereRaw("$countSql < ?", [$rangeFrom, $rangeTo, $requiredDays]);
                } else {
                    $query->whereRaw("$countSql >= 1", [$rangeFrom, $rangeTo]);
                }
            }

            if ($activityStatus !== 'all') {
                if ($activityStatus === 'played') {
                    $query->whereExists(function ($q) use ($mainDateStart, $mainDateEnd) {
                        $q->select(DB::raw(1))->from('quiz_users')->whereColumn('quiz_users.user_id', 'users.id')->whereBetween('quiz_users.created_at', [$mainDateStart, $mainDateEnd . ' 23:59:59']);
                    });
                } elseif ($activityStatus === 'not_played') {
                    $query->whereNotExists(function ($q) use ($mainDateStart, $mainDateEnd) {
                        $q->select(DB::raw(1))->from('quiz_users')->whereColumn('quiz_users.user_id', 'users.id')->whereBetween('quiz_users.created_at', [$mainDateStart, $mainDateEnd . ' 23:59:59']);
                    });
                }
            }
        };

        $applyQuizRangeFilter = function ($query) use ($request) {
            if ($request->filled('min_quizzes')) {
                $query->whereRaw('(SELECT COUNT(*) FROM quiz_users WHERE quiz_users.user_id = users.id) >= ?', [(int)$request->min_quizzes]);
            }
            if ($request->filled('max_quizzes')) {
                $query->whereRaw('(SELECT COUNT(*) FROM quiz_users WHERE quiz_users.user_id = users.id) <= ?', [(int)$request->max_quizzes]);
            }
        };

        // ==========================================
        // MAIN QUERY FOR EXPORT
        // ==========================================
        $query = User::query()
            ->where('role', 'user')
            // ✅ ADDED: wallet_balance and leader_board_point
            ->select('id', 'name', 'mobile', 'created_at', 'is_active', 'wallet_balance', 'leader_board_point')
            ->withCount(['quizUsers as total_quizzes'])
            ->addSelect([
                'last_played_date' => QuizUser::select('created_at')->whereColumn('user_id', 'users.id')->latest('created_at')->limit(1),
                'last_quiz_name' => Quiz::select('title')->where('id', function ($sub) {
                    $sub->select('quiz_id')->from('quiz_users')->whereColumn('user_id', 'users.id')->latest('created_at')->limit(1);
                }),
                'current_rank' => Leaderboard::select('rank')->whereColumn('user_id', 'users.id')->orderBy('rank', 'asc')->limit(1)
            ]);

        $applyCommonFilters($query);
        $applyActivityFilters($query);
        $applyQuizRangeFilter($query);

        $sortBy = $request->get('sort_by', 'created_at');
        $sortDir = $request->get('sort_dir', 'desc');
        $allowedColumns = ['name', 'created_at', 'total_quizzes', 'last_played_date', 'current_rank'];
        $query->orderBy(in_array($sortBy, $allowedColumns) ? $sortBy : 'created_at', $sortDir === 'asc' ? 'asc' : 'desc');

        $players = $query->get();

        // ==========================================
        // CSV STREAMING
        // ==========================================
        $filename = 'player_activity_' . now()->format('Y-m-d_H-i-s') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($players) {
            $file = fopen('php://output', 'w');
            
            // ✅ UPDATED HEADERS: Added Wallet Balance & Leaderboard Points at the end
            fputcsv($file, [
                'S.No', 'User ID', 'Name', 'Mobile', 'Joining Date', 'Status', 
                'Total Quizzes', 'Last Quiz Name', 'Last Played Date', 'Current Rank', 
                'Wallet Points', 'Leaderboard Marks'
            ]);

            $sno = 1;
            foreach ($players as $player) {
                fputcsv($file, [
                    $sno++,
                    $player->id,
                    $player->name,
                    $player->mobile ?? 'N/A',
                    \Carbon\Carbon::parse($player->created_at)->format('d M Y'),
                    $player->is_active == 1 ? 'Active' : 'Inactive',
                    $player->total_quizzes ?? 0,
                    $player->last_quiz_name ?? 'N/A',
                    $player->last_played_date ? \Carbon\Carbon::parse($player->last_played_date)->format('d M Y, h:i A') : 'Never',
                    $player->current_rank ? $player->current_rank : 'Unranked',
                    $player->wallet_balance ?? 0,          // ✅ ADDED DATA
                    $player->leader_board_point ?? 0,      // ✅ ADDED DATA
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}