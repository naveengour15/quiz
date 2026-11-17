<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Plan;
use App\Models\UserSubscription;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function index(Request $request)
    {
        // $plans = Plan::withCount('subscriptions')->orderBy('monthly_price')->get();
          $plans = Plan::withCount('subscriptions')->get();

        $subscriptions = UserSubscription::with(['user', 'plan'])
            ->when($request->plan_id, fn ($q) => $q->where('plan_id', $request->plan_id))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $editingPlan = $request->edit ? Plan::findOrFail($request->edit) : null;

        return view('superadmin.subscriptions.index', compact('plans', 'subscriptions', 'editingPlan'));
    }

    public function storePlan(Request $request)
    {
        $data = $this->validatedPlan($request);
        Plan::create($data);
        AuditLog::log('Created plan', 'Billing', $data['name']);

        return redirect()->route('superadmin.subscriptions.index')->with('success', 'Plan saved.');
    }

    public function updatePlan(Request $request, Plan $plan)
    {
        $data = $this->validatedPlan($request);
        $plan->update($data);
        AuditLog::log('Updated plan', 'Billing', $plan->name);

        return redirect()->route('superadmin.subscriptions.index')->with('success', 'Plan updated.');
    }

    public function renew(UserSubscription $subscription)
    {
        $subscription->update(['status' => 'active']);
        AuditLog::log('Renewed subscription', 'Billing', 'SUB-' . $subscription->id);

        return back()->with('success', 'Subscription renewed.');
    }

    public function cancel(UserSubscription $subscription)
    {
        $subscription->update(['status' => 'cancelled']);
        AuditLog::log('Cancelled subscription', 'Billing', 'SUB-' . $subscription->id);

        return back()->with('success', 'Subscription cancelled.');
    }

    private function validatedPlan(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:191',
            'tier_level' => 'required|string',
            'monthly_price' => 'required|numeric|min:0',
            'annual_price' => 'required|numeric|min:0',
            'max_devices' => 'required|integer|min:1',
            'features.*' => 'nullable|string',
            'status' => 'required|in:active,archived',
        ]);
        $data['features'] = $request->input('features', []);

        return $data;
    }
}
