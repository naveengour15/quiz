@php
    $quizId = $editing->id
        ?? request()->query('editing')
        ?? request()->route('quiz');

    $rewards = $quizId
        ? \App\Models\QuizRankReward::where('quiz_id', $quizId)->get()
        : collect();

    $coupons = \App\Models\Coupon::where('status', 'active')->get();
@endphp

@if(!$quizId)
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle"></i>
        Please save the Quiz details first to configure Rank Rewards.
    </div>
@else
    <form method="POST" action="{{ route('admin.quizzes.rewards.save') }}" id="rewardForm" onsubmit="return validateRewardForm(event)">
        @csrf
        <input type="hidden" name="quiz_id" value="{{ $quizId }}">

        <div class="form-grid" style="grid-template-columns: 1fr;">

            <div class="field full" style="margin-top:6px; display:flex; justify-content:space-between; align-items:center;">
                <label style="text-transform:none; font-weight:700; color:var(--ink-soft); font-size:12px; margin:0;">
                    Quiz Rank Rewards Configuration
                </label>
                <button type="button" class="btn btn-primary" onclick="addRewardRow()" style="padding: 6px 14px; font-size: 12px;">
                    <i class="fas fa-plus"></i> Add More Reward
                </button>
            </div>

            <div id="rewardsContainer"></div>

            <div id="noRewardMsg" style="display:none; text-align:center; padding:20px; color:var(--ink-soft); border:1px dashed var(--line); border-radius:8px;">
                <i class="fas fa-gift" style="font-size:24px; opacity:0.4; display:block; margin-bottom:8px;"></i>
                No rewards added yet. Click <strong>"Add More Reward"</strong> to add one.
            </div>

        </div>

        <div class="form-foot">
            <button class="btn btn-primary" type="submit">
                <i class="fas fa-save"></i> Save All Rewards
            </button>
        </div>
    </form>

    <script>
        window.couponsData = @json($coupons->map(fn($c) => ['id' => $c->id, 'code' => $c->code]));
        window.existingRewards = @json($rewards);
    </script>
@endif

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (window.existingRewards && window.existingRewards.length > 0) {
        window.existingRewards.forEach((r, idx) => addRewardRow(r));
    } else {
        addRewardRow();
    }
});

let rewardRowIndex = 0;

function addRewardRow(data = null) {
    const container = document.getElementById('rewardsContainer');
    const index = rewardRowIndex++;
    const rowId = `reward-row-${index}`;

    const rankRange = data?.rank_range ?? '';
    const rewardType = data?.type ?? 'point';
    const rewardValue = data?.reward ?? '';
    const couponId = data?.coupon_id ?? '';

    let couponOptions = `<option value="">-- Select Coupon --</option>`;
    window.couponsData.forEach(c => {
        const selected = (couponId == c.id) ? 'selected' : '';
        couponOptions += `<option value="${c.id}" ${selected}>${c.code}</option>`;
    });

    const rowHtml = `
        <div class="reward-row-card" id="${rowId}" style="background:#f9fafb; border:1px solid var(--line); border-radius:8px; padding:15px; margin-bottom:12px; position:relative;">
            <button type="button" onclick="removeRewardRow('${rowId}')" 
                style="position:absolute; top:10px; right:10px; background:#fee2e2; color:#dc2626; border:none; border-radius:50%; width:28px; height:28px; cursor:pointer; font-weight:bold;">×</button>
            
            <div class="form-grid" style="grid-template-columns: 1fr 1fr 1fr; gap: 12px;">
                <div class="field">
                    <label>Rank Range <span class="req">*</span> <span class="hint" style="font-weight:400;">(e.g., 1 or 1-15)</span></label>
                    <input type="text"
                           name="rewards[${index}][rank_range]"
                           value="${rankRange}"
                           pattern="^\\d+(-\\d+)?$"
                           title="Only numbers and hyphens allowed (e.g., 1 or 1-15)"
                           placeholder="e.g., 1 or 1-15"
                           required>
                </div>

                <div class="field">
                    <label>Reward Type <span class="req">*</span></label>
                    <select name="rewards[${index}][reward_type]" 
                            class="reward-type-select" 
                            onchange="toggleRewardFields(this, '${rowId}')" 
                            required>
                        <option value="point" ${rewardType === 'point' ? 'selected' : ''}>Points</option>
                        <option value="coupon" ${rewardType === 'coupon' ? 'selected' : ''}>Coupon</option>
                    </select>
                </div>

                <div class="field reward-value-field" style="${rewardType === 'coupon' ? 'display:none;' : ''}">
                    <label>Reward Value (Points) <span class="req">*</span></label>
                    <input type="number"
                           step="0.01"
                           name="rewards[${index}][reward_value]"
                           value="${rewardValue}"
                           placeholder="e.g., 500"
                           class="reward-value-input"
                           ${rewardType === 'point' ? 'required' : ''}>
                </div>

                <div class="field coupon-field" style="${rewardType === 'point' ? 'display:none;' : ''}">
                    <label>Coupon <span class="req">*</span></label>
                    <select name="rewards[${index}][coupon_id]" class="coupon-select" ${rewardType === 'coupon' ? 'required' : ''}>
                        ${couponOptions}
                    </select>
                </div>
            </div>
        </div>
    `;

    container.insertAdjacentHTML('beforeend', rowHtml);
    updateNoRewardMsg();
}

