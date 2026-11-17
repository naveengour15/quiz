@php
    $quizId = $editing->id
        ?? request()->query('editing')
        ?? request()->route('quiz');

    $rewards = $quizId
        ? \App\Models\QuizRankReward::where('quiz_id', $quizId)->get()
        : collect();

    $quiz = $quizId ? \App\Models\Quiz::find($quizId) : null;
    $maxPlayers = $quiz ? ($quiz->no_of_players ?? 0) : 0;
    $visibility = $quiz ? ($quiz->visibility ?? 'public') : 'public';

    // 👇 Leaderboard check (type ya visibility dono mein se)
    $isLeaderboard = $quiz && (
        ($quiz->type ?? '') === 'leaderboard' 
        || ($quiz->visibility ?? '') === 'leaderboard'
        || ($quiz->quiz_type ?? '') === 'leaderboard'
    );

    // $vouchers = \App\Models\Voucher::where('status', 'active')->get();

    use App\Models\Voucher;

    // ✅ Active vouchers + unke pending voucher_histories ka count
    $vouchers = Voucher::where('status', 'active')
        ->withCount([
            'histories as pending_count' => function ($query) {
                $query->where('status', 'pending');
            },
            'histories as assigned_count' => function ($query) {
                $query->where('status', 'assigned_to_user');
            },
            'histories as used_count' => function ($query) {
                $query->where('status', 'used');
            },
            'histories as total_count' 
        ])
        
        ->get();

      
    // Complex mapping ko alag se process kar liya
    $jsVouchers = $vouchers->map(fn($v) => [
        'id'            => $v->id,
        'name'          => $v->name,
        'pending_count' => $v->pending_count ?? 0,
        'total_count'   => $v->total_count ?? 0
    ])->values(); // ⚠️ values() lagana zaroori hai taaki JS mein clean Array [ {}, {} ] bane, Object nahi

@endphp

<style>
    /* Desktop: Ek line mein - Info left, Button right */
    .rewards-header-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
        margin: 6px 0 15px;
    }
    
    .rewards-header-info {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 6px;
    }
    
    /* Mobile: Stack layout - button full width */
    @media (max-width: 575px) {
        .rewards-header-row {
            flex-direction: column;
            align-items: stretch;
        }
        
        .rewards-header-info {
            align-items: center;
            text-align: center;
        }
        
        .rewards-header-row form {
            width: 100%;
        }
        
        .clear-rewards-btn {
            width: 100%;
        }
    }
</style>
@if(!$quizId)
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle"></i>
        Please save the Quiz details first to configure Rank Rewards.
    </div>
