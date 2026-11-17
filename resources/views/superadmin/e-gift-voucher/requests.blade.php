@extends('superadmin.layouts.app')
@section('title', 'Voucher Requests')
<link rel="stylesheet" href="{{ asset('assets/css/custom.css') }}">

@section('content')
    @push('styles')
        <style>
            .approve-status-change {
                width: 130px;
                padding: 6px 10px;
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
                box-shadow: 0 0 0 2px rgba(79, 70, 229, .15);
            }

            .note-box {
                display: block;
                font-size: 11px;
                color: #6b7280;
                font-style: italic;
                border-left: 2px solid #d1d5db;
                padding: 4px 8px;
                margin-top: 6px;
                max-width: 180px;
                background: #f9fafb;
                border-radius: 0 4px 4px 0;
            }

            .note-box.rejected strong {
                color: #dc2626;
            }

            .note-box.approved strong {
                color: #16a34a;
            }

            .note-box.cancelled strong {
                color: #ca8a04;
            }
        </style>
    @endpush

    <div class="panel active">
        <div class="page-head" style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px;">
            <div>
                <h1>E-Gift Voucher Requests</h1>
                <p style="color: #64748b; font-size: 14px;">Review and approve/reject user requests for shopping vouchers.
                </p>
            </div>
        </div>

        <!-- Search & Filter -->
        <div class="search-filter-box"
            style="background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 20px; margin-bottom: 24px;">
            <form method="GET" style="display: flex; gap: 16px; align-items: end; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 200px;">
                    <label
                        style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; display: block; margin-bottom: 6px;">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}"
                        placeholder="User name, mobile or voucher..."
                        style="width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 8px;">
                </div>
                <div style="min-width: 150px;">
                    <label
                        style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; display: block; margin-bottom: 6px;">Status</label>
                    <select name="status"
                        style="width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 8px;"
                        onchange="this.form.submit()">
                        <option value="">All Statuses</option>
                        @foreach (['pending', 'approved', 'rejected', 'cancelled'] as $s)
                            <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="display: flex; gap: 10px;">
                    <button type="submit"
                        style="padding: 10px 20px; background: #6366f1; color: white; border-radius: 8px; border: none; font-weight: 700; cursor: pointer;">Filter</button>
                    <a href="{{ route('superadmin.e_gift_voucher.requests') }}"
                        style="padding: 10px 20px; background: transparent; border: 1px solid #e2e8f0; border-radius: 8px; text-decoration: none; color: #475569; font-weight: 600;">Reset</a>
                </div>
            </form>
        </div>

        <!-- Requests Table -->
        <div class="table-wrap" style="overflow-x: auto; background: #fff; border-radius: 16px; border: 1px solid #e2e8f0;">
            <table style="width: 100%; border-collapse: collapse; text-align: left;">
                <thead>
                    <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                        <th style="padding: 16px; font-size: 12px; font-weight: 700; color: #64748b;">User</th>
                        <th style="padding: 16px; font-size: 12px; font-weight: 700; color: #64748b;">Voucher</th>
                        <th style="padding: 16px; font-size: 12px; font-weight: 700; color: #64748b;">Wallet Bal.</th>
                        <th style="padding: 16px; font-size: 12px; font-weight: 700; color: #64748b;">Points</th>
                        <th style="padding: 16px; font-size: 12px; font-weight: 700; color: #64748b;">Status / Note</th>
                        
                        <!-- ✅ NEW COLUMN: Voucher Info -->
                        <th style="padding: 16px; font-size: 12px; font-weight: 700; color: #64748b; text-align: center;">Voucher Info</th>
                        
                        <th style="padding: 16px; font-size: 12px; font-weight: 700; color: #64748b;">Date</th>
                        <th style="padding: 16px; font-size: 12px; font-weight: 700; color: #64748b; text-align: right;">
                            Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $req)
                        @php
                            $pointsAmount = (float) ($req->total_amount ?: ($req->voucher_amount ?: 0));
                            $userCurrentBal = (float) ($req->user->wallet_balance ?? 0);
                            $walletBalWithPoints = $req->wollet_balance
                                ? (float) $req->wollet_balance
                                : $userCurrentBal + $pointsAmount;
                        @endphp
                        <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.2s;">
                            <td style="padding: 16px;">
                                <div style="font-weight: 700; color: #0f172a; font-size: 14px;">
                                    {{ $req->user->name ?? '—' }}</div>
                                <div style="font-size: 12px; color: #64748b;">{{ $req->user->mobile ?? '—' }}</div>
                            </td>
                            <td style="padding: 16px; font-weight: 600; color: #475569;">{{ $req->voucher->name ?? 'N/A' }}
                            </td>
                            <td style="padding: 16px; font-size: 13px; font-weight: 600; color: #0f172a;">
                                {{ number_format($walletBalWithPoints, 2) }} pts</td>
                            <td style="padding: 16px; font-weight: 700; color: #0f172a;">
                                {{ number_format($pointsAmount, 2) }} pts
                                @if (($req->fee_percent ?? 0) > 0)
                                    <div style="font-size: 11px; font-weight: 500; color: #64748b;">
                                        (₹{{ number_format($req->voucher_amount, 0) }} + {{ $req->fee_percent }}% fee)
                                    </div>
                                @endif
                            </td>

                            <td style="padding: 16px;">
                                @if ($req->status == 'pending')
                                    <select class="approve-status-change" data-id="{{ $req->id }}">
                                        <option value="" selected disabled>Pending</option>
                                        <option value="approved">Approve</option>
                                        <option value="rejected">Reject</option>
                                    </select>
                                @else
                                    <span
                                        style="display: inline-block; padding: 4px 12px; border-radius: 9999px; font-size: 12px; font-weight: 600; 
                                    {{ $req->status == 'approved' ? 'background: #dcfce7; color: #166534;' : 'background: #fef2f2; color: #dc2626;' }}">
                                        {{ ucfirst($req->status) }}
                                    </span>
                                @endif

                                @if ($req->remark)
                                    <div class="note-box {{ $req->status }}">
                                        <strong>{{ $req->status == 'rejected' ? 'Reason:' : 'Note:' }}</strong>
                                        {{ Str::limit($req->remark, 35) }}
                                    </div>
                                @endif
                            </td>
                            <!-- ✅ NEW TD: Voucher Info -->
                            <td style="padding: 16px; text-align: center;">
                                @if($req->status === 'approved')
                                    @if($req->voucher_code)
                                        @php $infoValue = addslashes($req->voucher_code); @endphp
                                        <button onclick="showVoucherPopup('code', '{{ $infoValue }}')" style="background: #fef3c7; border: 1px solid #fcd34d; color: #92400e; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 4px; transition: all 0.2s;" onmouseover="this.style.background='#fde68a'" onmouseout="this.style.background='#fef3c7'">
                                            🎟️ Code
                                        </button>
                                    @elseif($req->url)
                                        @php 
                                            $infoValue = addslashes(Str::startsWith($req->url, 'http') ? $req->url : asset($req->url)); 
                                        @endphp
                                        <button onclick="showVoucherPopup('url', '{{ $infoValue }}')" style="background: #dbeafe; border: 1px solid #93c5fd; color: #1e40af; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 4px; transition: all 0.2s;" onmouseover="this.style.background='#bfdbfe'" onmouseout="this.style.background='#dbeafe'">
                                            🔗 Link
                                        </button>
                                    @elseif($req->image)
                                        @php 
                                            $infoValue = addslashes(Str::startsWith($req->image, 'http') ? $req->image : asset($req->image)); 
                                        @endphp
                                        <button onclick="showVoucherPopup('image', '{{ $infoValue }}')" style="background: #dcfce7; border: 1px solid #86efac; color: #166534; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 4px; transition: all 0.2s;" onmouseover="this.style.background='#bbf7d0'" onmouseout="this.style.background='#dcfce7'">
                                            🖼️ Image
                                        </button>
                                    @else
                                        <span style="color: #cbd5e1; font-size: 18px;">-</span>
                                    @endif
                                @else
                                    <span style="color: #cbd5e1; font-size: 18px;">-</span>
                                @endif
                            </td>
                            <td style="padding: 16px; font-size: 13px; color: #64748b;">
                                {{ \Carbon\Carbon::parse($req->created_at)->format('d M Y') }}</td>
                            <td style="padding: 16px; text-align: right;">
                                <button class="icon-btn btn-view-redeem" data-id="{{ $req->id }}" title="View Details"
                                    style="background: #f1f5f9; border: none; width: 32px; height: 32px; border-radius: 6px; cursor: pointer; font-size: 16px;">👁</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px; color: #94a3b8;">No voucher
                                requests found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

       
        <div class="pagination">
            @include('common.pagination.superadmin_pagination', ['pageData' => $requests])
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            document.addEventListener('change', function(e) {
                if (!e.target.classList.contains('approve-status-change')) return;

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
                            if (!value || value.trim() === '') return 'Rejection note cannot be empty!';
                        },
                        showCancelButton: true,
                        confirmButtonText: 'Reject',
                        confirmButtonColor: '#dc3545',
                        cancelButtonColor: '#6c757d'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            updateVoucherStatus(id, status, result.value, selectElement);
                        } else {
                            selectElement.selectedIndex = 0;
                        }
                    });
                } else if (status === 'approved') {
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

                        <!-- 2. Dynamic Input Fields based on Selection -->
                        <div>
                            <label style="font-size: 13px; font-weight: 700; color: #334155; display: block; margin-bottom: 6px;">
                                2. Enter Voucher Details:
                            </label>
                            <!-- Option A: Voucher URL Input Group -->
                            <div id="url_input_group">
                                <input id="swal_url" class="swal2-input" style="margin: 0; width: 100%; box-sizing: border-box; font-size: 14px; padding: 10px 14px; border: 1.5px solid #cbd5e1; border-radius: 8px;" placeholder="Enter Voucher Link / URL (e.g. https://amazon.in/claim/...)">
                            </div>

                            <!-- Option B: Voucher Image Input Group -->
                            <div id="image_input_group" style="display: none;">
                                <div style="border: 2px dashed #cbd5e1; padding: 14px; border-radius: 8px; text-align: center; background: #fafafa;">
                                    <input type="file" id="swal_image" style="margin: 0; width: 100%; font-size: 13px;" accept="image/*">
                                    <div style="font-size: 11px; color: #64748b; margin-top: 6px;">Supported: JPG, PNG, WEBP (Uploaded securely to AWS S3)</div>
                                </div>
                            </div>

                            <!-- Option C: Voucher Code Input Group -->
                            <div id="code_input_group" style="display: none;">
                                <input id="swal_voucher_code" class="swal2-input" style="margin: 0; width: 100%; box-sizing: border-box; font-size: 14px; padding: 10px 14px; border: 1.5px solid #cbd5e1; border-radius: 8px;" placeholder="Enter Voucher / Coupon Code (e.g. AMZN-500-GIFTX)">
                            </div>
                        </div>

                        <!-- Admin Note -->
                        <div>
                            <label style="font-size: 13px; font-weight: 700; color: #334155; display: block; margin-bottom: 6px;">
                                3. Admin Remark / Note <span style="font-size: 11px; color: #94a3b8; font-weight: 500;">(Optional)</span>:
                            </label>
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
                                // 1. Check the proper radio
                                const targetRadio = document.querySelector(`input[name="delivery_type"][value="${type}"]`);
                                if (targetRadio) {
                                    targetRadio.checked = true;
                                }

                                // 2. Toggle visible input groups
                                document.getElementById('url_input_group').style.display = type === 'url' ? 'block' : 'none';
                                document.getElementById('image_input_group').style.display = type === 'image' ? 'block' : 'none';
                                document.getElementById('code_input_group').style.display = type === 'code' ? 'block' : 'none';

                                // 3. Update style on all card labels
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

                                // 4. Focus relevant input
                                if (type === 'url') document.getElementById('swal_url').focus();
                                if (type === 'code') document.getElementById('swal_voucher_code').focus();
                            }

                            // Attach click event to all cards
                            Object.keys(labels).forEach(type => {
                                if (labels[type]) {
                                    labels[type].addEventListener('click', (e) => {
                                        e.preventDefault();
                                        switchDeliveryType(type);
                                    });
                                }
                            });

                            // Auto focus default input
                            setTimeout(() => {
                                const swalUrl = document.getElementById('swal_url');
                                if (swalUrl) swalUrl.focus();
                            }, 100);
                        },
                        preConfirm: () => {
                            const selectedType = document.querySelector(
                                'input[name="delivery_type"]:checked')?.value || 'url';
                            const note = document.getElementById('swal_note').value.trim();

                            let code = '';
                            let url = '';
                            let image = null;

                            if (selectedType === 'code') {
                                code = document.getElementById('swal_voucher_code').value.trim();
                                if (!code) {
                                    Swal.showValidationMessage('Please enter the Voucher Code!');
                                    return false;
                                }
                            } else if (selectedType === 'url') {
                                url = document.getElementById('swal_url').value.trim();
                                if (!url) {
                                    Swal.showValidationMessage('Please enter the Voucher URL!');
                                    return false;
                                }
                            } else if (selectedType === 'image') {
                                image = document.getElementById('swal_image').files[0];
                                if (!image) {
                                    Swal.showValidationMessage('Please select a Voucher Image to upload!');
                                    return false;
                                }
                            }

                            return {
                                delivery_type: selectedType,
                                voucher_code: code,
                                url: url,
                                voucher_image: image,
                                note: note || 'Approved by admin'
                            };
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            const formData = new FormData();
                            formData.append('id', id);
                            formData.append('status', 'approved');
                            formData.append('note', result.value.note);
                            formData.append('_token', '{{ csrf_token() }}');

                            if (result.value.voucher_code) {
                                formData.append('voucher_code', result.value.voucher_code);
                            }
                            if (result.value.url) {
                                formData.append('url', result.value.url);
                            }
                            if (result.value.voucher_image) {
                                formData.append('voucher_image', result.value.voucher_image);
                            }

                            updateVoucherStatusWithForm(id, formData, selectElement);
                        } else {
                            selectElement.selectedIndex = 0;
                        }
                    });
                }
            });

            // ✅ NEW: Function to handle FormData (File Uploads)
            function updateVoucherStatusWithForm(id, formData, selectElement) {
                // Show loading state
                Swal.fire({
                    title: 'Processing...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading()
                    }
                });

                fetch("{{ route('superadmin.e_gift_voucher.requests.changeStatus') }}", {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            // 'Content-Type' is automatically set to multipart/form-data with boundary by browser when using FormData
                        },
                        body: formData
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: data.message,
                                timer: 1500,
                                showConfirmButton: false
                            });
                            setTimeout(() => location.reload(), 1500);
                        } else {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Warning',
                                text: data.message || 'Unable to update status.'
                            });
                            selectElement.selectedIndex = 0;
                        }
                    })
                    .catch((error) => {
                        console.error(error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Something went wrong. Please try again.'
                        });
                        selectElement.selectedIndex = 0;
                    });
            }

            // Legacy function for Reject/Cancel (still uses JSON)
            function updateVoucherStatus(id, status, note, selectElement, url = '') {
                fetch("{{ route('superadmin.e_gift_voucher.requests.changeStatus') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            id: id,
                            status: status,
                            note: note,
                            url: url
                        })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: data.message,
                                timer: 1500,
                                showConfirmButton: false
                            });
                            setTimeout(() => location.reload(), 1500);
                        } else {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Warning',
                                text: data.message || 'Unable to update status.'
                            });
                            selectElement.selectedIndex = 0;
                        }
                    })
                    .catch(() => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Something went wrong.'
                        });
                        selectElement.selectedIndex = 0;
                    });
            }

            // View Request Details Modal
            document.addEventListener('click', function(e) {
                const btn = e.target.closest('.btn-view-redeem');
                if (!btn) return;

                const id = btn.dataset.id;
                Swal.fire({
                    title: 'Loading...',
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                fetch(`/superadmin/e-gift-voucher/requests/${id}`)
                    .then(res => res.json())
                    .then(res => {
                        if (!res.success) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: res.message || 'Failed to load details'
                            });
                            return;
                        }
                        const d = res.data;
                        const html = `
                        <div style="text-align:left; font-size:13px; line-height:1.6;">
                            <div style="padding:10px 0; border-bottom:1px solid #e2e8f0;">
                                <strong>User:</strong> ${d.user.name} (${d.user.mobile})<br>
                                <strong>Wallet Balance (with Points):</strong> <span style="font-weight:700; color:#0f172a;">${Number(d.user.wallet_balance).toLocaleString(undefined, {minimumFractionDigits: 2})} pts</span>
                            </div>
                            <div style="padding:10px 0; border-bottom:1px solid #e2e8f0;">
                                <strong>Voucher:</strong> ${d.voucher_name}<br>
                                <strong>Points:</strong> <span style="font-weight:700; color:#0f172a;">${Number(d.total_amount).toLocaleString(undefined, {minimumFractionDigits: 2})} pts</span>
                                ${d.fee_percent > 0 ? `<br><small style="color:#64748b;">(Voucher: ₹${d.voucher_amount} + ${d.fee_percent}% fee: ${d.fee_amount} pts)</small>` : ''}
                            </div>
                            <div style="padding:10px 0;">
                                <strong>Status:</strong> <span style="text-transform:capitalize; font-weight:600;">${d.status}</span><br>
                                ${d.voucher_code ? `<strong>Voucher Code:</strong> <code style="background: #fef3c7; padding: 2px 6px; border-radius: 4px; font-family: monospace; font-weight: 600;">${d.voucher_code}</code><br>` : ''}
                                ${d.url ? `<strong>Code/URL:</strong> <code>${d.url}</code><br>` : ''}
                                ${d.image ? `<strong>Voucher Image:</strong> <a href="${d.image}" target="_blank" style="color:#4f46e5; text-decoration:underline; font-weight:600;">View Full Image ↗</a><div style="margin-top: 6px; margin-bottom: 6px;"><a href="${d.image}" target="_blank"><img src="${d.image}" alt="Voucher Image" style="max-width: 100%; max-height: 200px; border-radius: 8px; border: 1px solid #cbd5e1; object-fit: contain; background: #f8fafc; padding: 4px;"></a></div>` : ''}
                                ${d.remark ? `<strong>Note / Reason:</strong> ${d.remark}<br>` : ''}
                                ${d.approver && d.approver !== '—' ? `<strong>Approved By:</strong> ${d.approver} on ${d.approve_date}` : ''}
                            </div>
                        </div>
                    `;
                        Swal.fire({
                            title: 'Voucher Request Details',
                            html: html,
                            confirmButtonText: 'Close',
                            confirmButtonColor: '#6366f1'
                        });
                    })
                    .catch(() => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Failed to fetch details.'
                        });
                    });
            });
        </script>

        <script>
            // ✅ NEW: Function to show small popup for Voucher Info
            function showVoucherPopup(type, value) {
                let content = '';
                let title = '';
                
                if (type === 'code') {
                    title = 'Voucher Code';
                    content = `
                        <div style="text-align:center; padding: 20px 10px;">
                            <div style="font-size: 40px; margin-bottom: 15px;">🎟️</div>
                            <code style="display:block; background: #fef3c7; padding: 12px 20px; border-radius: 8px; font-size: 18px; font-weight: bold; letter-spacing: 1px; border: 1px solid #fcd34d; color: #92400e; word-break: break-all;">${value}</code>
                            <br>
                            <button onclick="navigator.clipboard.writeText('${value}'); Swal.fire({icon: 'success', title: 'Copied!', timer: 1000, showConfirmButton: false});" style="padding: 8px 16px; background: #6366f1; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 14px; margin-top: 10px;">Copy Code</button>
                        </div>`;
                } else if (type === 'url') {
                    title = 'Voucher URL';
                    content = `
                        <div style="text-align:center; padding: 20px 10px;">
                            <div style="font-size: 40px; margin-bottom: 15px;">🔗</div>
                            <a href="${value}" target="_blank" style="display:block; word-break: break-all; color: #4f46e5; font-weight: 600; text-decoration: underline; margin-bottom: 15px;">${value}</a>
                            <a href="${value}" target="_blank" style="display:inline-block; padding: 8px 16px; background: #16a34a; color: white; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 14px;">Open Link</a>
                        </div>`;
                } else if (type === 'image') {
                    title = 'Voucher Image';
                    content = `
                        <div style="text-align:center; padding: 10px;">
                            <img src="${value}" alt="Voucher" style="max-width: 100%; max-height: 300px; border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 15px; object-fit: contain; background: #f8fafc;">
                            <br>
                            <a href="${value}" target="_blank" style="display:inline-block; padding: 8px 16px; background: #6366f1; color: white; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 14px;">View Full Image</a>
                        </div>`;
                }

                Swal.fire({
                    title: title,
                    html: content,
                    confirmButtonText: 'Close',
                    confirmButtonColor: '#64748b',
                    width: 400
                });
            }
        </script>
    @endpush
@endsection
