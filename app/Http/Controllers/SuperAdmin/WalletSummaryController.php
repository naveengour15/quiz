<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WalletHistory;
use App\Models\RedeemRequest;
use Illuminate\Http\Request;

class WalletSummaryController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        $tenantId = getSuperAdminTenantId(); 

        // ==========================================
        // 1. TOP STATS CARDS
        // ==========================================
        $totalCredit = WalletHistory::where('tenant_id', $tenantId)
            ->where('type', 'credit')
            ->where('transaction_type', '!=', 'refund') 
            ->sum('amount');

        $totalRedeemed = RedeemRequest::where('tenant_id', $tenantId)
            ->where('status', 'approved')
            ->sum('redeem_amount');

        $totalPending = RedeemRequest::where('tenant_id', $tenantId)
            ->where('status', 'pending')
            ->sum('redeem_amount');

        // ✅ NEW: Total Cancelled / Rejected Redeem Amount
        $totalCancelled = RedeemRequest::where('tenant_id', $tenantId)
            ->whereIn('status', ['rejected', 'cancelled'])
            ->sum('redeem_amount');

        $totalRequests = RedeemRequest::where('tenant_id', $tenantId)->count();

        // ==========================================
        // 2. USER LIST
        // ==========================================
        $users = User::query()
            ->where('tenant_id', $tenantId)
            ->where('role', 'user') 
            ->select('id', 'name', 'email', 'mobile', 'wallet_balance', 'created_at')
            
            ->addSelect([
                'total_credit' => WalletHistory::selectRaw('COALESCE(SUM(amount), 0)')
                    ->whereColumn('user_id', 'users.id')
                    ->where('tenant_id', $tenantId)
                    ->where('type', 'credit')
                    ->where('transaction_type', '!=', 'refund') 
            ])
            ->addSelect([
                'total_redeemed' => RedeemRequest::selectRaw('COALESCE(SUM(redeem_amount), 0)')
                    ->whereColumn('user_id', 'users.id')
                    ->where('tenant_id', $tenantId)
                    ->where('status', 'approved')
            ])
            ->addSelect([
                'total_pending' => RedeemRequest::selectRaw('COALESCE(SUM(redeem_amount), 0)')
                    ->whereColumn('user_id', 'users.id')
                    ->where('tenant_id', $tenantId)
                    ->where('status', 'pending')
            ])
            ->addSelect([
                'total_requests' => RedeemRequest::selectRaw('COUNT(*)')
                    ->whereColumn('user_id', 'users.id')
                    ->where('tenant_id', $tenantId)
            ])
            
            ->when($request->search, function ($q) use ($request) {
                $search = $request->search;
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('mobile', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            })
            ->when($request->filled('filter_pending') && $request->filter_pending === 'yes', function ($q) {
                $q->havingRaw('total_pending > 0');
            })
            ->when($request->filled('sort_by'), function ($q) use ($request) {
                $allowedSorts = ['name', 'wallet_balance', 'total_credit', 'total_redeemed', 'total_pending', 'total_requests', 'created_at'];
                $sortBy = in_array($request->sort_by, $allowedSorts) ? $request->sort_by : 'created_at';
                $sortDir = $request->sort_dir === 'asc' ? 'asc' : 'desc';
                $q->orderBy($sortBy, $sortDir);
            }, function ($q) {
                $q->orderBy('created_at', 'desc');
            })
            ->paginate($perPage)
            ->withQueryString();

        return view('superadmin.wallet-summary.index', compact(
            'users', 
            'totalCredit', 
            'totalRedeemed', 
            'totalPending',
            'totalCancelled', // ✅ Pass to view
            'totalRequests'
        ));
    }
}