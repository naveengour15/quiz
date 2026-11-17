<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ModerationReport;
use App\Models\Redemption;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserSubscription;

class ADashboardController extends Controller
{
    public function index()
    {
        $tenantId = getTenantId();
        $stats = [
            'total_users' => User::where('tenant_id', $tenantId)->count(),
            'new_signups_today' => User::where('tenant_id', $tenantId)->whereDate('created_at', today())->count(),
            'active_subscriptions' => UserSubscription::where('tenant_id', $tenantId)->where('status', 'active')->count(),
            'moderation_queue' => ModerationReport::where('tenant_id', $tenantId)->where('status', 'pending')->count(),
            'revenue_today' => Transaction::where('tenant_id', $tenantId)->where('type', 'subscription')
                ->where('status', 'success')
                ->whereDate('created_at', today())
                ->sum('amount'),
        ];

        return view('admin.dashboard', compact('stats'));
    }
}