@else

  
    <div class="rewards-header-row">
    
        <div class="rewards-header-info">
            <label style="text-transform:none; font-weight:700; color:var(--ink-soft); font-size:12px; margin:0;">
                👥 Players Allowed : <strong>{{ $maxPlayers }}</strong>
            </label>
            @if($isLeaderboard)
                <span style="background:#fef3c7; color:#92400e; padding:3px 10px; border-radius:6px; font-size:11px; font-weight:600;">
                    🏆 Leaderboard Quiz — Reward Type locked
                </span>
            @endif
        </div>

        {{-- Right Side: Clear All Button --}}
        @if($rewards->count() > 0)
            <form method="POST" action="{{ route('admin.quizzes.rewards.clear', $quizId) }}" 
                style="margin:0;" 
                onsubmit="return confirm('⚠️ Are you sure you want to delete ALL rewards for this quiz? This action cannot be undone!');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn clear-rewards-btn" style="
                    background: linear-gradient(135deg, #ef4444, #dc2626);
                    color: white;
                    border: none;
                    padding: 8px 18px;
                    border-radius: 8px;
                    font-size: 13px;
                    font-weight: 600;
                    cursor: pointer;
                    box-shadow: 0 2px 8px rgba(239, 68, 68, 0.3);
                    transition: all 0.2s;
                    white-space: nowrap;
                " onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(239, 68, 68, 0.4)';"
                onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(239, 68, 68, 0.3)';">
                    <i class="fas fa-trash-alt"></i> Clear All Rewards
                </button>
            </form>

        @else
            {{--  Yahan "Add Default Rewards" ka stylish green button --}}
    <form method="POST" action="{{ route('admin.quizzes.rewards.add-default', $quizId) }}" 
        style="margin:0;" 
        onsubmit="return confirm('Are you sure you want to add default rewards for this quiz?');">
        @csrf
        <button type="submit" class="btn add-rewards-btn" style="
            background: linear-gradient(135deg, #10b981, #059669); /* Green Gradient */
            color: white;
            border: none;
            padding: 8px 18px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
            transition: all 0.2s;
            white-space: nowrap;
        " onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(16, 185, 129, 0.4)';"
        onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(16, 185, 129, 0.3)';">
            <i class="fas fa-plus-circle"></i> + Add Default Rewards
        </button>
    </form>
        @endif
    </div>

    <form method="POST" action="{{ route('admin.quizzes.rewards.save') }}" id="rewardForm" onsubmit="return validateRewardForm(event)">
        @csrf
        <input type="hidden" name="quiz_id" value="{{ $quizId }}">

        <div class="form-grid" style="grid-template-columns: 1fr; grid-template-columns: auto !important;">
           

            <div id="rewardsContainer" style="grid-template-columns: 3fr;"></div>

            <div id="noRewardMsg" style="display:none; text-align:center; padding:20px; color:var(--ink-soft); border:1px dashed var(--line); border-radius:8px;">
                <i class="fas fa-gift" style="font-size:24px; opacity:0.4; display:block; margin-bottom:8px;"></i>
                No rewards added yet. Click <strong>"Add More Reward"</strong> to add one.
            </div>
        </div>

        <div style="width:100%; text-align:right;">
            <button type="button" class="btn btn-primary" onclick="addRewardRow()" style="padding: 6px 14px; font-size: 12px; margin-bottom: 15px;">
                <i class="fas fa-plus"></i> + Add More Reward
            </button>
        </div>

        <div class="form-foot">
            <button class="btn btn-primary" type="submit"    style="background-color: #16a34a; border-color: #16a34a; color: #fff;">
                <i class="fas fa-save"></i> Save All Rewards
            </button>
        </div>
    </form>

    <script>
        window.vouchersData = @json($jsVouchers);
        window.existingRewards = @json($rewards);
        window.maxPlayers = {{ $maxPlayers }};
        window.isLeaderboard = @json($isLeaderboard); // 👈 JS ko pass kiya
    </script>
@endif
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (window.existingRewards && window.existingRewards.length > 0) {
        window.existingRewards.forEach((r) => addRewardRow(r));
    } else {
        addRewardRow();
    }
});

let rewardRowIndex = 0;

