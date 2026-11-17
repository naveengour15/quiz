@extends('superadmin.layouts.app')
@section('title', 'Subscriptions')
<link rel="stylesheet" href="{{ asset('assets/css/custom.css') }}">
@section('content')

    <div class="panel active">
        <div class="page-head">
            <div>
                <h1>Subscriptions</h1>
                <p>Plan catalog and every user's active billing status.</p>
            </div>
        </div>

        <div class="section-label">Plan Catalog</div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Plan</th>
                        <th>Monthly / Annual</th>
                        <th>Max Devices</th>
                        <th>Active Subscribers</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($plans as $plan)
                        <tr>
                            <td><b>{{ $plan->name }}</b></td>
                            <td class="mono">₹{{ $plan->monthly_price }} / ₹{{ $plan->annual_price }}</td>
                            <td class="mono">{{ $plan->max_devices }}</td>
                            <td class="mono">{{ $plan->subscriptions_count }}</td>
                            <td>
                                @if ($plan->status === 'active')
                                <span class="badge success">Active</span>@else<span class="badge neutral">Archived</span>
                                @endif
                            </td>
                            <td class="row-actions"><a class="icon-btn"
                                    href="{{ route('superadmin.subscriptions.index', ['edit' => $plan->id]) }}">✎</a></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align:center;color:var(--ink-faint);">No plans yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="section-label">User Subscription Records</div>
        <form method="GET" class="toolbar">
            <select class="tb-select" name="plan_id" onchange="this.form.submit()">
                <option value="">All Plans</option>
                @foreach ($plans as $plan)
                    <option value="{{ $plan->id }}" @selected(request('plan_id') == $plan->id)>{{ $plan->name }}</option>
                @endforeach
            </select>
            <select class="tb-select" name="status" onchange="this.form.submit()">
                <option value="">All Statuses</option>
                @foreach (['active', 'cancelled', 'expired', 'payment_failed'] as $s)
                    <option value="{{ $s }}" @selected(request('status') === $s)>
                        {{ str($s)->replace('_', ' ')->title() }}</option>
                @endforeach
            </select>
        </form>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Plan</th>
                        <th>Renewal Date</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($subscriptions as $sub)
                        <tr>
                            <td>{{ $sub->user->name ?? '—' }}</td>
                            <td>{{ $sub->plan->name ?? '—' }} ({{ ucfirst($sub->billing_cycle) }})</td>
                            <td class="mono">{{ $sub->renewal_date?->format('d M Y') ?? '—' }}</td>
                            <td class="mono">₹{{ $sub->amount }}</td>
                            <td>{{ $sub->payment_method }}</td>
                            <td>
                                @php $stClass = ['active'=>'success','cancelled'=>'neutral','expired'=>'neutral','payment_failed'=>'danger'][$sub->status] ?? 'neutral'; @endphp
                                <span
                                    class="badge {{ $stClass }}">{{ str($sub->status)->replace('_', ' ')->title() }}</span>
                            </td>
                            <td class="row-actions">
                                <form method="POST" action="{{ route('superadmin.subscriptions.renew', $sub) }}">
                                    @csrf
                                    <button class="icon-btn" type="submit" title="Renew">↻</button>
                                </form>
                                <form method="POST" action="{{ route('superadmin.subscriptions.cancel', $sub) }}"
                                    onsubmit="return confirm('Cancel this subscription?')">
                                    @csrf
                                    <button class="icon-btn danger" type="submit" title="Cancel">✕</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align:center;color:var(--ink-faint);">No subscription records
                                yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="pagination">
                <span>Showing {{ $subscriptions->firstItem() }}–{{ $subscriptions->lastItem() }} of
                    {{ $subscriptions->total() }}</span>{{ $subscriptions->links() }}
                </div>
        </div>

        <div class="form-card">
            <div class="fh">
                <h3>{{ $editingPlan ? 'Edit Plan' : 'Add Plan' }}</h3>
            </div>
            <form method="POST"
                action="{{ $editingPlan ? route('superadmin.subscriptions.plans.update', $editingPlan) : route('superadmin.subscriptions.plans.store') }}">
                @csrf
                @if ($editingPlan)
                    @method('PUT')
                @endif
                <div class="form-grid form-grid-02">
                    <div class="field"><label>Plan Name <span class="req">*</span></label><input type="text"
                            name="name" value="{{ $editingPlan->name ?? old('name') }}" required></div>
                    <div class="field"><label>Tier Level <span class="req">*</span></label><input type="text"
                            name="tier_level" value="{{ $editingPlan->tier_level ?? old('tier_level') }}"
                            placeholder="e.g. 2 — Gold" required></div>
                    <div class="field"><label>Monthly Price (₹) <span class="req">*</span></label><input type="number"
                            name="monthly_price" value="{{ $editingPlan->monthly_price ?? 0 }}" required></div>
                    <div class="field"><label>Annual Price (₹) <span class="req">*</span></label><input type="number"
                            name="annual_price" value="{{ $editingPlan->annual_price ?? 0 }}" required></div>
                    <div class="field"><label>Max Devices <span class="req">*</span></label><input type="number"
                            name="max_devices" value="{{ $editingPlan->max_devices ?? 1 }}" required></div>
                    <div class="field"><label>Status <span class="req">*</span></label>
                        <select name="status" required>
                            <option value="active" @selected(($editingPlan->status ?? 'active') === 'active')>Active</option>
                            <option value="archived" @selected(($editingPlan->status ?? '') === 'archived')>Archived</option>
                        </select>
                    </div>
                    <div class="field full"><label>Included Features <span class="hint">(comma box, one per
                                line)</span></label>
                        @php $features = $editingPlan->features ?? ['','','']; @endphp
                        @foreach ($features as $f)
                            <input type="text" name="features[]" value="{{ $f }}" style="margin-bottom:6px;">
                        @endforeach
                    </div>
                </div>
                <div class="form-foot">
                    @if ($editingPlan)
                        <a class="btn btn-ghost" href="{{ route('superadmin.subscriptions.index') }}">Cancel</a>
                    @endif
                    <button class="btn btn-primary" type="submit">Save Plan</button>
                </div>
            </form>
        </div>
    </div>

@endsection
