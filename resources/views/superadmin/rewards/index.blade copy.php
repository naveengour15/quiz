@extends('superadmin.layouts.app')
@section('title', 'Rewards')
@section('content')
    @push('styles')
        <style>
            .reward-status-change {
                min-width: 150px;
                padding: 8px 12px;
                border: 1px solid #dcdfe6;
                border-radius: 8px;
                background: #fff;
                font-size: 13px;
                font-weight: 600;
                cursor: pointer;
            }

            .reward-status-change:focus {
                outline: none;
                border-color: #4F46E5;
            }
        </style>
    @endpush
    <div class="panel active">
        <div class="page-head">
            <div>
                <h1>Rewards</h1>
                <p>Reward store catalog and the redemption approval queue.</p>
            </div>
        </div>

        <div class="section-label">Redemption Queue — needs approval</div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Reward</th>
                        <th>Coins</th>
                        <th>Requested</th>
                        <th>Fraud Score</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($queue as $r)
                        <tr>
                            <td>{{ $r->user->name ?? '—' }}</td>
                            <td>{{ $r->reward->name ?? '—' }}</td>
                            <td class="mono">{{ $r->coins }}</td>
                            <td class="mono">{{ $r->created_at->diffForHumans() }}</td>
                            <td><span
                                    class="badge {{ $r->fraud_score > 0.3 ? 'warn' : 'success' }}">{{ $r->fraud_score > 0.3 ? 'Medium' : 'Low' }}
                                    — {{ $r->fraud_score }}</span></td>
                            <td><span class="badge warn">Pending</span></td>
                            <td class="row-actions">
                                <form method="POST" action="{{ route('superadmin.rewards.redemptions.approve', $r) }}">
                                    @csrf<button class="icon-btn" type="submit">✓</button></form>
                                <form method="POST" action="{{ route('superadmin.rewards.redemptions.reject', $r) }}"
                                    onsubmit="return confirm('Reject this redemption?')">@csrf @method('DELETE')<button
                                        class="icon-btn danger" type="submit">✕</button></form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align:center;color:var(--ink-faint);">No pending redemptions.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="section-label">Reward Catalog</div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Reward</th>
                        <th>Category</th>
                        <th>Coin Cost</th>
                        <th>Cash Value</th>
                        <th>Fulfillment</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rewards as $reward)
                        <tr>
                            <td>{{ $reward->name }}</td>
                            <td>{{ $reward->category }}</td>
                            <td class="mono">{{ $reward->coin_cost }}</td>
                            <td class="mono">₹{{ $reward->cash_value }}</td>
                            <td>{{ $reward->fulfillment_type === 'instant_api' ? 'Instant (API)' : 'Manual Approval' }}
                            </td>
                            {{-- <td>
                                  @php $stClass = ['active'=>'success','inactive'=>'neutral','out_of_stock'=>'neutral'][$reward->status] ?? 'neutral'; @endphp
                                  <span class="badge {{ $stClass }}">{{ str($reward->status)->replace('_',' ')->title() }}</span>
                                </td> --}}

                            <td>
                                <select class="reward-status-change" data-id="{{ $reward->id }}">
                                    <option value="active" {{ $reward->status == 'active' ? 'selected' : '' }}>
                                        Active
                                    </option>

                                    <option value="inactive" {{ $reward->status == 'inactive' ? 'selected' : '' }}>
                                        Inactive
                                    </option>

                                    <option value="out_of_stock" {{ $reward->status == 'out_of_stock' ? 'selected' : '' }}>
                                        Out Of Stock
                                    </option>
                                </select>
                            </td>
                            <td class="row-actions"><a class="icon-btn"
                                    href="{{ route('superadmin.rewards.index', ['edit' => $reward->id]) }}">✎</a></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align:center;color:var(--ink-faint);">No rewards yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="form-card">
            <div class="fh">
                <h3>{{ $editing ? 'Edit Reward' : 'Add Reward' }}</h3>
            </div>
            <form method="POST"
                action="{{ $editing ? route('superadmin.rewards.update', $editing) : route('superadmin.rewards.store') }}">
                @csrf
                @if ($editing)
                    @method('PUT')
                @endif
                <div class="form-grid">
                    <div class="field"><label>Reward Name <span class="req">*</span></label><input type="text"
                            name="name" value="{{ $editing->name ?? old('name') }}" required></div>
                    <div class="field"><label>Category <span class="req">*</span></label>
                        <select name="category" required>
                            @foreach (['Voucher', 'Mobile Recharge', 'Bill Payment', 'Merchandise', 'Subscription Upgrade'] as $c)
                                <option @selected(($editing->category ?? '') === $c)>{{ $c }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field"><label>Partner / Provider</label><input type="text" name="partner"
                            value="{{ $editing->partner ?? old('partner') }}"></div>
                    <div class="field"><label>Coin Cost <span class="req">*</span></label><input type="number"
                            name="coin_cost" value="{{ $editing->coin_cost ?? 0 }}" required></div>
                    <div class="field"><label>Cash Value (₹) <span class="req">*</span></label><input type="number"
                            name="cash_value" value="{{ $editing->cash_value ?? 0 }}" required></div>
                    <div class="field"><label>Stock <span class="hint">(blank = unlimited)</span></label><input
                            type="number" name="stock" value="{{ $editing->stock ?? old('stock') }}"></div>
                    <div class="field"><label>Fulfillment Type <span class="req">*</span></label>
                        <select name="fulfillment_type" required>
                            <option value="instant_api" @selected(($editing->fulfillment_type ?? '') === 'instant_api')>Instant (API)</option>
                            <option value="manual_approval" @selected(($editing->fulfillment_type ?? 'manual_approval') === 'manual_approval')>Manual Approval</option>
                        </select>
                    </div>
                    <div class="field"><label>Status <span class="req">*</span></label>
                        <select name="status" required>
                            @foreach (['active' => 'Active', 'inactive' => 'Inactive', 'out_of_stock' => 'Out of Stock'] as $val => $label)
                                <option value="{{ $val }}" @selected(($editing->status ?? 'active') === $val)>{{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field full"><label>Reward Image URL</label><input type="text" name="image"
                            value="{{ $editing->image ?? old('image') }}"></div>
                </div>
                <div class="form-foot">
                    @if ($editing)
                        <a class="btn btn-ghost" href="{{ route('superadmin.rewards.index') }}">Cancel</a>
                    @endif
                    <button class="btn btn-primary" type="submit">Save Reward</button>
                </div>
            </form>
        </div>
    </div>

@endsection
@push('scripts')
    <script>
        document.addEventListener('change', function(e) {

            if (!e.target.classList.contains('reward-status-change')) {
                return;
            }

            fetch("{{ route('superadmin.rewards.changeStatus') }}", {

                    method: "POST",

                    headers: {
                        "Content-Type": "application/json",
                        "Accept": "application/json",
                        "X-CSRF-TOKEN": "{{ csrf_token() }}"
                    },

                    body: JSON.stringify({
                        id: e.target.dataset.id,
                        status: e.target.value
                    })

                })
                .then(res => res.json())
                .then(data => {

                    if (data.success) {

                        showAlert(
                            'success',
                            'Success',
                            data.message
                        );

                    } else {

                        showAlert(
                            'error',
                            'Error',
                            'Unable to update reward status.'
                        );

                    }

                })
                .catch(() => {

                    showAlert(
                        'error',
                        'Error',
                        'Something went wrong.'
                    );

                });

        });
    </script>
@endpush
