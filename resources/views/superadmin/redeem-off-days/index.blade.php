@extends('superadmin.layouts.app')

@section('title', 'Redeem Off Days')

@section('content')
<style>
    /* Clean Calendar Layout */
    .cal-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
        overflow: hidden;
        margin-bottom: 24px;
    }

    /* Top Control Header */
    .cal-header {
        padding: 16px 20px;
        border-bottom: 1px solid #e2e8f0;
        background: #fafbfc;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
    }

    .cal-title-section {
        display: flex;
        align-items: center;
        gap: 16px;
        flex-wrap: wrap;
    }

    .cal-title-section h2 {
        font-size: 18px;
        font-weight: 700;
        color: #0f172a;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    /* Filter Form */
    .filter-group {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: #ffffff;
        padding: 4px 8px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
    }

    .filter-select {
        border: none;
        outline: none;
        background: transparent;
        font-size: 13.5px;
        font-weight: 600;
        color: #1e293b;
        cursor: pointer;
        padding: 4px 2px;
    }

    /* Quick Action Buttons */
    .quick-actions {
        display: flex;
        align-items: center;
        gap: 6px;
        flex-wrap: wrap;
    }

    .btn-chip {
        background: #ffffff;
        border: 1px solid #cbd5e1;
        color: #475569;
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.15s ease;
    }

    .btn-chip:hover {
        background: #f1f5f9;
        color: #0f172a;
    }

    .btn-chip.sunday {
        color: #e11d48;
        border-color: #fecdd3;
        background: #fff5f5;
    }
    .btn-chip.sunday:hover {
        background: #fee2e2;
    }

    .btn-chip.festival {
        color: #d97706;
        border-color: #fde68a;
        background: #fffbeb;
    }
    .btn-chip.festival:hover {
        background: #fef3c7;
    }

    /* Calendar Grid */
    .cal-body {
        padding: 16px 20px 20px 20px;
    }

    .cal-responsive-grid-wrap {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    .cal-grid-inner {
        width: 100%;
        min-width: 320px;
    }

    .cal-weekdays {
        display: grid;
        grid-template-columns: repeat(7, minmax(0, 1fr)) !important;
        gap: 6px;
        margin-bottom: 6px;
        width: 100%;
    }

    .cal-weekday {
        text-align: center;
        font-size: 12px;
        font-weight: 700;
        color: #64748b;
        padding: 8px 2px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .cal-weekday.sun {
        color: #e11d48;
    }

    .cal-days-grid {
        display: grid;
        grid-template-columns: repeat(7, minmax(0, 1fr)) !important;
        gap: 6px;
        width: 100%;
    }

    .cal-empty {
        min-height: 64px;
        min-width: 0 !important;
        width: 100% !important;
    }

    /* Clean Day Box */
    .cal-day {
        position: relative;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        min-height: 64px;
        padding: 6px 7px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        cursor: pointer;
        transition: all 0.15s ease;
        user-select: none;
        min-width: 0 !important;
        width: 100% !important;
        box-sizing: border-box;
        overflow: hidden;
    }

    .cal-day:hover {
        border-color: #94a3b8;
        background: #f8fafc;
    }

    /* Sunday Day */
    .cal-day.is-sunday {
        background: #fffcfc;
        border-color: #fee2e2;
    }

    /* Blocked / Selected Off Day */
    .cal-day.is-blocked {
        background: #fef2f2 !important;
        border: 2px solid #ef4444 !important;
    }

    .day-top-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 2px;
        width: 100%;
        min-width: 0;
    }

    .day-num {
        font-size: 14.5px;
        font-weight: 700;
        color: #1e293b;
        line-height: 1;
        flex-shrink: 0;
    }

    .is-sunday .day-num {
        color: #e11d48;
    }

    .is-blocked .day-num {
        color: #991b1b;
    }

    .badge-off {
        display: none;
        font-size: 9px;
        font-weight: 800;
        color: #ffffff;
        background: #ef4444;
        padding: 1.5px 5px;
        border-radius: 4px;
        text-transform: uppercase;
        line-height: 1;
        white-space: nowrap;
        flex-shrink: 0;
        letter-spacing: 0.2px;
    }

    .is-blocked .badge-off {
        display: inline-block;
    }

    /* Festival Text in Box */
    .day-festival {
        font-size: 10px;
        font-weight: 600;
        color: #b45309;
        background: #fef3c7;
        padding: 2px 4px;
        border-radius: 4px;
        line-height: 1.2;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        display: block;
        width: 100% !important;
        max-width: 100% !important;
        box-sizing: border-box;
        margin-top: 4px;
    }

    /* Bottom Status & Save Bar */
    .cal-footer {
        padding: 14px 20px;
        border-top: 1px solid #e2e8f0;
        background: #fafbfc;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
    }

    .cal-legend {
        display: flex;
        align-items: center;
        gap: 14px;
        font-size: 12px;
        color: #64748b;
    }

    .legend-dot {
        display: inline-block;
        width: 8px;
        height: 8px;
        border-radius: 50%;
        margin-right: 4px;
    }

    .save-btn {
        background: #4f46e5;
        color: #ffffff;
        border: none;
        padding: 9px 22px;
        border-radius: 6px;
        font-weight: 600;
        font-size: 13.5px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: background 0.15s ease;
    }

    .save-btn:hover {
        background: #4338ca;
    }

    .cal-back-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 7px 14px;
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        color: #334155;
        font-size: 13px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.15s ease;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
    }

    .cal-back-btn:hover {
        background: #f1f5f9;
        color: #0f172a;
        border-color: #94a3b8;
    }

    /* Mobile Responsive Optimizations */
    @media (max-width: 768px) {
        .cal-back-btn {
            padding: 6px 12px;
            font-size: 12px;
        }

        .cal-header {
            padding: 12px 14px;
            flex-direction: column;
            align-items: stretch;
            gap: 10px;
        }

        .cal-title-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            gap: 6px;
        }

        .cal-title-section h2 {
            font-size: 14px;
        }

        .filter-group {
            padding: 2px 6px;
            gap: 4px;
        }

        .filter-select {
            font-size: 12px;
            padding: 2px 0;
        }

        .quick-actions {
            display: flex;
            flex-wrap: nowrap;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            gap: 6px;
            width: 100%;
            padding-bottom: 2px;
        }

        .btn-chip {
            font-size: 11px;
            padding: 5px 8px;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .cal-body {
            padding: 10px 8px;
        }

        .cal-weekdays {
            grid-template-columns: repeat(7, minmax(0, 1fr)) !important;
            gap: 3px;
            margin-bottom: 4px;
            width: 100%;
        }

        .cal-weekday {
            font-size: 10.5px;
            padding: 4px 1px;
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .cal-days-grid {
            grid-template-columns: repeat(7, minmax(0, 1fr)) !important;
            gap: 3px;
            width: 100%;
        }

        .cal-day {
            min-height: 52px;
            padding: 4px 3px;
            border-radius: 6px;
        }

        .cal-day.is-blocked {
            border-width: 1.5px !important;
        }

        .cal-empty {
            min-height: 52px;
        }

        .day-num {
            font-size: 12.5px;
        }

        .badge-off {
            padding: 1px 3px !important;
            font-size: 8px !important;
            border-radius: 3px;
            line-height: 1;
            letter-spacing: -0.2px;
        }

        .day-festival {
            font-size: 8px;
            padding: 1px 2px;
            margin-top: 2px;
            line-height: 1.1;
        }

        .cal-footer {
            padding: 12px 14px;
            flex-direction: column;
            align-items: stretch;
            gap: 12px;
        }

        .cal-legend {
            flex-wrap: wrap;
            gap: 6px 10px;
            font-size: 11px;
        }

        .save-footer-actions {
            display: flex;
            flex-direction: column;
            width: 100%;
            gap: 8px !important;
        }

        .status-select-wrap {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
        }

        .save-btn {
            width: 100%;
            justify-content: center;
            padding: 10px;
            font-size: 13.5px;
        }
    }
</style>

<div class="panel active">
    <!-- ══════════════════════════════════════════ -->
    <!-- PAGE HEADER                                -->
    <!-- ══════════════════════════════════════════ -->
    <div class="page-head" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; margin-bottom: 16px;">
        <div>
            <h1>🗓️ Redeem Request Off Days</h1>
            <p style="color: var(--ink-soft, #64748b); font-size: 13px; margin-top: 4px;">Configure dates when student redeem requests will be blocked.</p>
        </div>

        <div style="display: flex; gap: 10px; align-items: center;">
            <!-- ✅ Naya Settings Button -->
            <a href="{{ route('superadmin.redeem-voucher-settings.index') }}" class="btn btn-primary" style="text-decoration: none;">
                ⚙️ Redeem Voucher Settings
            </a>

            <!-- Purana Back Button -->
            <a href="{{ route('superadmin.quiz-settings.index') }}" class="btn btn-ghost" style="text-decoration: none;">
                ← Back to Quiz Settings
            </a>
        </div>
    </div>

    @if(session('success'))
        <div style="background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; padding: 10px 16px; border-radius: 8px; margin-bottom: 16px; font-weight: 600; font-size: 13.5px; display: flex; align-items: center; gap: 8px;">
            <span>✅</span> {{ session('success') }}
        </div>
    @endif

    {{-- MAIN UNIFIED CARD --}}
    <div class="cal-card">
        <!-- Clean Header with Month-Year Filters & Quick Actions -->
        <div class="cal-header">
            <div class="cal-title-section">
                <!-- Month & Year Jump Filter -->
                <form method="GET" action="{{ route('superadmin.redeem-off-days.index') }}" id="filterForm">
                    <div class="filter-group">
                        <span style="font-size: 11px; color: #64748b; font-weight: 600;">M:</span>
                        <select name="month" class="filter-select" onchange="document.getElementById('filterForm').submit()">
                            @foreach($months as $num => $name)
                                <option value="{{ $num }}" {{ $num == $currentMonth ? 'selected' : '' }}>
                                    {{ $name }}
                                </option>
                            @endforeach
                        </select>

                        <span style="color: #cbd5e1;">|</span>

                        <span style="font-size: 11px; color: #64748b; font-weight: 600;">Y:</span>
                        <select name="year" class="filter-select" onchange="document.getElementById('filterForm').submit()">
                            @for($y = (date('Y') - 1); $y <= (date('Y') + 5); $y++)
                                <option value="{{ $y }}" {{ $y == $currentYear ? 'selected' : '' }}>
                                    {{ $y }}
                                </option>
                            @endfor
                        </select>
                    </div>
                </form>
            </div>

            <!-- Quick Select Buttons -->
            <div class="quick-actions">
                <button type="button" class="btn-chip sunday" onclick="selectSundays()">
                    🔴 All Sundays
                </button>
                @if(count($festivals) > 0)
                    <button type="button" class="btn-chip festival" onclick="selectFestivals()">
                        🎉 All Festivals ({{ count($festivals) }})
                    </button>
                @endif
                <button type="button" class="btn-chip" onclick="clearAllDates()">
                    Clear All
                </button>
            </div>
        </div>

        <!-- Off Days Save Form -->
        <form method="POST" action="{{ route('superadmin.redeem-off-days.store') }}" id="offDaysForm">
            @csrf
            <input type="hidden" name="year" value="{{ $currentYear }}">
            <input type="hidden" name="month" value="{{ $currentMonth }}">

            <div class="cal-body">
                <div class="cal-responsive-grid-wrap">
                    <div class="cal-grid-inner">
                        <!-- Day Names Header -->
                        <div class="cal-weekdays">
                            <div class="cal-weekday sun">Sun</div>
                            <div class="cal-weekday">Mon</div>
                            <div class="cal-weekday">Tue</div>
                            <div class="cal-weekday">Wed</div>
                            <div class="cal-weekday">Thu</div>
                            <div class="cal-weekday">Fri</div>
                            <div class="cal-weekday">Sat</div>
                        </div>

                        <!-- Calendar Days Grid -->
                        <div class="cal-days-grid">
                            {{-- Leading empty space before day 1 --}}
                            @for($i = 0; $i < $firstDayOfWeek; $i++)
                                <div class="cal-empty"></div>
                            @endfor

                            {{-- Exact Days of the Month (e.g. Feb 28 or 29, Apr 30, Oct 31) --}}
                            @php
                                $savedDates = $currentMonthData->date ?? [];
                            @endphp

                            @for($d = 1; $d <= $daysInMonth; $d++)
                                @php
                                    $dayDateStr = sprintf('%04d-%02d-%02d', $currentYear, $currentMonth, $d);
                                    $dayOfWeek = (int) date('w', strtotime($dayDateStr));
                                    $isSunday = ($dayOfWeek === 0);
                                    $hasFestival = isset($festivals[$d]);
                                    $isChecked = in_array($d, $savedDates);
                                @endphp

                                <div class="cal-day {{ $isSunday ? 'is-sunday' : '' }} {{ $isChecked ? 'is-blocked' : '' }}"
                                     data-day="{{ $d }}"
                                     data-sunday="{{ $isSunday ? '1' : '0' }}"
                                     data-festival="{{ $hasFestival ? '1' : '0' }}"
                                     onclick="toggleDay({{ $d }})">

                                    <input type="checkbox" 
                                           name="dates[]" 
                                           value="{{ $d }}" 
                                           id="chk-{{ $d }}" 
                                           class="day-checkbox" 
                                           {{ $isChecked ? 'checked' : '' }} 
                                           style="display: none;"
                                           onchange="syncDayVisual({{ $d }})">

                                    <div class="day-top-row">
                                        <span class="day-num">{{ $d }}</span>
                                        <span class="badge-off" id="badge-{{ $d }}">OFF</span>
                                    </div>

                                    @if($hasFestival)
                                        <span class="day-festival" title="{{ $festivals[$d] }}">
                                            {{ $festivals[$d] }}
                                        </span>
                                    @else
                                        <div></div>
                                    @endif
                                </div>
                            @endfor
                        </div>
                    </div>
                </div>
            </div>

            <!-- Clean Footer: Legend, Status & Save -->
            <div class="cal-footer">
                <div class="cal-legend">
                    <span><strong id="selectedCount">0</strong> days blocked</span>
                    <span>•</span>
                    <span><span class="legend-dot" style="background: #e2e8f0;"></span> Available</span>
                    <span><span class="legend-dot" style="background: #e11d48;"></span> Sun</span>
                    <span><span class="legend-dot" style="background: #d97706;"></span> Fest</span>
                    <span><span class="legend-dot" style="background: #ef4444;"></span> Off</span>
                </div>

                <div class="save-footer-actions" style="display: flex; align-items: center; gap: 12px;">
                    <div class="status-select-wrap" style="display: flex; align-items: center; gap: 6px;">
                        <span style="font-size: 13px; font-weight: 600; color: #475569;">Status:</span>
                        <select name="status" style="padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; font-weight: 600; background: #fff;">
                            <option value="active" {{ ($currentMonthData->status ?? 'active') == 'active' ? 'selected' : '' }}>🟢 Active</option>
                            <option value="inactive" {{ ($currentMonthData->status ?? '') == 'inactive' ? 'selected' : '' }}>⚪ Inactive</option>
                        </select>
                    </div>

                    <button type="submit" class="save-btn">
                        💾 Save Off Days
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- SAVED HISTORY TABLE --}}
    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px 20px;">
        <h3 style="font-size: 15px; font-weight: 700; margin-bottom: 12px; color: #0f172a;">
            📋 Saved Off Days History
        </h3>

        @if($allOffDays->isEmpty())
            <p style="text-align: center; color: #94a3b8; padding: 20px 0; font-size: 13px; margin: 0;">No off days configured yet.</p>
        @else
            <div style="overflow-x: auto; width: 100%; -webkit-overflow-scrolling: touch;">
                <table style="width: 100%; min-width: 520px; border-collapse: collapse; font-size: 13px;">
                    <thead>
                        <tr style="border-bottom: 2px solid #e2e8f0; color: #64748b; background: #f8fafc;">
                            <th style="padding: 10px 12px; text-align: left; font-weight: 700;">Year</th>
                            <th style="padding: 10px 12px; text-align: left; font-weight: 700;">Month</th>
                            <th style="padding: 10px 12px; text-align: left; font-weight: 700;">Blocked Dates</th>
                            <th style="padding: 10px 12px; text-align: left; font-weight: 700;">Total</th>
                            <th style="padding: 10px 12px; text-align: left; font-weight: 700;">Status</th>
                            <th style="padding: 10px 12px; text-align: right; font-weight: 700;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($allOffDays as $offDay)
                            @php
                                $isCurrent = ($offDay->year == $currentYear && $offDay->month == $currentMonth);
                                $cnt = !empty($offDay->date) ? count($offDay->date) : 0;
                            @endphp
                            <tr style="border-bottom: 1px solid #f1f5f9; background: {{ $isCurrent ? '#fdf4ff' : 'transparent' }};">
                                <td style="padding: 10px 12px; font-weight: 600;">{{ $offDay->year }}</td>
                                <td style="padding: 10px 12px; font-weight: 700; color: #1e293b;">
                                    {{ $months[$offDay->month] ?? $offDay->month }}
                                    @if($isCurrent)
                                        <span style="font-size: 9px; background: #6366f1; color: #fff; padding: 2px 5px; border-radius: 4px; margin-left: 4px;">Current</span>
                                    @endif
                                </td>
                                <td style="padding: 10px 12px; color: #ef4444; font-weight: 600; max-width: 320px;">
                                    {{ !empty($offDay->date) ? implode(', ', $offDay->date) : 'None' }}
                                </td>
                                <td style="padding: 10px 12px; font-weight: 600; color: #475569;">
                                    {{ $cnt }} {{ Str::plural('day', $cnt) }}
                                </td>
                                <td style="padding: 10px 12px;">
                                    <span style="padding: 3px 8px; border-radius: 10px; font-size: 11px; font-weight: 700; background: {{ $offDay->status === 'active' ? '#dcfce7' : '#fee2e2' }}; color: {{ $offDay->status === 'active' ? '#166534' : '#991b1b' }};">
                                        {{ strtoupper($offDay->status ?? 'active') }}
                                    </span>
                                </td>
                                <td style="padding: 10px 12px; text-align: right;">
                                    <div style="display: inline-flex; align-items: center; gap: 8px;">
                                        <a href="{{ route('superadmin.redeem-off-days.index', ['year' => $offDay->year, 'month' => $offDay->month]) }}" 
                                           style="color: #4f46e5; text-decoration: none; font-size: 12px; font-weight: 600;">
                                            ✏️ Edit
                                        </a>
                                        <form method="POST" action="{{ route('superadmin.redeem-off-days.destroy', $offDay->id) }}" onsubmit="return confirm('Delete off days for this month?');" style="display: inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" style="background: none; border: none; color: #ef4444; cursor: pointer; font-size: 12px; font-weight: 600;">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="pagination">
                @include('common.pagination.superadmin_pagination', ['pageData' => $allOffDays])
            </div>
        @endif
    </div>
