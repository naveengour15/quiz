@extends('superadmin.layouts.app')

@section('title', 'Joined Players & Winners - ' . ($quiz->title ?? 'Quiz'))
<link rel="stylesheet" href="{{ asset('assets/css/custom.css') }}">

@push('styles')
<style>
    /* ═══════════════════════════════════════════════
       CUSTOM TABS STYLING
    ═══════════════════════════════════════════════ */
    .custom-tabs-wrapper {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
        overflow: hidden;
        border: 1px solid var(--line, #e5e7eb);
    }

    .custom-tabs {
        display: flex;
        list-style: none;
        padding: 0;
        margin: 0;
        background: #f8fafc;
        border-bottom: 2px solid #e2e8f0;
    }

    .custom-tab-link {
        flex: 1;
        text-align: center;
        padding: 16px 24px;
        font-size: 15px;
        font-weight: 700;
        color: #64748b;
        cursor: pointer;
        border-bottom: 3px solid transparent;
        transition: all 0.25s ease;
        text-decoration: none;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .custom-tab-link:hover {
        color: #1e293b;
        background: #f1f5f9;
    }

    .custom-tab-link.active {
        color: #4f46e5;
        background: #ffffff;
        border-bottom-color: #4f46e5;
    }

    .custom-tab-content {
        padding: 24px 20px;
        background: #fff;
        min-height: 400px;
    }

    .custom-tab-pane {
        display: none;
        opacity: 0;
        transform: translateY(8px);
        transition: opacity 0.3s ease, transform 0.3s ease;
    }

    .custom-tab-pane.active {
        display: block;
        opacity: 1;
        transform: translateY(0);
        animation: fadeInTab 0.35s ease forwards;
    }

    @keyframes fadeInTab {
        from { opacity: 0; transform: translateY(8px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* ═══════════════════════════════════════════════
       STATS BOXES STYLING
    ═══════════════════════════════════════════════ */
    .stats-box-wrapper {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
        margin-bottom: 24px;
    }

    .stat-box {
        border-radius: 12px;
        padding: 16px 20px;
        display: flex;
        align-items: center;
        gap: 16px;
        border: 1px solid transparent;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .stat-box:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.05);
    }

    .stat-icon {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        flex-shrink: 0;
    }

    .stat-content {
        display: flex;
        flex-direction: column;
    }

    .stat-label {
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 4px;
    }

    .stat-value {
        font-size: 26px;
        font-weight: 800;
        line-height: 1;
    }

    .joined-box {
        background: #fff;
        border-color: #bfdbfe;
    }
    .joined-box .stat-icon { background: #E0F2FE; color: #ffffff; }
    .joined-box .stat-label { color: #1e40af; }
    .joined-box .stat-value { color: #1e3a8a; }

    .played-box {
        background: #fff;
        border-color: #10B981;
    }
    .played-box .stat-icon { background: #DCFCE7; color: #ffffff; }
    .played-box .stat-label { color: #166534; }
    .played-box .stat-value { color: #14532d; }

    .waiting-box {
        background: linear-gradient(135deg, #fff7ed, #ffedd5);
        border: 1px solid #fdba74;
    }
    .waiting-box .stat-icon { background: rgba(249, 115, 22, 0.1); }
    .waiting-box .stat-value { color: #ea580c; }

    .left-box {
        background: #fff;
        border-color: #fecaca;
    }
    .left-box .stat-icon { background: #FEF3C7; color: #ffffff; }
    .left-box .stat-label { color: #991b1b; }
    .left-box .stat-value { color: #7f1d1d; }

    /* ═══════════════════════════════════════════════
       WINNERS PODIUM STYLING
    ═══════════════════════════════════════════════ */
    .podium-row {
        display: grid;
        grid-template-columns: 1fr 1.3fr 1fr;
        gap: 16px;
        margin-bottom: 28px;
        align-items: end;
    }

    .podium-card {
        background: #fff;
        border: 2px solid var(--line, #e5e7eb);
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

    .podium-name h2 {
        font-size: 15px;
        font-weight: 700;
        color: var(--ink, #111827);
        margin-bottom: 6px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .podium-points {
        display: inline-block;
        padding: 4px 14px;
        background: linear-gradient(135deg, #4f46e5, #7c3aed);
        color: #fff;
        border-radius: 999px;
        font-size: 13px;
        font-weight: 800;
        box-shadow: 0 2px 8px rgba(124, 58, 237, 0.3);
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

    /* ═══════════════════════════════════════════════
       SEARCH & FILTER BOXES
    ═══════════════════════════════════════════════ */
    .joined-user-search-filter-box,
    .quizwinner-search-filter-box {
        width: 100%;
        display: flex;
        align-items: flex-end;
        gap: 10px;
    }

    .joined-user-search-filter-wrapper {
        width: 100%;
        display: grid;
        grid-template-columns: repeat(1, 1fr);
    }

    .quizwinner-search-filter-wrapper {
        width: 100%;
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
    }

    .joined-user-filter-btn,
    .quizwinner-filter-btn {
        display: flex;
        gap: 10px;
        white-space: nowrap;
    }

    .winner-user-box {
        display: flex;
        flex-direction: column;
        gap: 3px;
    }

    .joined-date-box {
        display: flex;
        flex-direction: column;
        gap: 5px;
        font-size: 13px;
        color: var(--ink, #111827);
        white-space: nowrap;
    }

    .joined-player-btn-box {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    /* ═══════════════════════════════════════════════
       MODALS STYLING
    ═══════════════════════════════════════════════ */
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

    .modal-table { border-collapse: collapse; }
    .modal-table th { font-size: 11px; font-weight: 700; color: var(--ink-faint, #6b7280); text-transform: uppercase; padding: 12px 16px; text-align: left; border-bottom: 1px solid var(--line, #e5e7eb); }
    .modal-table td { padding: 12px 16px; border-bottom: 1px solid var(--line, #f3f4f6); font-size: 13px; vertical-align: top; }
    
    .voucher-details-table { width: 100%; border-collapse: collapse; }
    .voucher-details-table tr { border-bottom: 1px solid var(--line, #f3f4f6); }
    .voucher-details-table tr:last-child { border-bottom: none; }
    .voucher-details-table th {
        text-align: left; padding: 14px 16px; font-size: 13px; font-weight: 600;
        color: var(--ink-faint, #6b7280); width: 35%; background: #F9FAFB;
    }
    .voucher-details-table td { padding: 14px 16px; font-size: 14px; color: var(--ink, #111827); font-weight: 500; }
    .voucher-code {
        background: #EEF2FF; color: #4F46E5; padding: 4px 10px; border-radius: 6px;
        font-family: monospace; font-size: 14px; font-weight: 600; letter-spacing: 0.5px;
    }
    
    .btn-view-voucher {
        display: inline-block; margin-top: 6px; padding: 4px 10px; font-size: 11px;
        font-weight: 600; color: #4F46E5; background: #EEF2FF; border: 1px solid #C7D2FE;
        border-radius: 6px; cursor: pointer; transition: all 0.2s;
    }
    .btn-view-voucher:hover { background: #4F46E5; color: white; border-color: #4F46E5; }

    /* Badges */
    .badge {
        display: inline-block; padding: 4px 10px; border-radius: 6px;
        font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.3px;
    }
    .badge.success { background: #dcfce7; color: #166534; }
    .badge.warn { background: #fef3c7; color: #92400e; }
    .badge.info { background: #dbeafe; color: #1e40af; }
    .badge.neutral { background: #f3f4f6; color: #4b5563; }
    .badge.danger { background: #fee2e2; color: #991b1b; }

    /* Responsive */
    @media(max-width: 1024px) {
        .quizwinner-search-filter-wrapper { grid-template-columns: repeat(2, 1fr); }
    }
    @media(max-width: 991px) {
        .joined-date-box { text-align: end; }
        .table-wrap-res-02, .table-wrap-res-01 { background: transparent !important; border: none !important; }
    }
    @media(max-width: 768px) {
        .stats-box-wrapper { grid-template-columns: repeat(2, 1fr); }
        .podium-row { grid-template-columns: 1fr; gap: 12px; }
        .podium-card.gold { margin-top: 0; order: -1; }
        .quizwinner-search-filter-box { flex-direction: column; align-items: stretch; }
        .quizwinner-search-filter-wrapper { grid-template-columns: repeat(1, 1fr); }
        .quizwinner-filter-btn { flex-direction: row; }
        .custom-tabs { flex-direction: column; }
        .custom-tab-link { border-bottom: 1px solid #e2e8f0; border-left: 3px solid transparent; justify-content: flex-start; }
        .custom-tab-link.active { border-left-color: #4f46e5; border-bottom: 1px solid #e2e8f0; }
    }
    @media(max-width: 575px) {
        .joined-user-search-filter-box { flex-direction: column; }
        .stats-box-wrapper { grid-template-columns: repeat(1, 1fr); }
        .joined-player-btn-box { flex-wrap: wrap; }
    }
</style>
@endpush

@section('content')
    <div class="panel active">
        <!-- ══════════════════════════════════════════ -->
        <!-- PAGE HEADER                                -->
        <!-- ══════════════════════════════════════════ -->
        <div class="page-head" style="display:flex; justify-content:space-between; align-items:center; flex-wrap: wrap; gap: 15px; margin-bottom: 15px;">
            <div>
                <h1>👥 Quiz Players & Winners</h1>
                <p>Joined players and winner leaderboard for: <strong>{{ $quiz->title }}</strong></p>
            </div>

            <div>
                @include('superadmin.quizzes.component.quiz-timer')
            </div>

            <div class="joined-player-btn-box">
                @if(!empty($canShowUpdatePlayDataBtn))
                <button type="button" id="updateQuizPlayBtn" data-quiz-id="{{ $quiz->id }}"
                    data-visibility="{{ $quiz->visibility }}"
                    data-complete-status="{{ $quiz->complete_status ?? '' }}"
                    data-url="{{ route('superadmin.quizzes.play.update-data', $quiz->id) }}"
                    data-scheduled-at="{{ $quiz->scheduled_at }}"
                    data-answer-end-time="{{ $answerEndTime ? $answerEndTime->toISOString() : '' }}"
                    class="btn btn-danger"
                    style="{{ !empty($showUpdatePlayDataBtn) ? 'display: inline-block;' : 'display: none !important;' }}background-color: #dc3545; color: white; border: none; padding: 8px 16px; border-radius: 5px; cursor: pointer; font-weight: 600;">
                    🔄 Update Quiz Play Data
                </button>
                @endif

                <a href="{{ route('superadmin.quizzes.index') }}" class="btn btn-ghost">← Back to Quizzes</a>
            </div>
        </div>

        {{-- SUCCESS / ERROR MESSAGE CONTAINER --}}
        <div id="updateResponseMsg" style="display:none; margin: 15px 0; padding: 12px; border-radius: 5px; font-weight: 500;"></div>

        <!-- ══════════════════════════════════════════ -->
        <!-- 2 TABS NAVIGATION                          -->
        <!-- ══════════════════════════════════════════ -->
        <div class="custom-tabs-wrapper">
            <ul class="custom-tabs" id="quizTabs">
                <li class="nav-item" role="presentation" style="flex: 1;">
                    <a class="custom-tab-link active" data-target="joined-tab" href="#joined">
                        👥 1. Joined Players 
                        <span class="badge info" style="margin-left: 6px; font-size: 11px;">{{ $totalJoinedCount ?? 0 }}</span>
                    </a>
                </li>
                <li class="nav-item" role="presentation" style="flex: 1;">
                    <a class="custom-tab-link" data-target="winners-tab" href="#winners">
                        🏆 2. Quiz Winners 
                        <span class="badge success" style="margin-left: 6px; font-size: 11px;">{{ $winners->total() ?? 0 }}</span>
                    </a>
                </li>
            </ul>

            <div class="custom-tab-content">
                <!-- ══════════════════════════════════════════ -->
                <!-- TAB 1: JOINED PLAYERS                     -->
                <!-- ══════════════════════════════════════════ -->
                <div class="custom-tab-pane active" id="joined-tab">
                    
                    {{-- Stats Boxes --}}
                    <div class="stats-box-wrapper">
                        <!-- Box 1: Joined Users -->
                        <div class="stat-box joined-box">
                            <div class="stat-icon">👤</div>
                            <div class="stat-content">
                                <div class="stat-label">Joined Users</div>
                                <div class="stat-value">{{ $totalJoinedCount ?? 0 }}</div>
                            </div>
                        </div>

                        <!-- Box 2: Played Users -->
                        <div class="stat-box played-box">
                            <div class="stat-icon">🎮</div>
                            <div class="stat-content">
                                <div class="stat-label">Played Users</div>
                                <div class="stat-value">{{ $totalPlayedCount ?? 0 }}</div>
                            </div>
                        </div>

                        <!-- Box 3: Waiting Users -->
                        <div class="stat-box waiting-box">
                            <div class="stat-icon">⏳</div>
                            <div class="stat-content">
                                <div class="stat-label">Waiting</div>
                                <div class="stat-value">{{ $totalWaitingCount ?? 0 }}</div>
                            </div>
                        </div>

                        <!-- Box 4: Left Users -->
                        <div class="stat-box left-box">
                            <div class="stat-icon">🚪</div>
                            <div class="stat-content">
                                <div class="stat-label">Left Users</div>
                                <div class="stat-value">{{ $totalLeftCount ?? 0 }}</div>
                            </div>
                        </div>
                    </div>

                    {{-- Search & Filter Box for Joined Users --}}
                    <div class="search-filter-box" style="margin-bottom: 20px;">
                        <form method="GET" class="toolbar" action="{{ route('superadmin.quizzes.joined-users', $quiz->encrypted_id) }}">
                            <input type="hidden" name="tab" value="joined">
                            <div class="joined-user-search-filter-box">
                                <div class="joined-user-search-filter-wrapper">
                                    <div class="input-field">
                                        <label class="lbl" for="joined_search_input">Search Player</label>
                                        <input class="tb-input" id="joined_search_input" name="search" value="{{ ($activeTab === 'joined') ? request('search') : request('joined_search') }}"
                                            placeholder="Name, mobile, email…">
                                    </div>
                                </div>
                                <div class="joined-user-filter-btn">
                                    <button class="btn btn-primary" type="submit">Filter</button>
                                    <a href="{{ route('superadmin.quizzes.joined-users', ['quiz' => $quiz->encrypted_id, 'tab' => 'joined']) }}#joined" class="btn btn-ghost">Reset</a>
                                </div>
                            </div>
                        </form>
                    </div>

                    {{-- Data Table for Joined Users --}}
                    <div class="table-wrap table-wrap-res-02">
                        <div class="quiz-winners-table-box">
                            <table class="quiz-winners-table-wrapper">
                                <thead>
                                    <tr>
                                        <th style="width: 60px;">#</th>
                                        <th>User Details</th>
                                        <th>No Played</th>
                                        <th>Joined Date & Time</th>
                                        <th>Fifty-Fifty</th>
                                        <th>Switch Option</th>
                                        <th>Play Status</th>
                                        <th>Out Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($joinedUsers as $index => $joined)
                                        <tr>
                                            <td data-label="#" class="mono">
                                                {{ $joinedUsers->firstItem() + $index }}
                                            </td>

                                            <td data-label="User Details">
                                                <div class="winner-user-box">
                                                    <div style="font-weight: 600; color: var(--ink);">
                                                        {{ $joined->user?->name ?? 'Unknown User' }}</div>
                                                    <div style="font-size: 12px; color: var(--ink-faint);">
                                                        📱 {{ $joined->user?->mobile ?? 'No mobile' }}
                                                    </div>
                                                    <div style="font-size: 12px; color: var(--ink-faint);">
                                                        📅 {{ $joined->user?->created_at ? $joined->user->created_at->format('d M Y') : 'N/A' }}
                                                    </div>
                                                    <div style="font-size: 12px; color: var(--ink-faint);">
                                                        🕒 Last Login:
                                                        {{ $joined->user?->last_login_at?->diffForHumans() ?? '—' }}
                                                    </div>
                                                    @if ($joined->user?->last_login_at)
                                                        <span class="badge info" style="font-size:10px;">
                                                            Via {{ ucfirst($joined->user->login_type ?? 'web') }}
                                                        </span>
                                                    @endif
                                                </div>
                                            </td>
                                            
                                            <td data-label="Total Quizzes Joined" class="mono"> 
                                                <span class="badge success" style="font-size:15px;">
                                                    {{ $joined->user?->quiz_users_count ?? 0 }}
                                                </span>
                                            </td>

                                            <td data-label="Joined Date" class="mono">
                                                <div class="joined-date-box">
                                                    <span>
                                                        📅 {{ $joined->created_at ? $joined->created_at->format('d M Y') : 'N/A' }}
                                                    </span>
                                                    <span>
                                                        🕒 {{ $joined->created_at ? $joined->created_at->format('h:i A') : '' }}
                                                    </span>
                                                </div>
                                            </td>

                                            <td data-label="Fifty-Fifty" style="text-align: center;">
                                                @if ($joined->fifty_fifty == 1)
                                                    <span class="badge success">✅ Used</span>
                                                @else
                                                    <span class="badge neutral">❌ Not Used</span>
                                                @endif
                                            </td>

                                            <td data-label="Switch Option" style="text-align: center;">
                                                @if ($joined->switch_option == 1)
                                                    <span class="badge success">✅ Used</span>
                                                @else
                                                    <span class="badge neutral">❌ Not Used</span>
                                                @endif
                                            </td>

                                            <td data-label="Play Status" style="text-align: center;">
                                                @php
                                                    $ansCount = $joined->answer_count ?? 0;
                                                    $targetThreshold = ($quiz->visibility === 'fastest_finger_first') ? 1 : 10;
                                                @endphp

                                                @if ($ansCount == 0)
                                                    <span class="badge warn">
                                                        📌 Joined
                                                    </span>
                                                @elseif($ansCount < $targetThreshold)
                                                    <span class="badge" style="background: #fee2e2; color: #991b1b;">
                                                        🎮 Played (<span style="color: #dc2626; font-weight: 800;">{{ $ansCount }}</span> Ans)
                                                    </span>
                                                @else
                                                    <span class="badge success">
                                                        🎮 Played (<span style="color: #166534; font-weight: 800;">{{ $ansCount }}</span> Ans)
                                                    </span>
                                                @endif
                                            </td>

                                            <td data-label="Out Status" style="text-align: center;">
                                                @php
                                                    $ansCount = $joined->answer_count ?? 0;
                                                    $rawOutStatus = $joined->out_status ?? null;

                                                    if ($rawOutStatus === 'out') {
                                                        $displayStatus = 'out';
                                                    } elseif ($ansCount > 0 || $rawOutStatus === 'in') {
                                                        $displayStatus = 'in';
                                                    } else {
                                                        $displayStatus = 'waiting';
                                                    }
                                                @endphp
                                                @if ($displayStatus === 'in')
                                                    <span class="badge success" style="font-weight: 700;">
                                                        🟢 In
                                                    </span>
                                                @elseif ($displayStatus === 'out')
                                                    <span class="badge" style="background: #fee2e2; color: #991b1b; font-weight: 700;" title="{{ $joined->remark ?? 'Out of quiz' }}">
                                                        🔴 Out
                                                        @if(!empty($joined->remark))
                                                            <br><small style="font-size: 10px; font-weight: 500; opacity: 0.85;">{{ Str::limit($joined->remark, 25) }}</small>
                                                        @endif
                                                    </span>
                                                @else
                                                    <span class="badge warn" style="font-weight: 700;">
                                                        ⏳ Waiting
                                                    </span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" style="text-align: center; color: var(--ink-faint); padding: 40px 0;">
                                                No players have joined this quiz yet.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        {{-- Pagination for Joined Users --}}
                        <div class="pagination">
                            @include('common.pagination.superadmin_pagination', ['pageData' => $joinedUsers, 'paginationTab' => 'joined', 'paginationPageName' => 'joined_page'])
                        </div>
                    </div>
                </div>

                <!-- ══════════════════════════════════════════ -->
                <!-- TAB 2: WINNERS                           -->
                <!-- ══════════════════════════════════════════ -->
                <div class="custom-tab-pane" id="winners-tab">
                    
                    @php
                        $firstWinner = $topWinners[1] ?? null;
                        $secondWinner = $topWinners[2] ?? null;
                        $thirdWinner = $topWinners[3] ?? null;
                    @endphp

                    {{-- Top 3 Podium Cards --}}
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
                                <div style="margin-top: 6px;">
                                    <span class="badge bg-info" style="color: #2563eb">Old: {{ $secondWinner->point_history->old_point }}</span>
                                    <span class="badge bg-primary" style="color: #16a34a">New: {{ $secondWinner->point_history->new_point }}</span>
                                </div>
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
                                    <div style="margin-top: 6px;">
                                        <span class="badge bg-info" style="color: #2563eb">Old: {{ $firstWinner->point_history->old_point }}</span>
                                        <span class="badge bg-primary" style="color: #16a34a">New: {{ $firstWinner->point_history->new_point }}</span>
                                    </div>
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
                                    <div style="margin-top: 6px;">
                                        <span class="badge bg-info" style="color: #2563eb">Old: {{ $thirdWinner->point_history->old_point }}</span>
                                        <span class="badge bg-primary" style="color: #16a34a">New: {{ $thirdWinner->point_history->new_point }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Search & Filter Box for Winners --}}
                    <div class="search-filter-box" style="margin-bottom: 20px;">
                        <form method="GET" class="toolbar" action="{{ route('superadmin.quizzes.joined-users', $quiz->encrypted_id) }}">
                            <input type="hidden" name="tab" value="winners">
                            <div class="quizwinner-search-filter-box">
                                <div class="quizwinner-search-filter-wrapper">
                                    <div class="input-field">
                                        <label class="lbl" for="winner_search_input">Search Player</label>
                                        <input class="tb-input search-field" id="winner_search_input" name="search" value="{{ ($activeTab === 'winners') ? request('search') : request('winner_search') }}" placeholder="Rank, Name, email, mobile…">
                                    </div>
                                    <div class="input-field">
                                        <label class="lbl" for="winner_status_select">Status</label>
                                        @php $currentWinnerStatus = ($activeTab === 'winners') ? request('status') : request('winner_status'); @endphp
                                        <select class="tb-select status-field" id="winner_status_select" name="status" onchange="this.form.submit()">
                                            <option value="">All Status</option>
                                            <option value="active" {{ $currentWinnerStatus == 'active' ? 'selected' : '' }}> Active </option>
                                            <option value="inactive" {{ $currentWinnerStatus == 'inactive' ? 'selected' : '' }}> Inactive </option>
                                        </select>
                                    </div>
                                </div>
                                <div class="quizwinner-filter-btn">
                                    <button class="btn btn-primary" type="submit">Filter</button>
                                    <a href="{{ route('superadmin.quizzes.joined-users', ['quiz' => $quiz->encrypted_id, 'tab' => 'winners']) }}#winners" class="btn btn-ghost">Reset</a>
                                </div>
                            </div>
                        </form>
                    </div>

                    {{-- Data Table for Winners --}}
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
                                                    <div style="font-size: 11px; color: var(--ink-faint);">
                                                        🕒 Last Login:
                                                        {{ $winner->user?->last_login_at?->diffForHumans() ?? '—' }}
                                                    </div>
                                                    @if ($winner->user?->last_login_at)
                                                        <span class="badge info" style="font-size:10px;">
                                                            Via {{ ucfirst($winner->user->login_type ?? 'web') }}
                                                        </span>
                                                    @endif
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
                                                                <div style="margin-top: 4px;">
                                                                    <span class="badge bg-info" style="color: #2563eb">Old: {{ $winner->point_history->old_point }}</span>
                                                                    <span class="badge bg-primary" style="color: #16a34a">New: {{ $winner->point_history->new_point }}</span>
                                                                </div>
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

                        {{-- Pagination for Winners --}}
                        <div class="pagination">
                            @include('common.pagination.superadmin_pagination', ['pageData' => $winners, 'paginationTab' => 'winners', 'paginationPageName' => 'winners_page'])
                        </div>
                    </div>
                </div>
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

{{-- SweetAlert2 CDN --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // ══════════════════════════════════════════
            // 1. TABS SWITCHING LOGIC
            // ══════════════════════════════════════════
            const tabLinks = document.querySelectorAll('.custom-tab-link');
            const tabPanes = document.querySelectorAll('.custom-tab-pane');

            function activateTab(targetId) {
                tabLinks.forEach(link => link.classList.remove('active'));
                tabPanes.forEach(pane => pane.classList.remove('active'));

                const targetLink = document.querySelector(`.custom-tab-link[data-target="${targetId}"]`);
                const targetPane = document.getElementById(targetId);

                if (targetLink && targetPane) {
                    targetLink.classList.add('active');
                    targetPane.classList.add('active');

                    const hashName = (targetId === 'winners-tab') ? 'winners' : 'joined';
                    history.replaceState(null, null, `#${hashName}`);
                }
            }

            tabLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const targetId = this.getAttribute('data-target');
                    activateTab(targetId);
                });
            });

            // Auto-activate tab based on Hash or URL Query Param
            const hash = window.location.hash.replace('#', '').toLowerCase();
            const queryTab = "{{ $activeTab ?? request('tab', '') }}".toLowerCase();

            if (hash === 'winners' || hash === 'winners-tab' || queryTab === 'winners') {
                activateTab('winners-tab');
            } else {
                activateTab('joined-tab');
            }

            // ══════════════════════════════════════════
            // 2. UPDATE PLAY DATA BUTTON (SWEETALERT)
            // ══════════════════════════════════════════
            const updateBtn = document.getElementById('updateQuizPlayBtn');

            if (updateBtn) {
                // If button is hidden waiting for quiz answer time to finish, automatically reveal when time expires
                const answerEndTimeStr = updateBtn.getAttribute('data-answer-end-time');
                if (answerEndTimeStr && updateBtn.style.display.indexOf('none') !== -1) {
                    const answerEndTimeMs = new Date(answerEndTimeStr).getTime();
                    const checkAnswerFinishTimer = setInterval(() => {
                        if (Date.now() >= answerEndTimeMs) {
                            updateBtn.style.removeProperty('display');
                            updateBtn.style.display = 'inline-block';
                            clearInterval(checkAnswerFinishTimer);
                        }
                    }, 1000);
                }

                updateBtn.addEventListener('click', function() {
                    const updateUrl = this.getAttribute('data-url');
                    const quizId = this.getAttribute('data-quiz-id');
                    const visibility = this.getAttribute('data-visibility');
                    const completeStatus = this.getAttribute('data-complete-status') || '';
                    const answerEndTimeVal = this.getAttribute('data-answer-end-time');

                    if (!['processing', 'completed'].includes(completeStatus) && answerEndTimeVal && new Date() < new Date(answerEndTimeVal)) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Wait!',
                            text: 'Quiz answer submission time has not finished yet.'
                        });
                        return;
                    }

                    Swal.fire({
                        title: 'Are you sure?',
                        html: `
                            <div style="text-align: left; font-size: 14px;">
                                This action will update:
                                <ul style="margin-top: 10px; margin-bottom: 10px;">
                                    <li>🏆 <b>Quiz Winners Table:</b> Recalculate ranks based on correct answers & time.</li>
                                    <li>📊 <b>Leaderboard History:</b> Update leaderboard points based on correct answers.</li>
                                    <li>💰 <b>User Wallets / Rewards:</b> Apply rank rewards if configured.</li>
                                    <li>⚡ <b>Cron Job History:</b> Mark status as <b>Success</b>.</li>
                                </ul>
                                <span style="color: #28a745; font-weight: bold;">Note: Ranks and points will be recalculated accurately.</span>
                            </div>
                        `,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#28a745',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Yes, Update Data!',
                        cancelButtonText: 'Cancel',
                        reverseButtons: true
                    }).then((result) => {
                        if (result.isConfirmed) {
                            Swal.fire({
                                title: 'Processing...',
                                text: 'Recalculating ranks and updating wallets. Please wait.',
                                allowOutsideClick: false,
                                didOpen: () => {
                                    Swal.showLoading();
                                }
                            });

                            fetch(updateUrl, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify({
                                    quiz_id: quizId
                                })
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Success!',
                                        text: data.message,
                                        confirmButtonColor: '#28a745'
                                    }).then(() => {
                                        window.location.reload();
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Failed!',
                                        text: data.message,
                                        confirmButtonColor: '#dc3545'
                                    });
                                }
                            })
                            .catch(error => {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error!',
                                    text: 'Something went wrong: ' + error.message,
                                    confirmButtonColor: '#dc3545'
                                });
                            });
                        }
                    });
                });
            }
        });

        // ══════════════════════════════════════════
        // 3. ANSWERS VIEW MODAL
        // ══════════════════════════════════════════
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
                                <td data-label="#" class="mono">${index + 1}</td>
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

        // ══════════════════════════════════════════
        // 4. VOUCHER MODAL FUNCTIONS
        // ══════════════════════════════════════════
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

            document.getElementById('v_name').textContent = details.name || 'N/A';
            document.getElementById('v_code').textContent = details.code || 'N/A';
            document.getElementById('v_point').textContent = (details.point ?? 0) + ' Points';
            document.getElementById('v_assign_date').textContent = details.assign_date || 'N/A';
            document.getElementById('v_expire_date').textContent = details.expire_date || 'N/A';
            document.getElementById('v_used_date').textContent = details.used_date || 'Not Used';

            const statusBadge = document.getElementById('v_status');
            statusBadge.textContent = details.status || 'Unknown';
            
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
