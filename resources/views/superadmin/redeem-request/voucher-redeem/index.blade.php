@extends('superadmin.layouts.app')
@section('title', 'Voucher & Redeem  Requests')
<link rel="stylesheet" href="{{ asset('assets/css/custom.css') }}">

@section('content')
@push('styles')
<style>
    .approve-status-change { width: 130px; padding: 6px 10px; border: 1px solid #dcdfe6; border-radius: 6px; background: #fff; font-size: 13px; font-weight: 500; color: #374151; cursor: pointer; outline: none; }
    .approve-status-change:focus { border-color: #4f46e5; box-shadow: 0 0 0 2px rgba(79, 70, 229, .15); }
    .note-box { display: block; font-size: 11px; color: #6b7280; font-style: italic; border-left: 2px solid #d1d5db; padding: 4px 8px; margin-top: 6px; max-width: 180px; background: #f9fafb; border-radius: 0 4px 4px 0; }
    .note-box.rejected strong { color: #dc2626; }
    .note-box.approved strong { color: #16a34a; }
    .note-box.cancelled strong { color: #ca8a04; }
    
    .badge-source { display: inline-block; padding: 3px 8px; border-radius: 4px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
    .badge-redeem { background: #dbeafe; color: #1e40af; }
    .badge-voucher { background: #fef3c7; color: #92400e; }
    
    .req-badge { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
    .req-mobile { background-color: #e0f2fe; color: #0369a1; }
    .req-electricity { background-color: #fef3c7; color: #b45309; }
    .req-voucher { background-color: #fce7f3; color: #be185d; }

    .voucher-info-btn { padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 4px; transition: all 0.2s; border: 1px solid; }
    .voucher-info-btn.code { background: #fef3c7; border-color: #fcd34d; color: #92400e; }
    .voucher-info-btn.code:hover { background: #fde68a; }
    .voucher-info-btn.url { background: #dbeafe; border-color: #93c5fd; color: #1e40af; }
    .voucher-info-btn.url:hover { background: #bfdbfe; }
    .voucher-info-btn.image { background: #dcfce7; border-color: #86efac; color: #166534; }
    .voucher-info-btn.image:hover { background: #bbf7d0; }
</style>
@endpush

<div class="panel active">
    <div class="page-head" style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px;">
        <div>
            <h1>🔄 Voucher &  Redeem Requests 
                <span style="display: inline-block; margin-left: 8px; padding: 3px 9px; background: #e0f2fe; color: #0369a1; font-size: 18px; font-weight: 700; border-radius: 12px; vertical-align: middle;">
                    Total - {{ $paginated->total() }}
                </span>
            </h1>
            <p style="color: #64748b; font-size: 14px;">Manage Mobile, Electricity, and E-Gift Voucher requests from a single dashboard.</p>
        </div>
    </div>

    <!-- Search & Filter -->
    <div class="search-filter-box" style="background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 20px; margin-bottom: 24px;">
        <form method="GET" style="display: flex; gap: 16px; align-items: end; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 150px;">
                <label style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; display: block; margin-bottom: 6px;">Source Type</label>
                <select name="source_type" style="width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 8px;" onchange="this.form.submit()">
                    <option value="all" @selected($sourceType === 'all')>All Requests</option>
                    <option value="Redeem" @selected($sourceType === 'Redeem')>Mobile & Electricity</option>
                    <option value="Voucher" @selected($sourceType === 'Voucher')>E-Gift Vouchers</option>
                </select>
            </div>
            <div style="flex: 1; min-width: 150px;">
                <label style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; display: block; margin-bottom: 6px;">Status</label>
                <select name="status" style="width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 8px;" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    @foreach (['pending', 'approved', 'rejected', 'cancelled'] as $s)
                        <option value="{{ $s }}" @selected($status === $s)>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
            </div>
            <div style="flex: 2; min-width: 200px;">
                <label style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; display: block; margin-bottom: 6px;">Search</label>
                <input type="text" name="search" value="{{ $search }}" placeholder="Name, mobile, consumer no, voucher..." style="width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 8px;">
            </div>
            <div style="display: flex; gap: 10px;">
                <button type="submit" style="padding: 10px 20px; background: #6366f1; color: white; border-radius: 8px; border: none; font-weight: 700; cursor: pointer;">Filter</button>
                <a href="{{ route('superadmin.voucher-redeem.index') }}" style="padding: 10px 20px; background: transparent; border: 1px solid #e2e8f0; border-radius: 8px; text-decoration: none; color: #475569; font-weight: 600;">Reset</a>
            </div>
        </form>
    </div>

    <!-- Unified Table -->
    <div class="table-wrap" style="overflow-x: auto; background: #fff; border-radius: 16px; border: 1px solid #e2e8f0;">
        <table style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead>
                <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                    {{-- <th style="padding: 16px; font-size: 12px; font-weight: 700; color: #64748b;">Source</th> --}}
                    <th style="padding: 16px; font-size: 12px; font-weight: 700; color: #64748b;">Request Type</th>
                    <th style="padding: 16px; font-size: 12px; font-weight: 700; color: #64748b;">User Details</th>
                    <th style="padding: 16px; font-size: 12px; font-weight: 700; color: #64748b;">Mobile</th>
                    <th style="padding: 16px; font-size: 12px; font-weight: 700; color: #64748b;">Company / Voucher</th>

                    <th style="padding: 16px; font-size: 12px; font-weight: 700; color: #64748b;">Wallet Balance</th>
                    {{-- <th style="padding: 16px; font-size: 12px; font-weight: 700; color: #64748b;">Amount / Points</th> --}}
                    
                    <th style="padding: 16px; font-size: 12px; font-weight: 700; color: #64748b;">Consumer No.</th>
                    <th style="padding: 16px; font-size: 12px; font-weight: 700; color: #64748b;">Zone</th>
                    {{-- <th style="padding: 16px; font-size: 12px; font-weight: 700; color: #64748b;">User Note</th> --}}
                    <th style="padding: 16px; font-size: 12px; font-weight: 700; color: #64748b;">Status / Note</th>
                    {{-- <th style="padding: 16px; font-size: 12px; font-weight: 700; color: #64748b;">Voucher Info</th> --}}
                    {{-- <th style="padding: 16px; font-size: 12px; font-weight: 700; color: #64748b;">Date</th> --}}
                    <th style="padding: 16px; font-size: 12px; font-weight: 700; color: #64748b; text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($paginated as $item)
                    @php
                        $model = $item->model;
                        $isRedeem = $item->source_table === 'Redeem';
                        $badgeClass = $isRedeem ? 'badge-redeem' : 'badge-voucher';
                        
                        $reqTypeClass = 'req-voucher';
                        if ($isRedeem) {
                            $rt = strtolower($model->request_type ?? '');
                            $reqTypeClass = str_contains($rt, 'mobile') ? 'req-mobile' : 'req-electricity';
                        }

                        $userCurrentBal = (float) ($model->user->wallet_balance ?? 0);
                        $pointsAmount = (float) $item->amount;
                        $walletBalWithPoints = $isRedeem ? $userCurrentBal : ($userCurrentBal + $pointsAmount);
                    @endphp
                    <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.2s;">
                        {{-- <td style="padding: 16px;">
                            <span class="badge-source {{ $badgeClass }}">{{ $item->source_table }}</span>
                        </td> --}}
                        <td style="padding: 16px;">
                            <span class="req-badge {{ $reqTypeClass }}">{{ $item->type_name }}</span>
                        </td>
                        <td style="padding: 16px;">
                            <div style="font-weight: 700; color: #0f172a; font-size: 14px;">{{ $model->user->name ?? '—' }}</div>
                            <div style="font-size: 12px; color: #64748b;">{{ \Carbon\Carbon::parse($item->created_at)->format('d M Y, h:i A') }}</div>
                        </td>
                        {{-- <td style="padding: 16px; font-size: 13px; color: #475569;">
                            {{ $isRedeem ? ($item->mobile ?? '—') : '—' }}
                        </td> --}}
                        <td style="padding: 16px; font-size: 13px; color: #475569;">
                            @if($isRedeem && !empty($item->mobile))
                                <div>
                                    <span >
                                        {{ $item->mobile }}
                                    </span>
                                </div>

                                @if(!empty($item->company))
                                    <div style="margin-top: 5px; text-align: center;">
                                        {{ $item->company }}
                                    </div>
                                @endif
                                <div>
                                <span style="margin-top: 5px; text-align: center;" class="req-badge {{ $reqTypeClass }}"> {{ number_format($pointsAmount, 2) }} {{ $isRedeem ? '₹' : 'pts' }}</span>
                                </div>
                            @else
                                —
                            @endif
                        </td>
                        
                        <td style="padding: 16px; font-size: 13px; color: #475569;">
                            @if($isRedeem)
                                {{-- {{ $item->company ?? '—' }} --}}
                                —
                            @else
                                {{ $model->voucher->name ?? 'Voucher' }}
                                    <span class="req-badge {{ $reqTypeClass }}"> {{ number_format($pointsAmount, 2) }} {{ $isRedeem ? '₹' : 'pts' }}</span>
                                        @if(!$isRedeem && ($model->fee_percent ?? 0) > 0)
                                            <div style="font-size: 11px; font-weight: 500; color: #64748b;">
                                                (₹{{ number_format($model->voucher_amount, 0) }} + {{ $model->fee_percent }}% fee)
                                            </div>
                                        @endif


                                            
                            @endif
                        </td>

                        <td style="padding: 16px; font-size: 13px; font-weight: 600; color: #0f172a;">
                            {{ number_format($walletBalWithPoints, 2) }} {{ $isRedeem ? '₹' : 'pts' }}
                        </td>
                        {{-- <td style="padding: 16px; font-weight: 700; color: #0f172a;">
                           <span class="req-badge {{ $reqTypeClass }}"> {{ number_format($pointsAmount, 2) }} {{ $isRedeem ? '₹' : 'pts' }}</span>
                            @if(!$isRedeem && ($model->fee_percent ?? 0) > 0)
                                <div style="font-size: 11px; font-weight: 500; color: #64748b;">
                                    (₹{{ number_format($model->voucher_amount, 0) }} + {{ $model->fee_percent }}% fee)
                                </div>
                            @endif
                        </td> --}}
                        
                        
                        <td style="padding: 12px 16px; font-size: 13px; color: #475569; vertical-align: middle;">
                            @if($isRedeem && !empty($item->consumer_number))
                                <div style="display: flex; flex-direction: column; align-items: flex-start; gap: 6px;">

                                    {{-- Consumer Number --}}
                                    <span style="font-weight: 600; color: #334155;">
                                        {{ $item->consumer_number }}
                                    </span>

                                    {{-- Amount / Points --}}
                                    <div>
                                        <span class="req-badge {{ $reqTypeClass }}"
                                            style="font-size: 10px; font-weight: 600; padding: 3px 7px; white-space: nowrap;">
                                            {{ number_format($pointsAmount, 2) }} {{ $isRedeem ? '₹' : 'pts' }}
                                        </span>
                                    </div>

                                </div>
                            @else
                                <span style="color: #94a3b8;">—</span>
                            @endif
                        </td>
                        <td style="padding: 16px; font-size: 13px; color: #475569;">
                            @if($isRedeem && $model->electricityZone)
                                {{ $model->electricityZone->zone }} ({{ $model->electricityZone->zone_code }})
                            @else
                                —
                            @endif
                        </td>
                        {{-- <td style="padding: 16px;">
                            @if($isRedeem && $item->user_note)
                                <small class="note-box user-note" title="{{ $item->user_note }}">
                                    <strong>User Note:</strong> {{ Str::limit($item->user_note, 20) }}
                                </small>
                            @else
                                <span style="color: #9ca3af;">—</span>
                            @endif
                        </td> --}}
                        <td style="padding: 16px;">
                            @if($item->status == 'pending')
                                <select class="approve-status-change" data-id="{{ $item->id }}" data-source="{{ $item->source_table }}">
                                    <option value="" selected disabled>Pending</option>
                                    <option value="approved">Approve</option>
                                    <option value="rejected">Reject</option>
                                    {{-- <option value="cancelled">Cancel</option> --}}
                                </select>
                            @else
                                <span style="display: inline-block; padding: 4px 12px; border-radius: 9999px; font-size: 12px; font-weight: 600; 
                                    {{ $item->status == 'approved' ? 'background: #dcfce7; color: #166534;' : 'background: #fef2f2; color: #dc2626;' }}">
                                    {{ ucfirst($item->status) }}
                                </span>
                            @endif
                            @if($item->note)
                                <div class="note-box {{ $item->status }}">
                                    <strong>{{ $item->status == 'rejected' ? 'Reason:' : 'Note:' }}</strong> {{ Str::limit($item->note, 30) }}
                                </div>
                            @endif
                        </td>
                        {{-- <td style="padding: 16px; text-align: center;">
                            @if(!$isRedeem && $item->status === 'approved')
                                @if($model->voucher_code)
                                    @php $infoValue = addslashes($model->voucher_code); @endphp
                                    <button onclick="showVoucherPopup('code', '{{ $infoValue }}')" class="voucher-info-btn code">
                                        🎟️ Code
                                    </button>
                                @elseif($model->url)
                                    @php $infoValue = addslashes(Str::startsWith($model->url, 'http') ? $model->url : asset($model->url)); @endphp
                                    <button onclick="showVoucherPopup('url', '{{ $infoValue }}')" class="voucher-info-btn url">
                                        🔗 Link
                                    </button>
                                @elseif($model->image)
                                    @php $infoValue = addslashes(Str::startsWith($model->image, 'http') ? $model->image : asset($model->image)); @endphp
                                    <button onclick="showVoucherPopup('image', '{{ $infoValue }}')" class="voucher-info-btn image">
                                        🖼️ Image
                                    </button>
                                @else
                                    <span style="color: #cbd5e1; font-size: 18px;">-</span>
                                @endif
                            @else
                                <span style="color: #cbd5e1; font-size: 18px;">-</span>
                            @endif
                        </td> --}}
                        {{-- <td style="padding: 16px; font-size: 13px; color: #64748b;">
                            {{ \Carbon\Carbon::parse($item->created_at)->format('d M Y, h:i A') }}
                        </td> --}}
                        <td style="padding: 16px; text-align: right;">
                            <button class="btn-view-unified" data-source="{{ $item->source_table }}" data-id="{{ $item->id }}" title="View Details"
                                style="background: #f1f5f9; border: none; width: 32px; height: 32px; border-radius: 6px; cursor: pointer; font-size: 16px;">👁</button>

                                @if(!$isRedeem && $item->status === 'approved')
                                    @if($model->voucher_code)
                                        @php $infoValue = addslashes($model->voucher_code); @endphp
                                        <button onclick="showVoucherPopup('code', '{{ $infoValue }}')" class="voucher-info-btn code">
                                            Code
                                        </button>
                                    @elseif($model->url)
                                        @php $infoValue = addslashes(Str::startsWith($model->url, 'http') ? $model->url : asset($model->url)); @endphp
                                        <button onclick="showVoucherPopup('url', '{{ $infoValue }}')" class="voucher-info-btn url">
                                            Link
                                        </button>
                                    @elseif($model->image)
                                        @php $infoValue = addslashes(Str::startsWith($model->image, 'http') ? $model->image : asset($model->image)); @endphp
                                        <button onclick="showVoucherPopup('image', '{{ $infoValue }}')" class="voucher-info-btn image">
                                            Image
                                        </button>
                                    @else
                                        <span style="color: #cbd5e1; font-size: 18px;">-</span>
                                    @endif
                                @else
                                    <span style="color: #cbd5e1; font-size: 18px;">-</span>
                                @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="14" style="text-align: center; padding: 40px; color: #94a3b8;">No requests found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

   

     <div class="pagination">
        @include('common.pagination.superadmin_pagination', ['pageData' => $paginated])
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // Status Change Logic
    document.addEventListener('change', function(e) {
        if (!e.target.classList.contains('approve-status-change')) return;
        const id = e.target.dataset.id;
        const source = e.target.dataset.source;
        const status = e.target.value;
        const selectElement = e.target;
        if (!status) return;

        if (status === 'rejected') {
            Swal.fire({
                title: 'Reject Request',
                input: 'textarea',
                inputLabel: 'Enter a reason for rejection (required)',
                inputPlaceholder: 'Type your reason here...',
                inputValidator: (value) => { if (!value || value.trim() === '') return 'Rejection note cannot be empty!'; },
                showCancelButton: true,
                confirmButtonText: 'Reject',
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d'
            }).then((result) => {
                if (result.isConfirmed) updateUnifiedStatus(id, source, status, result.value, selectElement);
                else selectElement.selectedIndex = 0;
            });
        } else if (status === 'approved') {
            if (source === 'Voucher') {
                // Voucher Approval Modal with URL/Image/Code selection
                const deliveryHtml = `
                    <div style="text-align: left; display: flex; flex-direction: column; gap: 16px; margin-top: 5px;">
                        <div>
                            <label style="font-size: 13px; font-weight: 700; color: #334155; display: block; margin-bottom: 8px;">
                                1. Select Voucher Format / Delivery Type: <span style="color: #ef4444;">*</span>
                            </label>
                            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px;">
                                <label id="label_type_url" class="delivery-type-label active" style="display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 6px; padding: 12px 8px; background: #eef2ff; border: 2px solid #6366f1; border-radius: 10px; cursor: pointer; font-size: 13px; font-weight: 700; color: #4338ca; text-align: center; transition: all 0.2s ease;">
                                    <input type="radio" name="delivery_type" value="url" checked style="accent-color: #6366f1; width: 18px; height: 18px; display: none;">
                                    <span style="font-size: 18px;">🔗</span>
                                    <span>Voucher URL</span>
                                </label>
                                <label id="label_type_image" class="delivery-type-label" style="display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 6px; padding: 12px 8px; background: #f8fafc; border: 2px solid #e2e8f0; border-radius: 10px; cursor: pointer; font-size: 13px; font-weight: 600; color: #64748b; text-align: center; transition: all 0.2s ease;">
                                    <input type="radio" name="delivery_type" value="image" style="accent-color: #6366f1; width: 18px; height: 18px; display: none;">
                                    <span style="font-size: 18px;">🖼️</span>
                                    <span>Voucher Image</span>
                                </label>
                                <label id="label_type_code" class="delivery-type-label" style="display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 6px; padding: 12px 8px; background: #f8fafc; border: 2px solid #e2e8f0; border-radius: 10px; cursor: pointer; font-size: 13px; font-weight: 600; color: #64748b; text-align: center; transition: all 0.2s ease;">
                                    <input type="radio" name="delivery_type" value="code" style="accent-color: #6366f1; width: 18px; height: 18px; display: none;">
                                    <span style="font-size: 18px;">🎟️</span>
                                    <span>Voucher Code</span>
                                </label>
                            </div>
                        </div>
                        <div>
                            <label style="font-size: 13px; font-weight: 700; color: #334155; display: block; margin-bottom: 6px;">2. Enter Voucher Details:</label>
                            <div id="url_input_group">
                                <input id="swal_url" class="swal2-input" style="margin: 0; width: 100%; box-sizing: border-box; font-size: 14px; padding: 10px 14px; border: 1.5px solid #cbd5e1; border-radius: 8px;" placeholder="Enter Voucher Link / URL">
                            </div>
                            <div id="image_input_group" style="display: none;">
                                <div style="border: 2px dashed #cbd5e1; padding: 14px; border-radius: 8px; text-align: center; background: #fafafa;">
                                    <input type="file" id="swal_image" style="margin: 0; width: 100%; font-size: 13px;" accept="image/*">
                                    <div style="font-size: 11px; color: #64748b; margin-top: 6px;">Supported: JPG, PNG, WEBP</div>
                                </div>
                            </div>
                            <div id="code_input_group" style="display: none;">
                                <input id="swal_voucher_code" class="swal2-input" style="margin: 0; width: 100%; box-sizing: border-box; font-size: 14px; padding: 10px 14px; border: 1.5px solid #cbd5e1; border-radius: 8px;" placeholder="Enter Voucher / Coupon Code">
                            </div>
                        </div>
                        <div>
                            <label style="font-size: 13px; font-weight: 700; color: #334155; display: block; margin-bottom: 6px;">3. Admin Remark / Note <span style="font-size: 11px; color: #94a3b8; font-weight: 500;">(Optional)</span>:</label>
                            <input id="swal_note" class="swal2-input" style="margin: 0; width: 100%; box-sizing: border-box; font-size: 14px; padding: 10px 14px; border: 1.5px solid #cbd5e1; border-radius: 8px;" placeholder="e.g. Approved and voucher sent">
                        </div>
                    </div>
                `;

                Swal.fire({
                    title: 'Approve Voucher Request',
                    html: deliveryHtml,
                    showCancelButton: true,
                    confirmButtonText: 'Approve & Send WhatsApp',
                    confirmButtonColor: '#16a34a',
                    cancelButtonColor: '#6c757d',
                    didOpen: () => {
                        const labels = {
                            url: document.getElementById('label_type_url'),
                            image: document.getElementById('label_type_image'),
                            code: document.getElementById('label_type_code')
                        };

                        function switchDeliveryType(type) {
                            const targetRadio = document.querySelector(`input[name="delivery_type"][value="${type}"]`);
                            if (targetRadio) targetRadio.checked = true;

                            document.getElementById('url_input_group').style.display = type === 'url' ? 'block' : 'none';
                            document.getElementById('image_input_group').style.display = type === 'image' ? 'block' : 'none';
                            document.getElementById('code_input_group').style.display = type === 'code' ? 'block' : 'none';

                            Object.keys(labels).forEach(key => {
                                if (key === type) {
                                    labels[key].style.background = '#eef2ff';
                                    labels[key].style.borderColor = '#6366f1';
                                    labels[key].style.color = '#4338ca';
                                    labels[key].style.fontWeight = '700';
                                } else {
                                    labels[key].style.background = '#f8fafc';
                                    labels[key].style.borderColor = '#e2e8f0';
                                    labels[key].style.color = '#64748b';
                                    labels[key].style.fontWeight = '600';
                                }
                            });

                            if (type === 'url') document.getElementById('swal_url').focus();
                            if (type === 'code') document.getElementById('swal_voucher_code').focus();
                        }

                        Object.keys(labels).forEach(type => {
                            if (labels[type]) {
                                labels[type].addEventListener('click', (e) => {
                                    e.preventDefault();
                                    switchDeliveryType(type);
                                });
                            }
                        });

                        setTimeout(() => {
                            const swalUrl = document.getElementById('swal_url');
                            if (swalUrl) swalUrl.focus();
                        }, 100);
                    },
                    preConfirm: () => {
                        const selectedType = document.querySelector('input[name="delivery_type"]:checked')?.value || 'url';
                        const note = document.getElementById('swal_note').value.trim();
                        let code = '', url = '', image = null;

                        if (selectedType === 'code') {
                            code = document.getElementById('swal_voucher_code').value.trim();
                            if (!code) { Swal.showValidationMessage('Please enter the Voucher Code!'); return false; }
                        } else if (selectedType === 'url') {
                            url = document.getElementById('swal_url').value.trim();
                            if (!url) { Swal.showValidationMessage('Please enter the Voucher URL!'); return false; }
                        } else if (selectedType === 'image') {
                            image = document.getElementById('swal_image').files[0];
                            if (!image) { Swal.showValidationMessage('Please select a Voucher Image!'); return false; }
                        }

                        return { delivery_type: selectedType, voucher_code: code, url: url, voucher_image: image, note: note || 'Approved by admin' };
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        const formData = new FormData();
                        formData.append('id', id);
                        formData.append('source_table', source);
                        formData.append('status', 'approved');
                        formData.append('note', result.value.note);
                        formData.append('_token', '{{ csrf_token() }}');

                        if (result.value.voucher_code) formData.append('voucher_code', result.value.voucher_code);
                        if (result.value.url) formData.append('url', result.value.url);
                        if (result.value.voucher_image) formData.append('voucher_image', result.value.voucher_image);

                        updateUnifiedStatusWithForm(id, source, formData, selectElement);
                    } else {
                        selectElement.selectedIndex = 0;
                    }
                });
            } else {
                // Redeem Approval (Simple note)
                Swal.fire({
                    title: 'Approve Redeem Request',
                    input: 'textarea',
                    inputLabel: 'Enter a note (optional)',
                    inputPlaceholder: 'Type your note here...',
                    showCancelButton: true,
                    confirmButtonText: 'Approve',
                    confirmButtonColor: '#16a34a',
                    cancelButtonColor: '#6c757d'
                }).then((result) => {
                    if (result.isConfirmed) updateUnifiedStatus(id, source, status, result.value, selectElement);
                    else selectElement.selectedIndex = 0;
                });
            }
        } else {
            // Cancel
            Swal.fire({
                title: 'Cancel Request',
                input: 'textarea',
                inputLabel: 'Enter a note (optional)',
                inputPlaceholder: 'Type your note here...',
                showCancelButton: true,
                confirmButtonText: 'Cancel',
                confirmButtonColor: '#f59e0b',
                cancelButtonColor: '#6c757d'
            }).then((result) => {
                if (result.isConfirmed) updateUnifiedStatus(id, source, status, result.value, selectElement);
                else selectElement.selectedIndex = 0;
            });
        }
    });

    function updateUnifiedStatus(id, source, status, note, selectElement) {
        Swal.fire({ title: 'Processing...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        
        const formData = new FormData();
        formData.append('id', id);
        formData.append('source_table', source);
        formData.append('status', status);
        formData.append('note', note || '');
        formData.append('_token', '{{ csrf_token() }}');

        fetch("{{ route('superadmin.voucher-redeem.changeStatus') }}", {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                Swal.fire({ icon: 'success', title: 'Success', text: data.message, timer: 1500, showConfirmButton: false });
                setTimeout(() => location.reload(), 1500);
            } else {
                Swal.fire({ icon: 'warning', title: 'Warning', text: data.message });
                selectElement.selectedIndex = 0;
            }
        })
        .catch(() => {
            Swal.fire({ icon: 'error', title: 'Error', text: 'Something went wrong.' });
            selectElement.selectedIndex = 0;
        });
    }

    function updateUnifiedStatusWithForm(id, source, formData, selectElement) {
        Swal.fire({ title: 'Processing...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

        fetch("{{ route('superadmin.voucher-redeem.changeStatus') }}", {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                Swal.fire({ icon: 'success', title: 'Success', text: data.message, timer: 1500, showConfirmButton: false });
                setTimeout(() => location.reload(), 1500);
            } else {
                Swal.fire({ icon: 'warning', title: 'Warning', text: data.message });
                selectElement.selectedIndex = 0;
            }
        })
        .catch(() => {
            Swal.fire({ icon: 'error', title: 'Error', text: 'Something went wrong.' });
            selectElement.selectedIndex = 0;
        });
    }

    // Voucher Info Popup
    function showVoucherPopup(type, value) {
        let content = '', title = '';
        if (type === 'code') {
            title = 'Voucher Code';
            content = `<div style="text-align:center; padding: 20px 10px;"><div style="font-size: 40px; margin-bottom: 15px;">🎟️</div><code style="display:block; background: #fef3c7; padding: 12px 20px; border-radius: 8px; font-size: 18px; font-weight: bold; letter-spacing: 1px; border: 1px solid #fcd34d; color: #92400e; word-break: break-all;">${value}</code><br><button onclick="navigator.clipboard.writeText('${value}'); Swal.fire({icon: 'success', title: 'Copied!', timer: 1000, showConfirmButton: false});" style="padding: 8px 16px; background: #6366f1; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 14px; margin-top: 10px;">Copy Code</button></div>`;
        } else if (type === 'url') {
            title = 'Voucher URL';
            content = `<div style="text-align:center; padding: 20px 10px;"><div style="font-size: 40px; margin-bottom: 15px;"></div><a href="${value}" target="_blank" style="display:block; word-break: break-all; color: #4f46e5; font-weight: 600; text-decoration: underline; margin-bottom: 15px;">${value}</a><a href="${value}" target="_blank" style="display:inline-block; padding: 8px 16px; background: #16a34a; color: white; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 14px;">Open Link</a></div>`;
        } else if (type === 'image') {
            title = 'Voucher Image';
            content = `<div style="text-align:center; padding: 10px;"><img src="${value}" alt="Voucher" style="max-width: 100%; max-height: 300px; border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 15px; object-fit: contain; background: #f8fafc;"><br><a href="${value}" target="_blank" style="display:inline-block; padding: 8px 16px; background: #6366f1; color: white; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 14px;">View Full Image</a></div>`;
        }
        Swal.fire({ title: title, html: content, confirmButtonText: 'Close', confirmButtonColor: '#64748b', width: 400 });
    }

    // View Details Modal
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-view-unified');
        if (!btn) return;
        const source = btn.dataset.source;
        const id = btn.dataset.id;

        Swal.fire({ title: 'Loading...', didOpen: () => Swal.showLoading() });
        fetch(`{{ route('superadmin.voucher-redeem.show', ['source' => ':source', 'id' => ':id']) }}`.replace(':source', source).replace(':id', id))
            .then(res => res.json())
            .then(res => {
                if (!res.success) { Swal.fire({ icon: 'error', title: 'Error', text: res.message }); return; }
                const d = res.data;
                const html = `
                    <div style="text-align:left; font-size:13px; line-height:1.6;">
                        <div style="padding:10px 0; border-bottom:1px solid #e2e8f0;">
                            <strong>Source:</strong> <span class="badge-source ${d.source.includes('Redeem') ? 'badge-redeem' : 'badge-voucher'}">${d.source}</span><br>
                            <strong>User:</strong> ${d.user} (${d.user_mobile})<br>
                            <strong>Type:</strong> ${d.type}
                        </div>
                        <div style="padding:10px 0; border-bottom:1px solid #e2e8f0;">
                            <strong>Amount:</strong> ${d.amount} ${d.source.includes('Redeem') ? '' : 'pts'}<br>
                            <strong>Company/Voucher:</strong> ${d.company}<br>
                            ${d.consumer_no !== '—' ? `<strong>Consumer No:</strong> ${d.consumer_no}<br>` : ''}
                            ${d.mobile !== '—' ? `<strong>Request Mobile No:</strong> ${d.mobile}<br>` : ''}
                            ${d.zone !== '—' ? `<strong>Zone:</strong> ${d.zone}<br>` : ''}
                        </div>
                        <div style="padding:10px 0;">
                            <strong>Status:</strong> <span style="text-transform:capitalize; font-weight:600;">${d.status}</span><br>
                            ${d.voucher_code && d.voucher_code !== '—' ? `<strong>Code:</strong> <code style="background:#fef3c7; padding:2px 6px; border-radius:4px;">${d.voucher_code}</code><br>` : ''}
                            ${d.url && d.url !== '—' ? `<strong>URL:</strong> <a href="${d.url}" target="_blank">${d.url}</a><br>` : ''}
                            ${d.image && d.image !== '—' ? `<strong>Image:</strong> <a href="${d.image}" target="_blank">View Image</a><br>` : ''}
                            <strong>Note:</strong> ${d.note}
                        </div>
                    </div>
                `;
                Swal.fire({ title: 'Request Details', html: html, confirmButtonText: 'Close', confirmButtonColor: '#6366f1' });
            })
            .catch(() => Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to fetch details.' }));
    });
</script>
@endpush
@endsection