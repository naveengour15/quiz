@extends('superadmin.layouts.app')

@section('title', 'Database Backup & Export')

@section('content')
    <div class="page-head">
        <div>
            <h1>🗄️ Database Backup & Export</h1>
            <p>Download full MySQL database backup (.sql) or export individual tables in SQL, CSV, or JSON format.</p>
        </div>
        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <a href="{{ route('superadmin.database.download') }}" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 6px; padding: 10px 18px; font-size: 13px;">
                <span>💾</span> Download Full Database (.sql)
            </a>
            <a href="{{ route('db.download') }}" class="btn btn-ghost" title="Direct 1-Click Download Link" style="display: inline-flex; align-items: center; gap: 6px; font-size: 12px;">
                <span>⚡</span> Quick Download
            </a>
        </div>
    </div>

    <!-- Top KPI Summary Cards -->
    <div class="kpi-grid" style="grid-template-columns: repeat(4, 1fr); margin-bottom: 20px;">
        <div class="kpi-card">
            <span class="lbl">Current Database</span>
            <b style="font-size: 18px; color: var(--indigo); word-break: break-all;">{{ $dbName }}</b>
            <span class="delta up">MySQL Connected</span>
        </div>

        <div class="kpi-card">
            <span class="lbl">Total Tables</span>
            <b>{{ $totalTablesCount }}</b>
            <span class="delta" style="color: var(--ink-soft);">In Active Schema</span>
        </div>

        <div class="kpi-card">
            <span class="lbl">Total Database Size</span>
            <b style="color: var(--emerald);">{{ $totalDatabaseSize }}</b>
            <span class="delta up">Data + Indexes</span>
        </div>

        <div class="kpi-card">
            <span class="lbl">Total Estimated Rows</span>
            <b style="color: var(--gold);">{{ $totalEstimatedRows }}</b>
            <span class="delta" style="color: var(--ink-soft);">Across all tables</span>
        </div>
    </div>

    <!-- Quick Export Options & Search Toolbar -->
    <div class="card" style="margin-bottom: 20px; padding: 16px 20px;">
        <form method="GET" action="{{ route('superadmin.database.index') }}" style="display: flex; gap: 14px; align-items: center; justify-content: space-between; flex-wrap: wrap;">
            <div style="display: flex; gap: 12px; align-items: center; flex: 1; max-width: 480px;">
                <input type="text" name="search" id="tableSearchInput" value="{{ $search ?? '' }}" 
                    placeholder="🔍 Filter table by name (e.g. users, quizzes, questions)..." 
                    style="width: 100%; padding: 8px 14px; border: 1px solid var(--line); border-radius: 9px; font-size: 12.5px; outline: none; background: var(--paper);"
                    onkeyup="filterTablesClientSide(this.value)">
                @if(!empty($search))
                    <a href="{{ route('superadmin.database.index') }}" class="btn btn-ghost" style="padding: 7px 12px; font-size: 11px;">Clear</a>
                @endif
            </div>

            <div style="display: flex; gap: 12px; align-items: center; font-size: 12px; color: var(--ink-soft);">
                <span style="font-weight: 600;">Full Backup Options:</span>
                <a href="{{ route('superadmin.database.download', ['structure_only' => 1]) }}" class="btn btn-ghost" style="padding: 6px 12px; font-size: 11.5px;" title="Only CREATE TABLE schema without records">
                    📋 Schema Only (.sql)
                </a>
                <a href="{{ route('superadmin.database.download', ['data_only' => 1]) }}" class="btn btn-ghost" style="padding: 6px 12px; font-size: 11.5px;" title="Only INSERT INTO statements without table schemas">
                    📦 Data Only (.sql)
                </a>
            </div>
        </form>
    </div>

    <!-- Table-Wise Export Form -->
    <form id="bulkExportForm" action="{{ route('superadmin.database.download-selected') }}" method="POST">
        @csrf

        <!-- Bulk Action Bar -->
        <div id="bulkActionBar" style="display: none; background: #EDEBFC; border: 1px solid var(--indigo); border-radius: 12px; padding: 12px 18px; margin-bottom: 16px; align-items: center; justify-content: space-between;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <span style="font-weight: 700; color: var(--indigo);" id="selectedCountBadge">0 tables selected</span>
            </div>
            <div style="display: flex; gap: 10px; align-items: center;">
                <label style="font-size: 11.5px; display: inline-flex; align-items: center; gap: 5px; cursor: pointer;">
                    <input type="checkbox" name="structure_only" value="1"> Schema Only
                </label>
                <label style="font-size: 11.5px; display: inline-flex; align-items: center; gap: 5px; cursor: pointer;">
                    <input type="checkbox" name="data_only" value="1"> Data Only
                </label>
                <button type="submit" class="btn btn-primary" style="padding: 6px 14px; font-size: 11.5px;">
                    📥 Download Selected (.sql)
                </button>
            </div>
        </div>

        <!-- Tables List Table -->
        <div class="table-wrap">
            <table id="dbTablesList">
                <thead>
                    <tr>
                        <th style="width: 40px; text-align: center;">
                            <input type="checkbox" id="selectAllCheckbox" onclick="toggleSelectAllTables(this)" style="cursor: pointer; width: 15px; height: 15px;">
                        </th>
                        <th>Table Name</th>
                        <th>Engine</th>
                        <th>Est. Rows</th>
                        <th>Data Size</th>
                        <th>Index Size</th>
                        <th>Total Size</th>
                        <th style="text-align: right; padding-right: 20px;">Download Options</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tables as $table)
                        <tr class="table-row-item" data-table-name="{{ strtolower($table['name']) }}">
                            <td style="text-align: center;">
                                <input type="checkbox" name="selected_tables[]" value="{{ $table['name'] }}" class="table-select-cb" onchange="updateSelectedCount()" style="cursor: pointer; width: 14px; height: 14px;">
                            </td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <span style="font-size: 14px;">📄</span>
                                    <b style="font-size: 13px; font-family: var(--font-mono); color: var(--ink);">{{ $table['name'] }}</b>
                                </div>
                            </td>
                            <td>
                                <span class="badge neutral" style="font-size: 9.5px;">{{ $table['engine'] }}</span>
                            </td>
                            <td>
                                <span style="font-family: var(--font-mono); font-weight: 700; color: {{ $table['rows'] > 0 ? 'var(--indigo)' : 'var(--ink-faint)' }};">
                                    {{ number_format($table['rows']) }}
                                </span>
                            </td>
                            <td style="font-family: var(--font-mono); font-size: 11.5px; color: var(--ink-soft);">{{ $table['data_size'] }}</td>
                            <td style="font-family: var(--font-mono); font-size: 11.5px; color: var(--ink-faint);">{{ $table['index_size'] }}</td>
                            <td>
                                <span class="badge info" style="font-family: var(--font-mono); font-size: 10.5px; font-weight: 700;">
                                    {{ $table['total_size'] }}
                                </span>
                            </td>
                            <td style="text-align: right; padding-right: 16px;">
                                <div style="display: flex; gap: 6px; justify-content: flex-end; align-items: center;">
                                    <!-- SQL Download Button -->
                                    <a href="{{ route('superadmin.database.download', ['table' => $table['name'], 'format' => 'sql']) }}" 
                                        class="btn btn-ghost" 
                                        style="padding: 4px 9px; font-size: 11px; font-weight: 700; border-color: #CBD5E1; color: var(--indigo);"
                                        title="Download {{ $table['name'] }} as SQL script">
                                        SQL
                                    </a>

                                    <!-- CSV Download Button -->
                                    <a href="{{ route('superadmin.database.download', ['table' => $table['name'], 'format' => 'csv']) }}" 
                                        class="btn btn-ghost" 
                                        style="padding: 4px 9px; font-size: 11px; font-weight: 700; border-color: #CBD5E1; color: var(--emerald);"
                                        title="Download {{ $table['name'] }} as CSV Spreadsheet">
                                        CSV
                                    </a>

                                    <!-- JSON Download Button -->
                                    <a href="{{ route('superadmin.database.download', ['table' => $table['name'], 'format' => 'json']) }}" 
                                        class="btn btn-ghost" 
                                        style="padding: 4px 9px; font-size: 11px; font-weight: 700; border-color: #CBD5E1; color: var(--orange);"
                                        title="Download {{ $table['name'] }} as JSON file">
                                        JSON
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 30px; color: var(--ink-faint);">
                                No tables found matching your search.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </form>

    <script>
        function filterTablesClientSide(query) {
            const q = query.trim().toLowerCase();
            const rows = document.querySelectorAll('.table-row-item');
            let matchCount = 0;

            rows.forEach(row => {
                const name = row.getAttribute('data-table-name') || '';
                if (!q || name.includes(q)) {
                    row.style.display = '';
                    matchCount++;
                } else {
                    row.style.display = 'none';
                }
            });
        }

        function toggleSelectAllTables(masterCb) {
            const checkboxes = document.querySelectorAll('.table-select-cb');
            checkboxes.forEach(cb => {
                const row = cb.closest('tr');
                if (row && row.style.display !== 'none') {
                    cb.checked = masterCb.checked;
                }
            });
            updateSelectedCount();
        }

        function updateSelectedCount() {
            const checked = document.querySelectorAll('.table-select-cb:checked');
            const bar = document.getElementById('bulkActionBar');
            const badge = document.getElementById('selectedCountBadge');

            if (checked.length > 0) {
                bar.style.display = 'flex';
                badge.textContent = `${checked.length} table${checked.length > 1 ? 's' : ''} selected`;
            } else {
                bar.style.display = 'none';
                const selectAll = document.getElementById('selectAllCheckbox');
                if (selectAll) selectAll.checked = false;
            }
        }
    </script>
@endsection