function removeRewardRow(rowId) {
    const row = document.getElementById(rowId);
    if (row) {
        row.remove();
        updateNoRewardMsg();
    }
}

// ✅ Conditional Required Logic
function toggleRewardFields(selectEl, rowId) {
    const row = document.getElementById(rowId);
    if (!row) return;

    const valueField = row.querySelector('.reward-value-field');
    const couponField = row.querySelector('.coupon-field');
    const valueInput = row.querySelector('.reward-value-input');
    const couponSelect = row.querySelector('.coupon-select');

    if (selectEl.value === 'point') {
        valueField.style.display = '';
        couponField.style.display = 'none';
        valueInput.required = true;
        couponSelect.required = false;
        couponSelect.value = ''; // Clear coupon selection
    } else {
        valueField.style.display = 'none';
        couponField.style.display = '';
        valueInput.required = false;
        valueInput.value = ''; // Clear points value
        couponSelect.required = true;
    }
}

function updateNoRewardMsg() {
    const container = document.getElementById('rewardsContainer');
    const msg = document.getElementById('noRewardMsg');
    msg.style.display = container.children.length === 0 ? 'block' : 'none';
}

// ✅ Frontend Overlap Validation before submit
function validateRewardForm(event) {
    const rows = document.querySelectorAll('.reward-row-card');
    if (rows.length === 0) {
        alert('Please add at least one reward before saving.');
        event.preventDefault();
        return false;
    }

    const ranges = [];
    for (let row of rows) {
        const rangeStr = row.querySelector('input[name$="[rank_range]"]').value.trim();
        ranges.push(rangeStr);
    }

    // Check for overlap
    for (let i = 0; i < ranges.length; i++) {
        for (let j = i + 1; j < ranges.length; j++) {
            if (isOverlap(ranges[i], ranges[j])) {
                alert(`Error: Rank range "${ranges[i]}" overlaps with "${ranges[j]}". Please use unique ranges.`);
                event.preventDefault();
                return false;
            }
        }
    }
    return true;
}

function isOverlap(range1, range2) {
    const parse = (r) => {
        const parts = r.split('-');
        return { min: parseInt(parts[0]), max: parts.length > 1 ? parseInt(parts[1]) : parseInt(parts[0]) };
    };
    const r1 = parse(range1);
    const r2 = parse(range2);
    return (r1.min <= r2.max && r1.max >= r2.min);
}
</script>
@endpush