function addRewardRow(data = null) {
    const container = document.getElementById('rewardsContainer');
    const index = rewardRowIndex++;
    const rowId = `reward-row-${index}`;

    const rewardId = data?.id ?? '';
    const startRank = data?.start_rank ?? '';
    const endRank = data?.end_rank ?? '';
    const rewardValue = data?.reward ?? '';
    const voucherId = data?.voucher_id ?? '';

    //  1. Leaderboard lock check
    const isLocked = window.isLeaderboard;
    const rewardType = isLocked ? 'leaderboard' : (data?.type ?? 'point');

    //  2. Dynamic dropdown options (leaderboard sirf locked quiz mein)
    let typeOptions = '';
    if (isLocked) {
        typeOptions = `<option value="leaderboard" selected>Leaderboard</option>`;
    } else {
        typeOptions = `
            <option value="point" ${rewardType === 'point' ? 'selected' : ''}>Points</option>
            <option value="voucher" ${rewardType === 'voucher' ? 'selected' : ''}>Voucher</option>
        `;
    }

    const selectDisabled = isLocked ? 'disabled' : '';
    const hiddenLeaderboardField = isLocked 
        ? `<input type="hidden" name="rewards[${index}][reward_type]" value="point">` 
        : '';

    //  3. Dynamic label + multiplier visibility
    const labelText = (rewardType === 'leaderboard') ? 'Leaderboard' : 'Reward Value (Points)';
    const multiplierDisplay = (rewardType === 'leaderboard') ? 'inline' : 'none';

    let voucherOptions = `<option value="">-- Select Voucher --</option>`;
    window.vouchersData.forEach(v => {
        const selected = (voucherId == v.id) ? 'selected' : '';
        voucherOptions += `<option value="${v.id}" ${selected}>${v.name}(${v.pending_count})</option>`;
    });

    const rowHtml = `
        <div class="reward-row-card" id="${rowId}" style="background:#f9fafb; border:1px solid var(--line); border-radius:8px; padding:15px; margin-bottom:12px; position:relative;">
            <button type="button" onclick="removeRewardRow('${rowId}')" 
                style="position:absolute; top:10px; right:10px; background:#fee2e2; color:#dc2626; border:none; border-radius:50%; width:28px; height:28px; cursor:pointer; font-weight:bold;">×</button>

            <input type="hidden" name="rewards[${index}][id]" value="${rewardId}">
            ${hiddenLeaderboardField}

            <div class="form-grid" style="grid-template-columns: 1fr 1fr 1fr 1fr; gap: 12px;">

                <div style="display:grid; grid-template-columns:repeat(2, 1fr); gap: 12px;">
                    <div class="field">
                        <label>Start Rank <span class="req">*</span></label>
                        <input type="number" name="rewards[${index}][start_rank]" value="${startRank}" min="1" placeholder="e.g., 1" class="start-rank-input" required>
                    </div>

                    <div class="field">
                        <label>End Rank <span class="req">*</span></label>
                        <input type="number" name="rewards[${index}][end_rank]" value="${endRank}" min="${startRank || 1}" placeholder="e.g., 15" class="end-rank-input" required>
                    </div>
                </div>

                <div class="field">
                    <label>Reward Type <span class="req">*</span></label>
                    <select name="rewards[${index}][reward_type]" class="reward-type-select" 
                        onchange="toggleRewardFields(this, '${rowId}')" 
                        ${selectDisabled} required
                        style="${isLocked ? 'background:#fef3c7; cursor:not-allowed;' : ''}">
                        ${typeOptions}
                    </select>
                </div>

                <div class="field reward-value-field" style="${rewardType === 'voucher' ? 'display:none;' : ''}">
                    <label>
                        <span class="reward-label-text">${labelText}</span>
                        <span class="reward-multiplier" style="color:#2563eb; font-weight:600; display:${multiplierDisplay};"></span>
                        <span class="req">*</span>
                        <div class="reward-total" style="font-size:11px; color:#059669; font-weight:500; margin-top:2px;"></div>
                    </label>
                    <input type="number" step="0.01" name="rewards[${index}][reward_value]" value="${rewardValue}" placeholder="e.g., 500" class="reward-value-input" ${rewardType === 'point' || rewardType === 'leaderboard' ? 'required' : ''}>
                </div>

                <div class="field voucher-field" style="${(rewardType === 'point' || rewardType === 'leaderboard') ? 'display:none;' : ''}">
                    <label>Voucher <span class="req">*</span></label>
                    <select name="rewards[${index}][voucher_id]" class="voucher-select" ${rewardType === 'voucher' ? 'required' : ''}>
                        ${voucherOptions}
                    </select>
                </div>
            </div>
        </div>
    `;

    container.insertAdjacentHTML('beforeend', rowHtml);

    const newRow = document.getElementById(rowId);
    const startInput = newRow.querySelector('.start-rank-input');
    const endInput = newRow.querySelector('.end-rank-input');
    const valueInput = newRow.querySelector('.reward-value-input');

    startInput.addEventListener('input', function() {
        const startVal = parseInt(this.value);
        const endVal = parseInt(endInput.value);
        if (!isNaN(startVal)) {
            endInput.min = startVal;
            if (isNaN(endVal) || endVal < startVal) {
                endInput.value = startVal;
            }
        }
        updateMultiplier(rowId);
    });

    endInput.addEventListener('input', function() {
        const startVal = parseInt(startInput.value);
        if (!isNaN(startVal)) {
            this.min = startVal;
        }
        updateMultiplier(rowId);
    });

    if (valueInput) {
        valueInput.addEventListener('input', function() {
            updateMultiplier(rowId);
        });
    }

    setTimeout(() => updateMultiplier(rowId), 50);
    updateNoRewardMsg();
}

