@extends('superadmin.layouts.app')

@section('title', 'Quiz Winners - ' . ($quiz->title ?? 'Quiz'))
<link rel="stylesheet" href="{{ asset('assets/css/custom.css') }}">

{{-- deepak css start  --}}

<style>
    .podium-row {
        display: grid;
        grid-template-columns: 1fr 1.3fr 1fr;
        gap: 16px;
        margin-bottom: 28px;
        align-items: end;
    }

    .podium-card {
        background: #fff;
        border: 2px solid var(--line);
        border-radius: 16px;
        padding: 20px 16px;
        text-align: center;
        position: relative;
        transition: all 0.3s;
    }

    .podium-card.silver {
        border-color: #9ca3af;
        background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);
    }

    .podium-card.gold {
        border-color: #fbbf24;
        box-shadow: 0 8px 24px rgba(251, 191, 36, 0.25);
        margin-top: -24px;
        padding: 24px 16px;
        background: linear-gradient(180deg, #fffbeb 0%, #ffffff 100%);
    }

    .podium-card.bronze {
        border-color: #fb923c;
        background: linear-gradient(180deg, #fff7ed 0%, #ffffff 100%);
    }

    .podium-medal {
        font-size: 42px;
        display: block;
        margin-bottom: 10px;
        filter: drop-shadow(0 2px 8px rgba(0, 0, 0, 0.15));
    }

    .podium-name h2{
        font-size: 15px;
        font-weight: 700;
        color: var(--ink);
        margin-bottom: 6px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .podium-points {
        display: inline-block;
        padding: 4px 14px;
        background: linear-gradient(135deg, var(--indigo), #7C3AED);
        color: #fff;
        border-radius: 999px;
        font-size: 13px;
        font-weight: 800;
        box-shadow: 0 2px 8px rgba(124, 58, 237, 0.3);
    }

    .quizwinner-search-filter-box{
        width: 100%;
        display: flex;
        align-items: flex-end;
        gap: 10px;
    }

    .quizwinner-search-filter-wrapper{
        width: 100%;
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
    }

    .quizwinner-filter-btn{
        width: 100%;
        display: flex;
        gap: 10px;
    }

    .quizwinner-filter-btn .btn{
       width: 100%;
       display: flex;
       justify-content: center;
    }

    .leader-badge-rank {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        font-weight: 700;
        font-size: 14px;
        background: #f3f4f6;
        color: #374151;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
    }

    .leader-badge-rank.gold {
        background: linear-gradient(135deg, #fde68a, #fbbf24);
        color: #78350f;
        box-shadow: 0 4px 12px rgba(251, 191, 36, 0.4);
    }

    .leader-badge-rank.silver {
        background: linear-gradient(135deg, #e5e7eb, #9ca3af);
        color: #1f2937;
        box-shadow: 0 4px 8px rgba(148, 163, 184, 0.3);
    }

    .leader-badge-rank.bronze {
        background: linear-gradient(135deg, #fed7aa, #fb923c);
        color: #7c2d12;
        box-shadow: 0 4px 8px rgba(251, 146, 60, 0.3);
    }

     @media(max-width:767px){
        .podium-row {
           grid-template-columns: 1fr;
           gap: 12px;
        }

        .podium-card.gold {
            margin-top: 0;
            order: -1;
        }
     }

    @media(max-width:575px){
        .quizwinner-search-filter-box {
            flex-direction: column;
        }
        .quizwinner-search-filter-wrapper {
            grid-template-columns: repeat(1, 1fr);
        }
        .your-ans-box,.correct-ans-box{
            text-align:end;
        }
    }

</style>


@section('content')
    <div class="panel active">
        <div class="page-head" style="display:flex; justify-content:space-between; align-items:center;">
            <div>
                <h1>🏆 Quiz Winners
                <!-- Direct Reset Link -->
                    {{-- ✅ Button sirf tabhi show hoga jab scheduled time future mein ho --}}
                    @if(\Carbon\Carbon::parse($quiz->scheduled_at)->isFuture())
                        <a href="{{ route('superadmin.quiz.reset-status', $quiz->id) }}" 
                        onclick="return confirm('Kya aap sach mein is Quiz (ID: {{ $quiz->id }}) ka status Pending karna chahte hain?');"
                        class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold text-amber-700 bg-amber-100 rounded-md hover:bg-amber-200 transition border border-amber-200"
                        title="Reset Status to Pending">
                            🔄 
                        </a>
                    @endif
                
                </h1>
                <p>Leaderboard and reward distribution for: <strong>{{ $quiz->title }}</strong></p>
            </div>
            <a href="{{ route('superadmin.quizzes.index') }}" class="btn btn-ghost">← Back to Quizzes</a>
        </div>


        {{-- @php
            $firstWinner = $winners->firstWhere('rank', 1);
            $secondWinner = $winners->firstWhere('rank', 2);
            $thirdWinner = $winners->firstWhere('rank', 3);
        @endphp --}}

        @php
            // Top 3 winners ko $topWinners array se nikal rahe hain
            $firstWinner = $topWinners[1] ?? null;
            $secondWinner = $topWinners[2] ?? null;
            $thirdWinner = $topWinners[3] ?? null;
        @endphp

        {{-- Top 3 Podium --}}
        <div class="podium-row">
            <div class="podium-card silver">
                <span class="podium-medal">🥈</span>
                <div class="podium-name">
                    <h2>{{ $secondWinner?->user?->name ?? 'No Winner' }}</h2>
                </div>
                <div class="podium-points">
                    <p>
                         {{ $secondWinner?->rankReward->reward ?? ($secondWinner?->reward ?? 0) }}
                         @if($quiz->visibility === 'leaderboard')
                             X Leaderboard Marks
                         @else
                             Points
                         @endif
                    </p>
                </div>
                @if(in_array($quiz->visibility, ['leaderboard', 'fastest_finger_first']) && $secondWinner && isset($secondWinner->point_history))
                        <span class="badge bg-info" style="color: #2563eb">Old: {{ $secondWinner->point_history->old_point }}</span>
                        <span class="badge bg-primary" style="color: #16a34a">New: {{ $secondWinner->point_history->new_point }}</span>
                @endif
            </div>
        
            <div class="podium-card gold">
                <span class="podium-medal">🥇</span>
                <div class="podium-name">
                    <h2>{{ $firstWinner?->user?->name ?? 'No Winner' }}</h2>
                </div>
                <div class="podium-points">
                    <p>
                         {{ $firstWinner?->rankReward->reward ?? ($firstWinner?->reward ?? 0) }}
                         @if($quiz->visibility === 'leaderboard')
                             X Leaderboard Marks
                         @else
                             Points
                         @endif
                    </p>
                </div>
                <div>
                    @if(in_array($quiz->visibility, ['leaderboard', 'fastest_finger_first']) && $firstWinner && isset($firstWinner->point_history))
                        <span class="badge bg-info" style="color: #2563eb">Old: {{ $firstWinner->point_history->old_point }}</span>
                        <span class="badge bg-primary" style="color: #16a34a">New: {{ $firstWinner->point_history->new_point }}</span>
                    @endif
                </div>
            </div>

            <div class="podium-card bronze">
                <span class="podium-medal">🥉</span>
                <div class="podium-name">
                    <h2>{{ $thirdWinner?->user?->name ?? 'No Winner' }}</h2>
                </div>
                <div class="podium-points">
                    <p>
                         {{ $thirdWinner?->rankReward->reward ?? ($thirdWinner?->reward ?? 0) }}
                         @if($quiz->visibility === 'leaderboard')
                             X Leaderboard Marks
                         @else
                             Points
                         @endif
                    </p>
                </div>
                <div>
                    @if(in_array($quiz->visibility, ['leaderboard', 'fastest_finger_first']) && $thirdWinner && isset($thirdWinner->point_history))
                        <span class="badge bg-info" style="color: #2563eb">Old: {{ $thirdWinner->point_history->old_point }}</span>
                        <span class="badge bg-primary" style="color: #16a34a">New: {{ $thirdWinner->point_history->new_point }}</span>
                    @endif
                </div>
            </div>
        </div>

        <div class="search-filter-box">
        <form method="GET" class="toolbar">
            <div class="quizwinner-search-filter-box">
                <div class="quizwinner-search-filter-wrapper">
                    <div class="input-field">
                        <label class="lbl" for="search">Search Player</label>
                        <input class="tb-input search-field" name="search" value="{{ request('search') }}" placeholder="Rank, Name, email, mobile…">
                    </div>
                    <div class="input-field">
                            <label class="lbl" for="search">Status</label>
                            <select class="tb-select status-field" name="status" onchange="this.form.submit()">
                               <option value="">All Status</option>
                                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}> Active </option>
                                <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>  Inactive </option>
                            </select>
                        </div>
                </div>
                <div class="quizwinner-filter-btn">
                    <button class="btn btn-primary" type="submit">Filter</button>
                    <a href="{{ route('superadmin.quizzes.winners', $quiz->encrypted_id) }}" class="btn btn-ghost">Reset</a>
                </div>
            </div>
        </form>
    </div>

        <div class="table-wrap table-wrap-res-01">
            <div class="quiz-winners-table-box">
                <table class="quiz-winners-table-wrapper">
                    <thead>
                        <tr>
                            <th style="width: 80px;">Rank</th>
                            <th>User</th>
                            <th>Level</th>
                            <th>Questions</th>
                            <th>Correct</th>
                            <th>Total Time</th>
                            <th>Reward Details</th>
                            <th>Status</th>
                            <th style="width: 80px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($winners as $winner)
                            <tr>
                                <td data-label="Rank">
                                    @if($winner->rank == 1) <span class="leader-badge-rank gold">🥇</span>
                                    @elseif($winner->rank == 2) <span class="leader-badge-rank silver">🥈</span>
                                    @elseif($winner->rank == 3) <span class="leader-badge-rank bronze">🥉</span>
                                    @else <span class="leader-badge-rank">#{{ $winner->rank }}</span>
                                    @endif
                                </td>

                                <td data-label="User">
                                    <div class="winner-user-box">
                                        <div style="font-weight: 600; color: var(--ink);">{{ $winner->user?->name ?? 'Unknown' }}</div>
                                        <div style="font-size: 11px; color: var(--ink-faint);">{{ $winner->user?->email ?? 'No email' }}</div>
                                        <div style="font-size: 11px; color: var(--ink-faint);">{{ $winner->user?->mobile ?? 'No mobile' }}</div>
                                    </div>
                                </td>

                                <td data-label="Level">{{ ucfirst($winner->level ?? '—') }}</td>
                                <td data-label="Questions" class="mono">{{ $winner->total_question ?? 0 }}</td>
                                <td data-label="Correct" class="mono" style="color: var(--success, #10b981); font-weight: 600;">{{ $winner->total_correct_answer ?? 0 }}</td>
                                <td data-label="Total Time" class="mono">{{ $winner->total_time ?? 0 }}s</td>

                                <td data-label="Reward Details">
                                    <div class="winner-reward-detail-box">
                                        @if($winner->rankReward)
                                            <span class="badge {{ $winner->rankReward->type === 'point' ? 'info' : 'warn' }}">
                                                {{ ucfirst($winner->rankReward->type) }}
                                            </span>
                                            
                                            @if($winner->rankReward->type === 'point')
                                                <div style="font-size: 12px; font-weight: 600; margin-top: 4px;">{{ $winner->rankReward->reward }}
                                                     @if($quiz->visibility === 'leaderboard')
                                                         X Leaderboard Marks
                                                    @else
                                                        Points
                                                    @endif
                                                </div>

                                                @if(in_array($quiz->visibility, ['leaderboard', 'fastest_finger_first']) && isset($winner->point_history))
                                                    <span class="badge bg-info" style="color: #2563eb">Old: {{ $winner->point_history->old_point }}</span>
                                                    <span class="badge bg-primary" style="color: #16a34a">New: {{ $winner->point_history->new_point }}</span>
                                                @endif
                                            
                                            @elseif($winner->rankReward->type === 'coupon' && $winner->rankReward->coupon)
                                                <div style="font-size: 12px; font-weight: 600; margin-top: 4px; color: var(--indigo, #4F46E5);">
                                                    Code: {{ $winner->rankReward->coupon->code }}
                                                </div>
                                            
                                            @elseif($winner->rankReward->type === 'voucher')
                                                @if($winner->voucherHistory && $winner->voucherHistory->voucher)
                                                    @php
                                                        $vh = $winner->voucherHistory;
                                                        $voucherDetails = [
                                                            'name' => $vh->voucher->name ?? 'N/A',
                                                            'code' => $vh->code ?? 'N/A',
                                                            'point' => $vh->voucher_point ?? 0,
                                                            'assign_date' => optional($vh->assign_date)->format('d M Y, h:i A') ?: 'N/A',
                                                            'expire_date' => optional($vh->expire_date)->format('d M Y, h:i A') ?: 'N/A',
                                                            'used_date' => optional($vh->used_date)->format('d M Y, h:i A') ?: 'Not Used',
                                                            'status' => ucfirst($vh->status ?? 'Unknown'),
                                                        ];
                                                    @endphp
                                                    
                                                    <div style="font-size: 12px; font-weight: 600; margin-top: 4px; color: var(--indigo, #4F46E5);">
                                                        
                                                        {{ $voucherDetails['name'] }} 
                                                    </div>
                                                    
                                                    <button 
                                                        type="button" 
                                                        class="btn-view-voucher"
                                                        data-details='@json($voucherDetails)'
                                                        title="View Voucher History"
                                                    >
                                                        {{ $voucherDetails['code'] }}
                                                    </button>
                                                @else
                                                    <div style="font-size: 11px; color: #dc3545; margin-top: 4px;">
                                                        History Not Found
                                                    </div>
                                                @endif
                                            @endif
                                        @elseif(!empty($winner->reward) || !empty($winner->reward_type))
                                            <span class="badge {{ ($winner->reward_type ?? 'point') === 'point' ? 'info' : 'warn' }}">
                                                {{ ucfirst($winner->reward_type ?? 'point') }}
                                            </span>
                                            <div style="font-size: 12px; font-weight: 600; margin-top: 4px;">{{ $winner->reward }} Points</div>
                                        @else
                                            <span class="badge neutral">None</span>
                                        @endif
                                    </div>
                                </td>

                                <td data-label="Status">
                                    @php
                                        $status = strtolower($winner->status ?? 'pending');
                                        $statusClass = match($status) {
                                            'completed', 'success' => 'success',
                                            'pending', 'processing' => 'warn',
                                            default => 'neutral'
                                        };
                                    @endphp
                                    <span class="badge {{ $statusClass }}">{{ ucfirst($winner->status ?? 'Pending') }}</span>
                                </td>

                                <td data-label="Action" style="text-align: center;">
                                    <button class="icon-btn btn-view-answers" 
                                            data-quiz="{{ $quiz->id }}" 
                                            data-user="{{ $winner->user_id }}"
                                            data-username="{{ $winner->user?->name ?? 'User' }}"
                                            title="View Detailed Answers">
                                        📝
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" style="text-align: center; color: var(--ink-faint); padding: 40px 0;">
                                    No winners found for this quiz yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="pagination">
                @include('common.pagination.superadmin_pagination', ['pageData' => $winners])
            </div>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- ANSWERS VIEW MODAL                         -->
    <!-- ========================================== -->
    <div id="answersModal" class="quiz-modal" style="display: none;">
        <div class="quiz-modal-backdrop" onclick="closeAnswersModal()"></div>
        <div class="quiz-modal-dialog">
            <div class="quiz-modal-header">
                <h3 id="modalAnswersTitle">User Answers</h3>
                <button type="button" class="quiz-modal-close" onclick="closeAnswersModal()">&times;</button>
            </div>
            
            <div class="quiz-modal-body quiz-ans-table-box" style="padding: 0;">
                <table class="modal-table quiz-ans-table-wrapper" style="width: 100%;">
                    <thead style="position: sticky; top: 0; background: #F9FAFB; z-index: 10;">
                        <tr>
                            <th style="width: 40px;">#</th>
                            <th>Question</th>
                            <th>Your Answer</th>
                            <th>Correct Answer</th>
                            <th style="width: 80px;">Result</th>
                            <th style="width: 80px;">Time</th>
                        </tr>
                    </thead>
                    <tbody id="answersTableBody">
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 40px; color: var(--ink-faint);">
                                Loading answers...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="quiz-modal-footer">
                <button class="btn btn-ghost" type="button" onclick="closeAnswersModal()">Close</button>
            </div>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- VOUCHER HISTORY MODAL                      -->
    <!-- ========================================== -->
    <div id="voucherModal" class="quiz-modal" style="display: none;">
        <div class="quiz-modal-backdrop" onclick="closeVoucherModal()"></div>
        <div class="quiz-modal-dialog" style="max-width: 600px;">
            <div class="quiz-modal-header">
                <h3>🎫 Voucher History Details</h3>
                <button type="button" class="quiz-modal-close" onclick="closeVoucherModal()">&times;</button>
            </div>
            
            <div class="quiz-modal-body voucher-histroy-modal">
                <table class="voucher-details-table">
                    <tr>
                        <th>Voucher Name</th>
                        <td id="v_name">N/A</td>
                    </tr>
                    <tr>
                        <th>Voucher Code</th>
                        <td><code id="v_code" class="voucher-code">N/A</code></td>
                    </tr>
                    <tr>
                        <th>Points / Value</th>
                        <td id="v_point">N/A</td>
                    </tr>
                    <tr>
                        <th>Assign Date</th>
                        <td id="v_assign_date">N/A</td>
                    </tr>
                    <tr>
                        <th>Expire Date</th>
                        <td id="v_expire_date">N/A</td>
                    </tr>
                    <tr>
                        <th>Used Date</th>
                        <td id="v_used_date">N/A</td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td>
                            <span id="v_status" class="badge neutral">N/A</span>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="quiz-modal-footer">
                <button class="btn btn-ghost" type="button" onclick="closeVoucherModal()">Close</button>
            </div>
        </div>
    </div>

@endsection

@push('styles')
<style>
    /* Modal Styles */
    .quiz-modal {
        position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
        z-index: 1050; display: flex; align-items: center; justify-content: center;
    }
    .quiz-modal-backdrop {
        position: absolute; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(16, 19, 43, 0.6); backdrop-filter: blur(4px);
    }
    .quiz-modal-dialog {
        position: relative; background: #ffffff; width: 90%; max-width: 900px;
        max-height: 85vh; border-radius: 16px; box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        display: flex; flex-direction: column; z-index: 1051; overflow: hidden;
        animation: modalFadeIn 0.25s ease-out;
    }
    @keyframes modalFadeIn { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }
    
    .quiz-modal-header {
        padding: 16px 24px; border-bottom: 1px solid var(--line, #e5e7eb);
        display: flex; justify-content: space-between; align-items: center; background: #FBFAFF;
    }
    .quiz-modal-header h3 { font-size: 17px; font-weight: 700; color: var(--ink, #111827); margin: 0; }
    .quiz-modal-close { background: transparent; border: none; font-size: 24px; color: var(--ink-faint, #9ca3af); cursor: pointer; }
    
    .quiz-modal-body { padding: 0; overflow-y: auto; flex: 1; }
    .quiz-modal-footer { padding: 14px 24px; border-top: 1px solid var(--line, #e5e7eb); display: flex; justify-content: flex-end; background: #FBFAFF; }

    /* Answers Modal Table */
    .modal-table { border-collapse: collapse; }
    .modal-table th { font-size: 11px; font-weight: 700; color: var(--ink-faint, #6b7280); text-transform: uppercase; padding: 12px 16px; text-align: left; border-bottom: 1px solid var(--line, #e5e7eb); }
    .modal-table td { padding: 12px 16px; border-bottom: 1px solid var(--line, #f3f4f6); font-size: 13px; vertical-align: top; }
    
    /* Voucher Details Table */
    .voucher-details-table {
        width: 100%;
        border-collapse: collapse;
    }
    .voucher-details-table tr {
        border-bottom: 1px solid var(--line, #f3f4f6);
    }
    .voucher-details-table tr:last-child {
        border-bottom: none;
    }
    .voucher-details-table th {
        text-align: left;
        padding: 14px 16px;
        font-size: 13px;
        font-weight: 600;
        color: var(--ink-faint, #6b7280);
        width: 35%;
        background: #F9FAFB;
    }
    .voucher-details-table td {
        padding: 14px 16px;
        font-size: 14px;
        color: var(--ink, #111827);
        font-weight: 500;
    }
    .voucher-code {
        background: #EEF2FF;
        color: #4F46E5;
        padding: 4px 10px;
        border-radius: 6px;
        font-family: monospace;
        font-size: 14px;
        font-weight: 600;
        letter-spacing: 0.5px;
    }
    
    /* Voucher Details Button */
    .btn-view-voucher {
        display: inline-block;
        margin-top: 6px;
        padding: 4px 10px;
        font-size: 11px;
        font-weight: 600;
        color: #4F46E5;
        background: #EEF2FF;
        border: 1px solid #C7D2FE;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.2s;
    }
    .btn-view-voucher:hover {
        background: #4F46E5;
        color: white;
        border-color: #4F46E5;
    }
    
    /* Badges */
    .badge.success { background: #dcfce7; color: #166534; }
    .badge.warn { background: #fef3c7; color: #92400e; }
    .badge.info { background: #dbeafe; color: #1e40af; }
    .badge.neutral { background: #f3f4f6; color: #4b5563; }
    .badge.danger { background: #fee2e2; color: #991b1b; }
</style>
@endpush

@push('scripts')
<script>
    // ==========================================
    // ANSWERS MODAL FUNCTIONS
    // ==========================================
    function closeAnswersModal() {
        document.getElementById('answersModal').style.display = 'none';
    }

    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-view-answers');
        if (!btn) return;

        const quizId = btn.dataset.quiz;
        const userId = btn.dataset.user;
        const userName = btn.dataset.username;
        const modal = document.getElementById('answersModal');
        const tbody = document.getElementById('answersTableBody');
        const title = document.getElementById('modalAnswersTitle');

        title.innerText = `Answers: ${userName}`;
        modal.style.display = 'flex';
        tbody.innerHTML = `<tr><td colspan="6" style="text-align: center; padding: 40px; color: var(--ink-faint);">Loading answers...</td></tr>`;

        const fetchUrl = `{{ route('superadmin.quizzes.winners.answers', ['quiz' => ':quiz', 'user' => ':user']) }}`
            .replace(':quiz', quizId)
            .replace(':user', userId);

        fetch(fetchUrl)
            .then(res => res.json())
            .then(res => {
                if (!res.success || !res.data || res.data.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="6" style="text-align: center; padding: 40px; color: var(--ink-faint);">No answer records found for this user.</td></tr>`;
                    return;
                }

                let html = '';
                res.data.forEach((item, index) => {
                    const isCorrect = item.is_correct;
                    const resultIcon = isCorrect 
                        ? '<span class="badge success" style="font-size: 16px;">✅</span>' 
                        : '<span class="badge danger" style="font-size: 16px;">❌</span>';
                    
                    const rowBg = isCorrect ? 'background: #f0fdf4;' : 'background: #fef2f2;';
                    const userAns = item.user_answer ? item.user_answer : '<span style="color:#ef4444;">Skipped</span>';
                    const correctAns = item.correct_answer || 'N/A';

                    html += `
                        <tr style="${rowBg}">
                            <td data-label="#"class="mono">${index + 1}</td>
                            <td data-label="Question" style="font-weight: 600;">
                                <div class="quiz-ans-question-box">
                                ${item.question_text}
                                </div>
                            </td>
                            <td data-label="Your Answer">
                                <span class="your-ans-box" style="font-weight: 600; color: ${isCorrect ? '#166534' : '#991b1b'};">
                                    ${userAns}
                                </span>
                            </td>
                            <td data-label="Correct Answer"><span class="badge success correct-ans-box" style="text-transform: none;">${correctAns}</span></td>
                            <td data-label="Result" style="text-align: center;">${resultIcon}</td>
                            <td data-label="Time" class="mono">${item.total_time}s</td>
                        </tr>
                    `;
                });

                tbody.innerHTML = html;
            })
            .catch(err => {
                console.error('Error fetching answers:', err);
                tbody.innerHTML = `<tr><td colspan="6" style="text-align: center; padding: 40px; color: #ef4444;">Failed to load answers. Please try again.</td></tr>`;
            });
    });

    // ==========================================
    // VOUCHER MODAL FUNCTIONS
    // ==========================================
    function closeVoucherModal() {
        document.getElementById('voucherModal').style.display = 'none';
    }

    function openVoucherModal() {
        document.getElementById('voucherModal').style.display = 'flex';
    }

    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-view-voucher');
        if (!btn) return;

        let details = {};
        try {
            details = JSON.parse(btn.dataset.details || '{}');
        } catch (error) {
            console.error('Voucher details JSON parse error:', error);
            return;
        }

        // Fill modal with data
        document.getElementById('v_name').textContent = details.name || 'N/A';
        document.getElementById('v_code').textContent = details.code || 'N/A';
        document.getElementById('v_point').textContent = (details.point ?? 0) + ' Points';
        document.getElementById('v_assign_date').textContent = details.assign_date || 'N/A';
        document.getElementById('v_expire_date').textContent = details.expire_date || 'N/A';
        document.getElementById('v_used_date').textContent = details.used_date || 'Not Used';

        const statusBadge = document.getElementById('v_status');
        statusBadge.textContent = details.status || 'Unknown';
        
        // Set status badge color
        statusBadge.className = 'badge ';
        if (details.status && details.status.toLowerCase() === 'used') {
            statusBadge.classList.add('success');
        } else if (details.status && details.status.toLowerCase() === 'expired') {
            statusBadge.classList.add('danger');
        } else {
            statusBadge.classList.add('neutral');
        }

        openVoucherModal();
    });

    // Close modals on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeAnswersModal();
            closeVoucherModal();
        }
    });
</script>
@endpush