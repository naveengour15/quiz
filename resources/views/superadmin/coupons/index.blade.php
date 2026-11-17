@extends('superadmin.layouts.app')
@section('title', 'Coupons')
@section('content')
@push('styles')
<style>
    .coupon-status-change {
        min-width: 150px;
        padding: 8px 12px;
        border: 1px solid #dcdfe6;
        border-radius: 8px;
        background: #fff;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
    }

    .coupon-status-change:focus {
        outline: none;
        border-color: #4F46E5;
    }
</style>
@endpush
    <div class="panel active">
        <div class="page-head">
            <div>
                <h1>Coupons</h1>
                <p>Discount codes for consumer and institutional subscriptions.</p>
            </div>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Discount</th>
                        <th>Usage</th>
                        <th>Expiry</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($coupons as $coupon)
                        <tr>
                            <td class="mono">{{ $coupon->code }}</td>
                            <td>{{ $coupon->discount_type === 'percentage' ? $coupon->discount_value . '% off' : '₹' . $coupon->discount_value . ' Fixed off' }}
                            </td>
                            <td class="mono">{{ $coupon->used_count }} / {{ $coupon->usage_limit_total ?? '∞' }}</td>
                            <td class="mono">{{ $coupon->expiry_date?->format('d M Y') }}</td>
                            {{-- <td>
                                @php $stClass = ['active'=>'success','paused'=>'neutral','expired'=>'neutral'][$coupon->status] ?? 'neutral'; @endphp
                                <span class="badge {{ $stClass }}">{{ ucfirst($coupon->status) }}</span>
                            </td> --}}

                            <td>
                              <select class="coupon-status-change" data-id="{{ $coupon->id }}">
                                    <option value="active" {{ $coupon->status == 'active' ? 'selected' : '' }}>
                                        Active
                                    </option>

                                    <option value="inactive" {{ $coupon->status == 'inactive' ? 'selected' : '' }}>
                                        Inactive
                                    </option>
                                </select>
                            </td>
                            <td class="row-actions"><a class="icon-btn"
                                    href="{{ route('superadmin.coupons.index', ['edit' => $coupon->id]) }}">✎</a></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align:center;color:var(--ink-faint);">No coupons yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
           <div class="pagination">
                @include('common.pagination.superadmin_pagination', ['pageData' => $coupons])
            </div> 
        </div>

        <div class="form-card">
            <div class="fh">
                <h3>{{ $editing ? 'Edit Coupon' : 'Add Coupon' }}</h3>
            </div>
            <form method="POST"
                action="{{ $editing ? route('superadmin.coupons.update', $editing) : route('superadmin.coupons.store') }}">
                @csrf
                @if ($editing)
                    @method('PUT')
                @endif
                <div class="form-grid">
                    <div class="field"><label>Coupon Code <span class="req">*</span></label><input type="text"
                            name="code" value="{{ $editing->code ?? old('code') }}" placeholder="e.g. SAVE20" required>
                    </div>
                    <div class="field"><label>Discount Type <span class="req">*</span></label>
                        <select name="discount_type" required>
                            <option value="percentage" @selected(($editing->discount_type ?? 'percentage') === 'percentage')>Percentage</option>
                            <option value="fixed" @selected(($editing->discount_type ?? '') === 'fixed')>Fixed Amount</option>
                        </select>
                    </div>
                    <div class="field"><label>Discount Value <span class="req">*</span></label><input type="number"
                            name="discount_value" value="{{ $editing->discount_value ?? old('discount_value') }}" required>
                    </div>
                    <div class="field"><label>Minimum Purchase (₹)</label><input type="number" name="min_purchase"
                            value="{{ $editing->min_purchase ?? old('min_purchase') }}"></div>
                    <div class="field"><label>Usage Limit — Total</label><input type="number" name="usage_limit_total"
                            value="{{ $editing->usage_limit_total ?? old('usage_limit_total') }}"></div>
                    <div class="field"><label>Usage Limit — Per User <span class="req">*</span></label><input
                            type="number" name="usage_limit_per_user" value="{{ $editing->usage_limit_per_user ?? 1 }}"
                            required></div>
                    <div class="field"><label>Applicable Plans <span class="hint">(comma-separated)</span></label><input
                            type="text" name="applicable_plans[]"
                            value="{{ implode(', ', $editing->applicable_plans ?? []) }}" placeholder="Silver, Gold"></div>
                    <div class="field"><label>Expiry Date <span class="req">*</span></label><input type="date"
                            name="expiry_date" value="{{ $editing?->expiry_date?->format('Y-m-d') }}" required></div>
                    <div class="field"><label>Status <span class="req">*</span></label>
                        {{-- <select name="status" required>
                            <option value="active" @selected(($editing->status ?? 'active') === 'active')>Active</option>
                            <option value="paused" @selected(($editing->status ?? '') === 'paused')>Paused</option>
                        </select> --}}

                        <select name="status" required>
                            <option value="active" @selected(($editing->status ?? 'active') == 'active')>Active </option>
                            <option value="inactive" @selected(($editing->status ?? '') == 'inactive')> Inactive </option>
                        </select>
                    </div>
                </div>
                <div class="form-foot">
                    @if ($editing)
                        <a class="btn btn-ghost" href="{{ route('superadmin.coupons.index') }}">Cancel</a>
                    @endif
                    <button class="btn btn-primary" type="submit">Save Coupon</button>
                </div>
            </form>
        </div>
    </div>

@endsection
@push('scripts')
<script>
document.addEventListener('change', function(e) {

    if (!e.target.classList.contains('coupon-status-change')) {
        return;
    }

    fetch("{{ route('superadmin.coupons.changeStatus') }}", {

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
                'Unable to update coupon status.'
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