@extends('superadmin.layouts.app')
@section('title', 'Page Visitors Analytics')
<link rel="stylesheet" href="{{ asset('assets/css/custom.css') }}">

@push('styles')
<style>
    /* Top KPI Cards in a Single Row */
    .visitor-kpi-grid {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 14px;
        margin-bottom: 20px;
    }
    @media (max-width: 1200px) {
        .visitor-kpi-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }
    @media (max-width: 768px) {
        .visitor-kpi-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }
    @media (max-width: 480px) {
        .visitor-kpi-grid {
            grid-template-columns: 1fr;
        }
    }

    .visitor-stat-card {
        background: var(--surface, #ffffff);
        border: 1px solid var(--line, #e2e8f0);
        border-radius: 12px;
        padding: 14px 16px;
        box-shadow: 0 1px 4px rgba(0,0,0,0.02);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        min-width: 0;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .visitor-stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }
    .visitor-stat-top {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 6px;
    }
    .visitor-stat-label {
        font-size: 12px;
        font-weight: 600;
        color: var(--ink-soft, #64748b);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .visitor-stat-icon {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        flex-shrink: 0;
    }
    .visitor-stat-val {
        font-size: 22px;
        font-weight: 800;
        color: var(--ink, #0f172a);
        letter-spacing: -0.02em;
        line-height: 1.2;
    }
    .visitor-stat-sub {
        font-size: 11px;
        color: var(--ink-faint, #94a3b8);
        margin-top: 4px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* Live Pulse Badge */
    .badge-live-pulse {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 10px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 700;
    }
    .badge-live-pulse.active {
        background: #DCFCE7;
        color: #16A34A;
    }
    .badge-live-pulse.paused {
        background: #FEF3C7;
        color: #D97706;
    }
    .pulse-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background-color: currentColor;
        animation: pulse 1.5s infinite;
    }
    @keyframes pulse {
        0% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.4; transform: scale(1.3); }
        100% { opacity: 1; transform: scale(1); }
    }

    /* Single Row Filters Toolbar */
    .visitor-single-row-filter {
        background: var(--surface, #ffffff);
        border: 1px solid var(--line, #e2e8f0);
        border-radius: 12px;
        padding: 12px 16px;
        margin-bottom: 20px;
        box-shadow: 0 1px 4px rgba(0,0,0,0.02);
    }
    .visitor-filter-row {
        display: flex;
        align-items: flex-end;
        gap: 10px;
        flex-wrap: wrap;
    }
    @media (min-width: 1280px) {
        .visitor-filter-row {
            flex-wrap: nowrap;
        }
    }

    .filter-item {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }
    .filter-item label {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: var(--ink-soft, #64748b);
        margin: 0;
    }
    .filter-input {
        height: 38px;
        border-radius: 8px;
        border: 1px solid var(--line, #cbd5e1);
        padding: 0 12px;
        font-size: 12.5px;
        color: var(--ink, #1e293b);
        background: var(--paper, #f8fafc);
        outline: none;
        transition: border-color 0.2s, background 0.2s;
    }
    .filter-input:focus {
        border-color: var(--indigo, #4F46E5);
        background: #ffffff;
    }

    /* Preset Date Buttons Group */
    .filter-preset-group {
        display: inline-flex;
        background: var(--paper, #f1f5f9);
        padding: 3px;
        border-radius: 8px;
        border: 1px solid var(--line, #e2e8f0);
        gap: 2px;
        height: 38px;
        align-items: center;
        white-space: nowrap;
    }
    .filter-preset-btn {
        padding: 6px 11px;
        border-radius: 6px;
        font-size: 11.5px;
        font-weight: 600;
        text-decoration: none;
        color: var(--ink-soft, #64748b);
        transition: all 0.15s ease;
    }
    .filter-preset-btn.active {
        background: var(--indigo, #4F46E5);
        color: #ffffff;
        box-shadow: 0 1px 3px rgba(79, 70, 229, 0.3);
    }
    .filter-preset-btn:hover:not(.active) {
        color: var(--ink, #0f172a);
        background: rgba(0, 0, 0, 0.05);
    }
</style>
@endpush

@section('content')
<div class="panel active">
    <!-- Page Header -->
    <div class="page-head" style="margin-bottom: 18px;">
        <div>
            <h1 style="display: flex; align-items: center; gap: 12px; margin: 0;">
                Front Page Visitors & Analytics
                @if($isQuizLiveNow)
                    <span class="badge-live-pulse paused" title="Visitor tracking auto-paused during live quiz to reserve CPU">
                        <span class="pulse-dot"></span> ⏸️ Auto-Paused (Quiz Live)
                    </span>
                @else
                    <span class="badge-live-pulse active" title="Recording front visits in real-time">
                        <span class="pulse-dot"></span> 🟢 Tracking Active (Live)
                    </span>
                @endif
            </h1>
            <p style="margin-top: 4px; color: var(--ink-soft);">Real-time tracking of user visits across all front web pages with zero server-load quiz safety.</p>
        </div>
    </div>

    <!-- Top KPI Metric Cards (Single Row) -->
    <div class="visitor-kpi-grid">
        <div class="visitor-stat-card">
            <div class="visitor-stat-top">
                <span class="visitor-stat-label">Total Visits</span>
                <span class="visitor-stat-icon" style="background: #E0E7FF; color: #4338CA;">📊</span>
            </div>
            <div class="visitor-stat-val">{{ number_format($totalVisitsAllTime) }}</div>
            <div class="visitor-stat-sub">All-time front page hits</div>
        </div>

        <div class="visitor-stat-card">
            <div class="visitor-stat-top">
                <span class="visitor-stat-label">Today's Visits</span>
                <span class="visitor-stat-icon" style="background: #DCFCE7; color: #16A34A;">📅</span>
            </div>
            <div class="visitor-stat-val">{{ number_format($todayVisits) }}</div>
            <div class="visitor-stat-sub">Recorded today</div>
        </div>

        <div class="visitor-stat-card">
            <div class="visitor-stat-top">
                <span class="visitor-stat-label">Unique Today</span>
                <span class="visitor-stat-icon" style="background: #FEF3C7; color: #D97706;">👤</span>
            </div>
            <div class="visitor-stat-val">{{ number_format($todayUniqueVisitors) }}</div>
            <div class="visitor-stat-sub">Distinct IP addresses</div>
        </div>

        <div class="visitor-stat-card">
            <div class="visitor-stat-top">
                <span class="visitor-stat-label">Active Live (15m)</span>
                <span class="visitor-stat-icon" style="background: #FCE7F3; color: #DB2777;">🟢</span>
            </div>
            <div class="visitor-stat-val">{{ number_format($liveActiveVisitors) }}</div>
            <div class="visitor-stat-sub">Last 15 minutes</div>
        </div>

        <div class="visitor-stat-card">
            <div class="visitor-stat-top">
                <span class="visitor-stat-label">Devices</span>
                <span class="visitor-stat-icon" style="background: #E0F2FE; color: #0284C7;">📱</span>
            </div>
            <div class="visitor-stat-val" style="font-size: 16px; font-weight: 700; line-height: 1.3;">
                📱 {{ number_format($deviceStats['Mobile'] ?? 0) }}
                <span style="font-size: 12px; font-weight: 500; color: var(--ink-faint);">| 💻 {{ number_format($deviceStats['Desktop'] ?? 0) }}</span>
            </div>
            <div class="visitor-stat-sub">Mobile vs Desktop</div>
        </div>
    </div>

    <!-- Unified Single Row Filter Toolbar (Search + Date Presets + Dropdowns + Submit in 1 Row) -->
    <div class="visitor-single-row-filter">
        <form method="GET" action="{{ route('superadmin.visitors.index') }}" class="visitor-filter-row">
            <!-- 1. Search Field -->
            <div class="filter-item" style="flex: 1.5; min-width: 200px;">
                <label for="search">Search</label>
                <input class="filter-input" type="text" name="search" value="{{ $search }}" placeholder="Search page, user, mobile, IP, browser…">
            </div>

            <!-- 2. Date Presets (All Time, Today, Yesterday, Last 7 Days) -->
            <div class="filter-item" style="flex-shrink: 0;">
                <label>Date Filter</label>
                <div class="filter-preset-group">
                    <a href="{{ route('superadmin.visitors.index', array_merge(request()->except(['preset', 'date', 'page']))) }}"
                       class="filter-preset-btn {{ empty($preset) && empty($dateFilter) ? 'active' : '' }}">All Time</a>
                    <a href="{{ route('superadmin.visitors.index', array_merge(request()->except(['page']), ['preset' => 'today', 'date' => ''])) }}"
                       class="filter-preset-btn {{ $preset === 'today' ? 'active' : '' }}">Today</a>
                    <a href="{{ route('superadmin.visitors.index', array_merge(request()->except(['page']), ['preset' => 'yesterday', 'date' => ''])) }}"
                       class="filter-preset-btn {{ $preset === 'yesterday' ? 'active' : '' }}">Yesterday</a>
                    <a href="{{ route('superadmin.visitors.index', array_merge(request()->except(['page']), ['preset' => '7days', 'date' => ''])) }}"
                       class="filter-preset-btn {{ $preset === '7days' ? 'active' : '' }}">Last 7 Days</a>
                </div>
            </div>

            <!-- Hidden preset field if currently set -->
            @if($preset)
                <input type="hidden" name="preset" value="{{ $preset }}">
            @endif

            <!-- 3. Page Name Dropdown Filter -->
            <div class="filter-item" style="flex: 1; min-width: 140px;">
                <label for="page_filter">Page</label>
                <select name="page_filter" class="filter-input" onchange="this.form.submit()">
                    <option value="">All Pages</option>
                    @foreach($allPageNames as $pName)
                        <option value="{{ $pName }}" {{ $pageFilter === $pName ? 'selected' : '' }}>{{ $pName }}</option>
                    @endforeach
                </select>
            </div>

            <!-- 4. Device Filter Dropdown -->
            <div class="filter-item" style="min-width: 110px;">
                <label for="device">Device</label>
                <select name="device" class="filter-input" onchange="this.form.submit()">
                    <option value="">All Devices</option>
                    <option value="Mobile" {{ $deviceFilter === 'Mobile' ? 'selected' : '' }}>📱 Mobile</option>
                    <option value="Desktop" {{ $deviceFilter === 'Desktop' ? 'selected' : '' }}>💻 Desktop</option>
                    <option value="Tablet" {{ $deviceFilter === 'Tablet' ? 'selected' : '' }}>📟 Tablet</option>
                </select>
            </div>

            <!-- 5. Specific Date Picker -->
            <div class="filter-item" style="min-width: 130px;">
                <label for="date">Specific Date</label>
                <input class="filter-input" type="date" name="date" value="{{ $dateFilter }}" onchange="this.form.submit()">
            </div>

            <!-- 6. Action Buttons -->
            <div class="filter-item" style="flex-shrink: 0;">
                <label>&nbsp;</label>
                <div style="display: flex; gap: 6px;">
                    <button class="btn btn-primary" type="submit" style="height: 38px; padding: 0 16px; border-radius: 8px;">Filter</button>
                    <a href="{{ route('superadmin.visitors.index') }}" class="btn btn-ghost" style="height: 38px; padding: 0 12px; border-radius: 8px; display: inline-flex; align-items: center;">Reset</a>
                </div>
            </div>
        </form>
    </div>

    <!-- Detailed Live Visitors Table -->
    <div class="table-wrap table-wrap-res-01">
        <div class="audit-logs-table-box" style="overflow-x: auto;">
            <table class="dash-table audit-logs-table-wrapper" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr>
                        <th style="padding: 12px 14px; text-align: left;">User Identity</th>
                        <th style="padding: 12px 14px; text-align: left;">Page Visited</th>
                        <th style="padding: 12px 14px; text-align: left;">Device & Platform</th>
                        <th style="padding: 12px 14px; text-align: left;">Browser</th>
                        <th style="padding: 12px 14px; text-align: left;">IP Address</th>
                        <th style="padding: 12px 14px; text-align: left;">Date & Time</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($visitors as $v)
                    <tr style="border-bottom: 1px solid var(--line, #e2e8f0);">
                        <!-- User Identity -->
                        <td data-label="User" style="padding: 12px 14px;">
                            @if($v->user)
                                <div style="font-weight: 700; color: var(--indigo, #4F46E5);">
                                    👤 {{ $v->user->name ?? 'User #' . $v->user->id }}
                                </div>
                                <div style="font-size: 11px; color: var(--ink-faint);">
                                    📞 {{ $v->user->mobile ?? $v->user->email ?? 'ID: ' . $v->user->id }}
                                </div>
                            @else
                                <span style="display: inline-flex; align-items: center; gap: 4px; font-size: 12px; color: var(--ink-faint); background: var(--paper, #f1f5f9); padding: 3px 8px; border-radius: 6px;">
                                    👤 Guest Visitor
                                </span>
                            @endif
                        </td>

                        <!-- Page Visited -->
                        <td data-label="Page" style="padding: 12px 14px;">
                            <div style="font-weight: 700; color: var(--ink, #1e293b);">
                                📄 {{ $v->page_name }}
                            </div>
                            <div style="font-size: 11px; color: var(--ink-faint); max-width: 260px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $v->page_url }}">
                                <a href="{{ $v->page_url }}" target="_blank" style="color: inherit; text-decoration: underline;">
                                    {{ Str::limit($v->page_url, 40) }}
                                </a>
                            </div>
                        </td>

                        <!-- Device & Platform -->
                        <td data-label="Device" style="padding: 12px 14px;">
                            <span style="font-size: 12px; font-weight: 600;">
                                @if($v->device_type === 'Mobile') 📱 @elseif($v->device_type === 'Tablet') 📟 @else 💻 @endif
                                {{ $v->device_type ?? 'Desktop' }}
                            </span>
                            <div style="font-size: 11px; color: var(--ink-faint);">
                                {{ $v->platform ?? 'Unknown OS' }}
                            </div>
                        </td>

                        <!-- Browser -->
                        <td data-label="Browser" style="padding: 12px 14px;">
                            <span class="badge" style="background: #F1F5F9; color: #334155; font-size: 12px; font-weight: 600; padding: 3px 8px; border-radius: 6px;">
                                🌐 {{ $v->browser ?? 'Other' }}
                            </span>
                        </td>

                        <!-- IP Address -->
                        <td data-label="IP Address" style="padding: 12px 14px; font-family: monospace; font-size: 12px; color: var(--indigo, #4F46E5);">
                            {{ $v->ip_address ?: 'Unknown' }}
                        </td>

                        <!-- Date & Time -->
                        <td data-label="Time" style="padding: 12px 14px;">
                            <div style="font-weight: 600; font-size: 12px;">
                                {{ $v->created_at ? $v->created_at->format('d M Y, h:i:s A') : '-' }}
                            </div>
                            <div style="font-size: 11px; color: var(--ink-faint);">
                                {{ $v->created_at ? $v->created_at->diffForHumans() : '' }}
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 40px 20px; color: var(--ink-faint);">
                            <div style="font-size: 32px; margin-bottom: 8px;">👥</div>
                            <div style="font-weight: 600;">No page visits recorded yet for this selection.</div>
                            <div style="font-size: 12px; margin-top: 4px;">Live visits from front web pages will appear here in real-time.</div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="pagination" style="margin-top: 16px;">
            @include('common.pagination.superadmin_pagination', ['pageData' => $visitors])
        </div>
    </div>
</div>
@endsection
