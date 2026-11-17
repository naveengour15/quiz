@extends('superadmin.layouts.app')
@section('title', 'Vouchers')
@section('content')
    @push('styles')
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
                margin-bottom: 14px;
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
            
            /* Details Modal */
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
                .add-btn { width: 100%; padding: 11px; }
            }
        </style>
    @endpush
    
    <div class="panel active">
        <div class="page-head">
            <div>
                <h1>Vouchers</h1>
                <p>Manage vouchers and their coupons.</p>
            </div>
        </div>

        <div class="section-label">Voucher Catalog</div>
        <button class="add-btn" onclick="openModal()">+ Add Voucher</button>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                {{-- <th>#</th> --}}
                                <th>Voucher</th>
                                <th>Category</th>
                                <th>Points</th>
                                <th>no of Coupons</th>
                                <th>Assigned</th>
                                <th>used Coupons</th>
                                <th>expire Coupons</th>
                                <th>coolected poin</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($rewards as $index => $reward)
                                <tr>
                                    {{-- <td>{{ $index + 1 }}</td> --}}
                                    <td>{{ $reward->name }}</td>
                                    <td>{{ $reward->category }}</td>
                                    <td class="mono">{{ $reward->points ?? $reward->coin_cost ?? '—' }}</td>
                                    
                                    {{-- Newly Added Stats Columns --}}
                                    <td class="mono">{{ $reward->stats->total ?? 0 }}</td>
                                    <td class="mono">{{ $reward->stats->assigned ?? 0 }}</td>
                                    <td class="mono">{{ $reward->stats->used ?? 0 }}</td>
                                    <td class="mono">{{ $reward->stats->expired ?? 0 }}</td>
                                    <td class="mono">{{ $reward->stats->collected_points ?? 0 }}</td>
                                    
                                    <td>
                                        <select class="reward-status-change" data-id="{{ $reward->id }}">
                                            <option value="active" {{ $reward->status == 'active' ? 'selected' : '' }}>Active</option>
                                            <option value="inactive" {{ $reward->status == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                            <option value="out_of_stock" {{ $reward->status == 'out_of_stock' ? 'selected' : '' }}>Out Of Stock</option>
                                        </select>
                                    </td>
                                    <td class="row-actions">
                                        <button class="action-btn btn-view" onclick="viewReward({{ $reward->id }})">👁 </button>
                                        @if($reward->category == "Voucher")
                                            <a href="{{ route('superadmin.rewards.coupons', $reward) }}" class="action-btn btn-coupons">🎟 </a>
                                        @endif
                                        <button class="action-btn btn-edit" onclick='openModal(@json($reward))'>✎ </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    {{-- Colspan updated to 11 to match the new number of columns --}}
                                    <td colspan="10" style="text-align:center;color:var(--ink-faint);">No rewards yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                     <div class="pagination">
                        @include('common.pagination.superadmin_pagination', ['pageData' => $rewards])
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
            
            <form method="POST" id="rewardForm" action="{{ route('superadmin.rewards.store') }}" enctype="multipart/form-data">
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
                            {{-- <option value="">-- Select --</option>
                            <option value="Mobile Recharge">Mobile Recharge</option>
                            <option value="Bill Payment">Bill Payment</option> --}}
                            <option value="Voucher">Voucher</option>
                        </select>
                    </div>
                    <div class="field" id="partnerField">
                        <label>Brand / Partner</label>
                        <input type="text" name="partner" id="partner">
                    </div>
                    {{-- <div class="field" id="pointsField">
                        <label>Points <span class="hint">(optional for Bill Payment)</span></label>
                        <input type="number" name="points" id="points" min="0" required>
                    </div> --}}
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

    <!-- View Details Modal (Short & Simple) -->
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
        const UPDATE_ROUTE = '{{ route('superadmin.rewards.update', ['reward' => '__ID__']) }}';
        const DETAILS_ROUTE = '{{ route('superadmin.rewards.details', ['reward' => '__ID__']) }}';
        const S3_BASE = '{{ Storage::disk('s3')->url('') }}';

            function openModal(reward = null) {
                const modal = document.getElementById('rewardModal');
                const form = document.getElementById('rewardForm');
                const nameInput = document.getElementById('name');
                const categorySelect = document.getElementById('category');
                
                document.getElementById('photo').value = '';
                document.getElementById('photoPreview').classList.remove('show');
                
                // Reset states (Add mode ke liye unlock karna)
                nameInput.readOnly = false;
                nameInput.style.backgroundColor = ''; 
                nameInput.style.cursor = '';
                categorySelect.disabled = false;
                categorySelect.style.backgroundColor = '';
                categorySelect.style.cursor = '';
                
                // Agar pichli baar ka hidden category input bacha hai toh use hata do
                const existingHidden = document.getElementById('hiddenCategory');
                if (existingHidden) existingHidden.remove();

                if (reward) {
                    // EDIT MODE
                    document.getElementById('modalTitle').textContent = 'Edit Voucher';
                    document.getElementById('formMethod').value = 'PUT';
                    form.action = UPDATE_ROUTE.replace('__ID__', reward.id);
                    
                    nameInput.value = reward.name || '';
                    categorySelect.value = reward.category || '';
                    document.getElementById('partner').value = reward.partner || '';
                    // document.getElementById('points').value = reward.points || reward.coin_cost || '';
                    document.getElementById('expire_duration').value = reward.expire_duration || '';
                  
                    document.getElementById('description').value = reward.description || '';
                    
                    // 🔒 1. NAME KO READONLY KARNA (Edit nahi kar sakte par value submit hogi)
                    nameInput.readOnly = true;
                    nameInput.style.backgroundColor = '#f3f4f6'; // Grey background for visual hint
                    nameInput.style.cursor = 'not-allowed';
                    
                    // 🔒 2. CATEGORY KO DISABLE KARNA
                    categorySelect.disabled = true;
                    categorySelect.style.backgroundColor = '#f3f4f6';
                    categorySelect.style.cursor = 'not-allowed';
                    
                    // 🔒 3. HIDDEN INPUT ADD KARNA (Taaki Laravel validation fail na ho)
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
                    // ADD MODE
                    document.getElementById('modalTitle').textContent = 'Add Voucher';
                    document.getElementById('formMethod').value = 'POST';
                    form.action = '{{ route('superadmin.rewards.store') }}';
                    form.reset();
                    
                    // handleCategoryChange(''); 
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
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) renderDetails(data.reward);
                else content.innerHTML = '<p style="color:#dc2626;">Failed to load.</p>';
            })
            .catch(() => content.innerHTML = '<p style="color:#dc2626;">Error loading.</p>');
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
        
        // function handleCategoryChange(cat) {
        //     const f = document.getElementById('pointsField');
        //     if (cat !== 'Voucher') f.classList.remove('field-hidden');
        //     else { f.classList.add('field-hidden'); document.getElementById('points').value = ''; }
        // }

        function handleCategoryChange(cat) {
            // Ye sabhi fields sirf Voucher ke liye hain
            const voucherOnlyFields = [
                'partnerField', 
                  // Points ko bhi ab hide list me daal diya hai
                'couponField', 
                'expireField', 
                
                'photoField'
            ];
            
            if (cat === 'Voucher') {
                // Agar Voucher select hai, toh sabhi fields show karo
                voucherOnlyFields.forEach(id => {
                    const el = document.getElementById(id);
                    if(el) el.classList.remove('field-hidden');
                });
            } else {
                // Agar Mobile Recharge ya Bill Payment hai, toh in sabko hide kar do
                voucherOnlyFields.forEach(id => {
                    const el = document.getElementById(id);
                    if(el) el.classList.add('field-hidden');
                });
                
                // Hidden fields ke values clear kar do taaki form submit me validation error na aaye
                document.getElementById('partner').value = '';
                // document.getElementById('points').value = '';
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
            fetch("{{ route('superadmin.rewards.changeStatus') }}", {
                method: "POST",
                headers: { "Content-Type": "application/json", "Accept": "application/json", "X-CSRF-TOKEN": "{{ csrf_token() }}" },
                body: JSON.stringify({ id: e.target.dataset.id, status: e.target.value })
            }).then(r => r.json()).then(d => showAlert(d.success ? 'success' : 'error', d.success ? 'Success' : 'Error', d.message || 'Something went wrong.'));
        });
    </script>
@endpush