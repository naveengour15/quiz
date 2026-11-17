<!-- Redeem Request Details Modal -->
<div id="redeemDetailsModal" class="redeem-modal" style="display: none;">
    <div class="redeem-modal-backdrop" onclick="closeRedeemModal()"></div>
    <div class="redeem-modal-dialog">
        <div class="redeem-modal-header">
            <h3>Redeem Request Details</h3>
            <button type="button" class="redeem-modal-close" onclick="closeRedeemModal()">&times;</button>
        </div>
        
        <div class="redeem-modal-body">
            <!-- User Information -->
            <div class="redeem-section">
                <h4 class="section-title">User Information</h4>
                <div class="detail-grid">
                    <div class="detail-item">
                        <span class="detail-label">Name:</span>
                        <span id="rd_user_name" class="detail-val">—</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Email:</span>
                        <span id="rd_user_email" class="detail-val">—</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Mobile:</span>
                        <span id="rd_user_mobile" class="detail-val">—</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Wallet Balance:</span>
                        <span id="rd_wallet_balance" class="detail-val mono">—</span>
                    </div>
                </div>
            </div>

            <!-- Request Details -->
            <div class="redeem-section">
                <h4 class="section-title">Request Details</h4>
                <div class="detail-grid">
                    <div class="detail-item">
                        <span class="detail-label">Request ID:</span>
                        <span id="rd_request_id" class="detail-val">—</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Request Mobile:</span>
                        <span id="rd_request_mobile" class="detail-val">—</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Amount:</span>
                        <span id="rd_amount" class="detail-val mono">—</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Consumer Number:</span>
                        <span id="rd_consumer_number" class="detail-val">—</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Company:</span>
                        <span id="rd_company" class="detail-val">—</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Zone (State):</span>
                        <span id="rd_zone" class="detail-val">—</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Request Type:</span>
                        <span id="rd_request_type" class="detail-val">—</span>
                    </div>
                     <div class="detail-item">
                        <span class="detail-label">Status:</span>
                        <span id="rd_status" class="detail-val">—</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Created At:</span>
                        <span id="rd_created_at" class="detail-val">—</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Updated At:</span>
                        <span id="rd_updated_at" class="detail-val">—</span>
                    </div>
                   
                </div>
            </div>

            <!-- Notes Section -->
            <div class="redeem-section">
                <h4 class="section-title">Notes</h4>
                <div class="notes-container">
                    <div class="note-full-box">
                        <div class="note-full-label">User Note:</div>
                        <div id="rd_user_note" class="note-full-content">—</div>
                    </div>
                    <div class="note-full-box">
                        <div class="note-full-label">Approver Note:</div>
                        <div id="rd_approver_note" class="note-full-content">—</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="redeem-modal-footer">
            <button class="btn btn-ghost" type="button" onclick="closeRedeemModal()">Close</button>
        </div>
    </div>
</div>

