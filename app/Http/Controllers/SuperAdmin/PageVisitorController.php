<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\PageVisitor;
use App\Models\Quiz;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PageVisitorController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->get('per_page', 20);
        $dateFilter = $request->get('date');
        $search = $request->get('search');
        $deviceFilter = $request->get('device');
        $pageFilter = $request->get('page_filter');

        // Preset date quick filters
        $preset = $request->get('preset');
        if ($preset === 'today') {
            $dateFilter = Carbon::today()->toDateString();
        } elseif ($preset === 'yesterday') {
            $dateFilter = Carbon::yesterday()->toDateString();
        } elseif ($preset === '7days') {
            $dateFilter = null; // Handled below
        }

        // 1. High-level Overview Metrics
        $totalVisitsAllTime = PageVisitor::count();
        $todayVisits = PageVisitor::today()->count();
        $todayUniqueVisitors = PageVisitor::today()->distinct('ip_address')->count('ip_address');
        $liveActiveVisitors = PageVisitor::live(15)->distinct('ip_address')->count('ip_address');
        $isQuizLiveNow = Quiz::isAnyQuizPlayingNow();

        // 2. Top Visited Pages (Aggregated Count)
        $topPagesQuery = PageVisitor::query();
        if ($preset === '7days') {
            $topPagesQuery->where('visit_date', '>=', Carbon::now()->subDays(7)->toDateString());
        } elseif ($dateFilter) {
            $topPagesQuery->where('visit_date', $dateFilter);
        }
        $topPages = $topPagesQuery->select(
                'page_name',
                DB::raw('COUNT(*) as total_hits'),
                DB::raw('COUNT(DISTINCT ip_address) as unique_visitors'),
                DB::raw('MAX(created_at) as last_visit_time')
            )
            ->groupBy('page_name')
            ->orderByDesc('total_hits')
            ->take(8)
            ->get();

        // 3. Device Breakdown
        $deviceStatsQuery = PageVisitor::query();
        if ($preset === '7days') {
            $deviceStatsQuery->where('visit_date', '>=', Carbon::now()->subDays(7)->toDateString());
        } elseif ($dateFilter) {
            $deviceStatsQuery->where('visit_date', $dateFilter);
        }
        $deviceStats = $deviceStatsQuery->select(
                'device_type',
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('device_type')
            ->pluck('count', 'device_type')
            ->toArray();

        // 4. Detailed Live Visitor Logs
        $logsQuery = PageVisitor::with('user:id,name,mobile,email');

        if ($preset === '7days') {
            $logsQuery->where('visit_date', '>=', Carbon::now()->subDays(7)->toDateString());
        } elseif ($dateFilter) {
            $logsQuery->where('visit_date', $dateFilter);
        }

        if ($pageFilter) {
            $logsQuery->where('page_name', $pageFilter);
        }

        if ($deviceFilter) {
            $logsQuery->where('device_type', $deviceFilter);
        }

        if (!empty($search)) {
            $logsQuery->where(function ($q) use ($search) {
                $q->where('page_name', 'like', "%{$search}%")
                  ->orWhere('page_url', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%")
                  ->orWhere('browser', 'like', "%{$search}%")
                  ->orWhere('platform', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                         ->orWhere('mobile', 'like', "%{$search}%");
                  });
            });
        }

        $visitors = $logsQuery->latest('created_at')->paginate($perPage)->withQueryString();

        // List of all distinct page names for filter dropdown
        $allPageNames = PageVisitor::distinct()->orderBy('page_name')->pluck('page_name');

        return view('superadmin.visitors.index', compact(
            'visitors',
            'topPages',
            'deviceStats',
            'totalVisitsAllTime',
            'todayVisits',
            'todayUniqueVisitors',
            'liveActiveVisitors',
            'isQuizLiveNow',
            'allPageNames',
            'dateFilter',
            'preset',
            'search',
            'deviceFilter',
            'pageFilter',
            'perPage'
        ));
    }
}

