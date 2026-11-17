@extends('superadmin.layouts.app')
@section('title', 'Coupons - ' . $reward->name)
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
                margin-bottom: 16px;
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
            
            .reward-info {
                background: #f9fafb;
                padding: 12px 16px;
                border-radius: 8px;
                margin-bottom: 20px;
                display: flex;
                justify-content: space-between;
                align-items: center;
                flex-wrap: wrap;
                gap: 10px;
            }
            .reward-info strong { color: #111827; }
            
            .add-coupon-btn {
                padding: 9px 18px;
                background: #10b981;
                color: #fff;
                border: none;
                border-radius: 8px;
                font-weight: 600;
                cursor: pointer;
                margin-bottom: 14px;
                font-size: 13px;
            }
            .add-coupon-btn:hover { background: #059669; }
            
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
                max-width: 450px;
                width: 100%;
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
            .field input {
                width: 100%;
                padding: 10px 12px;
                border: 1px solid #d1d5db;
                border-radius: 8px;
                font-size: 14px;
            }
            .field input:focus {
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
                background: #10b981;
                color: #fff;
                border: none;
                border-radius: 8px;
                cursor: pointer;
                font-weight: 600;
            }
            .btn-submit:hover { background: #059669; }
            
            @media (max-width: 768px) {
                .modal { align-items: flex-end; padding: 0; }
                .modal-box {
                    max-height: 95vh;
                    padding: 16px;
                    border-radius: 16px 16px 0 0;
                    margin-bottom: 0;
                }
                .stats-grid { grid-template-columns: repeat(2, 1fr); }
                .stat-value { font-size: 20px; }
                .coupon-code { font-size: 11px; padding: 2px 6px; }
                .add-coupon-btn { width: 100%; padding: 11px; }
            }
        </style>
    @endpush
    
    <div class="panel active">
        <div class="page-head">
            <div>
                <h1>Coupons</h1>
                <p>{{ $reward->name }} - All generated coupons</p>
            </div>
        </div>
        
        <a href="{{ route('superadmin.rewards.index') }}" class="back-btn">← Back to Voucher</a>
        
        <div class="reward-info">
            <div>
                <strong>{{ $reward->name }}</strong> — {{ $reward->category }}
                @if($reward->partner) | {{ $reward->partner }} @endif
            </div>
            <div>
                Points: <strong>{{ $reward->points ?? $reward->coin_cost ?? '—' }}</strong>
            </div>
        </div>
        
        <button class="add-coupon-btn" onclick="openAddCouponModal()">+ Add Coupons</button>
        
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
        
        <div class="table-wrap">
            <table>
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
                            <td>{{ $i + 1 }}</td>
                            <td><span class="coupon-code">{{ $coupon->code }}</span></td>
                            <td>
                                @php
                                    $pillClass = [
                                        'available' => 'pill-available',
                                        'used' => 'pill-used',
                                        'expired' => 'pill-expired'
                                    ][$coupon->status] ?? 'pill-available';
                                @endphp
                                <span class="status-pill {{ $pillClass }}">{{ ucfirst($coupon->status) }}</span>
                            </td>
                            <td>{{ $coupon->assignedUser->name ?? '—' }}</td>
                            <td>{{ $coupon->usedUser->name ?? '—' }}</td>
                            <td class="mono">{{ $coupon->assign_date ? $coupon->assign_date->format('Y-m-d') : '—' }}</td>
                            <td class="mono">{{ $coupon->expire_date ? $coupon->expire_date->format('Y-m-d') : '—' }}</td>
                            <td class="mono">{{ $coupon->deduct_point ?? '—' }}</td>
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
               <div class="pagination">
                @include('common.pagination.superadmin_pagination', ['pageData' => $coupons])
            </div> 
        </div>
    </div>

    <!-- Add Coupon Modal -->
    <div class="modal" id="addCouponModal">
        <div class="modal-box">
            <div class="modal-head">
                <h3>Add Coupons</h3>
                <button class="modal-close" onclick="closeAddCouponModal()">&times;</button>
            </div>
            
            <form method="POST" action="{{ route('superadmin.rewards.addCoupons', $reward) }}">
                @csrf
                
                <div class="field">
                    <label>Number of Coupons <span class="req">*</span></label>
                    <input type="number" name="no_of_coupon" min="1" value="1" required placeholder="Enter number of coupons">
                </div>
                
                <div class="field">
                    <label>Expiry Duration (days) <span class="hint">(blank = no expiry)</span></label>
                    <input type="number" name="expire_duration" min="0" placeholder="Blank = no expiry"  value="{{ $reward->expire_duration ?? 1 }}">
                </div>
                
                <div class="modal-foot">
                    <button type="button" class="btn-cancel" onclick="closeAddCouponModal()">Cancel</button>
                    <button type="submit" class="btn-submit">Generate Coupons</button>
                </div>
            </form>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        function openAddCouponModal() {
            document.getElementById('addCouponModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        }
        
        function closeAddCouponModal() {
            document.getElementById('addCouponModal').classList.remove('active');
            document.body.style.overflow = 'auto';
        }
        
        document.getElementById('addCouponModal').addEventListener('click', function(e) {
            if (e.target === this) closeAddCouponModal();
        });
        
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeAddCouponModal();
        });
    </script>
@endpush