//  MULTIPLIER UPDATE — Sirf Leaderboard ke liye
function updateMultiplier(rowId) {
    const row = document.getElementById(rowId);
    if (!row) return;

    const typeSelect = row.querySelector('.reward-type-select');
    const hiddenType = row.querySelector('input[name$="[reward_type]"][type="hidden"]');
    const currentType = hiddenType ? hiddenType.value : (typeSelect ? typeSelect.value : '');

    const multiplierSpan = row.querySelector('.reward-multiplier');
    const totalDiv = row.querySelector('.reward-total');

    //  Agar leaderboard nahi hai, to multiplier/total clear karke exit
    if (currentType !== 'leaderboard') {
        if (multiplierSpan) multiplierSpan.textContent = '';
        if (totalDiv) totalDiv.innerHTML = '';
        return;
    }

    const startInput = row.querySelector('.start-rank-input');
    const endInput = row.querySelector('.end-rank-input');
    const valueInput = row.querySelector('.reward-value-input');

    const start = parseInt(startInput.value);
    const end = parseInt(endInput.value);
    const value = parseFloat(valueInput.value);

    let count = 0;
    if (!isNaN(start) && !isNaN(end) && end >= start && start > 0) {
        count = end - start + 1;
    }

    if (multiplierSpan) {
        multiplierSpan.textContent = count > 0 ? ` × ${value}` : '';
    }

    if (totalDiv) {
        if (count > 0 && !isNaN(value) && value > 0) {
            const total = (value * count).toFixed(2);
            // totalDiv.innerHTML = `💰 Total: <strong>${total} points</strong> for ${count} player${count > 1 ? 's' : ''}`;
        } else {
            totalDiv.innerHTML = '';
        }
    }
}

function removeRewardRow(rowId) {
    const row = document.getElementById(rowId);
    if (row) {
        row.remove();
        updateNoRewardMsg();
    }
}

//  TOGGLE FIELDS — Label text + multiplier visibility handle
function toggleRewardFields(selectEl, rowId) {
    const row = document.getElementById(rowId);
    if (!row) return;

    const valueField = row.querySelector('.reward-value-field');
    const voucherField = row.querySelector('.voucher-field');
    const valueInput = row.querySelector('.reward-value-input');
    const voucherSelect = row.querySelector('.voucher-select');
    const labelText = row.querySelector('.reward-label-text');
    const multiplierSpan = row.querySelector('.reward-multiplier');

    if (selectEl.value === 'voucher') {
        valueField.style.display = 'none';
        voucherField.style.display = '';
        valueInput.required = false;
        valueInput.value = '';
        voucherSelect.required = true;
    } else if (selectEl.value === 'leaderboard') {
        //  Leaderboard: label "Leaderboard" + multiplier visible
        valueField.style.display = '';
        voucherField.style.display = 'none';
        valueInput.required = true;
        voucherSelect.required = false;
        voucherSelect.value = '';
        if (labelText) labelText.textContent = 'Leaderboard';
        if (multiplierSpan) multiplierSpan.style.display = 'inline';
    } else {
        // Points: label "Reward Value (Points)" + multiplier hidden
        valueField.style.display = '';
        voucherField.style.display = 'none';
        valueInput.required = true;
        voucherSelect.required = false;
        voucherSelect.value = '';
        if (labelText) labelText.textContent = 'Reward Value (Points)';
        if (multiplierSpan) multiplierSpan.style.display = 'none';
    }
    updateMultiplier(rowId);
}

