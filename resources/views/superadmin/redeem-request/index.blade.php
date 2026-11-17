@extends('superadmin.layouts.app')
@section('title', 'Redeem Requests')
<link rel="stylesheet" href="{{ asset('assets/css/custom.css') }}">

@section('content')
    @push('styles')
        <style>
            .approve-status-change {
                width: 140px;
                padding: 6px 30px 6px 10px;
                border: 1px solid #dcdfe6;
                border-radius: 6px;
                background: #fff;
                font-size: 13px;
                font-weight: 500;
                color: #374151;
                cursor: pointer;
                outline: none;
            }
            .approve-status-change:focus {
                border-color: #4f46e5;
                box-shadow: 0 0 0 2px rgba(79,70,229,.15);
            }

            /* Note Box Styles - Reusable for User Note & Approver Note */
            .note-box {
                display: block;
                font-size: 11px;
                color: #6b7280;
                font-style: italic;
                line-height: 1.4;
                border-left: 2px solid #d1d5db;
                padding: 4px 8px;
                margin-top: 6px;
                max-width: 180px;
                background: #f9fafb;
                border-radius: 0 4px 4px 0;
            }

            .note-box.empty-note {
                color: #9ca3af;
                font-style: normal;
                border-left-color: #e5e7eb;
                background: transparent;
            }

            .note-box.rejected strong { color: #dc2626; }
            .note-box.approved strong { color: #16a34a; }
            .note-box.cancelled strong { color: #ca8a04; }

            .note-box.user-note strong { color: #2563eb; }
              /* ✅ NEW: Request Type Badge Styles (Guaranteed to work) */
            .req-badge {
                display: inline-block;
                padding: 5px 14px;
                border-radius: 20px;
                font-size: 12px;
                font-weight: 600;
                text-align: center;
                white-space: nowrap;
            }
            .req-mobile { background-color: #dbeafe; color: #1e40af; border: 1px solid #bfdbfe; }       /* Blue */
            .req-electricity { background-color: #fef3c7; color: #92400e; border: 1px solid #fde68a; }  /* Yellow */
          
            .req-default { background-color: #f3f4f6; color: #374151; border: 1px solid #e5e7eb; }      /* Gray */
            .redeem-user {
                    
                    align-items: center;
                    gap: 8px;
                    white-space: nowrap;
                }

                .user-id-badge {
                    display: inline-block;
                    padding: 3px 8px;
                    border-radius: 5px;
                    background: #f1f1f1;
                    color: #555;
                    font-size: 12px;
                    font-weight: 600;
                }
        </style>
    @endpush

    <div class="panel active">
        <div class="page-head" style="display:flex;justify-content:space-between;align-items:center;">
            <div>
                <h1>Redeem Requests</h1>
            </div>
        </div>

        <div class="search-filter-box">
            <form method="GET" class="toolbar">
                <div class="redeem-request-search-filter-box">
                    <div class="redeem-request-search-filter-wrapper">
                        <div class="input-field">
                            <label class="lbl" for="search">Search</label>
                            <input class="tb-input search-field" name="search" value="{{ request('search') }}" placeholder="Search by name, mobile, consumer no...">
                        </div>

                        <div class="input-field">
                            <label class="lbl" for="status">Approval Status</label>
                            <select class="tb-select status-field" name="status" onchange="this.form.submit()">
                                <option value="">All Statuses</option>
                                @foreach (['pending', 'approved', 'rejected', 'cancelled'] as $s)
                                    <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="redeem-request-search-filter-btn">
                        <button class="btn btn-primary" type="submit">Filter</button>
                        <a href="{{ route('superadmin.redeem_requests.index') }}" class="btn btn-ghost">Reset</a>
                    </div>
                </div>
            </form>
        </div>

        <div class="table-wrap table-wrap-res-01" style="overflow: hidden">
            <div class="quizzes-table-box">
                <table class="quizzes-table-wrapper">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Request Type</th>
                            <th>Mobile</th>
                            <th>Wallet Balance</th>
                            <th>Amount</th>
                            <th>Consumer No.</th>
                            <th>Company</th>
                            <th>Zone (State)</th>
                            <th>User Note</th>
                            <th>Status</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($requests as $req)
                            <tr>
                                <td data-label="User">
                                    <div class="redeem-user">
                                        <strong>{{ $req->user->name ?? '—' }}</strong><br>
                                        {{-- <small>{{ $req->user->email ?? '—' }}</small><br> --}}
                                         <span class="user-id-badge">
                                            User ID: {{ $req->user->id ?? '—' }}
                                        </span>
                                    </div>
                                </td>
                               @php
                                    // Request type ko lowercase mein convert karo taaki "Mobile Recharge" ya "mobile recharge" dono kaam karein
                                    $reqType = strtolower(trim($req->request_type ?? ''));
                                    
                                    // Default class set karo
                                    $badgeClass = 'req-default';
                                    
                                    // Condition check karo aur sahi custom CSS class assign karo
                                    if (str_contains($reqType, 'mobile') || str_contains($reqType, 'recharge')) {
                                        $badgeClass = 'req-mobile';
                                    } elseif (str_contains($reqType, 'electricity') || str_contains($reqType, 'electric')) {
                                        $badgeClass = 'req-electricity';
                                    }
                                @endphp

                                <td data-label="Request Type" class="p-2">
                                    <!-- Yahan 'req-badge' base class aur dynamic '$badgeClass' dono lagana zaroori hai -->
                                    <span class="req-badge {{ $badgeClass }}">
                                        {{ $req->request_type ?? '—' }}
                                    </span>
                                </td>
                                <td data-label="Mobile">{{ $req->mobile ?? '—' }}</td>
                                <td data-label="Wallet Balance" class="mono">₹{{ number_format($req->user->wallet_balance ?? 0, 2) }}</td>
                                <td data-label="Amount" class="mono">₹{{ number_format($req->redeem_amount, 2) }}</td>
                                <td data-label="Consumer No.">{{ $req->consumer_number ?? '—' }}</td>
                                <td data-label="Company">{{ $req->company ?? '—' }}</td>
                                <td data-label="Zone">
                                    @if($req->electricityZone)
                                        {{ $req->electricityZone->zone }} ({{ $req->electricityZone->zone_code }})
                                    @else
                                        —
                                    @endif
                                </td>
                                <td data-label="User Note">
                                    @if($req->user_note)
                                        <small 
                                            class="note-box user-note"
                                            title="{{ $req->user_note }}">
                                            <strong>User Note:</strong>
                                            {{ Str::limit($req->user_note, 20) }}
                                        </small>
                                    @else
                                        <span style="color: #9ca3af;">—</span>
                                    @endif
                                </td>

                               <td data-label="Status">
                                    <div class="redeem-status">
                                        
                                        {{-- Status Badge / Dropdown --}}
                                        <div>
                                            @if($req->status == 'approved')
                                                <span class="badge success">Approved</span>
                                            @elseif($req->status == 'rejected')
                                                <span class="badge danger">Rejected</span>
                                            @elseif($req->status == 'cancelled')
                                                <span class="badge warn">Cancelled</span>
                                            @else
                                                <select class="approve-status-change" data-id="{{ $req->id }}">
                                                    <option value="" selected disabled>Pending</option>
                                                    <option value="approved">Approve</option>
                                                    <option value="rejected">Reject</option>
                                                    <option value="cancelled">Cancel</option>
                                                </select>
                                            @endif
                                        </div>

                                        {{-- Approver Note - Conditional Display --}}
                                        @if($req->approver_note)
                                            <small 
                                                class="note-box {{ $req->status }}"
                                                title="{{ $req->approver_note }}">
                                                @if($req->status == 'rejected')
                                                    <strong>Reason:</strong>
                                                @elseif($req->status == 'approved')
                                                    <strong>Note:</strong>
                                                @else
                                                    <strong>Note:</strong>
                                                @endif
                                                {{ Str::limit($req->approver_note) }}
                                            </small>
                                        @endif

                                    </div>
                                </td>
                               
                                <td data-label="Created"> 
                                        @if($req->redeem_msg_status == 'Sent')
                                               <span class="badge success">  W-Msg-Admin : Sent</span>
                                            @elseif($req->redeem_msg_status == 'failed')
                                                 <span class="badge danger">   W-Msg-Admin :Failed</span>
                                            @elseif($req->redeem_msg_status == 'pending')
                                                <span class="badge warn">  W-Msg-Admin : Pending</span>
                                            @endif
                                            <br>
                                            {{ $req->created_at?->format('d M Y, h:i A') ?? '—' }}</td>
                                <td data-label="Actions">
                                 
                                    <button class="icon-btn btn-view-quiz btn-view-redeem" data-id="{{ $req->id }}" title="View Details">👁</button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" style="text-align:center;color:var(--ink-faint);">No redeem requests found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="pagination">
                @include('common.pagination.superadmin_pagination', ['pageData' => $requests])
            </div>
        </div>
    </div>
@endsection
@include('superadmin.redeem-request.components.details-modal')
@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('change', function(e) {
            if (!e.target.classList.contains('approve-status-change')) {
                return;
            }

            const id = e.target.dataset.id;
            const status = e.target.value;
            const selectElement = e.target;

            if (!status) return;

            if (status === 'rejected') {
                Swal.fire({
                    title: 'Reject Request',
                    input: 'textarea',
                    inputLabel: 'Enter a reason for rejection (required)',
                    inputPlaceholder: 'Type your reason here...',
                    inputValidator: (value) => {
                        if (!value || value.trim() === '') {
                            return 'Rejection note cannot be empty!';
                        }
                    },
                    showCancelButton: true,
                    confirmButtonText: 'Reject',
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d'
                }).then((result) => {
                    if (result.isConfirmed) {
                        updateRedeemStatus(id, status, result.value, selectElement);
                    } else {
                        selectElement.selectedIndex = 0;
                    }
                });
            } else {
                const isApprove = status === 'approved';
                Swal.fire({
                    title: isApprove ? 'Approve Request' : 'Cancel Request',
                    input: 'textarea',
                    inputLabel: `Enter a note for ${isApprove ? 'approval' : 'cancellation'} (optional)`,
                    inputPlaceholder: 'Type your note here...',
                    showCancelButton: true,
                    confirmButtonText: isApprove ? 'Approve' : 'Cancel Request',
                    confirmButtonColor: isApprove ? '#28a745' : '#ffc107',
                    cancelButtonColor: '#6c757d'
                }).then((result) => {
                    if (result.isConfirmed) {
                        updateRedeemStatus(id, status, result.value, selectElement);
                    } else {
                        selectElement.selectedIndex = 0;
                    }
                });
            }
        });

        function updateRedeemStatus(id, status, note, selectElement) {
            fetch("{{ route('superadmin.redeem-requests.changeStatus') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    id: id,
                    status: status,
                    note: note
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showAlert('success', 'Success', data.message || 'Status updated successfully.');
                    setTimeout(() => location.reload(), 800);
                } else {
                    showAlert('warning', 'Warning', data.message || 'Unable to update status.');
                    selectElement.selectedIndex = 0;
                }
            })
            .catch(() => {
                showAlert('error', 'Error', 'Something went wrong.');
                selectElement.selectedIndex = 0;
            });
        }
    </script>
@endpush