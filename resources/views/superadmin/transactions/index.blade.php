@extends('superadmin.layouts.app')
@section('title', 'Transactions')
<link rel="stylesheet" href="{{ asset('assets/css/custom.css') }}">
@section('content')
    @push('styles')
        <style>
            .transaction-status-change {
                min-width: 140px;
                padding: 8px 12px;
                border: 1px solid #dcdfe6;
                border-radius: 8px;
                background: #fff;
                font-size: 13px;
                font-weight: 600;
                cursor: pointer;
            }

            .transaction-status-change:focus {
                outline: none;
                border-color: #4F46E5;
            }
        </style>
    @endpush
    <div class="panel active">
        <div class="page-head">
            <div>
                <h1>Transactions</h1>
                <p>Every financial event on the platform — subscriptions, redemptions, refunds.</p>
            </div>
        </div>

        <div class="search-filter-box">
            <form method="GET" class="toolbar">
                <div class="transaction-search-filter-box">
                    <div class="transaction-search-filter-wrapper">
                        <div class="input-field">
                            <label class="lbl" for="search">Search</label>
                            <input class="tb-input" name="search" value="{{ request('search') }}"
                             placeholder="Search by transaction ID or user…">
                        </div>

                         <div class="input-field">
                            <label class="lbl" for="search">Type</label>
                             <select class="tb-select" name="type" onchange="this.form.submit()">
                                <option value="">All Types</option>
                                @foreach (['subscription', 'redemption', 'refund', 'coupon_redemption'] as $t)
                                    <option value="{{ $t }}" @selected(request('type') === $t)>
                                        {{ str($t)->replace('_', ' ')->title() }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="input-field">
                            <label class="lbl" for="search">Status</label>
                             <select class="tb-select" name="status" onchange="this.form.submit()">
                                <option value="">All Statuses</option>
                                @foreach (['success', 'failed', 'refunded'] as $s)
                                    <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="input-field">
                            <label class="lbl" for="search">Date</label> 
                            <input class="tb-input" type="date" name="date" value="{{ request('date') }}"
                            onchange="this.form.submit()">
                        </div>
                    </div>

                    <div class="transaction-filter-btn">
                        <div class="tb-spacer"></div>
                <button class="btn btn-primary" type="submit">Filter</button>
                    </div>
                </div>
            </form>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Transaction ID</th>
                        <th>User</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $txn)
                        <tr>
                            <td class="mono">{{ $txn->txn_id }}</td>
                            <td>{{ $txn->user->name ?? '—' }}</td>
                            <td>{{ str($txn->type)->replace('_', ' ')->title() }}</td>
                            <td class="mono">₹{{ $txn->amount }}</td>
                            <td>{{ $txn->method }}</td>
                            {{-- <td>
                                @php $stClass = ['success'=>'success','failed'=>'danger','refunded'=>'warn'][$txn->status] ?? 'neutral'; @endphp
                                <span class="badge {{ $stClass }}">{{ ucfirst($txn->status) }}</span>
                            </td> --}}
                            <td>
                                <select class="transaction-status-change" data-id="{{ $txn->id }}">
                                    <option value="success" {{ $txn->status == 'success' ? 'selected' : '' }}>
                                        Success
                                    </option>

                                    <option value="failed" {{ $txn->status == 'failed' ? 'selected' : '' }}>
                                        Failed
                                    </option>

                                    <option value="refunded" {{ $txn->status == 'refunded' ? 'selected' : '' }}>
                                        Refunded
                                    </option>
                                </select>
                            </td>
                            <td class="mono">{{ $txn->created_at->format('d M Y') }}</td>
                            <td class="row-actions">
                                @if ($txn->status === 'success')
                                    <form method="POST" action="{{ route('superadmin.transactions.refund', $txn) }}"
                                        onsubmit="return confirm('Refund this transaction?')">
                                        @csrf
                                        <button class="icon-btn danger" type="submit" title="Refund">↻</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="text-align:center;color:var(--ink-faint);">No transactions yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
           <div class="pagination">
                @include('common.pagination.superadmin_pagination', ['pageData' => $transactions])
            </div> 
        </div>
    </div>


@endsection
@push('scripts')
    <script>
        document.addEventListener('change', function(e) {

            if (!e.target.classList.contains('transaction-status-change')) {
                return;
            }

            fetch("{{ route('superadmin.transactions.changeStatus') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
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
                            'Transaction status updated successfully.'
                        );

                    } else {

                        showAlert(
                            'warning',
                            'Warning',
                            'Unable to update transaction status.'
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
