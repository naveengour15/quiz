@extends('superadmin.layouts.app')
@section('title', 'Vouchers')
<link rel="stylesheet" href="{{ asset('assets/css/custom.css') }}">
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
                /* margin-bottom: 14px; */
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
                font-size: 12px;
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
                .add-btn {padding: 11px; }
            }

            @keyframes slideDown {
                    from {
                        opacity: 0;
                        transform: translateY(-10px);
                    }
                    to {
                        opacity: 1;
                        transform: translateY(0);
                    }
                }

                .status-toggle {
                position: relative;
                display: inline-block;
                width: 46px;
                height: 24px;
                cursor: pointer;
            }

            .status-toggle input {
                opacity: 0;
                width: 0;
                height: 0;
            }

            .status-slider {
                position: absolute;
                inset: 0;
                background-color: #d1d5db;
                border-radius: 30px;
                transition: 0.25s;
            }

            .status-slider::before {
                content: "";
                position: absolute;
                width: 18px;
                height: 18px;
                left: 3px;
                top: 3px;
                background: #fff;
                border-radius: 50%;
                transition: 0.25s;
                box-shadow: 0 1px 3px rgba(0,0,0,0.25);
            }

            .status-toggle input:checked + .status-slider {
                background-color: #22c55e;
            }

            .status-toggle input:checked + .status-slider::before {
                transform: translateX(22px);
            }

            .status-toggle input:disabled + .status-slider {
                opacity: 0.6;
                cursor: not-allowed;
            }
            
            @media(max-width:575px) {
                .modal-head h3 {
                    font-size: 14px;
                }
            }

        </style>
    @endpush
    
    <div class="panel active">
        <div class="page-head">
            <div>
                <h1>Vouchers</h1>
                <p>Manage vouchers and their coupons.</p>
            </div>
            <div class="voucher-add-btn">
                  <button class="add-btn" onclick="openModal()">+ Add Voucher</button>
            </div>
        </div>

        <div class="search-filter-box">
            <form method="GET" class="toolbar">
                <div class="users-search-filter-box">
                    <div class="users-search-filter-wrapper">
                        <div class="input-field">
                            <label class="lbl search-field" for="search">Search</label>
                            <input class="tb-input" name="search" value="{{ request('search') }}" placeholder="Search name" onchange="this.form.submit()">
                        </div>
                        
                        <div class="input-field">
                            <label class="lbl" for="search">Status</label>
                            <select class="tb-select status-field" name="status" onchange="this.form.submit()">
                                <option value="">All Statuses</option>
                                <option value="active" @selected(request('status') === 'active')>Active</option>
                                <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                            </select>
                        </div>

                        
                    </div>
                    <div class="users-filter-btn">
                        <button class="btn btn-primary" type="submit">Filter</button>
                        <a href="{{ request()->url() }}" class="btn btn-ghost">Reset</a>
                    </div>
                </div>
            </form>
        </div>


        {{-- <div class="section-label">Voucher Catalog</div> --}}
        <div class="table-wrap table-wrap-res-01" style="overflow: hidden;">
            <div class="voucher-table-box">
                <table class="voucher-table-wrapper">
                    <thead>
                        <tr>
                            {{-- <th>#</th> --}}
                            <th>Voucher</th>
                            <th>Partner</th>
                            <th>Category</th>
                            <th>Points</th>
                            <th>no of Coupons</th>
                            <th>Assigned</th>
                            <th>used Coupons</th>
                            <th>expire Coupons</th>
                            <th>coolected points</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($vouchers as $index => $voucher)
                            <tr>
                                {{-- <td>{{ $index + 1 }}</td> --}}
                                <td data-label="Voucher">{{ $voucher->name }}</td>
                                <td data-label="Voucher">{{ $voucher->partner ?? '-'}}</td>
                                <td data-label="Category">{{ $voucher->category }}</td>
                                <td data-label="Points"class="mono">{{ $voucher->points ?? $voucher->coin_cost ?? '—' }}</td>
                                
                                {{-- Newly Added Stats Columns --}}
                                <td data-label="No. of Coupons"class="mono">{{ $voucher->stats->total ?? 0 }}</td>
                                <td data-label="Assigned"class="mono">{{ $voucher->stats->assigned ?? 0 }}</td>
                                <td data-label="Used Coupons"class="mono">{{ $voucher->stats->used ?? 0 }}</td>
                                <td data-label="Expire Coupons"class="mono">{{ $voucher->stats->expired ?? 0 }}</td>
                                <td data-label="Collected Points"class="mono">{{ $voucher->stats->collected_points ?? 0 }}</td>
                                
                                <td data-label="Status">
                                    <div>
                                      <label class="status-toggle">
                                            <input   type="checkbox" class="voucher-status-toggle"data-id="{{ $voucher->id }}" {{ $voucher->status === 'active' ? 'checked' : '' }}>
                                            <span class="status-slider"></span>
                                           
                                        </label>
                                         <span class="status-text"> {{ ucfirst($voucher->status) }} </span>
                                    </div>
                                </td>
                                <td data-label="Actions" class="row-actions">
                                    <div style="display: flex; gap:10px; align-items:center;">
                                        <button class="icon-btn" onclick="viewReward({{ $voucher->id }})">👁 </button>
                                        @if($voucher->category == "Voucher")
                                            <a href="{{ route('superadmin.voucher.coupons', $voucher) }}" class="icon-btn">🎟 </a>
                                        @endif
                                        <button class="icon-btn" onclick='openModal(@json($voucher))'>✎ </button>
                                        {{-- 🔴 Delete Button (Red Color) --}}
                                            <button class="icon-btn danger" onclick="deleteVoucher({{ $voucher->id }})" title="Delete">🗑 </button>
                                    </div>
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
            </div>
            <div class="pagination">
                @include('common.pagination.superadmin_pagination', ['pageData' => $vouchers])
            </div> 
        </div>
    </div>

    <!-- Add/Edit Modal -->
   <!-- Add/Edit Modal -->
    <div class="modal" id="rewardModal">
        <div class="modal-box">
            <div class="modal-head">
                <h3 id="modalTitle">Add Voucher</h3>
                <button class="modal-close" onclick="closeModal('rewardModal')">&times;</button>
            </div>
            
            <form method="POST" id="rewardForm" action="{{ route('superadmin.voucher.store') }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="_method" id="formMethod" value="POST">
                
                <div class="form-grid">
                    <!-- Voucher Name Field -->
                    <div class="field" id="nameField">
                        <label>Voucher Name <span class="req">*</span></label>
                        <input type="text" name="name" id="name" required>
                    </div>
                    
                    <!-- Category Field -->
                    <div class="field" id="categoryField">
                        <label>Category <span class="req">*</span></label>
                        <select name="category" id="category" required>
                            <option value="Voucher">Voucher</option>
                        </select>
                    </div>

                    <!-- Partner/Brand Type Field -->
                    <div class="field full" id="partnerField">
                        <label>Brand / Partner Type <span class="req">*</span></label>
                        <select name="partner" id="partner" required onchange="handlePartnerChange(this.value)">
                            <option value="self">Self-Voucher (Auto Generate)</option>
                            <option value="amazon">Amazon</option>
                            <option value="flipkart">Flipkart</option>
                            {{-- <option value="other">Other Brand</option> --}}
                        </select>
                    </div>

                    <!-- Auto-Generate Options (for self vouchers) -->
                    <div id="autoGenerateOptions">
                        <div class="field" id="couponField">
                            <label>Number of Coupons <span class="req">*</span></label>
                            <input type="number" name="no_of_coupon" id="no_of_coupon" min="1" value="1">
                        </div>
                    </div>

                    <!-- Manual Codes Options (for external brands) -->
                    <div id="manualCodesOptions" style="display: none;" class="field full">
                        <label>Manual Coupon Codes <span class="req">*</span></label>
                        <div id="manualCodesContainer" style="display: flex; flex-direction: column; gap: 8px; margin-top:6px; margin-bottom: 10px;">
                            <!-- Dynamic code inputs will be injected here -->
                        </div>
                        <button type="button" class="btn btn-sm btn-secondary" onclick="addManualCodeInput()">
                            + Add More Code
                        </button>
                    </div>

                    <!-- Expire Duration Field -->
                    <div class="field" id="expireField">
                        <label>Expire Duration (days)</label>
                        <input type="number" name="expire_duration" id="expire_duration" value="1" min="0">
                    </div>
                    
                    <!-- Status Hidden Field -->
                    <div class="field" id="statusField" hidden>
                        <input type="hidden" name="status" value="active">
                    </div>
                    
                    <!-- Description Field -->
                    <div class="field full" id="descriptionField">
                        <label>Description</label>
                        <textarea name="description" id="description" rows="2"></textarea>
                    </div>
                    
                    <!-- Photo Upload Field -->
                    <div class="field full" id="photoField">
                        <label>Photo (optional)</label>
                        <input type="file" name="photo" id="photo" accept="image/*" onchange="previewPhoto(this)">
                        <div class="photo-preview" id="photoPreview">
                            <img id="previewImg" src="" alt="">
                        </div>
                    </div>
                </div>
                
                <!-- Form Footer Buttons -->
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
        // ============================================
        // 1. CONFIGURATION & CONSTANTS
        // ============================================
        const UPDATE_ROUTE = '{{ route('superadmin.voucher.update', ['voucher' => '__ID__']) }}';
        const DETAILS_ROUTE = '{{ route('superadmin.voucher.details', ['voucher' => '__ID__']) }}';
        const CODES_ROUTE = '{{ route('superadmin.voucher.codes', ['voucher' => '__ID__']) }}';
        const S3_BASE = '{{ Storage::disk('s3')->url('') }}';


        // ============================================
        // 2. PARTNER/BRAND TYPE HANDLER
        // ============================================
     
        function handlePartnerChange(partner) {
            const autoOptions = document.getElementById('autoGenerateOptions');
            const manualOptions = document.getElementById('manualCodesOptions');
            const noOfCouponInput = document.getElementById('no_of_coupon');
            const container = document.getElementById('manualCodesContainer');

            // Check if it's a self-voucher
            const isSelf = !partner || partner === 'self' || partner.toLowerCase() === 'self-voucher';

            if (isSelf) {
                // Show auto-generate options, hide manual codes
                autoOptions.style.display = 'block';
                manualOptions.style.display = 'none';
                noOfCouponInput.required = true;

                // FIX: Remove required attribute from hidden manual code inputs
                container.querySelectorAll('.manual-code-input').forEach(input => {
                    input.required = false;
                    input.removeAttribute('required');
                });

            } else {
                // ⭐ FIX: Only show manual codes if section is not hidden by edit mode
                const isHiddenByEditMode = manualOptions.classList.contains('field-hidden');
                
                if (isHiddenByEditMode) {
                    // Edit mode: Keep manual codes hidden, remove required
                    autoOptions.style.display = 'none';
                    manualOptions.style.display = 'none';
                    noOfCouponInput.required = false;
                    noOfCouponInput.value = '';
                    
                    // Remove required from all manual code inputs
                    container.querySelectorAll('.manual-code-input').forEach(input => {
                        input.required = false;
                        input.removeAttribute('required');
                    });
                } else {
                    // Add mode: Show manual codes section
                    autoOptions.style.display = 'none';
                    manualOptions.style.display = 'block';
                    noOfCouponInput.required = false;
                    noOfCouponInput.value = '';

                    // Add default empty input if container is empty
                    if (container.children.length === 0) {
                        addManualCodeInput();
                    }

                    // Set required attribute on visible manual code inputs
                    container.querySelectorAll('.manual-code-input').forEach(input => {
                        if (!input.readOnly) {
                            input.required = true;
                            input.setAttribute('required', 'required');
                        }
                    });
                }
            }
        }


        // ============================================
        // 3. MANUAL CODE INPUT MANAGEMENT
        // ============================================
     
        function addManualCodeInput(code = '', isExisting = false) {
            const container = document.getElementById('manualCodesContainer');
            const wrapper = document.createElement('div');
            wrapper.style.cssText = 'display: flex; gap: 8px; align-items: center; margin-bottom: 8px;';

            // Create input field
            const input = document.createElement('input');
            input.type = 'text';
            input.name = isExisting ? 'existing_codes[]' : 'manual_codes[]';
            input.value = code;
            input.placeholder = 'Enter unique coupon code';
            input.className = 'form-control manual-code-input';
            input.style.flex = '1';

            // FIX: Only set required if it's a NEW code (not existing)
            if (!isExisting) {
                input.required = true;
            }

            // Add real-time duplicate check on input
            input.addEventListener('input', function() {
                checkRealTimeDuplicates(this);
            });

            // Make existing codes read-only
            if (isExisting) {
                input.readOnly = true;
                input.style.backgroundColor = '#f3f4f6';
                input.name = 'existing_codes[]';
            }

            // Create remove button
            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.innerHTML = '&times;';
            removeBtn.className = 'btn btn-sm btn-danger';
            removeBtn.style.cssText = 'padding: 4px 10px; min-width: 32px;';
            removeBtn.onclick = function() {
                // If removing an existing code, mark it for deletion
                if (isExisting && code) {
                    const deleteInput = document.createElement('input');
                    deleteInput.type = 'hidden';
                    deleteInput.name = 'delete_codes[]';
                    deleteInput.value = code;
                    document.getElementById('rewardForm').appendChild(deleteInput);
                }
                wrapper.remove();

                // Check for duplicates after removal
                validateAllCodes();
            };

            wrapper.appendChild(input);
            wrapper.appendChild(removeBtn);
            container.appendChild(wrapper);

            // Validate after adding
            if (!isExisting) {
                validateAllCodes();
            }
        }


        // ============================================
        // 4. DUPLICATE CODE VALIDATION
        // ============================================
       
        function checkRealTimeDuplicates(currentInput) {
            const currentValue = currentInput.value.trim();

            // Remove previous error state
            currentInput.style.borderColor = '';
            currentInput.title = '';

            if (!currentValue) return;

            // Get all manual code inputs
            const allInputs = document.querySelectorAll('.manual-code-input');
            let duplicateCount = 0;

            allInputs.forEach(input => {
                if (input !== currentInput && input.value.trim() === currentValue) {
                    duplicateCount++;
                }
            });

            // Highlight if duplicate found
            if (duplicateCount > 0) {
                currentInput.style.borderColor = '#dc2626';
                currentInput.title = 'Duplicate code detected!';
            }
        }

        /**
         * Validate all codes and return array of duplicates
         * Also highlights duplicate inputs visually
         */
        function validateAllCodes() {
            const allInputs = document.querySelectorAll('.manual-code-input');
            const codes = [];
            const duplicates = [];

            // Reset all borders
            allInputs.forEach(input => {
                input.style.borderColor = '';
                input.title = '';
            });

            // Collect all codes and find duplicates
            allInputs.forEach(input => {
                const value = input.value.trim();
                if (value) {
                    if (codes.includes(value)) {
                        duplicates.push(value);
                        input.style.borderColor = '#dc2626';
                        input.title = 'Duplicate code!';
                    } else {
                        codes.push(value);
                    }
                }
            });

            return duplicates;
        }

        /**
         * Check for duplicates before form submission
         * Shows error message with close button and auto-hide after 5 seconds
         */
        function checkDuplicatesBeforeSubmit() {
            const partner = document.getElementById('partner').value;
            const isSelf = !partner || partner === 'self' || partner.toLowerCase() === 'self-voucher';

            // Only check for external brands
            if (isSelf) return true;

            const duplicates = validateAllCodes();

            if (duplicates.length > 0) {
                // Remove previous error messages
                removeDuplicateError();

                // Show error message with close button
                const errorMsg = document.createElement('div');
                errorMsg.id = 'duplicateError';
                errorMsg.style.cssText = `
                    background: #fee2e2;
                    color: #991b1b;
                    padding: 14px 16px;
                    border-radius: 8px;
                    margin-bottom: 16px;
                    font-size: 14px;
                    border: 1px solid #fecaca;
                    display: flex;
                    justify-content: space-between;
                    align-items: flex-start;
                    gap: 12px;
                    animation: slideDown 0.3s ease-out;
                    position: relative;
                `;

                // Unique duplicate codes list
                const uniqueDuplicates = [...new Set(duplicates)];

                errorMsg.innerHTML = `
                    <div style="flex: 1;">
                        <strong style="display: block; margin-bottom: 4px; font-size: 15px;">⚠️ Duplicate Codes Not Allowed!</strong>
                        <small style="color: #7f1d1d; line-height: 1.5;">
                            Please remove or change the following duplicate codes: 
                            <strong>${uniqueDuplicates.join(', ')}</strong>
                        </small>
                    </div>
                    <button type="button" onclick="removeDuplicateError()" 
                            style="
                                background: transparent;
                                border: none;
                                color: #991b1b;
                                font-size: 24px;
                                font-weight: bold;
                                cursor: pointer;
                                padding: 0;
                                width: 28px;
                                height: 28px;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                border-radius: 4px;
                                line-height: 1;
                                transition: all 0.2s;
                                flex-shrink: 0;
                            "
                            onmouseover="this.style.background='#fecaca'"
                            onmouseout="this.style.background='transparent'"
                            title="Close">&times;</button>
                `;

                const container = document.getElementById('manualCodesContainer');
                container.parentNode.insertBefore(errorMsg, container);

                // Scroll to error smoothly
                errorMsg.scrollIntoView({ behavior: 'smooth', block: 'center' });

                // Auto-hide after 5 seconds (5000 milliseconds)
                window.duplicateErrorTimeout = setTimeout(() => {
                    removeDuplicateError();
                }, 5000);

                return false;
            }

            // Remove error message if exists
            removeDuplicateError();

            return true;
        }

        /**
         * Remove duplicate error message and clear timeout
         */
        function removeDuplicateError() {
            // Clear the auto-hide timeout if it exists
            if (window.duplicateErrorTimeout) {
                clearTimeout(window.duplicateErrorTimeout);
                window.duplicateErrorTimeout = null;
            }

            // Remove the error element with fade-out animation
            const existingError = document.getElementById('duplicateError');
            if (existingError) {
                existingError.style.transition = 'opacity 0.3s ease-out, transform 0.3s ease-out';
                existingError.style.opacity = '0';
                existingError.style.transform = 'translateY(-10px)';

                setTimeout(() => {
                    if (existingError.parentNode) {
                        existingError.remove();
                    }
                }, 300);
            }
        }


        // ============================================
        // 5. FORM SUBMISSION HANDLER
        // ============================================
       
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('rewardForm');
            if (form) {
                form.addEventListener('submit', function(e) {
                    // Check duplicates first
                    if (!checkDuplicatesBeforeSubmit()) {
                        e.preventDefault();
                        return false;
                    }

                    // FIX: Remove required from hidden manual code inputs before submit
                    // This prevents "invalid form control not focusable" browser error
                    const manualOptions = document.getElementById('manualCodesOptions');
                    if (manualOptions.style.display === 'none' || manualOptions.classList.contains('field-hidden')) {
                        document.querySelectorAll('.manual-code-input').forEach(input => {
                            input.required = false;
                            input.removeAttribute('required');
                        });
                    }
                });
            }
        });


        // ============================================
        // 6. MODAL MANAGEMENT
        // ============================================
       
        function openModal(reward = null) {
            const modal = document.getElementById('rewardModal');
            const form = document.getElementById('rewardForm');
            const nameInput = document.getElementById('name');
            const categorySelect = document.getElementById('category');
            const partnerSelect = document.getElementById('partner');

            // Reset form and UI
            form.reset();
            document.getElementById('manualCodesContainer').innerHTML = '';
            document.getElementById('photo').value = '';
            document.getElementById('photoPreview').classList.remove('show');

            // Remove error message if exists
            removeDuplicateError();

            // Remove any hidden delete inputs from previous edits
            document.querySelectorAll('input[name="delete_codes[]"]').forEach(el => el.remove());

            // Reset field locks - Use removeAttribute for proper unlock
            nameInput.readOnly = false;
            nameInput.removeAttribute('disabled');
            nameInput.style.backgroundColor = '';
            nameInput.style.cursor = '';

            categorySelect.disabled = false;
            categorySelect.removeAttribute('disabled');
            categorySelect.style.backgroundColor = '';
            categorySelect.style.cursor = '';

            partnerSelect.disabled = false;
            partnerSelect.removeAttribute('disabled');
            partnerSelect.style.backgroundColor = '';
            partnerSelect.style.cursor = '';

            // Remove hidden inputs from previous edits
            ['hiddenCategory', 'hiddenPartner', 'hiddenName'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.remove();
            });

            // Show all fields by default (for Add mode)
            document.getElementById('couponField').classList.remove('field-hidden');
            document.getElementById('expireField').classList.remove('field-hidden');
            document.getElementById('manualCodesOptions').classList.remove('field-hidden');
            document.getElementById('autoGenerateOptions').style.display = 'block';

            if (reward) {
                // ========== EDIT MODE ==========
                document.getElementById('modalTitle').textContent = 'Edit Voucher';
                document.getElementById('formMethod').value = 'PUT';
                form.action = UPDATE_ROUTE.replace('__ID__', reward.id);

                // Populate form fields
                nameInput.value = reward.name || '';
                categorySelect.value = reward.category || '';
                partnerSelect.value = reward.partner || 'self';
                document.getElementById('expire_duration').value = reward.expire_duration || '';
                document.getElementById('description').value = reward.description || '';

                // Lock critical fields (name, category, partner)
                [nameInput, categorySelect, partnerSelect].forEach(el => {
                    el.setAttribute('disabled', 'disabled');
                    if (el.tagName === 'INPUT') el.readOnly = true;
                    el.style.backgroundColor = '#f3f4f6';
                    el.style.cursor = 'not-allowed';
                });

                // Add hidden inputs for locked fields (so they submit)
                addHiddenInput(form, 'name', reward.name, 'hiddenName');
                addHiddenInput(form, 'category', reward.category, 'hiddenCategory');
                addHiddenInput(form, 'partner', reward.partner, 'hiddenPartner');

                // 🔒 Hide these fields in edit mode (including manual codes)
                document.getElementById('couponField').classList.add('field-hidden');
                document.getElementById('expireField').classList.add('field-hidden');
                document.getElementById('manualCodesOptions').classList.add('field-hidden');
                document.getElementById('autoGenerateOptions').style.display = 'none';

                // Handle partner type display
                handlePartnerChange(reward.partner);

                // ⭐ FIX: Force hide manual codes and remove required in edit mode
                    document.getElementById('manualCodesOptions').style.display = 'none';
                    document.querySelectorAll('.manual-code-input').forEach(input => {
                        input.required = false;
                        input.removeAttribute('required');
                    });

                // ⭐ CRITICAL FIX: Remove required from manual code inputs in edit mode
                // Because manualCodesOptions is hidden, required inputs cause browser error
                document.querySelectorAll('.manual-code-input').forEach(input => {
                    input.required = false;
                    input.removeAttribute('required');
                });

                // Also hide manual codes container display
                document.getElementById('manualCodesOptions').style.display = 'none';

                // Display photo if exists
                if (reward.photo) {
                    document.getElementById('previewImg').src = S3_BASE + reward.photo;
                    document.getElementById('photoPreview').classList.add('show');
                }

            } else {
                // ========== ADD MODE ==========
                document.getElementById('modalTitle').textContent = 'Add Voucher';
                document.getElementById('formMethod').value = 'POST';
                form.action = '{{ route('superadmin.voucher.store') }}';

                // Default to self-voucher
                partnerSelect.value = 'self';
                handlePartnerChange('self');
            }

            // Show modal
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        /**
         * Add hidden input field to form
         * Used to submit locked field values in edit mode
         */
        function addHiddenInput(form, name, value, id) {
            let input = document.createElement('input');
            input.type = 'hidden';
            input.name = name;
            input.value = value;
            input.id = id;
            form.appendChild(input);
        }


        // ============================================
        // 7. VIEW DETAILS MODAL
        // ============================================
       
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

        /**
         * Render voucher details in modal
         */
        function renderDetails(r) {
            let html = '';
            if (r.photo) html += `<img src="${S3_BASE}${r.photo}" class="detail-photo">`;

            html += `
                <div class="detail-row"><span class="detail-label">Name</span><span class="detail-value">${r.name || '—'}</span></div>
                <div class="detail-row"><span class="detail-label">Category</span><span class="detail-value">${r.category || '—'}</span></div>
                <div class="detail-row"><span class="detail-label">Brand/Partner</span><span class="detail-value">${r.partner || 'Self'}</span></div>
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


        // ============================================
        // 8. UTILITY FUNCTIONS
        // ============================================
        /**
         * Close modal by ID
         */
        function closeModal(id) {
            document.getElementById(id).classList.remove('active');
            document.body.style.overflow = 'auto';

            // Remove error message when closing
            removeDuplicateError();
        }

        /**
         * Preview uploaded photo
         */
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

        /**
         * Delete voucher with confirmation
         */
        function deleteVoucher(id) {
            if (!confirm('Are you sure you want to delete this voucher? It will be moved to the trash.')) {
                return;
            }

            const DELETE_ROUTE = "{{ route('superadmin.vouchers.destroy', '__ID__') }}";

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
                    if (typeof showAlert === 'function') {
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
                if (typeof showAlert === 'function') showAlert('error', 'Error', 'Failed to delete.');
                else alert('Failed to delete.');
            });
        }


        // ============================================
        // 9. EVENT LISTENERS
        // ============================================

        // Close modal when clicking backdrop
        document.querySelectorAll('.modal').forEach(m => {
            m.addEventListener('click', e => {
                if (e.target === m) {
                    m.classList.remove('active');
                    document.body.style.overflow = 'auto';

                    // Remove error message
                    removeDuplicateError();
                }
            });
        });

        // Close modal on Escape key
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal.active').forEach(m => m.classList.remove('active'));
                document.body.style.overflow = 'auto';

                // Remove error message
                removeDuplicateError();
            }
        });

        // Handle status change dropdown
        document.addEventListener('change', function(e) {
            if (!e.target.classList.contains('reward-status-change')) return;

            fetch("{{ route('superadmin.voucher.changeStatus') }}", {
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
            .then(r => r.json())
            .then(d => showAlert(
                d.success ? 'success' : 'error',
                d.success ? 'Success' : 'Error',
                d.message || 'Something went wrong.'
            ));
        });

        // Initialize partner field on page load
        document.addEventListener('DOMContentLoaded', function() {
            const partnerSelect = document.getElementById('partner');
            if (partnerSelect) {
                handlePartnerChange(partnerSelect.value);
            }
        });
    </script>
    <script>
       document.addEventListener('change', function(e) {

        if (!e.target.classList.contains('voucher-status-toggle')) {
            return;
        }

        const toggle = e.target;
        const voucherId = toggle.dataset.id;

        // Toggle ke according status
        const newStatus = toggle.checked ? 'active' : 'inactive';

        // Isi row ka status text
        const statusText = toggle
            .closest('td')
            .querySelector('.status-text');

        // Old status save kar lo
        const oldStatus = toggle.checked ? 'Inactive' : 'Active';

        toggle.disabled = true;

        fetch("{{ route('superadmin.voucher.changeStatus') }}", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "Accept": "application/json",
                "X-CSRF-TOKEN": "{{ csrf_token() }}"
            },
            body: JSON.stringify({
                id: voucherId,
                status: newStatus
            })
        })
        .then(response => response.json())
        .then(data => {

            if (data.success) {

                // ⭐ Status text update
                statusText.textContent =
                    newStatus === 'active' ? 'Active' : 'Inactive';

                if (typeof showAlert === 'function') {
                    showAlert(
                        'success',
                        'Success',
                        data.message || 'Voucher status updated successfully.'
                    );
                }

            } else {

                // API fail hua to toggle wapas
                toggle.checked = !toggle.checked;

                statusText.textContent = oldStatus;

                if (typeof showAlert === 'function') {
                    showAlert(
                        'error',
                        'Error',
                        data.message || 'Unable to update status.'
                    );
                }
            }

        })
            .catch(error => {

                console.error('Status Update Error:', error);

                // Request fail hua to previous state restore
                toggle.checked = !toggle.checked;

                statusText.textContent = oldStatus;

                if (typeof showAlert === 'function') {
                    showAlert(
                        'error',
                        'Error',
                        'Failed to update voucher status.'
                    );
                }

            })
            .finally(() => {
                toggle.disabled = false;
            });
        });
    </script>
@endpush