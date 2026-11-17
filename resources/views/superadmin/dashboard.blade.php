@extends('superadmin.layouts.app')
@section('title', 'Dashboard')
<link rel="stylesheet" href="{{ asset('assets/css/custom.css') }}">
@push('styles')
    <style>
        /* Modern Dashboard Styling */
        .dashboard-container {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .dash-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            background: var(--surface);
            padding: 20px 24px;
            border-radius: 16px;
            border: 1px solid var(--line);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.02);
        }

        .dash-title-group h1 {
            font-family: var(--font-display);
            font-size: 24px;
            font-weight: 800;
            color: var(--ink);
            letter-spacing: -0.02em;
        }

        .dash-title-group p {
            font-size: 13px;
            color: var(--ink-soft);
            margin-top: 4px;
        }

        .dash-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .time-selector {
            background: var(--paper);
            border: 1px solid var(--line);
            border-radius: 10px;
            padding: 8px 14px;
            font-size: 13px;
            font-weight: 600;
            color: var(--ink);
            cursor: pointer;
            outline: none;
            transition: all 0.2s ease;
        }

        .time-selector:hover {
            border-color: var(--indigo);
        }

        .btn-notif {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #EDEBFC;
            color: var(--indigo);
            border: 1px solid #D9D5FA;
            padding: 8px 16px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
        }

        .btn-notif:hover {
            background: var(--indigo);
            color: #fff;
        }

        .btn-notif .notif-badge {
            background: var(--coral);
            color: #fff;
            font-size: 10px;
            font-weight: 800;
            padding: 2px 6px;
            border-radius: 999px;
            margin-left: 2px;
        }

        /* KPI Grid 5 columns */
        .kpi-5-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 16px;
        }

        @media (max-width: 1200px) {
            .kpi-5-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 768px) {
            .kpi-5-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 480px) {
            .kpi-5-grid {
                grid-template-columns: 1fr;
            }
        }

        .kpi-stat-card {
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: 16px;
            padding: 18px 20px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .kpi-stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.04);
        }

        .kpi-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .kpi-icon-wrapper {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .kpi-icon-wrapper.blue {
            background: #E0F2FE;
            color: #0284C7;
        }

        .kpi-icon-wrapper.green {
            background: #DCFCE7;
            color: #16A34A;
        }

        .kpi-icon-wrapper.purple {
            background: #F3E8FF;
            color: #9333EA;
        }

        .kpi-icon-wrapper.amber {
            background: #FEF3C7;
            color: #D97706;
        }

        .kpi-icon-wrapper.rose {
            background: #FFE4E6;
            color: #E11D48;
        }

        .kpi-title {
            font-size: 12px;
            font-weight: 700;
            color: var(--ink-soft);
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .kpi-val {
            font-family: var(--font-display);
            font-size: 24px;
            font-weight: 800;
            color: var(--ink);
            margin: 4px 0 6px;
        }

        .kpi-growth {
            font-family: var(--font-mono);
            font-size: 11px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 3px;
            padding: 2px 8px;
            border-radius: 6px;
        }

        .kpi-growth.up {
            background: #ECFDF5;
            color: #10B981;
        }

        /* Standard Grid Layouts */
        .grid-60-40 {
            display: grid;
            grid-template-columns: 3fr 2fr;
            gap: 20px;
        }

        .grid-60-40>* {
            min-width: 0;
        }

        .grid-50-50 {
             width: 100%;
            display: grid;
             grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
        }

        /* Cards */
        .dash-card {
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: 16px;
            padding: 20px;
            display: flex;
            flex-direction: column;
        }

        .dash-card-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--line);
        }

        .dash-card-title {
            font-family: var(--font-display);
            font-size: 15px;
            font-weight: 700;
            color: var(--ink);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Live Quizzes List */
        .live-quiz-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .live-quiz-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: var(--paper);
            padding: 10px 14px;
            border-radius: 10px;
            border: 1px solid var(--line);
        }

        .live-quiz-info {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            font-size: 13px;
        }

        .live-quiz-count {
            font-family: var(--font-mono);
            font-weight: 700;
            font-size: 13px;
            color: var(--indigo);
        }

        .live-players-summary {
            margin-top: 14px;
            background: linear-gradient(135deg, #4F46E5, #3628B0);
            color: #fff;
            padding: 14px;
            border-radius: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .live-players-summary span {
            font-weight: 600;
            font-size: 13px;
        }

        .live-players-summary b {
            font-family: var(--font-display);
            font-size: 20px;
        }

        /* Metrics List Styles */
        .metric-rows {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .metric-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 12px;
            border-radius: 8px;
            background: var(--paper);
            font-size: 13px;
        }

        .metric-row .label {
            color: var(--ink-soft);
            font-weight: 500;
        }

        .metric-row .value {
            font-family: var(--font-mono);
            font-weight: 700;
            color: var(--ink);
        }

        /* Top Players Leaderboard */
        .leader-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .leader-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 9px 12px;
            border-radius: 10px;
            background: var(--paper);
            border: 1px solid var(--line);
        }

        .leader-rank-name {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
            font-size: 13px;
        }

        .rank-badge {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 700;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
            background: #f3f4f6;
            color: #374151;
        }

        .rank-1 {
            background: linear-gradient(135deg, #fde68a, #fbbf24);
            color: #78350f;
            box-shadow: 0 4px 12px rgba(251, 191, 36, 0.4);
        }

        .rank-2 {
            background: linear-gradient(135deg, #e5e7eb, #9ca3af);
            color: #1f2937;
            box-shadow: 0 4px 8px rgba(148, 163, 184, 0.3);
        }

        .rank-3 {
            background: linear-gradient(135deg, #fed7aa, #fb923c);
            color: #7c2d12;
            box-shadow: 0 4px 8px rgba(251, 146, 60, 0.3);
        }

        .rank-num {
            background: #f1f5f9;
            color: #94a3b8;
            border: 2px dashed #e2e8f0;
        }

        .stat-mini.reward {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 12px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 700;
            background: #ede9fe;
            color: #6d28d9;
            border: 1px solid #c4b5fd;
        }

        .empty-rank-row {
            background: #fafbfc;
            opacity: 0.6;
        }

        .empty-rank-row:hover {
            opacity: 0.85;
        }

        .empty-rank-row td {
            color: #94a3b8 !important;
            font-style: italic;
        }

        .blank-badge {
            display: inline-block;
            padding: 6px 14px;
            background: #f1f5f9;
            color: #94a3b8;
            border-radius: 8px;
            font-size: 12px;
            font-style: italic;
            border: 1px dashed #cbd5e1;
        }

        .user-points-display {
            display: inline-flex;
            align-items: baseline;
            gap: 3px;
        }

        .user-points-display b {
            color: var(--indigo);
            font-size: 15px;
            font-weight: 800;
        }

        .users-table-wrapper tbody tr {
            transition: all 0.2s;
        }

        .users-table-wrapper tbody tr:not(.empty-rank-row):hover {
            background: linear-gradient(90deg, rgba(124, 58, 237, 0.02) 0%, rgba(79, 70, 229, 0.05) 100%);
        }

        /* Table Styling */
        .dash-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .dash-table th {
            background: var(--paper);
            padding: 10px 14px;
            font-size: 11px;
            font-weight: 700;
            color: var(--ink-faint);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            text-align: left;
            border-bottom: 1px solid var(--line);
        }

        .dash-table td {
            padding: 12px 14px;
            font-size: 13px;
            border-bottom: 1px solid var(--line);
        }

        .dash-table tr:last-child td {
            border-bottom: none;
        }

        /* Progress bar meters */
        .progress-meter {
            width: 100%;
            height: 8px;
            background: #E5E7EB;
            border-radius: 999px;
            overflow: hidden;
            margin-top: 4px;
        }

        .progress-bar-fill {
            height: 100%;
            border-radius: 999px;
            transition: width 0.5s ease;
        }

        .fill-indigo {
            background: var(--indigo);
        }

        .fill-emerald {
            background: var(--emerald);
        }

        .fill-gold {
            background: var(--gold);
        }

        .fill-coral {
            background: var(--coral);
        }

        .fill-cyan {
            background: #06B6D4;
        }

        /* Ad performance top bar */
        .ad-metrics-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin-bottom: 16px;
        }

        .ad-metric-box {
            background: var(--paper);
            border: 1px solid var(--line);
            padding: 12px 14px;
            border-radius: 12px;
            text-align: center;
        }

        .ad-metric-box label {
            font-size: 11px;
            font-weight: 700;
            color: var(--ink-faint);
            text-transform: uppercase;
        }

        .ad-metric-box b {
            display: block;
            font-family: var(--font-display);
            font-size: 18px;
            font-weight: 800;
            color: var(--ink);
            margin-top: 2px;
        }

        /* Recent activity timeline */
        .activity-timeline {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .activity-item {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 8px 12px;
            border-radius: 10px;
            background: var(--paper);
            border-left: 3px solid var(--indigo);
            font-size: 13px;
        }

        .activity-time {
            font-family: var(--font-mono);
            font-size: 11px;
            font-weight: 700;
            color: var(--ink-faint);
            min-width: 45px;
        }

        .activity-desc {
            color: var(--ink);
            font-weight: 500;
        }

        /* System alert badges */
        .alert-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 14px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
        }

        .alert-item.danger {
            background: #FEF2F2;
            color: #DC2626;
            border: 1px solid #FCA5A5;
        }

        .alert-item.warning {
            background: #FFFBEB;
            color: #D97706;
            border: 1px solid #FCD34D;
        }

        .alert-item.success {
            background: #ECFDF5;
            color: #059669;
            border: 1px solid #6EE7B7;
        }

        /* Animated Winner Card with Golden/Green Border Ring */
        .animated-winner-card {
            position: relative;
            overflow: hidden;
            border: none;
            box-shadow: 0 5px 15px rgba(16, 185, 129, 0.1);
            z-index: 1;
        }

        .animated-winner-card::before {
            content: "";
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: conic-gradient(transparent, transparent, transparent, #10B981, #F59E0B, #10B981, transparent, transparent);
            animation: spin-glow 4s linear infinite;
            z-index: -2;
        }

        .animated-winner-card::after {
            content: "";
            position: absolute;
            inset: 2px;
            background: linear-gradient(135deg, #fafafa 0%, #f1f5f9 50%, #fafafa 100%);
            background-size: 200% 200%;
            animation: gray-shift 6s ease infinite;
            border-radius: 14px;
            z-index: -1;
        }

        .animated-winner-card>* {
            position: relative;
            z-index: 1;
        }

        @keyframes gray-shift {
            0% {
                background-position: 0% 50%;
            }

            50% {
                background-position: 100% 50%;
            }

            100% {
                background-position: 0% 50%;
            }
        }

        @keyframes spin-glow {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        /* responsive css start  */


        @media (max-width: 992px) {

            .grid-60-40,
            .grid-50-50 {
                grid-template-columns: repeat(1,1fr);
            }

             .grid-50-50 {
        grid-template-columns: minmax(0, 1fr);
        width: 100%;
    }

    .grid-50-50 > .dash-card {
        width: 100%;
    }
        }

        @media (max-width: 767px) {
            .grid-60-40 {
                grid-template-columns: 1fr;
            }
        }

        @media(max-width:575px) {
            .ad-metrics-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .popular-quiz-table-box {
                overflow: visible !important;
            }

            .popular-quiz-table-wrapper thead {
                display: none;
            }

            .popular-quiz-table-wrapper,
            .popular-quiz-table-wrapper tbody,
            .popular-quiz-table-wrapper tr {
                display: block;
                width: 100%;
            }

            .popular-quiz-table-wrapper tr {
                padding: 10px 12px;
                margin-bottom: 10px;
                border: 1px solid var(--line);
                border-radius: 10px;
            }

            .popular-quiz-table-wrapper td {
                display: flex;
                justify-content: space-between;
                padding: 6px 0;
                border: 0;
            }

            .popular-quiz-table-wrapper td::before {
                content: attr(data-label);
                color: #9299bb;
                font-weight: 600;
            }
        }

        @media(max-width:400px) {
            .dash-actions {
                width: 100%;
                flex-direction: column;
            }

            .time-selector {
                width: 100%;
            }

            .dash-actions .btn-notif {
                width: 100%;
                justify-content: space-between;
            }
        }
    </style>
@endpush

@section('content')

    <div class="dashboard-container">

        <!-- ================= HEADER SECTION ================= -->
        <div class="dash-header">
            <div class="dash-title-group">
                <h1>FSRQuiz Dashboard</h1>
                <p>Welcome back, <strong>Super Admin</strong> 👋</p>
            </div>
            <div class="dash-actions">
                {{-- <select class="time-selector" id="timePeriodFilter">
                <option value="today" selected>Today ▼</option>
                <option value="yesterday">Yesterday</option>
                <option value="7days">Last 7 Days</option>
                <option value="month">This Month</option>
            </select> --}}
                <button class="btn-notif"
                    onclick="showAlert('info', 'Notifications', 'You have 3 new system notifications.')">
                    🔔 Notifications <span class="notif-badge">3</span>
                </button>
            </div>
        </div>

        <!-- ================= TOP 5 KPI CARDS ================= -->
        <div class="kpi-5-grid">
            <!-- 👥 Users -->
            <div class="kpi-stat-card">
                <div class="kpi-top">
                    <span class="kpi-title">👥 Users</span>
                    <div class="kpi-icon-wrapper blue">👥</div>
                </div>
                <div class="kpi-val">{{ number_format($stats['total_users']) }}</div>
                {{-- <span class="kpi-growth up">↑ 12.5%</span> --}}
            </div>

            <!-- 🟢 Active -->
            <div class="kpi-stat-card">
                <div class="kpi-top">
                    <span class="kpi-title">🟢 Active</span>
                    <div class="kpi-icon-wrapper green">🟢</div>
                </div>
                <div class="kpi-val">{{ number_format($stats['active_users']) }}</div>
                {{-- <span class="kpi-growth up">↑ 8.4%</span> --}}
            </div>

            <!-- 📝 Quizzes -->
            <div class="kpi-stat-card">
                <div class="kpi-top">
                    <span class="kpi-title">📝 Quizzes</span>
                    <div class="kpi-icon-wrapper purple">📝</div>
                </div>
                <div class="kpi-val">{{ number_format($stats['total_quizzes']) }}</div>
                {{-- <span class="kpi-growth up">↑ 5.2%</span> --}}
            </div>

            <!-- ❓ Questions -->
            <div class="kpi-stat-card">
                <div class="kpi-top">
                    <span class="kpi-title">❓ Questions</span>
                    <div class="kpi-icon-wrapper amber">❓</div>
                </div>
                <div class="kpi-val">{{ number_format($stats['total_questions']) }}</div>
                {{-- <span class="kpi-growth up">↑ 7.1%</span> --}}
            </div>

            <!-- 🎮 Quiz Plays -->
            <div class="kpi-stat-card">
                <div class="kpi-top">
                    <span class="kpi-title">🎮 Quiz Plays</span>
                    <div class="kpi-icon-wrapper rose">🎮</div>
                </div>
                <div class="kpi-val">{{ number_format($stats['quiz_plays']) }}</div>
                <span class="kpi-growth up"> User Play - {{ number_format($stats['user_plays']) }}</span>
            </div>
        </div>

        <!-- ================= ROW 1: USER GROWTH & LIVE QUIZ ACTIVITY ================= -->
        <div class="grid-60-40">
            <!-- 📈 USER GROWTH -->
            <div class="dash-card">
                <div class="dash-card-head">
                    <div class="dash-card-title">📈 USER GROWTH</div>
                    <span class="badge info">Live Trends</span>
                </div>
                <div id="userGrowthChart" style="min-height: 230px;"></div>
            </div>

            <!-- 🎮 LIVE QUIZ ACTIVITY -->
            <div class="dash-card">
                <div class="dash-card-head" style="display: flex; gap:10px; flex-wrap:wrap;">
                    <div class="dash-card-title">🎮 LIVE QUIZ ACTIVITY</div>
                    <span class="badge success">Active Now</span>
                </div>
                <div class="live-quiz-list">
                    @foreach ($liveQuizzes as $lq)
                        <div class="live-quiz-item">
                            <div class="live-quiz-info">🟢 {{ $lq['title'] }}</div>
                            <div class="live-quiz-count">{{ number_format($lq['plays']) }}</div>
                        </div>
                    @endforeach
                </div>
                <div class="live-players-summary">
                    <span>👥 Live Players</span>
                    <b>{{ number_format($totalLivePlayers) }}</b>
                </div>
            </div>
        </div>

        <!-- ================= ROW 1.5: ACTIVE TOP PLAYERS ================= -->
        <div class="dash-card">
            <div class="dash-card-head" style="display: flex; gap:10px; flex-wrap:wrap;">
                <div class="dash-card-title">🏆 TOP PLAYERS</div>
                <a href="{{ route('superadmin.reports.user-quiz') }}" style="background: var(--indigo, #4F46E5); color: #fff; padding: 6px 14px; border-radius: 8px; font-size: 12px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 4px 6px rgba(79, 70, 229, 0.2); transition: all 0.2s ease;" onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 6px 8px rgba(79, 70, 229, 0.3)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 6px rgba(79, 70, 229, 0.2)';">View All <i class="ti ti-arrow-right" style="font-size: 13px;"></i></a>
            </div>
            <div class="top-players-table-box" style="overflow-x: auto;">
                <table class="dash-table op-players-table-wrapper">
                    <thead>
                        <tr>
                            <th class="ajax-sort-col" data-col="name" style="cursor:pointer; user-select:none;">User Name <span class="sort-icon">⇅</span></th>
                            <th class="ajax-sort-col" data-col="quizzes_played" style="cursor:pointer; user-select:none;">Quizzes Played <span class="sort-icon">↓</span></th>
                            <th class="ajax-sort-col" data-col="total_questions" style="cursor:pointer; user-select:none;">Total Question <span class="sort-icon">⇅</span></th>
                            <th class="ajax-sort-col" data-col="unanswered" style="cursor:pointer; user-select:none;">Unanswered <span class="sort-icon">⇅</span></th>
                            <th class="ajax-sort-col" data-col="correct_answers" style="cursor:pointer; user-select:none;">Correct Ans <span class="sort-icon">⇅</span></th>
                            <th class="ajax-sort-col" data-col="wrong_answers" style="cursor:pointer; user-select:none;">Wrong Ans <span class="sort-icon">⇅</span></th>
                            <th class="ajax-sort-col" data-col="wallet_balance" style="cursor:pointer; user-select:none;">Wallet Balance <span class="sort-icon">⇅</span></th>
                            <th class="ajax-sort-col" data-col="leader_board_point" style="cursor:pointer; user-select:none;">Leaderboard Point <span class="sort-icon">⇅</span></th>
                        </tr>
                    </thead>
                    <tbody id="activePlayersTbody">
                        @foreach ($topActivePlayers as $actPlayer)
                            <tr>
                                <td data-label="User Name"><strong>{{ $actPlayer->name ?? 'Unknown' }}</strong></td>
                                <td data-label="Quizzes Played"><span
                                        class="mono">{{ number_format($actPlayer->quizzes_played) }}</span></td>
                                <td data-label="Total Question"><span
                                        class="mono">{{ number_format($actPlayer->total_questions) }}</span></td>
                                <td data-label="Unanswered"><span class="badge info"
                                        style="font-size:11px;">{{ number_format($actPlayer->unanswered) }}</span></td>
                                <td data-label="Correct Ans"><span class="badge success"
                                        style="font-size:11px;">{{ number_format($actPlayer->correct_answers) }}</span>
                                </td>
                                <td data-label="Wrong Ans"><span class="badge danger"
                                        style="font-size:11px;">{{ number_format($actPlayer->wrong_answers) }}</span></td>
                                <td data-label="Wallet Balance"><span class="mono"
                                        style="color:var(--emerald); font-weight:700;">
                                        {{ number_format($actPlayer->wallet_balance ?? 0, 2) }}</span>
                                </td>
                                <td data-label="Leaderboard Point">
                                    <div class="user-points-display">
                                        <b>{{ number_format($actPlayer->leader_board_point) }}</b>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ================= ROW 2: QUIZ PERFORMANCE & TOP PLAYERS ================= -->
        <div class="grid-50-50">
            <!-- 📊 QUIZ PERFORMANCE -->
            <div class="dash-card">
                <div class="dash-card-head" style="display: flex; gap:10px; flex-wrap:wrap;">
                    <div class="dash-card-title">📊 QUIZ PERFORMANCE</div>
                    <span class="badge info">Analytics</span>
                </div>
                <div class="metric-rows">
                    <div class="metric-row">
                        <span class="label">Played</span>
                        <span class="value">{{ number_format($stats['quiz_played']) }}</span>
                    </div>
                    <div class="metric-row">
                        <span class="label">Completed</span>
                        <span class="value"
                            style="color: var(--emerald);">{{ number_format($stats['quiz_completed']) }}</span>
                    </div>
                    <div class="metric-row">
                        <span class="label">Abandoned</span>
                        <span class="value"
                            style="color: var(--coral);">{{ number_format($stats['quiz_abandoned']) }}</span>
                    </div>
                    <div class="metric-row">
                        <span class="label">Avg Score</span>
                        <span class="value">{{ $stats['avg_score'] }}</span>
                    </div>
                    <div class="metric-row">
                        <span class="label">Avg Time</span>
                        <span class="value">{{ $stats['avg_time'] }}</span>
                    </div>
                </div>
                <div style="margin-top: 14px;">
                    <div
                        style="display: flex; justify-content: space-between; font-size: 11px; font-weight: 700; color: var(--ink-soft); margin-bottom: 4px;">
                        <span>Completion Rate</span>
                        <span>{{ $stats['completion_rate'] }}%</span>
                    </div>
                    <div class="progress-meter">
                        <div class="progress-bar-fill fill-emerald" style="width: {{ $stats['completion_rate'] }}%;">
                        </div>
                    </div>
                </div>
            </div>

            <!-- 🏆 TOP PLAYERS -->
            <div class="dash-card">
                <div class="dash-card-head">
                    <div class="dash-card-title">🏆 Yearly Leaderboard</div>
                    {{-- <span class="badge warn">Leaderboard Marks</span> --}}
                </div>
                <div style="overflow-x: auto;">
                    <table class="dash-table users-table-wrapper">
                        <thead>
                            <tr>
                                <th style="width:70px; text-align:center;">Rank</th>
                                <th style="text-align:center;">Reward</th>
                                <th style="text-align:center;">Username</th>
                                <th style="text-align:center;">Leaderboard Point</th>
                                <th style="text-align:center;">Sponsored By</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($topPlayers as $entry)
                                @if ($entry->has_user)
                                    <tr data-entry-id="{{ $entry->lb_id }}">
                                        <td data-label="Rank" style="text-align:center;">
                                            @if ($entry->lb_rank == 1)
                                                <span class="rank-badge rank-1">🥇</span>
                                            @elseif($entry->lb_rank == 2)
                                                <span class="rank-badge rank-2">🥈</span>
                                            @elseif($entry->lb_rank == 3)
                                                <span class="rank-badge rank-3">🥉</span>
                                            @else
                                                <span class="rank-badge rank-num">#{{ $entry->lb_rank }}</span>
                                            @endif
                                        </td>
                                        <td data-label="Reward" style="text-align:center;">
                                            <span class="stat-mini reward">{{ number_format($entry->lb_points) }}</span>
                                        </td>
                                        <td data-label="Username" style="text-align:center;">
                                            <b>{{ $entry->name }}</b>
                                        </td>
                                        <td data-label="Leaderboard Point" style="text-align:center;">
                                            <div class="user-points-display">
                                                <b>{{ number_format($entry->leader_board_point) }}</b>
                                            </div>
                                        </td>
                                        <td data-label="Sponsored By" style="text-align:center;">
                                            <span class="badge warn" style="font-size:11px;">
                                                {{ $entry->sponsored_by ?? 'Chalo Online' }}
                                            </span>
                                        </td>
                                    </tr>
                                @else
                                    <tr class="empty-rank-row" data-entry-id="{{ $entry->lb_id }}">
                                        <td data-label="Rank" style="text-align:center;">
                                            <span class="rank-badge rank-num">#{{ $entry->lb_rank }}</span>
                                        </td>
                                        <td data-label="Reward" style="text-align:center;">
                                            <span
                                                style="color:#94a3b8; font-size:13px;">{{ number_format($entry->lb_points) }}</span>
                                        </td>
                                        <td data-label="Username" style="text-align:center;">
                                            <span class="blank-badge">🚫 No player assigned</span>
                                        </td>
                                        <td data-label="Leaderboard Point" style="text-align:center;">
                                            <span style="color:#94a3b8; font-size:13px;">0</span>
                                        </td>
                                        <td data-label="Sponsored By" style="text-align:center;">
                                            <span class="badge warn" style="font-size:11px;">
                                                {{ $entry->sponsored_by ?? 'Chalo Online' }}
                                            </span>
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ================= ROW 3: POPULAR QUIZZES & LAST QUIZ WINNERS ================= -->
        <div class="grid-50-50">
            <!-- 🔥 POPULAR QUIZZES -->
            <div class="dash-card">
                <div class="dash-card-head" style="display: flex; gap:10px; flex-wrap:wrap;">
                    <div class="dash-card-title">🔥 POPULAR QUIZZES</div>
                    <span class="badge info">Top Engagement</span>
                </div>
                <div class="popular-quiz-table-box" style="overflow-x: auto;">
                    <table class="dash-table popular-quiz-table-wrapper">
                        <thead>
                            <tr>
                                <th>Quiz</th>
                                <th>Plays</th>
                                <th>Completion</th>
                                <th>Avg Score</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($popularQuizzes as $pq)
                                <tr>
                                    <td data-label="Quiz"><strong>{{ $pq['title'] }}</strong></td>
                                    <td data-label="Plays"><span class="mono">{{ number_format($pq['plays']) }}</span>
                                    </td>
                                    <td data-label="Completion"><span
                                            class="badge success">{{ $pq['completion'] }}</span></td>
                                    <td data-label="Avg Score"><span class="mono">{{ $pq['avg_score'] }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 🏆 LAST QUIZ WINNERS -->
            <div class="dash-card animated-winner-card">
                <div class="dash-card-head" style="display: flex; gap:10px; flex-wrap:wrap;">
                    <div class="dash-card-title">🏆 WINNERS: {{ strtoupper($lastQuizTitle) }}</div>
                    <span class="badge success">Latest Quiz</span>
                </div>
                <div class="winner-list-scroll" style="max-height: 280px; overflow-y: auto;">
                    @if ($lastQuizWinners->count() > 0)
                    <div class="winner-test-table-box">
                        <table class="dash-table winner-test-table-wrapper">
                            <thead>
                                <tr>
                                    <th>Rank</th>
                                    <th>Player</th>
                                    <th>Score</th>
                                    <th>Time</th>
                                    <th>Reward</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($lastQuizWinners as $winner)
                                    <tr>
                                        <td>
                                            @if ($winner->rank == 1)
                                                <span class="rank-badge rank-1">🥇</span>
                                            @elseif($winner->rank == 2)
                                                <span class="rank-badge rank-2">🥈</span>
                                            @elseif($winner->rank == 3)
                                                <span class="rank-badge rank-3">🥉</span>
                                            @else
                                                <span class="rank-badge rank-num">{{ $winner->rank }}</span>
                                            @endif
                                        </td>
                                        <td><strong>{{ $winner->user->name ?? 'Unknown' }}</strong></td>
                                        <td><span
                                                class="mono">{{ $winner->total_correct_answer }}/{{ $winner->total_question }}</span>
                                        </td>
                                        <td><span class="mono">{{ $winner->total_time }}s</span></td>
                                        <td><span
                                                class="badge success">{{ $winner->reward_type == 'money' ? '₹' : '' }}{{ $winner->reward }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                        <div style="padding: 20px; text-align: center; color: var(--ink-faint);">
                            No winners found for the last quiz.
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- ================= ROW 4: REVENUE & REDEEM REQUESTS ================= -->
        <div class="grid-50-50">
            <!-- 💰 REVENUE -->
            <div class="dash-card">
                <div class="dash-card-head">
                    <div class="dash-card-title">💰 REVENUE</div>
                    <span class="badge success">Earnings</span>
                </div>
                <div class="metric-rows">
                    <div class="metric-row">
                        <span class="label">Today</span>
                        <span class="value"
                            style="color: var(--emerald); font-size: 15px;">{{ number_format($stats['revenue_today']) }}</span>
                    </div>
                    <div class="metric-row">
                        <span class="label">This Month</span>
                        <span class="value"
                            style="color: var(--indigo); font-size: 15px;">{{ number_format($stats['revenue_month']) }}</span>
                    </div>
                    <div class="metric-row">
                        <span class="label">Transactions</span>
                        <span class="value">{{ number_format($stats['total_transactions']) }}</span>
                    </div>
                    <div class="metric-row">
                        <span class="label">Failed Payments</span>
                        <span class="value"
                            style="color: var(--coral);">{{ number_format($stats['failed_payments']) }}</span>
                    </div>
                </div>
            </div>

            <!-- 🎁 REDEEM REQUESTS -->
            <div class="dash-card">
                <div class="dash-card-head" style="display: flex; gap:10px; flex-wrap:wrap;">
                    <div class="dash-card-title">🎁 REDEEM REQUESTS</div>
                    <span class="badge warn">Action Needed</span>
                </div>
                <div class="metric-rows">
                    <div class="metric-row">
                        <span class="label">Pending</span>
                        <span class="badge warn"
                            style="font-size: 12px;">{{ number_format($stats['redeem_pending']) }}</span>
                    </div>
                    <div class="metric-row">
                        <span class="label">Cancelled</span>
                        <span class="badge danger"
                            style="font-size: 12px;">{{ number_format($stats['redeem_cancelled']) }}</span>
                    </div>
                    <div class="metric-row">
                        <span class="label">Approved</span>
                        <span class="badge success"
                            style="font-size: 12px;">{{ number_format($stats['redeem_approved']) }}</span>
                    </div>
                    <div class="metric-row">
                        <span class="label">Rejected</span>
                        <span class="badge info"
                            style="font-size: 12px;">{{ number_format($stats['redeem_rejected']) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ================= ROW 5: AD PERFORMANCE (FULL WIDTH) ================= -->
        <div class="dash-card">
            <div class="dash-card-head" style="display: flex; gap:10px; flex-wrap:wrap;">
                <div class="dash-card-title">📢 AD PERFORMANCE</div>
                <span class="badge info">Monetization</span>
            </div>
            <div class="ad-metrics-grid">
                <div class="ad-metric-box">
                    <label>Impressions</label>
                    <b>{{ number_format($adStats['impressions']) }}</b>
                </div>
                <div class="ad-metric-box">
                    <label>Clicks</label>
                    <b>{{ number_format($adStats['clicks']) }}</b>
                </div>
                <div class="ad-metric-box">
                    <label>CTR</label>
                    <b style="color: var(--indigo);">{{ $adStats['ctr'] }}</b>
                </div>
                <div class="ad-metric-box">
                    <label>Ad Revenue</label>
                    <b style="color: var(--emerald);">₹{{ number_format($adStats['ad_revenue']) }}</b>
                </div>
            </div>
            <div>
                <div style="font-size: 12px; font-weight: 700; color: var(--ink-soft); margin-bottom: 10px;">📈 Ad
                    Performance Graph</div>
                <div id="adPerformanceChart" style="min-height: 200px;"></div>
            </div>
        </div>

        <!-- ================= ROW 6: AD PLACEMENTS & CAMPAIGN STATUS ================= -->
        <div class="grid-50-50">
            <!-- 📱 AD PLACEMENTS -->
            <div class="dash-card">
                <div class="dash-card-head" style="display: flex; gap:10px; flex-wrap:wrap;">
                    <div class="dash-card-title">📱 AD PLACEMENTS</div>
                    <span class="badge info">Distribution</span>
                </div>
                <div class="metric-rows">
                    @php
                        $colors = ['fill-indigo', 'fill-cyan', 'fill-gold', 'fill-emerald', 'fill-coral'];
                        $colorIdx = 0;
                    @endphp
                    @foreach ($adPlacements as $placement => $pct)
                        <div>
                            <div class="metric-row">
                                <span class="label">{{ $placement }}</span>
                                <span class="value">{{ $pct }}%</span>
                            </div>
                            <div class="progress-meter">
                                <div class="progress-bar-fill {{ $colors[$colorIdx % count($colors)] }}"
                                    style="width: {{ $pct }}%;"></div>
                            </div>
                        </div>
                        @php $colorIdx++; @endphp
                    @endforeach
                </div>
            </div>

            <!-- 📢 CAMPAIGN STATUS -->
            <div class="dash-card">
                <div class="dash-card-head" style="display: flex; gap:10px; flex-wrap:wrap;">
                    <div class="dash-card-title">📢 CAMPAIGN STATUS</div>
                    <span class="badge info">Active Campaigns</span>
                </div>
                <div class="metric-rows">
                    <div class="metric-row">
                        <span class="label">🟢 Live</span>
                        <span class="value" style="color: var(--emerald);">{{ $campaignStatus['live'] }}</span>
                    </div>
                    <div class="metric-row">
                        <span class="label">🟡 Scheduled</span>
                        <span class="value" style="color: var(--gold);">{{ $campaignStatus['scheduled'] }}</span>
                    </div>
                    <div class="metric-row">
                        <span class="label">⏸ Paused</span>
                        <span class="value" style="color: var(--ink-soft);">{{ $campaignStatus['paused'] }}</span>
                    </div>
                    <div class="metric-row">
                        <span class="label">🔴 Expired</span>
                        <span class="value" style="color: var(--coral);">{{ $campaignStatus['expired'] }}</span>
                    </div>
                    <div class="metric-row">
                        <span class="label">📝 Draft</span>
                        <span class="value">{{ $campaignStatus['draft'] }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ================= ROW 7: CATEGORY PERFORMANCE & LEVEL PERFORMANCE ================= -->
        <div class="grid-50-50">
            <!-- 📈 CATEGORY PERFORMANCE -->
            <div class="dash-card">
                <div class="dash-card-head" style="display: flex; gap:10px; flex-wrap:wrap;">
                    <div class="dash-card-title">📈 CATEGORY PERFORMANCE</div>
                    <span class="badge info">Categories</span>
                </div>
                <div class="metric-rows">
                    @php $colorIdx = 0; @endphp
                    @foreach ($categoryPlays as $cat => $pct)
                        <div>
                            <div class="metric-row">
                                <span class="label">{{ $cat }}</span>
                                <span class="value">{{ $pct }}%</span>
                            </div>
                            <div class="progress-meter">
                                <div class="progress-bar-fill {{ $colors[$colorIdx % count($colors)] }}"
                                    style="width: {{ $pct }}%;"></div>
                            </div>
                        </div>
                        @php $colorIdx++; @endphp
                    @endforeach
                </div>
            </div>

            <!-- 🎯 LEVEL PERFORMANCE -->
            <div class="dash-card">
                <div class="dash-card-head" style="display: flex; gap:10px; flex-wrap:wrap;">
                    <div class="dash-card-title">🎯 LEVEL PERFORMANCE</div>
                    <span class="badge info">Player Levels</span>
                </div>
                <div class="metric-rows">
                    @foreach ($levelPlays as $lvlName => $playCount)
                        <div class="metric-row">
                            <span class="label">{{ $lvlName }}</span>
                            <span class="value">{{ $playCount }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- ================= ROW 8: NOTIFICATIONS & SYSTEM ALERTS ================= -->
        <div class="grid-50-50">
            <!-- 🔔 NOTIFICATIONS -->
            <div class="dash-card">
                <div class="dash-card-head">
                    <div class="dash-card-title">🔔 NOTIFICATIONS</div>
                    <span class="badge info">Broadcasts</span>
                </div>
                <div class="metric-rows">
                    <div class="metric-row">
                        <span class="label">Sent Today</span>
                        <span class="value">{{ number_format($stats['notifications_sent']) }}</span>
                    </div>
                    <div class="metric-row">
                        <span class="label">Delivered</span>
                        <span class="value"
                            style="color: var(--emerald);">{{ number_format($stats['notifications_delivered']) }}</span>
                    </div>
                    <div class="metric-row">
                        <span class="label">Read</span>
                        <span class="value"
                            style="color: var(--indigo);">{{ number_format($stats['notifications_read']) }}</span>
                    </div>
                    <div class="metric-row">
                        <span class="label">Failed</span>
                        <span class="value"
                            style="color: var(--coral);">{{ number_format($stats['notifications_failed']) }}</span>
                    </div>
                </div>
            </div>

            <!-- ⚠️ SYSTEM ALERTS -->
            <div class="dash-card">
                <div class="dash-card-head">
                    <div class="dash-card-title">⚠️ SYSTEM ALERTS</div>
                    <span class="badge warn">System Health</span>
                </div>
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <div class="alert-item danger">
                        🔴 {{ number_format($stats['redeem_pending']) }} Redeem Pending
                    </div>
                    {{-- <div class="alert-item warning">
                    🟠 {{ number_format($stats['failed_payments']) }} Payment Failed
                </div> --}}
                    <div class="alert-item warning">
                        🟠 {{ number_format($stats['moderation_queue']) }} Questions Reported
                    </div>
                    {{-- <div class="alert-item success">
                    🟢 Database Backup OK
                </div> --}}
                </div>
            </div>
        </div>

        <!-- ================= ROW 9: RECENT ACTIVITY (FULL WIDTH) ================= -->
        <div class="dash-card">
            <div class="dash-card-head" style="display: flex; gap:10px; flex-wrap:wrap;">
                <div class="dash-card-title">📜 RECENT ACTIVITY</div>
                <span class="badge info">Realtime Log</span>
            </div>
            <div class="activity-timeline">
                @foreach ($auditLogs as $act)
                    <div class="activity-item">
                        <div class="activity-time">{{ $act['time'] }}</div>
                        <div class="activity-desc">{!! $act['desc'] !!}</div>
                    </div>
                @endforeach
            </div>
        </div>

    </div>

@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {

            // 1. User Growth Chart
            const userGrowthOptions = {
                series: [{
                    name: 'New Users',
                    data: {!! json_encode($chartUserCounts) !!}
                }],
                chart: {
                    type: 'area',
                    height: 220,
                    toolbar: {
                        show: false
                    },
                    fontFamily: 'Inter, sans-serif'
                },
                colors: ['#4F46E5'],
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.45,
                        opacityTo: 0.05,
                        stops: [0, 95, 100]
                    }
                },
                dataLabels: {
                    enabled: false
                },
                stroke: {
                    curve: 'smooth',
                    width: 3
                },
                xaxis: {
                    categories: {!! json_encode($chartDays) !!},
                    labels: {
                        style: {
                            colors: '#8E92AC',
                            fontSize: '11px'
                        }
                    },
                    axisBorder: {
                        show: false
                    }
                },
                yaxis: {
                    labels: {
                        style: {
                            colors: '#8E92AC',
                            fontSize: '11px'
                        }
                    }
                },
                grid: {
                    borderColor: '#E5E3F2',
                    strokeDashArray: 4
                }
            };
            new ApexCharts(document.querySelector("#userGrowthChart"), userGrowthOptions).render();

            // 2. Ad Performance Line Chart
            const adPerformanceOptions = {
                series: [{
                    name: 'Impressions',
                    data: {!! json_encode($adChartImpressions) !!}
                }],
                chart: {
                    type: 'line',
                    height: 180,
                    toolbar: {
                        show: false
                    },
                    fontFamily: 'Inter, sans-serif'
                },
                colors: ['#10B981'],
                stroke: {
                    curve: 'smooth',
                    width: 3
                },
                markers: {
                    size: 4,
                    colors: ['#10B981'],
                    strokeColors: '#fff',
                    strokeWidth: 2
                },
                xaxis: {
                    categories: {!! json_encode($adChartDays) !!},
                    labels: {
                        style: {
                            colors: '#8E92AC',
                            fontSize: '11px'
                        }
                    },
                    axisBorder: {
                        show: false
                    }
                },
                yaxis: {
                    labels: {
                        style: {
                            colors: '#8E92AC',
                            fontSize: '11px'
                        }
                    }
                },
                grid: {
                    borderColor: '#E5E3F2',
                    strokeDashArray: 4
                }
            };
            new ApexCharts(document.querySelector("#adPerformanceChart"), adPerformanceOptions).render();

            // 3. AJAX Sorting for Active Top Players
            let currentSort = 'quizzes_played';
            let currentDir = 'desc';

            document.querySelectorAll('.ajax-sort-col').forEach(col => {
                col.addEventListener('click', function() {
                    const column = this.getAttribute('data-col');
                    
                    if (currentSort === column) {
                        currentDir = currentDir === 'desc' ? 'asc' : 'desc';
                    } else {
                        currentSort = column;
                        currentDir = 'desc';
                    }

                    // Update icons
                    document.querySelectorAll('.ajax-sort-col .sort-icon').forEach(icon => {
                        icon.textContent = '⇅';
                    });
                    const icon = currentDir === 'desc' ? '↓' : '↑';
                    this.querySelector('.sort-icon').textContent = icon;

                    // Fetch data
                    fetch(`{{ route('superadmin.dashboard') }}?sort=${currentSort}&dir=${currentDir}`, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.players) {
                            const tbody = document.getElementById('activePlayersTbody');
                            tbody.innerHTML = '';
                            data.players.forEach(p => {
                                const tr = document.createElement('tr');
                                tr.innerHTML = `
                                    <td data-label="User Name"><strong>${p.name ?? 'Unknown'}</strong></td>
                                    <td data-label="Quizzes Played"><span class="mono">${new Intl.NumberFormat().format(p.quizzes_played)}</span></td>
                                    <td data-label="Total Question"><span class="mono">${new Intl.NumberFormat().format(p.total_questions)}</span></td>
                                    <td data-label="Unanswered"><span class="badge info" style="font-size:11px;">${new Intl.NumberFormat().format(p.unanswered)}</span></td>
                                    <td data-label="Correct Ans"><span class="badge success" style="font-size:11px;">${new Intl.NumberFormat().format(p.correct_answers)}</span></td>
                                    <td data-label="Wrong Ans"><span class="badge danger" style="font-size:11px;">${new Intl.NumberFormat().format(p.wrong_answers)}</span></td>
                                    <td data-label="Wallet Balance"><span class="mono" style="color:var(--emerald); font-weight:700;">${new Intl.NumberFormat().format(p.wallet_balance ?? 0, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span></td>
                                    <td data-label="Leaderboard Point">
                                        <div class="user-points-display">
                                            <b>${new Intl.NumberFormat().format(p.leader_board_point)}</b>
                                        </div>
                                    </td>
                                `;
                                tbody.appendChild(tr);
                            });
                        }
                    })
                    .catch(err => console.error(err));
                });
            });

        });
    </script>
@endpush