@push('styles')
<style>
    .redeem-modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        z-index: 1050;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .redeem-modal-backdrop {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(16, 19, 43, 0.6);
        backdrop-filter: blur(4px);
    }

    .redeem-modal-dialog {
        position: relative;
        background: #ffffff;
        width: 90%;
        max-width: 700px;
        max-height: 85vh;
        border-radius: 16px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        display: flex;
        flex-direction: column;
        z-index: 1051;
        overflow: hidden;
        animation: redeemModalFadeIn 0.25s ease-out;
    }

    @keyframes redeemModalFadeIn {
        from { opacity: 0; transform: scale(0.95); }
        to { opacity: 1; transform: scale(1); }
    }

    .redeem-modal-header {
        padding: 16px 24px;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #f9fafb;
    }

    .redeem-modal-header h3 {
        font-size: 17px;
        font-weight: 700;
        color: #111827;
        margin: 0;
    }

    .redeem-modal-close {
        background: transparent;
        border: none;
        font-size: 24px;
        color: #9ca3af;
        cursor: pointer;
        line-height: 1;
        padding: 0;
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 6px;
        transition: all 0.2s;
    }

    .redeem-modal-close:hover {
        background: #f3f4f6;
        color: #374151;
    }

    .redeem-modal-body {
        padding: 24px;
        overflow-y: auto;
        flex: 1;
    }

    .redeem-section {
        margin-bottom: 24px;
    }

    .redeem-section:last-child {
        margin-bottom: 0;
    }

    .section-title {
        font-size: 14px;
        font-weight: 700;
        color: #374151;
        margin-bottom: 12px;
        padding-bottom: 8px;
        border-bottom: 2px solid #e5e7eb;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .detail-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
    }

    .detail-item {
        display: flex;
        flex-direction: column;
        gap: 4px;
        background: #f9fafb;
        padding: 10px 14px;
        border-radius: 8px;
        border: 1px solid #f3f4f6;
    }

    .detail-label {
        font-size: 11px;
        font-weight: 700;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }

    .detail-val {
        font-size: 13px;
        font-weight: 600;
        color: #111827;
        word-break: break-word;
    }

    .detail-val.mono {
        font-family: 'Courier New', monospace;
        color: #059669;
    }

    .notes-container {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .note-full-box {
        background: #f9fafb;
        padding: 12px 16px;
        border-radius: 8px;
        border: 1px solid #e5e7eb;
    }

    .note-full-label {
        font-size: 11px;
        font-weight: 700;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        margin-bottom: 6px;
    }

    .note-full-content {
        font-size: 13px;
        color: #374151;
        line-height: 1.6;
        white-space: pre-wrap;
        word-break: break-word;
    }

    .redeem-modal-footer {
        padding: 14px 24px;
        border-top: 1px solid #e5e7eb;
        display: flex;
        justify-content: flex-end;
        background: #f9fafb;
    }

    @media(max-width: 640px) {
        .detail-grid {
            grid-template-columns: 1fr;
        }
        
        .redeem-modal-dialog {
            width: 95%;
            max-height: 90vh;
        }
    }
</style>
@endpush

<script>
    // View Details Button Click
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-view-redeem');
        if (!btn) return;

        const requestId = btn.dataset.id;
        
        // Show loading
        const modal = document.getElementById('redeemDetailsModal');
        modal.style.display = 'flex';

        // Fetch Request Details
        // fetch(`/admin/redeem-requests/${requestId}`)
        const showUrl = "{{ route('admin.redeem-requests.show', ['id' => '__ID__']) }}".replace('__ID__', requestId);

        // Fetch Request Details
        fetch(showUrl)
            .then(res => res.json())
            .then(res => {
                if (!res.success) {
                    showAlert('error', 'Error', res.message || 'Failed to fetch details.');
                    closeRedeemModal();
                    return;
                }

                const r = res.data;

                // User Information
                document.getElementById('rd_user_name').innerText = r.user?.name || '—';
                document.getElementById('rd_user_email').innerText = r.user?.email || '—';
                document.getElementById('rd_user_mobile').innerText = r.user?.mobile || '—';
                document.getElementById('rd_request_mobile').innerText = r.mobile || '—';
                document.getElementById('rd_wallet_balance').innerText = '₹' + (r.user?.wallet_balance ? parseFloat(r.user.wallet_balance).toFixed(2) : '0.00');

                // Request Details
                document.getElementById('rd_request_id').innerText = '#' + r.id;
                document.getElementById('rd_amount').innerText = '₹' + parseFloat(r.redeem_amount).toFixed(2);
                document.getElementById('rd_consumer_number').innerText = r.consumer_number || '—';
                document.getElementById('rd_company').innerText = r.company || '—';
                document.getElementById('rd_zone').innerText = r.zone?.name || '—';
                document.getElementById('rd_request_type').innerText = r.request_type ? r.request_type.toUpperCase() : '—';
                document.getElementById('rd_created_at').innerText = r.created_at ? new Date(r.created_at).toLocaleString() : '—';
                document.getElementById('rd_updated_at').innerText = r.updated_at ? new Date(r.updated_at).toLocaleString() : '—';

                // Status Badge
                const statusEl = document.getElementById('rd_status');
                if (r.status === 'approved') {
                    statusEl.innerHTML = '<span class="badge success">Approved</span>';
                } else if (r.status === 'rejected') {
                    statusEl.innerHTML = '<span class="badge danger">Rejected</span>';
                } else if (r.status === 'cancelled') {
                    statusEl.innerHTML = '<span class="badge warn">Cancelled</span>';
                } else {
                    statusEl.innerHTML = '<span class="badge secondary">Pending</span>';
                }

                // Notes
                document.getElementById('rd_user_note').innerText = r.user_note || 'No note provided by user.';
                document.getElementById('rd_approver_note').innerText = r.approver_note || 'No note provided by approver.';

            })
            .catch(err => {
                console.error(err);
                showAlert('error', 'Error', 'Failed to fetch redeem request details.');
                closeRedeemModal();
            });
    });

    function closeRedeemModal() {
        document.getElementById('redeemDetailsModal').style.display = 'none';
    }
</script>