</div>

<script>
    function toggleDay(d) {
        const chk = document.getElementById('chk-' + d);
        if (!chk) return;
        chk.checked = !chk.checked;
        syncDayVisual(d);
        updateCount();
    }

    function syncDayVisual(d) {
        const chk = document.getElementById('chk-' + d);
        const cell = document.querySelector(`.cal-day[data-day="${d}"]`);
        if (!chk || !cell) return;

        if (chk.checked) {
            cell.classList.add('is-blocked');
        } else {
            cell.classList.remove('is-blocked');
        }
    }

    function updateCount() {
        const total = document.querySelectorAll('.day-checkbox:checked').length;
        const countEl = document.getElementById('selectedCount');
        if (countEl) countEl.innerText = total;
    }

    function selectSundays() {
        document.querySelectorAll('.cal-day[data-sunday="1"]').forEach(cell => {
            const d = cell.getAttribute('data-day');
            const chk = document.getElementById('chk-' + d);
            if (chk) {
                chk.checked = true;
                syncDayVisual(d);
            }
        });
        updateCount();
    }

    function selectFestivals() {
        document.querySelectorAll('.cal-day[data-festival="1"]').forEach(cell => {
            const d = cell.getAttribute('data-day');
            const chk = document.getElementById('chk-' + d);
            if (chk) {
                chk.checked = true;
                syncDayVisual(d);
            }
        });
        updateCount();
    }

    function clearAllDates() {
        document.querySelectorAll('.day-checkbox').forEach(chk => {
            chk.checked = false;
            syncDayVisual(chk.value);
        });
        updateCount();
    }

    document.addEventListener('DOMContentLoaded', updateCount);
</script>
@endsection