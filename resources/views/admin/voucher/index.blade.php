@extends('admin.layouts.app')
@section('title', 'Vouchers')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/custom.css') }}">
    <style>
        .reward-status-change {
            min-width: 140px;
            padding: 6px 10px;
            border: 1px solid #dcdfe6;
            border-radius: 6px;
            background: #fff;
            font-size: 13px;
            cursor: pointer;
        }
        .reward-status-change:focus { outline: none; border-color: #4F46E5; }
        
        .modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            z-index: 9999;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }
        .modal.active { display: flex; }
        .modal-box {
            background: #fff;
            border-radius: 12px;
            max-width: 600px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            padding: 24px;
        }
        .modal-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 1px solid #e5e7eb;
        }
        .modal-head h3 { margin: 0; font-size: 18px; }
        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #666;
        }
        .add-btn {
            padding: 9px 18px;
            background: #4F46E5;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            font-size: 13px;
        }
        .add-btn:hover { background: #4338CA; }
        .photo-preview {
            margin-top: 8px;
            max-width: 120px;
            border-radius: 8px;
            overflow: hidden;
            display: none;
        }
        .photo-preview.show { display: block; }
        .photo-preview img { width: 100%; }
        .field-hidden { display: none !important; }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #f3f4f6;
            font-size: 14px;
        }
        .detail-row:last-child { border-bottom: none; }
        .detail-label { color: #6b7280; font-weight: 500; }
        .detail-value { color: #111827; font-weight: 600; text-align: right; }
        .detail-photo {
            width: 100%;
            max-width: 200px;
            border-radius: 8px;
            margin-bottom: 12px;
        }
        .detail-desc {
            background: #f9fafb;
            padding: 10px;
            border-radius: 6px;
            color: #374151;
            font-size: 13px;
            line-height: 1.5;
            margin-top: 8px;
        }
        
        .action-btn {
            padding: 4px 10px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 500;
            margin-right: 4px;
        }
        .btn-view { background: #3b82f6; color: #fff; }
        .btn-coupons { background: #10b981; color: #fff; }
        .btn-edit { background: #f59e0b; color: #fff; }
        .action-btn:hover { opacity: 0.9; }
        
        @media (max-width: 768px) {
            .modal { align-items: flex-end; padding: 0; }
            .modal-box {
                max-height: 95vh;
                padding: 16px;
                border-radius: 16px 16px 0 0;
            }
            .form-grid { grid-template-columns: 1fr !important; gap: 12px; }
            .form-grid .field.full { grid-column: 1; }
            .form-foot { flex-direction: column; gap: 10px; }
            .form-foot .btn { width: 100%; }
            .add-btn { padding: 11px; }
        }
    </style>
@endpush

@section('content')
    <div class="panel active">
        <div class="page-head">
            <div>
                <h1>Vouchers</h1>
                <p>Manage vouchers and their coupons for your tenant.</p>
            </div>
            <div class="voucher-add-btn">
                <button class="add-btn" onclick="openModal()">+ Add Voucher</button>
            </div>
        </div>

        {{-- <div class="section-label">Voucher Catalog</div> --}}
        <div class="table-wrap table-wrap-res-01" style="overflow: hidden;">
            <div class="voucher-table-box">
                <table class="voucher-table-wrapper">
                    <thead>
                        <tr>
                            <th>Voucher</th>
                            <th>Category</th>
                            <th>Points</th>
                            <th>No of Coupons</th>
                            <th>Assigned</th>
                            <th>Used Coupons</th>
                            <th>Expire Coupons</th>
                            <th>Collected Points</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($vouchers as $index => $voucher)
                            <tr>
                                <td data-label="Voucher">{{ $voucher->name }}</td>
                                <td data-label="Category">{{ $voucher->category }}</td>
                                <td data-label="Points" class="mono">{{ $voucher->points ?? $voucher->coin_cost ?? '—' }}</td>
                                
                                <td data-label="No. of Coupons" class="mono">{{ $voucher->stats->total ?? 0 }}</td>
                                <td data-label="Assigned" class="mono">{{ $voucher->stats->assigned ?? 0 }}</td>
                                <td data-label="Used Coupons" class="mono">{{ $voucher->stats->used ?? 0 }}</td>
                                <td data-label="Expire Coupons" class="mono">{{ $voucher->stats->expired ?? 0 }}</td>
                                <td data-label="Collected Points" class="mono">{{ $voucher->stats->collected_points ?? 0 }}</td>
                                
                                <td data-label="Status">
                                    <div>
                                        <select class="reward-status-change" data-id="{{ $voucher->id }}">
                                            <option value="active" {{ $voucher->status == 'active' ? 'selected' : '' }}>Active</option>
                                            <option value="inactive" {{ $voucher->status == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                            <option value="out_of_stock" {{ $voucher->status == 'out_of_stock' ? 'selected' : '' }}>Out Of Stock</option>
                                        </select>
                                    </div>
                                </td>
                                <td data-label="Actions" class="row-actions">
                                    <div style="display: flex; gap:10px; align-items:center;">
                                        <button class="action-btn btn-view" onclick="viewReward({{ $voucher->id }})">👁 </button>
                                        @if($voucher->category == "Voucher")
                                            <a href="{{ route('admin.voucher.coupons', $voucher) }}" class="action-btn btn-coupons">🎟 </a>
                                        @endif
                                        <button class="action-btn btn-edit" onclick='openModal(@json($voucher))'>✎ </button>
                                        <button class="action-btn" style="background:#ef4444; color:#fff;" onclick="deleteVoucher({{ $voucher->id }})" title="Delete">🗑 </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" style="text-align:center;color:var(--ink-faint);">No vouchers yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="pagination">
                @include('common.pagination.superadmin_pagination', ['pageData' => $vouchers])
            </div> 
        </div>
    </div>

    <!-- Add/Edit Modal -->
    <div class="modal" id="rewardModal">
        <div class="modal-box">
            <div class="modal-head">
                <h3 id="modalTitle">Add Voucher</h3>
                <button class="modal-close" onclick="closeModal('rewardModal')">&times;</button>
            </div>
            
            <form method="POST" id="rewardForm" action="{{ route('admin.voucher.store') }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="_method" id="formMethod" value="POST">
                
                <div class="form-grid">
                    <div class="field" id="nameField">
                        <label>Voucher Name <span class="req">*</span></label>
                        <input type="text" name="name" id="name" required>
                    </div>
                    <div class="field" id="categoryField">
                        <label>Category <span class="req">*</span></label>
                        <select name="category" id="category" required>
                            <option value="Voucher">Voucher</option>
                        </select>
                    </div>
                    <div class="field" id="partnerField">
                        <label>Brand / Partner</label>
                        <input type="text" name="partner" id="partner">
                    </div>
                    <div class="field" id="couponField">
                        <label>Number of Coupons <span class="req">*</span></label>
                        <input type="number" name="no_of_coupon" id="no_of_coupon" min="0" value="1">
                    </div>
                    <div class="field" id="expireField">
                        <label>Expire Duration (days)</label>
                        <input type="number" name="expire_duration" id="expire_duration" value="1" min="0">
                    </div>
                    <div class="field" id="statusField" hidden>
                        <input type="hidden" name="status" value="active">
                    </div>
                    <div class="field full" id="descriptionField">
                        <label>Description</label>
                        <textarea name="description" id="description" rows="2"></textarea>
                    </div>
                    <div class="field full" id="photoField">
                        <label>Photo (optional)</label>
                        <input type="file" name="photo" id="photo" accept="image/*" onchange="previewPhoto(this)">
                        <div class="photo-preview" id="photoPreview">
                            <img id="previewImg" src="" alt="">
                        </div>
                    </div>
                </div>
                <div class="form-foot">
                    <button type="button" class="btn btn-ghost" onclick="closeModal('rewardModal')">Cancel</button>
                    <button class="btn btn-primary" type="submit">Save Voucher</button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Details Modal -->
    <div class="modal" id="detailsModal">
        <div class="modal-box">
            <div class="modal-head">
                <h3>Voucher Details</h3>
                <button class="modal-close" onclick="closeModal('detailsModal')">&times;</button>
            </div>
            <div id="detailsContent">
                <p style="text-align:center;color:#6b7280;">Loading...</p>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const UPDATE_ROUTE = '{{ route('admin.voucher.update', ['voucher' => '__ID__']) }}';
        const DETAILS_ROUTE = '{{ route('admin.voucher.details', ['voucher' => '__ID__']) }}';
        const S3_BASE = '{{ Storage::disk('s3')->url('') }}';

        function openModal(reward = null) {
            const modal = document.getElementById('rewardModal');
            const form = document.getElementById('rewardForm');
            const nameInput = document.getElementById('name');
            const categorySelect = document.getElementById('category');
            
            document.getElementById('photo').value = '';
            document.getElementById('photoPreview').classList.remove('show');
            
            nameInput.readOnly = false;
            nameInput.style.backgroundColor = ''; 
            nameInput.style.cursor = '';
            categorySelect.disabled = false;
            categorySelect.style.backgroundColor = '';
            categorySelect.style.cursor = '';
            
            const existingHidden = document.getElementById('hiddenCategory');
            if (existingHidden) existingHidden.remove();

            if (reward) {
                document.getElementById('modalTitle').textContent = 'Edit Voucher';
                document.getElementById('formMethod').value = 'PUT';
                form.action = UPDATE_ROUTE.replace('__ID__', reward.id);
                
                nameInput.value = reward.name || '';
                categorySelect.value = reward.category || '';
                document.getElementById('partner').value = reward.partner || '';
                document.getElementById('expire_duration').value = reward.expire_duration || '';
                document.getElementById('description').value = reward.description || '';
                
                nameInput.readOnly = true;
                nameInput.style.backgroundColor = '#f3f4f6';
                nameInput.style.cursor = 'not-allowed';
                
                categorySelect.disabled = true;
                categorySelect.style.backgroundColor = '#f3f4f6';
                categorySelect.style.cursor = 'not-allowed';
                
                let hiddenCat = document.createElement('input');
                hiddenCat.type = 'hidden';
                hiddenCat.name = 'category';
                hiddenCat.value = reward.category;
                hiddenCat.id = 'hiddenCategory';
                form.appendChild(hiddenCat);
                
                if (reward.photo) {
                    document.getElementById('previewImg').src = S3_BASE + reward.photo;
                    document.getElementById('photoPreview').classList.add('show');
                }

                handleCategoryChange(reward.category);
                document.getElementById('couponField').classList.add('field-hidden');
                document.getElementById('expireField').classList.add('field-hidden');
            } else {
                document.getElementById('modalTitle').textContent = 'Add Voucher';
                document.getElementById('formMethod').value = 'POST';
                form.action = '{{ route('admin.voucher.store') }}';
                form.reset();
                handleCategoryChange(document.getElementById('category').value); 
            }
            
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function viewReward(id) {
            const modal = document.getElementById('detailsModal');
            const content = document.getElementById('detailsContent');
            content.innerHTML = '<p style="text-align:center;color:#6b7280;">Loading...</p>';
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
            
            fetch(DETAILS_ROUTE.replace('__ID__', id), {
                headers: { 'Accept': 'application/json' }
            })
            .then(r => {
                if (!r.ok) throw new Error(`HTTP Error: ${r.status}`);
                return r.json();
            })
            .then(data => {
                if (data.success) {
                    renderDetails(data.voucher);
                } else {
                    content.innerHTML = '<p style="color:#dc2626;">Failed to load data.</p>';
                } 
            })
            .catch(err => {
                console.error("Voucher Details Error:", err);
                content.innerHTML = `<p style="color:#dc2626;">Error loading details. (${err.message})</p>`;
            });
        }
        
        function renderDetails(r) {
            let html = '';
            if (r.photo) html += `<img src="${S3_BASE}${r.photo}" class="detail-photo">`;
            
            html += `
                <div class="detail-row"><span class="detail-label">Name</span><span class="detail-value">${r.name || '—'}</span></div>
                <div class="detail-row"><span class="detail-label">Category</span><span class="detail-value">${r.category || '—'}</span></div>
                <div class="detail-row"><span class="detail-label">Brand/Partner</span><span class="detail-value">${r.partner || '—'}</span></div>
                <div class="detail-row"><span class="detail-label">Points</span><span class="detail-value mono">${r.points ?? r.coin_cost ?? '—'}</span></div>
                <div class="detail-row"><span class="detail-label">Total Coupons</span><span class="detail-value mono">${r.no_of_coupon ?? 0}</span></div>
                <div class="detail-row"><span class="detail-label">Status</span><span class="detail-value">${r.status || '—'}</span></div>
                <div class="detail-row"><span class="detail-label">Expire Duration</span><span class="detail-value">${r.expire_duration ? r.expire_duration + ' days' : 'No expiry'}</span></div>
            `;
            
            if (r.description) {
                html += `<div class="detail-row"><span class="detail-label">Description</span></div>
                         <div class="detail-desc">${r.description}</div>`;
            }
            
            document.getElementById('detailsContent').innerHTML = html;
        }
        
        function closeModal(id) {
            document.getElementById(id).classList.remove('active');
            document.body.style.overflow = 'auto';
        }
        
        function previewPhoto(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = e => {
                    document.getElementById('previewImg').src = e.target.result;
                    document.getElementById('photoPreview').classList.add('show');
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        function handleCategoryChange(cat) {
            const voucherOnlyFields = ['partnerField', 'couponField', 'expireField', 'photoField'];
            if (cat === 'Voucher') {
                voucherOnlyFields.forEach(id => {
                    const el = document.getElementById(id);
                    if(el) el.classList.remove('field-hidden');
                });
            } else {
                voucherOnlyFields.forEach(id => {
                    const el = document.getElementById(id);
                    if(el) el.classList.add('field-hidden');
                });
                
                document.getElementById('partner').value = '';
                document.getElementById('no_of_coupon').value = '0';
                document.getElementById('expire_duration').value = '';
                document.getElementById('photo').value = '';
                document.getElementById('photoPreview').classList.remove('show');
            }
        }
        
        document.getElementById('category').addEventListener('change', e => handleCategoryChange(e.target.value));
        
        document.querySelectorAll('.modal').forEach(m => {
            m.addEventListener('click', e => {
                if (e.target === m) { m.classList.remove('active'); document.body.style.overflow = 'auto'; }
            });
        });
        
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal.active').forEach(m => m.classList.remove('active'));
                document.body.style.overflow = 'auto';
            }
        });
        
        document.addEventListener('change', function(e) {
            if (!e.target.classList.contains('reward-status-change')) return;
            fetch("{{ route('admin.voucher.changeStatus') }}", {
                method: "POST",
                headers: { "Content-Type": "application/json", "Accept": "application/json", "X-CSRF-TOKEN": "{{ csrf_token() }}" },
                body: JSON.stringify({ id: e.target.dataset.id, status: e.target.value })
            }).then(r => r.json()).then(d => showAlert(d.success ? 'success' : 'error', d.success ? 'Success' : 'Error', d.message || 'Something went wrong.'));
        });

        function deleteVoucher(id) {
            if (!confirm('Are you sure you want to delete this voucher?')) {
                return;
            }
            
            const DELETE_ROUTE = "{{ route('admin.voucher.destroy', '__ID__') }}";
            
            fetch(DELETE_ROUTE.replace('__ID__', id), {
                method: "DELETE",
                headers: { 
                    "Content-Type": "application/json", 
                    "Accept": "application/json", 
                    "X-CSRF-TOKEN": "{{ csrf_token() }}" 
                }
            })
            .then(r => r.json())
            .then(d => {
                if (d.success) {
                    if(typeof showAlert === 'function') {
                        showAlert('success', 'Success', d.message);
                    } else {
                        alert(d.message);
                    }
                    setTimeout(() => location.reload(), 1200);
                } else {
                    showAlert('error', 'Error', d.message || 'Something went wrong.');
                }
            })
            .catch(() => {
                if(typeof showAlert === 'function') showAlert('error', 'Error', 'Failed to delete.');
                else alert('Failed to delete.');
            });
        }
    </script>
@endpush
