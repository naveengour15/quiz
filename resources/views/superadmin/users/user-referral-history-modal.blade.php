
{{-- ========================================== --}}
{{-- REFERRAL HISTORY MODAL --}}
{{-- ========================================== --}}
<div id="referralHistoryModal" class="modal">
    <div class="modal-backdrop" onclick="closeReferralHistoryModal()"></div>
    <div class="modal-content form-card" style="margin-bottom:0; max-width:900px;">
        
        {{-- Header --}}
        <div class="fh">
            <h3>Referral History - <span id="referralUserName" style="color:var(--indigo);"></span></h3>
            <button type="button" class="icon-btn" onclick="closeReferralHistoryModal()" style="border:none;background:transparent;box-shadow:none;">✕</button>
        </div>

        <div style="padding: 0 24px 24px;">
            
            {{-- Top Stats Box (Total Count) --}}
            <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; padding: 16px; display: flex; align-items: center; gap: 16px; margin-bottom: 20px;">
                <div style="width: 48px; height: 48px; background: #22c55e; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 24px; font-weight: 800;" id="referralTotalCount">0</div>
                <div>
                    <div style="font-size: 14px; font-weight: 700; color: #166534;">Total Referred Users</div>
                    <div style="font-size: 12px; color: #15803d;">Users who joined using this referral code</div>
                </div>
            </div>

            {{-- Loader --}}
            <div id="referralHistoryLoader" class="text-center" style="padding:30px 0; display: none;">
                <i class="ti ti-loader-2 ti-spin" style="font-size: 24px; color: var(--indigo);"></i>
                <div style="font-size: 13px; color: var(--ink-soft); margin-top: 8px;">Loading referrals...</div>
            </div>

            {{-- Table Content --}}
            <div id="referralHistoryContent" style="display:none;">
                <div class="table-wrap table-wrap-res-01">
                    <table class="users-table-wrapper user-history-table" style="min-width:auto;">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                {{-- <th>Age</th> --}}
                                <th>Gender</th>
                                <th>Quizzes Played</th>
                                <th>Joined At</th>
                            </tr>
                        </thead>
                        <tbody id="referralHistoryTableBody">
                            <!-- Data will be injected here -->
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Empty State --}}
            <div id="referralHistoryEmpty" class="text-center" style="display:none; padding:30px 0; color:var(--ink-faint);">
                <i class="ti ti-users-off" style="font-size: 32px; display: block; margin-bottom: 8px;"></i>
                No referral history found.
            </div>

        </div>
    </div>
</div>

<script>
 // ==========================================
// REFERRAL HISTORY MODAL LOGIC (Event Delegation)
// ==========================================

document.addEventListener('click', function (e) {
    // Check if the clicked element is our referral history button
    const button = e.target.closest('.referral-history-btn');
    if (!button) {
        return;
    }
    e.preventDefault();

    // Get data from attributes (Route name se aaya hua URL)
    const userId = button.dataset.userId;
    const userName = button.dataset.userName;
    const historyUrl = button.dataset.historyUrl; // ✅ Yahan se dynamic route URL aa raha hai

    // Set User Name in Modal
    document.getElementById('referralUserName').innerText = userName || 'User';

    // Reset Modal States
    document.getElementById('referralHistoryLoader').style.display = 'block';
    document.getElementById('referralHistoryContent').style.display = 'none';
    document.getElementById('referralHistoryEmpty').style.display = 'none';
    document.getElementById('referralHistoryTableBody').innerHTML = '';
    document.getElementById('referralTotalCount').innerText = '0';

    // Open Modal
    const modal = document.getElementById('referralHistoryModal');
    modal.classList.add('active');

    // Fetch Data using the dynamic URL from data attribute
    fetch(historyUrl, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(res => res.json())
    .then(response => {
        document.getElementById('referralHistoryLoader').style.display = 'none';

        if (!response.success || !response.data || response.data.length === 0) {
            document.getElementById('referralHistoryEmpty').style.display = 'block';
            return;
        }

        // Update Total Count
        document.getElementById('referralTotalCount').innerText = response.count;
        document.getElementById('referralHistoryContent').style.display = 'block';

        // Build Table Rows
        let html = '';
        response.data.forEach(function(item, index) {
            html += `
                <tr>
                    <td data-label="#">${index + 1}</td>
                    <td data-label="Name" style="font-weight:600; color:var(--ink);">
                        <p style="margin-bottom: 5px;">${escapeHtml(item.name)}</p>
                      <p>  +91 ${escapeHtml(item.mobile)}</p></td>
                   
                    <td data-label="Gender">
                        <span class="badge info" style="font-size:10px;">${escapeHtml(item.gender)}</span>
                    </td>
                    <td data-label="Quizzes Played">
                        <span class="badge success" style="font-size:11px; font-weight:700;">${item.quiz_count}</span>
                    </td>
                    <td data-label="Joined At" class="mono" style="font-size:12px;">${escapeHtml(item.created_at)}</td>
                </tr>
            `;
        });
        
        document.getElementById('referralHistoryTableBody').innerHTML = html;
    })
    .catch(error => {
        console.error('Referral History Error:', error);
        document.getElementById('referralHistoryLoader').style.display = 'none';
        document.getElementById('referralHistoryEmpty').innerHTML = '<span style="color:#dc2626;">Failed to load referral history.</span>';
        document.getElementById('referralHistoryEmpty').style.display = 'block';
    });
});

// Close Modal Function
function closeReferralHistoryModal() {
    const modal = document.getElementById('referralHistoryModal');
    if (modal) {
        modal.classList.remove('active');
    }
}

// Close modal on backdrop click
document.getElementById('referralHistoryModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeReferralHistoryModal();
    }
});
</script>