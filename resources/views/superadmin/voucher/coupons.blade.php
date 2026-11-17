@extends('superadmin.layouts.app')
@section('title', 'Coupons - ' . $voucher->name)
<link rel="stylesheet" href="{{ asset('assets/css/custom.css') }}">
@section('content')
    @push('styles')
        <style>
            .back-btn {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                padding: 8px 16px;
                background: #6b7280;
                color: #fff;
                border-radius: 8px;
                text-decoration: none;
                font-size: 14px;
            }
            .back-btn:hover { background: #4b5563; }
            
            .stats-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                gap: 12px;
                margin-bottom: 20px;
            }
            .stat-card {
                background: #fff;
                padding: 16px;
                border-radius: 10px;
                border: 1px solid #e5e7eb;
            }
            .stat-label {
                font-size: 12px;
                color: #6b7280;
                text-transform: uppercase;
                margin-bottom: 4px;
            }
            .stat-value {
                font-size: 24px;
                font-weight: 700;
                color: #111827;
            }
            .stat-card.total .stat-value { color: #4F46E5; }
            .stat-card.available .stat-value { color: #10b981; }
            .stat-card.used .stat-value { color: #ef4444; }
            .stat-card.expired .stat-value { color: #f59e0b; }
            .stat-card.points .stat-value { color: #8b5cf6; }
            
            .coupon-code {
                font-family: ui-monospace, monospace;
                background: #eef2ff;
                color: #4F46E5;
                padding: 3px 8px;
                border-radius: 4px;
                font-weight: 600;
                font-size: 13px;
            }
            .status-pill {
                display: inline-block;
                padding: 3px 10px;
                border-radius: 12px;
                font-size: 11px;
                font-weight: 600;
                text-transform: uppercase;
            }
            .pill-available { background: #d1fae5; color: #065f46; }
            .pill-used { background: #fee2e2; color: #991b1b; }
            .pill-expired { background: #fef3c7; color: #92400e; }
            .pill-pending { background: var(--gold-bg); color: var(--gold); }
            
            .reward-main-info {
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            .reward-info {
                background: #f9fafb;
                padding: 12px 16px;
                border-radius: 8px;
                margin-bottom: 10px;
                display: flex;
                justify-content: space-between;
                align-items: center;
                flex-wrap: wrap;
                gap: 10px;
            }
            .reward-info strong { color: #111827; }
            
            .add-coupon-btn {
                padding: 9px 18px;
                background: #4338CA;
                color: #fff;
                border: none;
                border-radius: 8px;
                font-weight: 600;
                cursor: pointer;
                margin-bottom: 14px;
                font-size: 13px;
            }
            .add-coupon-btn:hover { background: #4338CA; }
            
            /* Modal Styles */
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
                max-width: 500px;
                width: 100%;
                max-height: 90vh;
                overflow-y: auto;
                padding: 24px;
            }
            .modal-head {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 20px;
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
            .field { margin-bottom: 16px; }
            .field label {
                display: block;
                font-size: 13px;
                font-weight: 600;
                color: #374151;
                margin-bottom: 6px;
            }
            .field input, .field select {
                width: 100%;
                padding: 10px 12px;
                border: 1px solid #d1d5db;
                border-radius: 8px;
                font-size: 14px;
            }
            .field input:focus, .field select:focus {
                outline: none;
                border-color: #4F46E5;
                box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
            }
            .hint {
                font-size: 12px;
                color: #6b7280;
                font-weight: 400;
            }
            .modal-foot {
                display: flex;
                justify-content: flex-end;
                gap: 10px;
                margin-top: 20px;
            }
            .btn-cancel {
                padding: 9px 18px;
                background: #f3f4f6;
                color: #374151;
                border: none;
                border-radius: 8px;
                cursor: pointer;
                font-weight: 500;
            }
            .btn-cancel:hover { background: #e5e7eb; }
            .btn-submit {
                padding: 9px 18px;
                background: #4338CA;
                color: #fff;
                border: none;
                border-radius: 8px;
                cursor: pointer;
                font-weight: 600;
            }
            .btn-submit:hover { background: #3730A3; }
            .btn-submit:disabled {
                background: #9ca3af;
                cursor: not-allowed;
            }
            
            /* Manual Codes Container */
            .manual-codes-container {
                display: flex;
                flex-direction: column;
                gap: 8px;
                margin-bottom: 12px;
                max-height: 300px;
                overflow-y: auto;
            }
            .code-input-row {
                display: flex;
                gap: 8px;
                align-items: center;
            }
            .code-input-row input {
                flex: 1;
            }
            .remove-code-btn {
                background: #fee2e2;
                color: #dc2626;
                border: none;
                border-radius: 6px;
                padding: 8px 12px;
                cursor: pointer;
                font-weight: 600;
                font-size: 16px;
                min-width: 36px;
            }
            .remove-code-btn:hover {
                background: #fecaca;
            }
            .add-more-btn {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                padding: 8px 16px;
                background: #eef2ff;
                color: #4F46E5;
                border: 1px dashed #4F46E5;
                border-radius: 8px;
                cursor: pointer;
                font-size: 13px;
                font-weight: 600;
                width: 100%;
                justify-content: center;
            }
            .add-more-btn:hover {
                background: #e0e7ff;
            }
            
            /* Duplicate/Error Messages */
            .error-message {
                background: #fee2e2;
                color: #991b1b;
                padding: 12px 16px;
                border-radius: 8px;
                margin-bottom: 16px;
                font-size: 13px;
                border: 1px solid #fecaca;
                display: none;
                animation: slideDown 0.3s ease-out;
            }
            .error-message.show {
                display: flex;
                align-items: flex-start;
                justify-content: space-between;
                gap: 12px;
            }
            .error-message .error-text {
                flex: 1;
            }
            .error-message .error-close {
                background: transparent;
                border: none;
                color: #991b1b;
                font-size: 20px;
                font-weight: bold;
                cursor: pointer;
                padding: 0;
                width: 24px;
                height: 24px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 4px;
                line-height: 1;
                transition: all 0.2s;
                flex-shrink: 0;
            }
            .error-message .error-close:hover {
                background: #fecaca;
            }
            
            .success-message {
                background: #d1fae5;
                color: #065f46;
                padding: 12px 16px;
                border-radius: 8px;
                margin-bottom: 16px;
                font-size: 13px;
                border: 1px solid #a7f3d0;
                display: none;
                animation: slideDown 0.3s ease-out;
            }
            .success-message.show {
                display: flex;
                align-items: flex-start;
                justify-content: space-between;
                gap: 12px;
            }
            .success-message .success-text {
                flex: 1;
            }
            .success-message .success-close {
                background: transparent;
                border: none;
                color: #065f46;
                font-size: 20px;
                font-weight: bold;
                cursor: pointer;
                padding: 0;
                width: 24px;
                height: 24px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 4px;
                line-height: 1;
                transition: all 0.2s;
                flex-shrink: 0;
            }
            .success-message .success-close:hover {
                background: #a7f3d0;
            }
            
            .input-duplicate {
                border-color: #dc2626 !important;
                background: #fef2f2 !important;
            }
            
            .input-existing {
                border-color: #f59e0b !important;
                background: #fef3c7 !important;
            }
            
            /* Loading spinner */
            .spinner {
                display: inline-block;
                width: 16px;
                height: 16px;
                border: 2px solid #ffffff;
                border-radius: 50%;
                border-top-color: transparent;
                animation: spin 0.8s linear infinite;
                margin-right: 8px;
                vertical-align: middle;
            }
            
            @keyframes spin {
                to { transform: rotate(360deg); }
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
            
            @keyframes fadeOut {
                from {
                    opacity: 1;
                    transform: translateY(0);
                }
                to {
                    opacity: 0;
                    transform: translateY(-10px);
                }
            }
            
            @media (max-width: 768px) {
                .modal { align-items: flex-end; padding: 0; }
                .modal-box {
                    max-height: 95vh;
                    padding: 16px;
                    border-radius: 16px 16px 0 0;
                }
                .stats-grid { grid-template-columns: repeat(2, 1fr); }
                .stat-value { font-size: 20px; }
                .coupon-code { font-size: 11px; padding: 2px 6px; }
                .add-coupon-btn { padding: 11px; }
            }

            @media(max-width:575px) {
                .reward-main-info {
                    flex-direction: column;
                    align-items: start;
                }
                .modal-head h3 {
                    font-size: 14px;
                }
            }
        </style>
    @endpush
    
    <div class="panel active">
        <div class="page-head">
            <div>
                <h1>Coupons</h1>
                <p>{{ $voucher->name }} - All generated coupons</p>
            </div>
            <div class="back-btn-box">
                <a href="{{ route('superadmin.voucher.index') }}" class="btn btn-ghost" style="display: inline-flex">← Back to Voucher</a>
            </div>
        </div>

        

        <div class="reward-main-info">
            <div class="reward-info">
                <div>
                    <strong>{{ $voucher->name }}</strong> — {{ $voucher->category }}
                    @if($voucher->partner) | {{ $voucher->partner }} @endif
                </div>
                <div>
                    Points: <strong>{{ $voucher->points ?? $voucher->coin_cost ?? '—' }}</strong>
                </div>
            </div>
            <button class="add-coupon-btn btn-primary" onclick="openAddCouponModal()">+ Add Coupons</button>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card total">
                <div class="stat-label">Total Coupons</div>
                <div class="stat-value">{{ $stats['total'] }}</div>
            </div>
            <div class="stat-card available">
                <div class="stat-label">Available</div>
                <div class="stat-value">{{ $stats['available'] }}</div>
            </div>
            <div class="stat-card used">
                <div class="stat-label">Used</div>
                <div class="stat-value">{{ $stats['used'] }}</div>
            </div>
            <div class="stat-card expired">
                <div class="stat-label">Expired</div>
                <div class="stat-value">{{ $stats['expired'] }}</div>
            </div>
            <div class="stat-card points">
                <div class="stat-label">Total Points Collected</div>
                <div class="stat-value">{{ $stats['total_points'] }}</div>
            </div>
        </div>
        
        <div class="table-wrap table-wrap-res-01" style="overflow: hidden;">
            <div class="coupons-table-box">
                <table class="coupons-table-wrapper">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Coupon Code</th>
                            <th>Status</th>
                            <th>Assigned To</th>
                            <th>Used By</th>
                            <th>Assign Date</th>
                            <th>Expire Date</th>
                            <th>Points</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($coupons as $i => $coupon)
                            <tr>
                                <td data-label="#">{{ $i + 1 }}</td>
                                <td data-label="Coupon Code"><span class="coupon-code">{{ $coupon->code }}</span></td>
                                <td data-label="Status">
                                    @php
                                        $pillClass = [
                                            'available' => 'pill-available',
                                            'used' => 'pill-used',
                                            'expired' => 'pill-expired',
                                            'pending' => 'pill-pending'
                                        ][$coupon->status] ?? 'pill-available';
                                    @endphp
                                    <span class="status-pill {{ $pillClass }}">{{ ucfirst($coupon->status) }}</span>
                                </td>
                                <td data-label="Assigned To">{{ $coupon->assignedUser->name ?? '—' }}</td>
                                <td data-label="Used By">{{ $coupon->usedUser->name ?? '—' }}</td>
                                <td data-label="Assign Date" class="mono">{{ $coupon->assign_date ? $coupon->assign_date->format('Y-m-d') : '—' }}</td>
                                <td data-label="Expire Date" class="mono">{{ $coupon->expire_date ? $coupon->expire_date->format('Y-m-d') : '—' }}</td>
                                <td data-label="Points" class="mono">{{ $coupon->deduct_point ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" style="text-align:center;color:var(--ink-faint);padding:30px;">
                                    No coupons generated yet. Click "+ Add Coupons" to generate.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="pagination">
                @include('common.pagination.superadmin_pagination', ['pageData' => $coupons])
            </div> 
        </div>
    </div>

    {{-- ============================================ --}}
    {{-- MODAL 1: Self Voucher (Auto-Generate Codes) --}}
    {{-- ============================================ --}}
    <div class="modal" id="selfCouponModal">
        <div class="modal-box">
            <div class="modal-head">
                <h3>Auto-Generate Coupons</h3>
                <button class="modal-close" onclick="closeModal('selfCouponModal')">&times;</button>
            </div>
            
            <form method="POST" action="{{ route('superadmin.voucher.addCoupons', $voucher) }}">
                @csrf
                
                <div class="field">
                    <label>Number of Coupons <span class="req">*</span></label>
                    <input type="number" name="no_of_coupon" min="1" max="1000" value="1" required placeholder="Enter number of coupons">
                </div>
                
                <div class="field">
                    <label>Expiry Duration (days) <span class="hint">(blank = no expiry)</span></label>
                    <input type="number" name="expire_duration" min="0" placeholder="Blank = no expiry" value="{{ $voucher->expire_duration ?? 1 }}">
                </div>
                
                <div class="modal-foot">
                    <button type="button" class="btn-cancel" onclick="closeModal('selfCouponModal')">Cancel</button>
                    <button type="submit" class="btn-submit">Generate Coupons</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ============================================ --}}
    {{-- MODAL 2: External Brand (Manual Codes) --}}
    {{-- ============================================ --}}
    <div class="modal" id="manualCouponModal">
        <div class="modal-box">
            <div class="modal-head">
                <h3>Add Manual Coupons ({{ ucfirst($voucher->partner ?? 'External') }})</h3>
                <button class="modal-close" onclick="closeModal('manualCouponModal')">&times;</button>
            </div>
            
            <form method="POST" action="{{ route('superadmin.voucher.addManualCoupons', $voucher) }}" id="manualCouponForm">
                @csrf
                
                {{-- Error Message Box --}}
                <div class="error-message" id="formError">
                    <span class="error-text" id="formErrorText"></span>
                    <button type="button" class="error-close" onclick="hideFormError()">&times;</button>
                </div>
                
                <div class="field">
                    <label>Coupon Codes <span class="req">*</span></label>
                    
                    {{-- Manual Codes Container --}}
                    <div class="manual-codes-container" id="manualCodesContainer">
                        {{-- Default one input will be added by JS --}}
                    </div>
                    
                    {{-- Add More Button --}}
                    <button type="button" class="add-more-btn" onclick="addCodeInput()">
                        + Add More Code
                    </button>
                </div>
                
                <div class="field">
                    <label>Expiry Duration (days) <span class="hint">(blank = no expiry)</span></label>
                    <input type="number" name="expire_duration" min="0" placeholder="Blank = no expiry" value="{{ $voucher->expire_duration ?? 1 }}">
                </div>
                
                <div class="modal-foot">
                    <button type="button" class="btn-cancel" onclick="closeModal('manualCouponModal')">Cancel</button>
                    <button type="submit" class="btn-submit" id="submitBtn">Save Coupons</button>
                </div>
            </form>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        // ============================================
        // 1. CONFIGURATION
        // ============================================
        const VOUCHER_PARTNER = '{{ $voucher->partner ?? '' }}'.toLowerCase();
        const IS_SELF_VOUCHER = !VOUCHER_PARTNER || VOUCHER_PARTNER === 'self' || VOUCHER_PARTNER === 'self-voucher';
        const CHECK_CODE_ROUTE = '{{ route('superadmin.voucher.checkCode') }}';
        const AUTO_HIDE_DURATION = 5000; // 5 seconds

        // Store existing codes to check against database
        const existingCodesInDB = new Set();
        const autoHideTimers = {};

        // ============================================
        // 2. MESSAGE MANAGEMENT (Auto-Hide)
        // ============================================
        /**
         * Show error message with auto-hide
         */
        function showError(elementId, message) {
            const errorBox = document.getElementById(elementId);
            const errorText = document.getElementById(elementId + 'Text');
            
            if (!errorBox || !errorText) return;
            
            errorText.textContent = message;
            errorBox.classList.add('show');
            
            // Clear existing timer
            if (autoHideTimers[elementId]) {
                clearTimeout(autoHideTimers[elementId]);
            }
            
            // Auto-hide after 5 seconds
            autoHideTimers[elementId] = setTimeout(() => {
                hideMessage(elementId);
            }, AUTO_HIDE_DURATION);
        }

        /**
         * Show success message with auto-hide
         */
        function showSuccess(elementId, message) {
            const successBox = document.getElementById(elementId);
            const successText = successBox ? successBox.querySelector('.success-text') : null;
            
            if (!successBox || !successText) return;
            
            successText.textContent = message;
            successBox.classList.add('show');
            
            // Clear existing timer
            if (autoHideTimers[elementId]) {
                clearTimeout(autoHideTimers[elementId]);
            }
            
            // Auto-hide after 5 seconds
            autoHideTimers[elementId] = setTimeout(() => {
                hideMessage(elementId);
            }, AUTO_HIDE_DURATION);
        }

        /**
         * Hide message with animation
         */
        function hideMessage(elementId) {
            const element = document.getElementById(elementId);
            if (!element) return;
            
            // Clear timer
            if (autoHideTimers[elementId]) {
                clearTimeout(autoHideTimers[elementId]);
                delete autoHideTimers[elementId];
            }
            
            // Fade out animation
            element.style.animation = 'fadeOut 0.3s ease-out';
            setTimeout(() => {
                element.classList.remove('show');
                element.style.animation = '';
            }, 300);
        }

        /**
         * Show form-specific error
         */
        function showFormError(message) {
            showError('formError', message);
            document.getElementById('formErrorText').textContent = message;
        }

        /**
         * Hide form error
         */
        function hideFormError() {
            hideMessage('formError');
        }

        // Auto-hide session messages on page load
        document.addEventListener('DOMContentLoaded', function() {
            const sessionSuccess = document.getElementById('sessionSuccess');
            const sessionError = document.getElementById('sessionError');
            
            if (sessionSuccess) {
                autoHideTimers['sessionSuccess'] = setTimeout(() => {
                    hideMessage('sessionSuccess');
                }, AUTO_HIDE_DURATION);
            }
            
            if (sessionError) {
                autoHideTimers['sessionError'] = setTimeout(() => {
                    hideMessage('sessionError');
                }, AUTO_HIDE_DURATION);
            }
        });


        // ============================================
        // 3. MODAL MANAGEMENT
        // ============================================
        function openAddCouponModal() {
            if (IS_SELF_VOUCHER) {
                document.getElementById('selfCouponModal').classList.add('active');
            } else {
                document.getElementById('manualCouponModal').classList.add('active');
                
                const container = document.getElementById('manualCodesContainer');
                if (container.children.length === 0) {
                    addCodeInput();
                }
            }
            document.body.style.overflow = 'hidden';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
            document.body.style.overflow = 'auto';
            hideFormError();
            
            // Clear all input error states
            document.querySelectorAll('.manual-code-input').forEach(input => {
                input.classList.remove('input-duplicate', 'input-existing');
            });
        }

        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.remove('active');
                    document.body.style.overflow = 'auto';
                    hideFormError();
                }
            });
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal.active').forEach(modal => {
                    modal.classList.remove('active');
                });
                document.body.style.overflow = 'auto';
                hideFormError();
            }
        });


        // ============================================
        // 4. MANUAL CODE INPUT MANAGEMENT
        // ============================================
        let debounceTimer = null;

        function addCodeInput(value = '') {
            const container = document.getElementById('manualCodesContainer');
            
            const row = document.createElement('div');
            row.className = 'code-input-row';
            
            const input = document.createElement('input');
            input.type = 'text';
            input.name = 'manual_codes[]';
            input.value = value;
            input.required = true;
            input.placeholder = 'Enter unique coupon code';
            input.className = 'manual-code-input';
            
            // Debounced input handler for database check
            input.addEventListener('input', function() {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => {
                    handleInputChange(this);
                }, 500); // Wait 500ms after typing stops
            });
            
            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.innerHTML = '&times;';
            removeBtn.className = 'remove-code-btn';
            removeBtn.onclick = function() {
                if (container.children.length <= 1) {
                    input.value = '';
                    input.classList.remove('input-duplicate', 'input-existing');
                    hideFormError();
                    return;
                }
                row.remove();
                validateAllCodes();
            };
            
            row.appendChild(input);
            row.appendChild(removeBtn);
            container.appendChild(row);
            
            input.focus();
        }

        /**
         * Handle input change - check duplicates and database
         */
        async function handleInputChange(currentInput) {
            const currentValue = currentInput.value.trim();
            
            // Reset states
            currentInput.classList.remove('input-duplicate', 'input-existing');
            
            if (!currentValue) {
                validateAllCodes();
                return;
            }
            
            // Check for duplicates in current form
            const allInputs = document.querySelectorAll('.manual-code-input');
            let duplicateInForm = false;
            
            allInputs.forEach(input => {
                if (input !== currentInput && input.value.trim() === currentValue) {
                    duplicateInForm = true;
                    input.classList.add('input-duplicate');
                }
            });
            
            if (duplicateInForm) {
                currentInput.classList.add('input-duplicate');
                showFormError(`Duplicate code "${currentValue}" found in form!`);
                return;
            }
            
            // Check against database via AJAX
            try {
                const response = await fetch(CHECK_CODE_ROUTE, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ code: currentValue })
                });
                
                const data = await response.json();
                
                if (data.exists) {
                    currentInput.classList.add('input-existing');
                    showFormError(`Code "${currentValue}" already exists in database!`);
                }
            } catch (error) {
                console.error('Error checking code:', error);
            }
            
            // Re-validate all
            validateAllCodes();
        }


        // ============================================
        // 5. VALIDATION
        // ============================================
        function validateAllCodes() {
            const allInputs = document.querySelectorAll('.manual-code-input');
            const codes = [];
            const duplicates = [];
            
            // Don't remove existing class (database check result)
            allInputs.forEach(input => {
                input.classList.remove('input-duplicate');
            });
            
            allInputs.forEach(input => {
                const value = input.value.trim();
                if (value) {
                    if (codes.includes(value)) {
                        duplicates.push(value);
                        input.classList.add('input-duplicate');
                    } else {
                        codes.push(value);
                    }
                }
            });
            
            if (duplicates.length > 0) {
                allInputs.forEach(input => {
                    const value = input.value.trim();
                    if (value && duplicates.includes(value)) {
                        input.classList.add('input-duplicate');
                    }
                });
            }
            
            return duplicates;
        }


        // ============================================
        // 6. FORM SUBMISSION
        // ============================================
        document.getElementById('manualCouponForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const submitBtn = document.getElementById('submitBtn');
            const originalText = submitBtn.innerHTML;
            
            // Check for duplicates
            const duplicates = validateAllCodes();
            if (duplicates.length > 0) {
                const uniqueDuplicates = [...new Set(duplicates)];
                showFormError(`Duplicate codes not allowed: ${uniqueDuplicates.join(', ')}`);
                return false;
            }
            
            // Check for empty codes
            const allInputs = document.querySelectorAll('.manual-code-input');
            let hasEmpty = false;
            
            allInputs.forEach(input => {
                if (!input.value.trim()) {
                    hasEmpty = true;
                    input.classList.add('input-duplicate');
                }
            });
            
            if (hasEmpty) {
                showFormError('Please fill all coupon code fields or remove empty ones.');
                return false;
            }
            
            // Check for existing codes (already marked with input-existing class)
            const existingInForm = document.querySelectorAll('.input-existing');
            if (existingInForm.length > 0) {
                const existingCodes = Array.from(existingInForm).map(input => input.value.trim());
                showFormError(`Cannot submit: These codes already exist: ${existingCodes.join(', ')}`);
                return false;
            }
            
            // All validations passed - submit form
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner"></span> Saving...';
            
            // Submit the form
            this.submit();
        });
    </script>
@endpush