function updateNoRewardMsg() {
    const container = document.getElementById('rewardsContainer');
    const msg = document.getElementById('noRewardMsg');
    msg.style.display = container.children.length === 0 ? 'block' : 'none';
}

function validateRewardForm(event) {
    const rows = document.querySelectorAll('.reward-row-card');
    if (rows.length === 0) {
        if (typeof showAlert === 'function') showAlert('warning', 'Add Reward', 'Please add at least one reward before saving.');
        else alert('Please add at least one reward before saving.');
        event.preventDefault();
        return false;
    }

    const ranges = [];
    for (let row of rows) {
        const startInput = row.querySelector('.start-rank-input');
        const endInput = row.querySelector('.end-rank-input');

        const start = parseInt(startInput.value);
        const end = parseInt(endInput.value);

        if (isNaN(start) || isNaN(end)) {
            if (typeof showAlert === 'function') showAlert('warning', 'Invalid Rank', 'Please enter valid numbers for Start and End Rank.');
            else alert('Please enter valid numbers for Start and End Rank.');
            event.preventDefault();
            startInput.focus();
            return false;
        }

        if (start > end) {
            if (typeof showAlert === 'function') showAlert('warning', 'Invalid Rank Range', `Start Rank (${start}) cannot be greater than End Rank (${end}).`);
            else alert(`Start Rank (${start}) cannot be greater than End Rank (${end}).`);
            event.preventDefault();
            startInput.focus();
            return false;
        }

        if (window.maxPlayers > 0 && end > window.maxPlayers) {
            if (typeof showAlert === 'function') showAlert('warning', 'Exceeds Max Players', `End Rank (${end}) exceeds the maximum allowed players (${window.maxPlayers}).`);
            else alert(`End Rank (${end}) exceeds the maximum allowed players (${window.maxPlayers}).`);
            event.preventDefault();
            endInput.focus();
            return false;
        }

        const typeSelect = row.querySelector('.reward-type-select');
        const hiddenType = row.querySelector('input[name$="[reward_type]"][type="hidden"]');
        const currentType = hiddenType ? hiddenType.value : (typeSelect ? typeSelect.value : '');
        
        const voucherSelect = row.querySelector('.voucher-select');
        if (currentType === 'voucher' && !voucherSelect.value) {
            if (typeof showAlert === 'function') showAlert('warning', 'Missing Voucher', 'Please select a Voucher for the selected rank range.');
            else alert('Please select a Voucher for the selected rank range.');
            event.preventDefault();
            voucherSelect.focus();
            return false;
        }

        const valueInput = row.querySelector('.reward-value-input');
        if ((currentType === 'point' || currentType === 'leaderboard') && (!valueInput.value || parseFloat(valueInput.value) <= 0)) {
            if (typeof showAlert === 'function') showAlert('warning', 'Missing Reward Value', 'Please enter a valid reward value.');
            else alert('Please enter a valid reward value.');
            event.preventDefault();
            valueInput.focus();
            return false;
        }

        ranges.push({ start, end, row });
    }

    for (let i = 0; i < ranges.length; i++) {
        for (let j = i + 1; j < ranges.length; j++) {
            const r1 = ranges[i];
            const r2 = ranges[j];

            if (r1.start <= r2.end && r1.end >= r2.start) {
                if (typeof showAlert === 'function') showAlert('warning', 'Overlapping Ranks', `Rank range ${r1.start}-${r1.end} overlaps with ${r2.start}-${r2.end}.`);
                else alert(`Rank range ${r1.start}-${r1.end} overlaps with ${r2.start}-${r2.end}.`);
                event.preventDefault();
                r1.row.querySelector('.start-rank-input').focus();
                return false;
            }
        }
    }
    return true;
}
</script>
@endpush