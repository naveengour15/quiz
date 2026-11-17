@extends('superadmin.layouts.app')
@section('title', 'User Quiz Report')
<link rel="stylesheet" href="{{ asset('assets/css/custom.css') }}">
@section('content')
    <div class="panel active">
        @php
            $getSortData = function ($col) use ($sortColumn, $sortDirection) {
                if ($sortColumn === $col) {
                    return $sortDirection === 'desc' ? '↓' : '↑';
                }
                return '⇅';
            };
        @endphp
        <div class="page-head">
            <div>
                <h1 style="display: flex; align-items: center; gap: 10px;">
                    User Quiz Report 
                    <span class="badge info" style="font-size: 14px;">{{ number_format($users->total()) }} Users</span>
                </h1>
                <p>Detailed performance metrics of all users.</p>
            </div>
            {{-- <div class="head-actions">
                <form method="GET" style="display:flex; gap:10px;">
                    <input type="text" name="search" class="tb-input" placeholder="Search by name, email, phone..."
                        value="{{ request('search') }}">
                    <button class="btn btn-primary" type="submit">Search</button>
                    @if(request('search'))
                        <a href="{{ route('superadmin.reports.user-quiz') }}" class="btn btn-ghost">Clear</a>
                    @endif
                </form>
            </div> --}}
        </div>

           <div class="search-filter-box">
            <form method="GET" class="toolbar">
                <div class="user-quiz-report-filter-box">
                     <div class="user-quiz-report-filter-wrapper">
                        <div class="input-field">
                            <label class="lbl" for="search">Search</label>
                            <input type="text" name="search" class="tb-input user-quiz-report-input" placeholder="Search by name, email, phone..."
                        value="{{ request('search') }}">
                        </div>
                     </div>
                     <div class="user-quiz-report-filter-btn">
                        <button class="btn btn-primary" type="submit">Search</button>
                    @if(request('search'))
                        <a href="{{ route('superadmin.reports.user-quiz') }}" class="btn btn-ghost">Clear</a>
                    @endif
                     </div>
                </div>
            </form>
        </div>

        <div class="table-wrap table-wrap-res-01">
            <div class="user-quiz-report-table-box" style="overflow-x: auto;">
                <table class="dash-table user-quiz-report-table-wrapper">
                    <thead>
                        <tr>
                            <th class="sort-col" data-col="name" style="cursor:pointer; user-select:none;">User Name <span class="sort-icon">{{ $getSortData('name') }}</span></th>
                            <th class="sort-col" data-col="quizzes_played" style="cursor:pointer; user-select:none;">Quizzes Played <span class="sort-icon">{{ $getSortData('quizzes_played') }}</span></th>
                            <th class="sort-col" data-col="total_questions" style="cursor:pointer; user-select:none;">Total Question <span class="sort-icon">{{ $getSortData('total_questions') }}</span></th>
                            <th class="sort-col" data-col="unanswered" style="cursor:pointer; user-select:none;">Unanswered <span class="sort-icon">{{ $getSortData('unanswered') }}</span></th>
                            <th class="sort-col" data-col="correct_answers" style="cursor:pointer; user-select:none;">Correct Ans <span class="sort-icon">{{ $getSortData('correct_answers') }}</span></th>
                            <th class="sort-col" data-col="wrong_answers" style="cursor:pointer; user-select:none;">Wrong Ans <span class="sort-icon">{{ $getSortData('wrong_answers') }}</span></th>
                            <th class="sort-col" data-col="wallet_balance" style="cursor:pointer; user-select:none;">Wallet Balance <span class="sort-icon">{{ $getSortData('wallet_balance') }}</span></th>
                            <th class="sort-col" data-col="leader_board_point" style="cursor:pointer; user-select:none;">Leaderboard Point <span class="sort-icon">{{ $getSortData('leader_board_point') }}</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $actPlayer)
                            <tr>
                                <td data-label="User Name">
                                    <div class="cell-user">
                                        <div class="cell-av">{{ strtoupper(substr($actPlayer->name ?? 'U', 0, 2)) }}</div>
                                        <div>
                                            <b>{{ $actPlayer->name ?? 'Unknown' }}</b>
                                            <span>{{ $actPlayer->mobile ?? $actPlayer->email }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td data-label="Quizzes Played"><span class="mono">{{ number_format($actPlayer->quizzes_played) }}</span></td>
                                <td data-label="Total Question"><span class="mono">{{ number_format($actPlayer->total_questions) }}</span></td>
                                <td data-label="Unanswered"><span class="badge info" style="font-size:11px;">{{ number_format($actPlayer->unanswered) }}</span></td>
                                <td data-label="Correct Ans"><span class="badge success" style="font-size:11px;">{{ number_format($actPlayer->correct_answers) }}</span></td>
                                <td data-label="Wrong Ans"><span class="badge danger" style="font-size:11px;">{{ number_format($actPlayer->wrong_answers) }}</span></td>
                                <td data-label="Wallet Balance"><span class="mono" style="color:var(--emerald); font-weight:700;">{{ number_format($actPlayer->wallet_balance ?? 0, 2) }}</span></td>
                                <td data-label="Leaderboard Point">
                                    <div class="user-points-display">
                                        <b>{{ number_format($actPlayer->leader_board_point) }}</b>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 20px; color: var(--ink-faint);">
                                    No user data found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div style="margin-top: 15px;">
            {{ $users->links('common.pagination.fsrapp_pagination', ['pageData' => $users]) }}
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function() {
        document.querySelectorAll('.sort-col').forEach(col => {
            col.addEventListener('click', function() {
                const column = this.getAttribute('data-col');
                const urlParams = new URLSearchParams(window.location.search);
                let currentSort = urlParams.get('sort') || 'quizzes_played';
                let currentDir = urlParams.get('dir') || 'desc';

                if (currentSort === column) {
                    currentDir = currentDir === 'desc' ? 'asc' : 'desc';
                } else {
                    currentSort = column;
                    currentDir = 'desc';
                }

                urlParams.set('sort', currentSort);
                urlParams.set('dir', currentDir);
                urlParams.set('page', 1);

                window.location.href = window.location.pathname + '?' + urlParams.toString();
            });
        });
    });
</script>
@endpush
