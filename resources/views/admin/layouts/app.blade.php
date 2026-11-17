<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Admin Panel') — QuizHub AI</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link
        href="https://fonts.googleapis.com/css2?family=Sora:wght@600;700;800&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/css/custom.css') }}">
    <style>
        :root {
            --indigo: #4F46E5;
            --indigo-deep: #3628B0;
            --indigo-ink: #221A5C;
            --orange: #FB923C;
            --emerald: #10B981;
            --emerald-bg: #ECFDF5;
            --coral: #EF4444;
            --coral-bg: #FEF2F2;
            --gold: #F59E0B;
            --gold-bg: #FFFBEB;
            --ink: #10132B;
            --ink-soft: #5B5F7A;
            --ink-faint: #8E92AC;
            --paper: #F5F4FB;
            --surface: #FFFFFF;
            --line: #E5E3F2;
            --font-display: 'Sora', sans-serif;
            --font-body: 'Inter', sans-serif;
            --font-mono: 'JetBrains Mono', monospace;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html,
        body {
            height: 100%;
        }

        body {
            font-family: var(--font-body);
            background: var(--paper);
            color: var(--ink);
            font-size: 13.5px;
        }

        .shell {
            display: grid;
            grid-template-columns: 250px 1fr;
            grid-template-rows: 60px 1fr;
            height: 100vh;
        }

        /* ---------- Topbar ---------- */
        .topbar {
            grid-column: 1/3;
            background: var(--surface);
            border-bottom: 1px solid var(--line);
            display: flex;
            align-items: center;
            padding: 0 22px;
            gap: 18px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 9px;
            width: 228px;
        }

        .brand-mark {
            width: 30px;
            height: 30px;
            border-radius: 9px;
            background: linear-gradient(135deg, var(--indigo), var(--indigo-ink));
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-family: var(--font-display);
            font-weight: 800;
            font-size: 14px;
        }

        .brand span {
            font-family: var(--font-display);
            font-weight: 700;
            font-size: 14.5px;
        }

        .brand small {
            display: block;
            font-size: 9.5px;
            color: var(--ink-faint);
            font-weight: 600;
            letter-spacing: .05em;
            text-transform: uppercase;
        }

        .search-box {
            flex: 1;
            max-width: 420px;
            display: flex;
            align-items: center;
            gap: 8px;
            background: var(--paper);
            border: 1px solid var(--line);
            border-radius: 10px;
            padding: 8px 12px;
            color: var(--ink-faint);
            font-size: 12.5px;
        }

        .top-actions {
            margin-left: auto;
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .env-tag {
            font-family: var(--font-mono);
            font-size: 10px;
            font-weight: 700;
            background: var(--emerald-bg);
            color: var(--emerald);
            border: 1px solid #A7F3D0;
            padding: 4px 9px;
            border-radius: 999px;
        }

        .bell {
            position: relative;
            font-size: 16px;
            color: var(--ink-soft);
        }

        .bell::after {
            content: "";
            position: absolute;
            top: -2px;
            right: -2px;
            width: 7px;
            height: 7px;
            background: var(--coral);
            border-radius: 50%;
            border: 1.5px solid #fff;
        }

        .admin-chip {
            display: flex;
            align-items: center;
            gap: 8px;
            padding-left: 14px;
            border-left: 1px solid var(--line);
        }

        .admin-av {
            width: 28px;
            height: 28px;
            border-radius: 8px;
            background: linear-gradient(135deg, var(--indigo), #7C3AED);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: var(--font-display);
            font-weight: 700;
            font-size: 11px;
        }

        .admin-chip .who {
            font-size: 11.5px;
            font-weight: 700;
            line-height: 1.2;
        }

        .admin-chip .who small {
            display: block;
            font-weight: 500;
            color: var(--ink-faint);
            font-size: 9.5px;
        }

        /* ---------- Sidebar ---------- */
        .sidebar {
            background: var(--surface);
            border-right: 1px solid var(--line);
            padding: 16px 12px 20px;
            overflow-y: auto;
        }

        .side-group {
            margin-bottom: 16px;
        }

        .side-title {
            font-family: var(--font-mono);
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .09em;
            text-transform: uppercase;
            color: var(--ink-faint);
            padding: 0 10px;
            margin-bottom: 6px;
        }

        .side-link {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 10px;
            border-radius: 9px;
            font-size: 12.5px;
            font-weight: 600;
            color: var(--ink-soft);
            cursor: pointer;
            margin-bottom: 2px;
        }

        .side-link .ic {
            width: 18px;
            text-align: center;
            font-size: 13px;
        }

        .side-link:hover {
            background: var(--paper);
        }

        .side-link.active {
            background: #EDEBFC;
            color: var(--indigo);
        }

        .side-link .count {
            margin-left: auto;
            font-family: var(--font-mono);
            font-size: 9.5px;
            font-weight: 700;
            background: var(--coral);
            color: #fff;
            padding: 1px 6px;
            border-radius: 999px;
        }

        /* ---------- Main ---------- */
        .main {
            overflow-y: auto;
            padding: 20px 20px 20px;
        }

        .panel {
            display: none;
            animation: fade .15s ease;
        }

        .panel.active {
            display: block;
        }

        @keyframes fade {
            from {
                opacity: 0;
                transform: translateY(4px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .page-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 20px;
        }

        .page-head h1 {
            font-family: var(--font-display);
            font-size: 21px;
            font-weight: 800;
        }

        .page-head p {
            font-size: 12px;
            color: var(--ink-soft);
            margin-top: 3px;
            max-width: 560px;
        }

        .btn {
            font-family: var(--font-body);
            font-weight: 700;
            font-size: 12px;
            padding: 9px 16px;
            border-radius: 9px;
            border: 1px solid transparent;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .btn-primary {
            background: var(--indigo);
            color: #fff;
        }

        .btn-ghost {
            background: var(--surface);
            border-color: var(--line);
            color: var(--ink);
        }

        .btn-danger-ghost {
            background: var(--coral-bg);
            color: var(--coral);
            border-color: #FCA5A5;
        }

        /* KPI cards */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 14px;
            margin-bottom: 22px;
        }

        .kpi-card {
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: 14px;
            padding: 16px;
        }

        .kpi-card .lbl {
            font-size: 10.5px;
            font-weight: 700;
            color: var(--ink-faint);
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        .kpi-card b {
            display: block;
            font-family: var(--font-display);
            font-size: 23px;
            font-weight: 800;
            margin-top: 6px;
        }

        .kpi-card .delta {
            font-family: var(--font-mono);
            font-size: 10.5px;
            font-weight: 700;
            margin-top: 4px;
            display: inline-block;
        }

        .delta.up {
            color: var(--emerald);
        }

        .delta.down {
            color: var(--coral);
        }

        .delta.warn {
            color: var(--gold);
        }

        .grid-2 {
            display: grid;
            grid-template-columns: 1.4fr 1fr;
            gap: 16px;
            margin-bottom: 22px;
        }

        .card {
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: 16px;
            padding: 18px;
        }

        .card h3 {
            font-family: var(--font-display);
            font-size: 14px;
            font-weight: 700;
            margin-bottom: 14px;
        }

        /* Toolbar */
        .toolbar {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }

        .tb-input {
            border: 1px solid var(--line);
            background: var(--surface);
            border-radius: 9px;
            padding: 8px 12px;
            font-size: 12px;
            font-family: var(--font-body);
            min-width: 170px;
        }

        .tb-select {
            border: 1px solid var(--line);
            background: var(--surface);
            border-radius: 9px;
            padding: 8px 10px;
            font-size: 12px;
            font-family: var(--font-body);
            color: var(--ink-soft);
        }

        .tb-spacer {
            flex: 1;
        }

        /* Table */
        .table-wrap {
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: 16px;
            margin-bottom: 24px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead th {
            text-align: left;
            font-size: 10.5px;
            font-weight: 700;
            color: var(--ink-faint);
            text-transform: uppercase;
            letter-spacing: .04em;
            padding: 12px 16px;
            border-bottom: 1px solid var(--line);
            background: #FBFAFF;
        }

        tbody td {
            padding: 11px 16px;
            border-bottom: 1px solid var(--line);
            font-size: 12.5px;
            vertical-align: middle;
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        tbody tr:hover {
            background: #FAFAFE;
        }

        .cell-user {
            display: flex;
            align-items: center;
            gap: 9px;
        }

        .cell-av {
            width: 28px;
            height: 28px;
            border-radius: 8px;
            background: #EDEBFC;
            color: var(--indigo);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: var(--font-display);
            font-weight: 700;
            font-size: 11px;
            flex: 0 0 28px;
        }

        .cell-user b {
            display: block;
            font-size: 12px;
        }

        .cell-user span {
            font-size: 10.5px;
            color: var(--ink-faint);
        }

        .mono {
            font-family: var(--font-mono);
            font-size: 11.5px;
            color: var(--ink-soft);
        }

        .badge {
            display: inline-block;
            font-size: 10px;
            font-weight: 700;
            padding: 3px 9px;
            border-radius: 999px;
            text-transform: uppercase;
            letter-spacing: .02em;
        }

        .badge.success {
            background: var(--emerald-bg);
            color: var(--emerald);
        }

        .badge.warn {
            background: var(--gold-bg);
            color: var(--gold);
        }

        .badge.danger {
            background: var(--coral-bg);
            color: var(--coral);
        }

        .badge.neutral {
            background: var(--paper);
            color: var(--ink-soft);
        }

        .badge.info {
            background: #EDEBFC;
            color: var(--indigo);
        }

        /* .row-actions {
            display: flex;
            gap: 6px;
        } */

        .icon-btn {
            width: 26px;
            height: 26px;
            border-radius: 7px;
            border: 1px solid var(--line);
            background: var(--surface);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            cursor: pointer;
        }

        .icon-btn.danger {
            color: var(--coral);
            border-color: #FCA5A5;
        }

        /* Form */
        .form-card {
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: 16px;
            padding: 22px;
            margin-bottom: 26px;
        }

        .form-card .fh {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 18px;
        }

        .form-card .fh h3 {
            font-family: var(--font-display);
            font-size: 15px;
            font-weight: 700;
        }

        .form-card .fh span {
            font-size: 11px;
            color: var(--ink-faint);
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px 18px;
        }

        .field {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .field.full {
            grid-column: 1/3;
        }

        .field label {
            font-size: 11px;
            font-weight: 700;
            color: var(--ink-soft);
        }

        .field label .req {
            color: var(--coral);
        }

        .field label .hint {
            font-weight: 500;
            color: var(--ink-faint);
            text-transform: none;
            letter-spacing: 0;
        }

        .field input,
        .field select,
        .field textarea {
            border: 1px solid var(--line);
            border-radius: 9px;
            padding: 9px 11px;
            font-family: var(--font-body);
            font-size: 12.5px;
            background: var(--paper);
            color: var(--ink);
        }

        .field textarea {
            resize: vertical;
            min-height: 70px;
        }

        .field .desc {
            font-size: 10px;
            color: var(--ink-faint);
        }

        .form-foot {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
            border-top: 1px solid var(--line);
            padding-top: 16px;
        }

        a.side-link {
            text-decoration: none;
            color: var(--ink-soft);
        }

        a.side-link.active {
            color: var(--indigo);
        }

        /* --- ✨ CREATIVE TOP-RIGHT NOTIFICATION ENGINE --- */
        #alert-container {
            position: fixed;
            top: 24px;
            right: 24px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 14px;
            max-width: 380px;
            width: 100%;
        }

        .alert-toast {
            background: rgba(30, 41, 59, 0.85);
            backdrop-filter: blur(8px);
            color: #ffffff;
            padding: 16px;
            border-radius: 12px;
            display: flex;
            align-items: flex-start;
            gap: 14px;
            border: 1px solid rgba(255, 255, 255, 0.05);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3), 0 10px 10px -5px rgba(0, 0, 0, 0.2);
            position: relative;
            overflow: hidden;
            animation: slideInRight 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        .alert-toast.success {
            border-left: 5px solid #10b981;
            box-shadow: 0 0 20px rgba(16, 185, 129, 0.15);
        }

        .alert-toast.warning {
            border-left: 5px solid #f59e0b;
            box-shadow: 0 0 20px rgba(245, 158, 11, 0.15);
        }

        .alert-toast.error {
            border-left: 5px solid #ef4444;
            box-shadow: 0 0 20px rgba(239, 68, 68, 0.15);
        }

        .alert-toast.info {
            border-left: 5px solid #3b82f6;
            box-shadow: 0 0 20px rgba(59, 130, 246, 0.15);
        }

        .alert-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            margin-top: 2px;
        }

        .alert-content {
            flex-grow: 1;
        }

        .alert-title {
            font-weight: 700;
            font-size: 14px;
            margin-bottom: 3px;
            text-transform: capitalize;
        }

        .alert-toast.success .alert-title {
            color: #34d399;
        }

        .alert-toast.warning .alert-title {
            color: #fbbf24;
        }

        .alert-toast.error .alert-title {
            color: #f87171;
        }

        .alert-toast.info .alert-title {
            color: #60a5fa;
        }

        .alert-message {
            font-size: 13px;
            color: #cbd5e1;
            line-height: 1.4;
        }

        .alert-close {
            background: rgba(255, 255, 255, 0.05);
            border: none;
            color: #94a3b8;
            font-size: 14px;
            cursor: pointer;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }

        .alert-close:hover {
            background: rgba(255, 255, 255, 0.15);
            color: #ffffff;
        }

        .progress-bar {
            position: absolute;
            bottom: 0;
            left: 0;
            height: 3px;
            width: 100%;
            transform-origin: left;
            animation: shrinkProgress 4s linear forwards;
        }

        .alert-toast.success .progress-bar {
            background: #10b981;
        }

        .alert-toast.warning .progress-bar {
            background: #f59e0b;
        }

        .alert-toast.error .progress-bar {
            background: #ef4444;
        }

        .alert-toast.info .progress-bar {
            background: #3b82f6;
        }

        @keyframes slideInRight {
            from {
                transform: translateX(120%);
                opacity: 0;
            }

            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes fadeOutUp {
            to {
                transform: translateY(-20px);
                opacity: 0;
            }
        }

        @keyframes shrinkProgress {
            from {
                transform: scaleX(1);
            }

            to {
                transform: scaleX(0);
            }
        }

        .menu-toggle {
            display: none;
            width: 40px;
            height: 36px;
            border: none;
            background: #5b5ce2;
            color: #fff;
            border-radius: 8px;
            font-size: 18px;
            cursor: pointer;
        }

        .close-sidebar {
            display: none;
        }

        @media(max-width:991px) {
            .topbar .header-search-bar {
                display: none;
            }

            .shell {
                grid-template-columns: 1fr;
            }

            .menu-toggle {
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .sidebar {
                position: fixed;
                top: 0;
                left: -280px;
                width: 280px;
                height: 100vh;
                transition: .3s;
                z-index: 999;
            }

            .sidebar.active {
                left: 0;
            }

            .main {
                margin-left: 0;
                padding: 12px 12px 12px 12px;
            }

            a#closeSidebar {
                position: absolute;
                top: 0;
                right: 0;
                font-size: 18px;
                text-decoration: none;
                color: #fff;
                width: 20px;
                height: 20px;
                background: var(--indigo);
                display: flex;
                justify-content: center;
                align-items: center;
            }
        }

        @media(max-width:575px) {
            .topbar {
                padding: 0 10px;
                gap: 10px;
            }

            .user-name {
                display: none;
            }

            .admin-chip {
                padding-left: 0px;
                border-left: none;
            }

            .sidebar {
                width: 220px;
            }

            .env-tag {
                display: none;
            }

            .brand small {
                font-size: 10px;
            }

            .brand span {
                font-size: 12px;
            }
        }
    </style>
    @stack('styles')
</head>

<body>

    <div class="shell">
        <!-- ============ TOPBAR ============ -->
        @php
            $tenantId = getTenantId();
            $currentTenant = $tenantId ? \App\Models\Tenant::find($tenantId) : null;
            $tenantDomain = $currentTenant?->domain ?? $currentTenant?->sub_domain ?? request()->getHost();
            $tenantEmail = $currentTenant?->email ?? Auth::user()?->email ?? '—';
        @endphp
        <div class="topbar">
            <div class="brand">
                <button class="menu-toggle" id="menuToggle">
                    ☰
                </button>
                <div class="brand-mark">Q</div>
                <div>
                    <!-- <span>FSRQuiz Admin</span> -->
                    <span>{{ $tenantDomain }}</span>
                    <small style="display: flex; flex-direction: column; gap: 2px; line-height: 1.2; text-transform: none; margin-top: 2px;">
                        <!-- <span style="color: var(--indigo); font-weight: 600; font-size: 11px;">{{ $tenantDomain }}</span> -->
                        <span style="color: var(--ink-soft); font-weight: 500; font-size: 10.5px;">✉ {{ $tenantEmail }}</span>
                    </small>
                </div>
            </div>
            <div class="search-box header-search-bar">🔍 Search categories, quizzes, questions…</div>
            <div class="top-actions">
                <div class="env-tag">RETAILER ADMIN</div>
                <div class="bell">🔔</div>
                <div class="admin-chip">
                    <div class="admin-av">
                        {{-- {{ strtoupper(substr(session('user_name', Auth::user()->name ?? 'AD'), 0, 2)) }} --}}
                           {{ strtoupper(substr(Auth::user()->name ?? 'AD', 0, 2)) }}
                    </div>
                    <div class="who user-name">
                        {{-- {{ session('user_name', Auth::user()->name ?? 'Admin User') }}<small>{{ session('user_tenant_name', 'Tenant Panel') }}</small> --}}
                            {{ Auth::user()->name ?? 'Admin User' }}
                           <small>{{ $currentTenant?->name ?? 'Tenant Panel' }}</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============ SIDEBAR ============ -->
        <div class="sidebar">
            <a href="javascript:;" id="closeSidebar" class="close-sidebar">&times;</a>

            <div class="side-group">
                <div class="side-title">Overview</div>
                <a class="side-link {{ request()->routeIs('admin.dashboard*') ? 'active' : '' }}"
                    href="{{ route('admin.dashboard') }}"><span class="ic">◈</span>Dashboard</a>
            </div>

            <div class="side-group">
                <div class="side-title">Content</div>
                <a class="side-link {{ request()->routeIs('admin.categories*') ? 'active' : '' }}"
                    href="{{ route('admin.categories.index') }}"><span class="ic">🗂</span>Categories</a>

                <a class="side-link {{ request()->routeIs('admin.quiz-types*') ? 'active' : '' }}"
                    href="{{ route('admin.quiz-types.index') }}"><span class="ic">🏷️</span>Quiz Types</a>

                <a class="side-link {{ request()->routeIs('admin.questions*') ? 'active' : '' }}"
                    href="{{ route('admin.questions.index') }}"><span class="ic">❓</span>Questions</a>

                <a class="side-link {{ request()->routeIs('admin.quizzes*') ? 'active' : '' }}"
                    href="{{ route('admin.quizzes.index') }}"><span class="ic">📝</span>Quizzes</a>

                {{-- <a class="side-link {{ request()->routeIs('admin.survey*') ? 'active' : '' }}"
                    href="{{ route('admin.survey.index') }}"><span class="ic">📊</span>Survey</a> --}}
            </div>

            <div class="side-group">
                <div class="side-title">Management</div>
                <a class="side-link {{ request()->routeIs('admin.users*') ? 'active' : '' }}"
                    href="{{ Route::has('admin.users.index') ? route('admin.users.index') : '#' }}"><span class="ic">👤</span>Users</a>
                
                {{-- <a class="side-link {{ request()->routeIs('admin.redeem_requests.index') ? 'active' : '' }}"
                    href="{{ route('admin.redeem_requests.index') }}">
                    <span class="ic">📝</span> Redeem Request
                </a>     --}}
                <a class="side-link {{ request()->routeIs('admin.voucher*') ? 'active' : '' }}"
                    href="{{ Route::has('admin.voucher.index') ? route('admin.voucher.index') : '#' }}"><span class="ic">🎁</span>Vouchers @if (isset($redemptionsCount) && $redemptionsCount)
                        <span class="count">{{ $redemptionsCount }}</span>
                    @endif
                </a>
            </div>

            <div class="side-group">
                <div class="side-title">Account</div>
                <a class="side-link" href="{{ route('admin-logout') }}"><span class="ic">⚙️</span>Logout</a>
            </div>
        </div>

        <!-- ============ MAIN ============ -->
        <div class="main">
            {{-- Success Toast --}}
            @if (session('success'))
                <script>
                    document.addEventListener("DOMContentLoaded", function() {
                        showAlert('success', 'Success', @json(session('success')));
                    });
                </script>
            @endif

            {{-- Error Toast --}}
            @if (session('error'))
                <script>
                    document.addEventListener("DOMContentLoaded", function() {
                        showAlert('error', 'Error', @json(session('error')));
                    });
                </script>
            @endif

            {{-- Validation Errors Toast --}}
            @if ($errors->any())
                <script>
                    document.addEventListener("DOMContentLoaded", function() {
                        showAlert('error', 'Validation Error', @json($errors->first()));
                    });
                </script>
            @endif

            @yield('content')
        </div>
    </div>

    <script>
        const alertIcons = {
            success: '✅',
            error: '❌',
            warning: '⚠️',
            info: 'ℹ️'
        };

        function showAlert(type, title, message) {
            const container = document.getElementById('alert-container');
            const toast = document.createElement('div');
            toast.className = 'alert-toast ' + type;

            toast.innerHTML = `
                <div>${alertIcons[type]}</div>
                <div class="alert-content">
                    <div class="alert-title">${title}</div>
                    <div class="alert-message">${message}</div>
                </div>
                <button class="alert-close">&times;</button>
                <div class="progress-bar"></div>
            `;

            container.appendChild(toast);

            toast.querySelector('.alert-close').onclick = function() {
                removeToast(toast);
            };

            setTimeout(() => {
                removeToast(toast);
            }, 4000);
        }

        function removeToast(toast) {
            toast.style.animation = 'fadeOutUp .3s forwards';
            toast.addEventListener('animationend', () => {
                toast.remove();
            });
        }
    </script>

    <script>
    document.addEventListener("DOMContentLoaded", function () {

        const menu = document.getElementById("menuToggle");
        const sidebar = document.querySelector(".sidebar");
        const closeBtn = document.getElementById("closeSidebar");

        // Open sidebar
        menu.addEventListener("click", function (e) {
            e.stopPropagation();
            sidebar.classList.add("active");
        });

        // Close button
        closeBtn.addEventListener("click", function (e) {
            e.preventDefault();
            sidebar.classList.remove("active");
        });

        // Website par kahin bhi click → sidebar close
        document.addEventListener("click", function (e) {

            // Agar click sidebar ke andar nahi hua
            // aur hamburger par bhi nahi hua
            if (
                !sidebar.contains(e.target) &&
                !menu.contains(e.target)
            ) {
                sidebar.classList.remove("active");
            }

        });

    });
</script>

    <div id="alert-container"></div>
    @stack('scripts')
</body>

</html>
