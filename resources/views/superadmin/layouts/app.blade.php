<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Super Admin') — QuizHub AI</title>
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
            /* text-transform: uppercase; */
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
            background: var(--gold-bg);
            color: var(--gold);
            border: 1px solid #FDE68A;
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

        .side-link.user-setting-link {
            margin-left: 18px;
            font-size: 11.5px;
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

        .bar-chart {
            display: flex;
            align-items: flex-end;
            gap: 6px;
            height: 150px;
        }

        .bar-chart .bar {
            flex: 1;
            background: linear-gradient(180deg, var(--indigo), #8B85F0);
            border-radius: 6px 6px 2px 2px;
            position: relative;
        }

        .bar-chart .bar span {
            position: absolute;
            bottom: -18px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 8.5px;
            color: var(--ink-faint);
            font-family: var(--font-mono);
        }

        .donut-row {
            display: flex;
            align-items: center;
            gap: 18px;
        }

        .donut {
            width: 110px;
            height: 110px;
            border-radius: 50%;
            background: conic-gradient(var(--indigo) 0 42%, var(--orange) 42% 68%, var(--emerald) 68% 86%, var(--line) 86% 100%);
            position: relative;
        }

        .donut::after {
            content: "";
            position: absolute;
            inset: 18px;
            background: var(--surface);
            border-radius: 50%;
        }

        .legend {
            font-size: 11.5px;
        }

        .legend div {
            display: flex;
            align-items: center;
            gap: 7px;
            margin-bottom: 7px;
            font-weight: 600;
        }

        .legend i {
            width: 9px;
            height: 9px;
            border-radius: 3px;
            display: inline-block;
        }

        /* Toolbar */
        .toolbar {
            display: flex;
            gap: 10px;
            align-items: center;
            /* margin: 22px 0 12px; */
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
            overflow: hidden;
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
            white-space: nowrap;
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

        /* .pagination {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 16px;
            font-size: 11px;
            color: var(--ink-faint);
        } */

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

        .opt-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .opt-list .opt-row {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .opt-list .opt-row input[type=text] {
            flex: 1;
        }

        .opt-list .opt-row .radio {
            width: 16px;
            height: 16px;
            border-radius: 50%;
            border: 2px solid var(--line);
            flex: 0 0 16px;
        }

        .opt-list .opt-row.is-correct .radio {
            border-color: var(--emerald);
            background: var(--emerald);
        }

        .tag-pills {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }

        .tag-pill {
            background: var(--paper);
            border: 1px solid var(--line);
            font-size: 11px;
            font-weight: 600;
            padding: 6px 11px;
            border-radius: 999px;
            color: var(--ink-soft);
        }

        .tag-pill.on {
            background: var(--indigo);
            color: #fff;
            border-color: var(--indigo);
        }

        .form-foot {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
            border-top: 1px solid var(--line);
            padding-top: 16px;
        }

        /* Permission matrix */
        .matrix-wrap {
            overflow-x: auto;
        }

        .matrix {
            width: 100%;
            border-collapse: collapse;
            min-width: 900px;
        }

        .matrix th,
        .matrix td {
            border: 1px solid var(--line);
            padding: 9px 10px;
            text-align: center;
            font-size: 11px;
        }

        .matrix th {
            background: #FBFAFF;
            font-weight: 700;
        }

        .matrix td:first-child,
        .matrix th:first-child {
            text-align: left;
            font-weight: 700;
            background: #FBFAFF;
            position: sticky;
            left: 0;
        }

        .matrix input[type=checkbox] {
            width: 14px;
            height: 14px;
        }

        .split-panel {
            display: grid;
            grid-template-columns: 1fr 1.2fr;
            gap: 16px;
        }

        .queue-item {
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 12px;
            margin-bottom: 10px;
            cursor: pointer;
        }

        .queue-item.active {
            border-color: var(--indigo);
            background: #FAFAFF;
        }

        .queue-item h5 {
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 3px;
        }

        .queue-item p {
            font-size: 10.5px;
            color: var(--ink-faint);
        }

        .section-label {
            font-family: var(--font-mono);
            font-size: 10.5px;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: var(--indigo-deep);
            margin: 26px 0 10px;
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
            /* Niche ki taraf naye alerts badhenge */
            gap: 14px;
            max-width: 380px;
            width: 100%;
        }

        .alert-toast {
            background: rgba(30, 41, 59, 0.85);
            /* Slick semi-transparent dark background */
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

        /* Type Specific Colors & Glow Effects */
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

        /* Typography & Structure */
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

        /* Timed Progress Bar Animation */
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

        /* Animations */
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

        /* deepak css  */

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
            /* margin-right:12px; */
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
                width: 30px;
                height: 30px;
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
        <div class="topbar">
            <div class="brand">
                <button class="menu-toggle" id="menuToggle">
                    ☰
                </button>
                <div class="brand-mark">Q</div>
                <div><span>FSRQuiz</span>
                    <small>support@chaloonline.in</small>
                </div>
            </div>
            {{-- <div class="search-box header-search-bar">🔍 Search users, questions, transactions, tickets…</div> --}}
            <div class="top-actions">
                <div class="env-tag">● {{ config('app.env') === 'production' ? 'Production' : 'Staging' }}</div>
                <div class="bell">🔔</div>
                <div class="admin-chip">
                    <div class="admin-av">{{ strtoupper(substr(auth()->user()->name ?? 'AD', 0, 2)) }}</div>
                    <div class="who user-name">{{ auth()->user()->name ?? 'Super Admin' }}<small>Super Admin</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============ SIDEBAR ============ -->
        <div class="sidebar">
            <a href="javascript:;" id="closeSidebar" class="close-sidebar">&times;</a>
            @php
                $link = fn($route, $label, $icon, $count = null) => [
                    'active' => request()->routeIs($route . '*'),
                    'href' => route($route . '.index'),
                    'label' => $label,
                    'icon' => $icon,
                    'count' => $count,
                ];
                $moderationCount = \App\Models\ModerationReport::where('status', 'pending')->count();
                $redemptionsCount = \App\Models\Redemption::where('status', 'pending')->count();
            @endphp

           <div class="side-group">
    <div class="side-title">Overview</div>

    <a class="side-link {{ request()->routeIs('superadmin.dashboard') ? 'active' : '' }}"
        href="{{ route('superadmin.dashboard') }}">
        <span class="ic">◈</span>Dashboard
    </a>
</div>


<div class="side-group">
    <div class="side-title">Content</div>

    <a class="side-link {{ request()->routeIs('superadmin.categories*') ? 'active' : '' }}"
        href="{{ route('superadmin.categories.index') }}">
        <span class="ic">🗂</span>Categories
    </a>

    <a class="side-link {{ request()->routeIs('superadmin.levels*') ? 'active' : '' }}"
        href="{{ route('superadmin.levels.index') }}">
        <span class="ic">⚡</span>Levels
    </a>

    <a class="side-link {{ request()->routeIs('superadmin.quiz-types*') ? 'active' : '' }}"
        href="{{ route('superadmin.quiz-types.index') }}">
        <span class="ic">🏷️</span>Quiz Types
    </a>

    <a class="side-link {{ request()->routeIs('superadmin.questions*') ? 'active' : '' }}"
        href="{{ route('superadmin.questions.index') }}">
        <span class="ic">❓</span>Questions
    </a>

    <a class="side-link {{ request()->routeIs('superadmin.quizzes*') ? 'active' : '' }}"
        href="{{ route('superadmin.quizzes.index') }}">
        <span class="ic">📝</span>Quizzes
    </a>

    <a class="side-link {{ request()->routeIs('superadmin.practice-quizzes*') ? 'active' : '' }}"
        href="{{ route('superadmin.practice-quizzes.index') }}">
        <span class="ic">📝</span>Practice Quizzes
    </a>

    <a class="side-link {{ request()->routeIs('superadmin.quiz.other-quizzes') ? 'active' : '' }}"
        href="{{ route('superadmin.quiz.other-quizzes') }}">
        <span class="ic">📋</span>Other Quizzes
    </a>

    <a class="side-link {{ request()->routeIs('superadmin.survey*') ? 'active' : '' }}"
        href="{{ route('superadmin.survey.index') }}">
        <span class="ic">📊</span>Survey
    </a>
</div>


<div class="side-group">
    <div class="side-title">People</div>

    <a class="side-link {{ request()->routeIs('superadmin.users*') ? 'active' : '' }}"
        href="{{ route('superadmin.users.index') }}">
        <span class="ic">👤</span>Users
    </a>

    <a class="side-link user-setting-link {{ request()->routeIs('superadmin.user-settings*') ? 'active' : '' }}"
        href="{{ route('superadmin.user-settings.index') }}">
        <span class="ic">⚙️</span>User Settings
    </a>

    <a class="side-link {{ request()->routeIs('superadmin.player.activity') ? 'active' : '' }}"
        href="{{ route('superadmin.player.activity') }}">
        <span class="ic">🏆</span>Player Activity
    </a>

    <a class="side-link {{ request()->routeIs('superadmin.users.whatsapp-notified*') ? 'active' : '' }}"
    href="{{ route('superadmin.users.whatsapp-notified') }}">
        <span class="ic">📱</span> WhatsApp Notified Users
    </a>

    <a class="side-link {{ request()->routeIs('superadmin.users.whatsapp-subscribe*') ? 'active' : '' }}"
    href="{{ route('superadmin.users.whatsapp-subscribe') }}">
        <span class="ic">📢</span> WhatsApp Subscribe Users
    </a>

    <a class="side-link {{ request()->routeIs('superadmin.tenants*') ? 'active' : '' }}"
        href="{{ route('superadmin.tenants.index') }}">
        <span class="ic">🏪</span>Retailer
    </a>

    {{-- <a class="side-link {{ request()->routeIs('superadmin.redeem_requests.index') ? 'active' : '' }}"
        href="{{ route('superadmin.redeem_requests.index') }}">
        <span class="ic">💸</span>Redeem Request
    </a> --}}

    <a class="side-link {{ request()->routeIs('superadmin.wallet-summary.index') ? 'active' : '' }}"
        href="{{ route('superadmin.wallet-summary.index') }}">
        <span class="ic">💳</span>Wallet Summary
    </a>

    <a class="side-link {{ request()->routeIs('superadmin.referralcodes.index') ? 'active' : '' }}"
        href="{{ route('superadmin.referralcodes.index') }}">
        <span class="ic">🔗</span>Referral Codes
    </a>

    <a class="side-link {{ request()->routeIs('superadmin.leaderboard*') ? 'active' : '' }}"
        href="{{ route('superadmin.leaderboard.index') }}">
        <span class="ic">🏆</span>Leaderboard
    </a>

    <a class="side-link {{ request()->routeIs('superadmin.quiz_user_feedback.index') ? 'active' : '' }}"
        href="{{ route('superadmin.quiz_user_feedback.index') }}">
        <span class="ic">🗣️</span>Quiz User Feedback
    </a>
</div>

<div class="side-group">
    <div class="side-title">News</div>

    <a class="side-link {{ request()->routeIs('superadmin.blogs.*') ? 'active' : '' }}"
        href="{{ route('superadmin.blogs.index') }}">
        <span class="ic">✍️</span> Blog Management
    </a>

    <a class="side-link {{ request()->routeIs('superadmin.news.*') ? 'active' : '' }}"
        href="{{ route('superadmin.news.index') }}">
        <span class="ic">📰</span> News Management
    </a>
</div>

<div class="side-group">
    <div class="side-title">Shopping Rewards</div>

     <!-- ✅ 1. E-Gift Forms (New) -->
    <a class="side-link {{ request()->routeIs('superadmin.e_gift_froms.index') ? 'active' : '' }}"
        href="{{ route('superadmin.e_gift_froms.index') }}">
        <span class="ic">📝</span> E-Gift From
    </a>

    <!-- ✅ 2. E-Gift Vouchers List -->
    <a class="side-link {{ request()->routeIs('superadmin.e_gift_voucher.index') ? 'active' : '' }}"
        href="{{ route('superadmin.e_gift_voucher.index') }}">
        <span class="ic">🎁</span> E-Gift Vouchers
    </a>

    <!-- ✅ 3. Voucher Requests -->
    {{-- <a class="side-link {{ request()->routeIs('superadmin.e_gift_voucher.requests*') ? 'active' : '' }}"
        href="{{ route('superadmin.e_gift_voucher.requests') }}">
        <span class="ic">📋</span>E-Gift Voucher Requests
    </a> --}}

    <a class="side-link {{ request()->routeIs('superadmin.voucher-redeem.index*') ? 'active' : '' }}"
        href="{{ route('superadmin.voucher-redeem.index') }}">
       <span class="ic">📋</span>All Redeem Request 
    </a>

   
</div>


<div class="side-group">
    <div class="side-title">Product</div>

    <a class="side-link {{ request()->routeIs('superadmin.product-brands.*') ? 'active' : '' }}"
        href="{{ route('superadmin.product-brands.index') }}">
        <span class="ic">🏷️</span>Product Brands
    </a>

    <a class="side-link {{ request()->routeIs('superadmin.product-categories.*') ? 'active' : '' }}"
        href="{{ route('superadmin.product-categories.index') }}">
        <span class="ic">📦</span>Product Categories
    </a>

    <a class="side-link {{ request()->routeIs('superadmin.product.*') ? 'active' : '' }}"
        href="{{ route('superadmin.product.index') }}">
        <span class="ic">🛍️</span>Products
    </a>
</div>


<div class="side-group">
    <div class="side-title">Promotions</div>

    <a class="side-link {{ request()->routeIs('superadmin.ad*') ? 'active' : '' }}"
        href="{{ route('superadmin.ad.index') }}">
        <span class="ic">📢</span>Ad
    </a>

    <a class="side-link {{ request()->routeIs('superadmin.banners*') ? 'active' : '' }}"
        href="{{ route('superadmin.banners.index') }}">
        <span class="ic">🖼️</span>Banner
    </a>

    <a class="side-link {{ request()->routeIs('superadmin.voucher*') ? 'active' : '' }}"
        href="{{ route('superadmin.voucher.index') }}">
        <span class="ic">🎟️</span>Vouchers

        @if ($redemptionsCount)
            <span class="count">{{ $redemptionsCount }}</span>
        @endif
    </a>
</div>


<div class="side-group">
    <div class="side-title">Engage</div>

    <a class="side-link {{ request()->routeIs('superadmin.notifications.quizHistory*') ? 'active' : '' }}"
        href="{{ route('superadmin.notifications.quizHistory') }}">
        <span class="ic">📣</span>Notifications History
    </a>

    <a class="side-link {{ request()->routeIs('superadmin.announcements*') ? 'active' : '' }}"
        href="{{ route('superadmin.announcements.index') }}">
        <span class="ic">📣</span>Announcement
    </a>

    <a class="side-link {{ request()->routeIs('superadmin.app-updations*') ? 'active' : '' }}"
        href="{{ route('superadmin.app-updations.index') }}">
        <span class="ic">📲</span>App Updation
    </a>
</div>


<div class="side-group">
    <div class="side-title">Reports & Insights</div>

    <a class="side-link {{ request()->routeIs('superadmin.reports.user-quiz*') ? 'active' : '' }}"
        href="{{ route('superadmin.reports.user-quiz') }}">
        <span class="ic">📊</span>User Quiz Report
    </a>

    <a class="side-link {{ request()->routeIs('superadmin.audit*') ? 'active' : '' }}"
        href="{{ route('superadmin.audit.index') }}">
        <span class="ic">📜</span>Audit Logs
    </a>

    <a class="side-link {{ request()->routeIs('superadmin.visitors*') ? 'active' : '' }}"
        href="{{ route('superadmin.visitors.index') }}">
        <span class="ic">👥</span>Page Visitors
    </a>
</div>


<div class="side-group">
    <div class="side-title">Settings</div>

    <a class="side-link {{ request()->routeIs('superadmin.quiz-settings*') ? 'active' : '' }}"
        href="{{ route('superadmin.quiz-settings.index') }}">
        <span class="ic">⚙️</span>Quiz Settings
    </a>

    {{-- <a class="side-link {{ request()->routeIs('superadmin.how-it-works*') ? 'active' : '' }}"
        href="{{ route('superadmin.how-it-works.index') }}">
        <span class="ic">❓</span>How It Works
    </a> --}}

    <a class="side-link {{ request()->routeIs('superadmin.privacy-policy*') ? 'active' : '' }}"
        href="{{ route('superadmin.privacy-policy.index') }}">
        <span class="ic">🛡️</span>Privacy Policy
    </a>

    <a class="side-link {{ request()->routeIs('superadmin.settings*') ? 'active' : '' }}"
        href="{{ route('superadmin.settings.index') }}">
        <span class="ic">⚙️</span>Settings
    </a>
   
    <a class="side-link {{ request()->routeIs('superadmin.apk-updates*') ? 'active' : '' }}"
        href="{{ route('superadmin.apk-updates.index') }}">
        <span class="ic">📱</span> APK Update
    </a>


    <a class="side-link" href="{{ route('superadmin.supper-admin-logout') }}">
        <span class="ic">🚪</span>Logout
    </a>
</div>
</div>

        <!-- ============ MAIN ============ -->
        <div class="main">
            {{-- @if (session('success'))
      <div class="badge success" style="display:block;padding:10px 14px;margin-bottom:16px;font-size:12px;">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
      <div class="badge danger" style="display:block;padding:10px 14px;margin-bottom:16px;font-size:12px;">
        <ul style="margin:0;padding-left:16px;">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
      </div>
    @endif --}}

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

    {{-- sidebar js start  --}}
    {{-- <script>
            document.addEventListener("DOMContentLoaded", function () {

            const menu = document.getElementById("menuToggle");
            const sidebar = document.querySelector(".sidebar");
            const closeBtn = document.getElementById("closeSidebar");
            // const overlay = document.querySelector(".sidebar-overlay");

            menu.addEventListener("click", function () {
                sidebar.classList.add("active");
            });

            closeBtn.addEventListener("click", function () {
                sidebar.classList.remove("active");
            });

        });
    </script> --}}

    <script>
        document.addEventListener("DOMContentLoaded", function() {

            const menu = document.getElementById("menuToggle");
            const sidebar = document.querySelector(".sidebar");
            const closeBtn = document.getElementById("closeSidebar");

            // Open sidebar
            menu.addEventListener("click", function(e) {
                e.stopPropagation();
                sidebar.classList.add("active");
            });

            // Close button
            closeBtn.addEventListener("click", function(e) {
                e.preventDefault();
                sidebar.classList.remove("active");
            });

            // Website par kahin bhi click → sidebar close
            document.addEventListener("click", function(e) {

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
