@extends('superadmin.layouts.app')
@section('title', 'Player Activity & Rank')

@section('content')
<style>
    /* Quiz Range Box Focus State */
    .quiz-range-box:focus-within {
        border-color: #6366f1;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
    }
    .quiz-range-box input::-webkit-outer-spin-button,
    .quiz-range-box input::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }
    .quiz-range-box input[type=number] {
        -moz-appearance: textfield;
    }
    
    /* Sortable Header Styles */
    .sortable-header {
        cursor: pointer;
        user-select: none;
    }
    .sortable-header a {
        text-decoration: none;
        color: inherit;
        display: flex;
        align-items: center;
        gap: 6px;
        transition: color 0.2s ease;
    }
    .sortable-header a:hover {
        color: #6366f1;
    }
    .sort-arrow {
        font-size: 14px;
        line-height: 1;
    }
    .sort-arrow.active {
        color: #6366f1;
    }
    .sort-arrow.inactive {
        opacity: 0.3;
    }
</style>

    <div class="panel active">
        <!-- Page Header -->
        <div class="page-head" style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px;">
            <div>
                <h1>Player Activity & Leaderboard Rank</h1>
                <p>Track quiz performance, last played date, and current rank of all players.</p>
            </div>
            <a href="{{ route('superadmin.users.index') }}" class="btn btn-ghost">← Back to Users</a>
        </div>

        <!-- ✅ 5 Stats Boxes Grid -->
        <div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 24px;">
            <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; display: flex; align-items: center; gap: 12px;">
                <div style="width: 40px; height: 40px; border-radius: 10px; background: #f1f5f9; color: #475569; display: flex; align-items: center; justify-content: center; font-size: 20px;">👥</div>
                <div>
                    <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Total Users</div>
                    <div style="font-size: 22px; font-weight: 800; color: #0f172a;">{{ $totalUsers }}</div>
                </div>
            </div>
            <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; display: flex; align-items: center; gap: 12px;">
                <div style="width: 40px; height: 40px; border-radius: 10px; background: #ecfdf5; color: #059669; display: flex; align-items: center; justify-content: center; font-size: 20px;">✅</div>
                <div>
                    <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Active</div>
                    <div style="font-size: 22px; font-weight: 800; color: #059669;">{{ $activeUsers }}</div>
                </div>
            </div>
            <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; display: flex; align-items: center; gap: 12px;">
                <div style="width: 40px; height: 40px; border-radius: 10px; background: #fef2f2; color: #dc2626; display: flex; align-items: center; justify-content: center; font-size: 20px;">🚫</div>
                <div>
                    <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Inactive</div>
                    <div style="font-size: 22px; font-weight: 800; color: #dc2626;">{{ $inactiveUsers }}</div>
                </div>
            </div>
            <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; display: flex; align-items: center; gap: 12px;">
                <div style="width: 40px; height: 40px; border-radius: 10px; background: #eff6ff; color: #2563eb; display: flex; align-items: center; justify-content: center; font-size: 20px;">🎮</div>
                <div>
                    <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Played (1+)</div>
                    <div style="font-size: 22px; font-weight: 800; color: #2563eb;">{{ $playedUsers }}</div>
                </div>
            </div>
            <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; display: flex; align-items: center; gap: 12px;">
                <div style="width: 40px; height: 40px; border-radius: 10px; background: #fff7ed; color: #ea580c; display: flex; align-items: center; justify-content: center; font-size: 20px;">⏸️</div>
                <div>
                    <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Not Played (0)</div>
                    <div style="font-size: 22px; font-weight: 800; color: #ea580c;">{{ $notPlayedUsers }}</div>
                </div>
            </div>
        </div>

        <!-- ✅ Search & Filter Box (All Filters + Export) -->
        <div class="search-filter-box" style="background: var(--surface, #ffffff); border: 1px solid var(--line, #e2e8f0); border-radius: 16px; padding: 20px; margin-bottom: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
            <form method="GET" class="toolbar" style="display: flex; gap: 16px; align-items: end; flex-wrap: wrap;">
                
                <!-- Search -->
                <div class="input-field" style="display: flex; flex-direction: column; gap: 6px; flex: 1; min-width: 200px;">
                    <label class="lbl" style="font-size: 11px; font-weight: 700; color: var(--ink-faint); text-transform: uppercase;">Search Player</label>
                    <input type="text" class="tb-input" name="search" value="{{ request('search') }}" placeholder="Name or Mobile..." style="padding: 10px; border: 1px solid #e2e8f0; border-radius: 8px;">
                </div>

                <!-- Status -->
                <div class="input-field" style="display: flex; flex-direction: column; gap: 6px;">
                    <label class="lbl" style="font-size: 11px; font-weight: 700; color: var(--ink-faint); text-transform: uppercase;">User Status</label>
                    <select class="tb-select" name="status" style="padding: 10px; border: 1px solid #e2e8f0; border-radius: 8px;">
                        <option value="">All Status</option>
                        <option value="active" @selected(request('status') === 'active')>Active</option>
                        <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                    </select>
                </div>

                <!-- Joining Date Start -->
                <div class="input-field" style="display: flex; flex-direction: column; gap: 6px;">
                    <label class="lbl" style="font-size: 11px; font-weight: 700; color: var(--ink-faint); text-transform: uppercase;">Joining (Start)</label>
                    <input type="date" class="tb-input" name="join_date_start" value="{{ request('join_date_start') }}" style="padding: 10px; border: 1px solid #e2e8f0; border-radius: 8px;">
                </div>

                <!-- Joining Date End -->
                <div class="input-field" style="display: flex; flex-direction: column; gap: 6px;">
                    <label class="lbl" style="font-size: 11px; font-weight: 700; color: var(--ink-faint); text-transform: uppercase;">Joining (End)</label>
                    <input type="date" class="tb-input" name="join_date_end" value="{{ request('join_date_end') }}" style="padding: 10px; border: 1px solid #e2e8f0; border-radius: 8px;">
                </div>

                <!-- ✅ Min/Max Quiz Range (Compact Box) -->
                <div class="input-field" style="display: flex; flex-direction: column; gap: 6px; width: 250px;">
                    <label class="lbl" style="font-size: 11px; font-weight: 700; color: var(--ink-faint); text-transform: uppercase;">Quiz Range</label>
                    <div class="quiz-range-box" style="display: flex; align-items: center; border: 1px solid #e2e8f0; border-radius: 10px; background: #f1f5f9; padding: 4px; gap: 4px; transition: all 0.2s ease; width: 100%;">
                        <div style="flex: 1; display: flex; align-items: center; background: #ffffff; border-radius: 6px; border: 1px solid #e2e8f0; padding: 0 4px; box-shadow: 0 1px 2px rgba(0,0,0,0.02);">
                            <span style="font-size: 9px; font-weight: 700; color: #94a3b8; margin-right: 4px; text-transform: uppercase;">Min</span>
                            <input type="number" name="min_quizzes" value="{{ request('min_quizzes', 0) }}" min="0" step="1" placeholder="0" 
                                style="width: 100%; border: none; background: transparent; font-weight: 700; outline: none; text-align: center; color: #0f172a; font-size: 13px; padding: 6px 0;">
                        </div>
                        <div style="flex: 1; display: flex; align-items: center; background: #ffffff; border-radius: 6px; border: 1px solid #e2e8f0; padding: 0 4px; box-shadow: 0 1px 2px rgba(0,0,0,0.02);">
                            <span style="font-size: 9px; font-weight: 700; color: #94a3b8; margin-right: 4px; text-transform: uppercase;">Max</span>
                            <input type="number" name="max_quizzes" value="{{ request('max_quizzes') }}" min="0" step="1" placeholder="∞" 
                                style="width: 100%; border: none; background: transparent; font-weight: 700; outline: none; text-align: center; color: #0f172a; font-size: 13px; padding: 6px 0;">
                        </div>
                    </div>
                </div>


                 <!-- 1. Sub-Range Filters (Top) -->
                <div class="input-field" style="display: flex; flex-direction: column; gap: 6px; min-width: 140px;">
                    <label class="lbl" style="font-size: 11px; font-weight: 700; color: #d97706; text-transform: uppercase;">Range From</label>
                    <input type="date" name="range_from" value="{{ request('range_from') }}" style="padding: 10px; border: 1px solid #f59e0b; border-radius: 8px; font-size: 13px; font-weight: 600; color: #0f172a; background: #fff;">
                </div>

                <div class="input-field" style="display: flex; flex-direction: column; gap: 6px; min-width: 140px;">
                    <label class="lbl" style="font-size: 11px; font-weight: 700; color: #d97706; text-transform: uppercase;">Range To</label>
                    <input type="date" name="range_to" value="{{ request('range_to') }}" style="padding: 10px; border: 1px solid #f59e0b; border-radius: 8px; font-size: 13px; font-weight: 600; color: #0f172a; background: #fff;">
                </div>


                <!-- ✅ NEW: Consistency Filter (Dropdown) -->
                <div class="input-field" style="display: flex; flex-direction: column; gap: 6px; min-width: 180px;">
                    <label class="lbl" style="font-size: 11px; font-weight: 700; color: #059669; text-transform: uppercase;">📊 Consistency in Range</label>
                    <select name="consistency_filter" class="tb-select" style="padding: 10px; border: 1px solid #10b981; border-radius: 8px; font-size: 13px; font-weight: 600; color: #0f172a; background: #fff;">
                        <option value="">Any Consistency</option>
                        <option value="100" @selected(request('consistency_filter') === '100')>🔥 100% Days (Daily Player)</option>
                        <option value="75" @selected(request('consistency_filter') === '75')>⭐ 75% or more Days</option>
                        <option value="50" @selected(request('consistency_filter') === '50')>👍 50% or more Days</option>
                        <option value="low" @selected(request('consistency_filter') === 'low')>⚠️ Less than 50% Days</option>
                    </select>
                </div>

                <!-- ✅ Horizontal Divider Line -->
                <div style="width: 100%; height: 1px; background: linear-gradient(to right, transparent, #cbd5e1, transparent); margin: 8px 0;"></div>


                <!-- Activity Status Dropdown -->
                <div class="input-field" style="display: flex; flex-direction: column; gap: 6px; min-width: 160px;">
                    <label class="lbl" style="font-size: 11px; font-weight: 700; color: var(--ink-faint); text-transform: uppercase;">Play Activity</label>
                    <select name="activity_status" class="tb-select" style="padding: 10px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 13px; font-weight: 600; color: #0f172a; background: #fff;">
                        <option value="all" @selected(request('activity_status') === 'all' || !request('activity_status'))>All Users</option>
                        <option value="played" @selected(request('activity_status') === 'played')>✅ Played in Range</option>
                        <option value="not_played" @selected(request('activity_status') === 'not_played')>❌ Not Played in Range</option>
                    </select>
                </div>

                <!-- Activity Date Start -->
                <div class="input-field" style="display: flex; flex-direction: column; gap: 6px; min-width: 140px;">
                    <label class="lbl" style="font-size: 11px; font-weight: 700; color: var(--ink-faint); text-transform: uppercase;">From Date</label>
                    <input type="date" name="activity_date_start" value="{{ request('activity_date_start') }}" style="padding: 10px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 13px; font-weight: 600; color: #0f172a; background: #fff;">
                </div>

                <!-- Activity Date End -->
                <div class="input-field" style="display: flex; flex-direction: column; gap: 6px; min-width: 140px;">
                    <label class="lbl" style="font-size: 11px; font-weight: 700; color: var(--ink-faint); text-transform: uppercase;">To Date</label>
                    <input type="date" name="activity_date_end" value="{{ request('activity_date_end') }}" style="padding: 10px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 13px; font-weight: 600; color: #0f172a; background: #fff;">
                </div>

                <!-- Action Buttons -->
                <div style="display: flex; gap: 10px;">
                    <button class="btn btn-primary" type="submit" style="padding: 10px 20px; background: #6366f1; color: white; border-radius: 8px; border: none; font-weight: 700; cursor: pointer;">Filter</button>
                    <a href="{{ route('superadmin.player.activity') }}" class="btn btn-ghost" style="padding: 10px 20px; background: transparent; border: 1px solid #e2e8f0; border-radius: 8px; text-decoration: none; color: #475569;">Reset</a>
                    
                    <!-- Export Button -->
                    <a href="{{ route('superadmin.player.activity.export', request()->query()) }}" class="btn" style="padding: 10px 20px; background: #10b981; color: white; border-radius: 8px; border: none; font-weight: 700; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                        Export CSV
                    </a>
                </div>
            </form>
        </div>

        <!-- ✅ Data Table with Sorting -->
        <div class="table-wrap" style="overflow-x: auto; background: #fff; border-radius: 16px; border: 1px solid #e2e8f0;">
            <table style="width: 100%; border-collapse: collapse; text-align: left;">
                @php
                    $currentSortBy = request('sort_by', 'created_at');
                    $currentSortDir = request('sort_dir', 'desc');
                    
                    $getSortUrl = function($column) use ($currentSortBy, $currentSortDir) {
                        $nextDir = ($currentSortBy === $column && $currentSortDir === 'asc') ? 'desc' : 'asc';
                        return request()->fullUrlWithQuery(['sort_by' => $column, 'sort_dir' => $nextDir]);
                    };
                    
                    $getArrow = function($column) use ($currentSortBy, $currentSortDir) {
                        if ($currentSortBy !== $column) return '<span class="sort-arrow inactive">⇅</span>';
                        return $currentSortDir === 'asc' ? '<span class="sort-arrow active">↑</span>' : '<span class="sort-arrow active">↓</span>';
                    };
                @endphp

                <thead>
                    <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                        <th class="sortable-header" style="padding: 16px; font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase;">
                            <a href="{{ $getSortUrl('name') }}">Player Details {!! $getArrow('name') !!}</a>
                        </th>
                        <th class="sortable-header" style="padding: 16px; font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase;">
                            <a href="{{ $getSortUrl('total_quizzes') }}">Quiz Activity {!! $getArrow('total_quizzes') !!}</a>
                        </th>
                        <th class="sortable-header" style="padding: 16px; font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase;">
                            <a href="{{ $getSortUrl('last_played_date') }}">Last Quiz Played {!! $getArrow('last_played_date') !!}</a>
                        </th>
                        <th class="sortable-header" style="padding: 16px; font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; text-align: center;">
                            <a href="{{ $getSortUrl('current_rank') }}" style="justify-content: center;">Current Rank {!! $getArrow('current_rank') !!}</a>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($players as $player)
                        <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.2s;">
                            <!-- Column 1: Player Details -->
                            <td style="padding: 16px;">
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <div style="width: 40px; height: 40px; border-radius: 10px; background: #e0e7ff; color: #4338ca; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 14px;">
                                        {{ strtoupper(substr($player->name, 0, 2)) }}
                                    </div>
                                    <div style="display: flex; flex-direction: column; gap: 2px;">
                                        <b style="font-size: 14px; color: #0f172a;">{{ $player->name }}</b>
                                        <span style="font-size: 12px; color: #64748b; font-family: monospace;">{{ $player->mobile ?? '—' }}</span>
                                        <span style="font-size: 11px; color: #94a3b8;">Joined: {{ \Carbon\Carbon::parse($player->created_at)->format('d M Y') }}</span>
                                    </div>
                                </div>
                            </td>

                            <!-- Column 2: Quiz Activity -->
                            <td style="padding: 16px;">
                                <div style="display: flex; flex-direction: column; gap: 8px; align-items: flex-start;">
                                    <span style="display: inline-flex; align-items: center; justify-content: center; min-width: 36px; height: 36px; padding: 0 10px; border-radius: 9999px; background: linear-gradient(135deg, #6366f1, #8b5cf6); color: #ffffff; font-size: 14px; font-weight: 800; box-shadow: 0 4px 6px -1px rgba(99, 102, 241, 0.3); letter-spacing: 0.5px;">
                                        {{ $player->total_quizzes ?? 0 }}
                                    </span>
                                    <span style="font-size: 11px; font-weight: 600; padding: 4px 10px; border-radius: 9999px; display: inline-flex; align-items: center; gap: 5px; width: fit-content; {{ $player->is_active == 1 ? 'background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0;' : 'background: #fef2f2; color: #dc2626; border: 1px solid #fecaca;' }}">
                                        <span style="width: 6px; height: 6px; border-radius: 50%; background: currentColor;"></span>
                                        {{ $player->is_active == 1 ? 'Active' : 'Inactive' }}
                                    </span>
                                </div>
                            </td>

                            <!-- Column 3: Last Quiz Played -->
                            <td style="padding: 16px;">
                                @if($player->last_played_date)
                                    <div style="display: flex; flex-direction: column; gap: 4px;">
                                        <b style="font-size: 13px; color: #0f172a;">{{ $player->last_quiz_name ?? 'Unknown Quiz' }}</b>
                                        <span style="font-size: 11px; color: #64748b;">
                                            {{ \Carbon\Carbon::parse($player->last_played_date)->diffForHumans() }}
                                            <br>
                                            <span style="color: #94a3b8;">({{ \Carbon\Carbon::parse($player->last_played_date)->format('d M Y, h:i A') }})</span>
                                        </span>
                                    </div>
                                @else
                                    <span style="font-size: 12px; color: #94a3b8; font-style: italic;">No quiz played yet</span>
                                @endif
                            </td>

                            <!-- Column 4: Current Rank -->
                                                        <!-- Column 4: Current Rank, Marks & Points -->
                            <td style="padding: 16px; text-align: center;">
                                <div style="display: flex; flex-direction: column; align-items: center; gap: 6px;">
                                    
                                    <!-- 1. Current Rank -->
                                    @if($player->current_rank)
                                        <div style="display: inline-flex; align-items: center; justify-content: center; min-width: 45px; height: 32px; padding: 0 12px; border-radius: 9999px; background: linear-gradient(135deg, #fbbf24, #f59e0b); color: #fff; font-size: 13px; font-weight: 800; box-shadow: 0 4px 6px -1px rgba(245, 158, 11, 0.3); white-space: nowrap;">
                                            #{{ $player->current_rank }}
                                        </div>
                                    @else
                                        <span style="font-size: 11px; color: #64748b; font-weight: 600; background: #f1f5f9; padding: 4px 10px; border-radius: 9999px; border: 1px solid #e2e8f0;">Unranked</span>
                                    @endif

                                    <!-- 2. Leaderboard Points (Marks) -->
                                    <div style="display: inline-flex; align-items: center; gap: 4px; font-size: 11px; font-weight: 700; color: #4f46e5; background: #eef2ff; padding: 3px 8px; border-radius: 6px; border: 1px solid #c7d2fe;">
                                        <span style="font-size: 12px;">🏆</span> {{ number_format($player->leader_board_point ?? 0) }} Marks
                                    </div>

                                    <!-- 3. Wallet Balance (Points) -->
                                    <div style="display: inline-flex; align-items: center; gap: 4px; font-size: 11px; font-weight: 700; color: #059669; background: #ecfdf5; padding: 3px 8px; border-radius: 6px; border: 1px solid #a7f3d0;">
                                        <span style="font-size: 12px;">💰</span> {{ number_format($player->wallet_balance ?? 0) }} Pts
                                    </div>

                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" style="text-align: center; padding: 40px; color: #94a3b8;">
                                <div style="font-size: 32px; margin-bottom: 10px;">📭</div>
                                No players found matching your filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="pagination" style="margin-top: 20px;">
            @include('common.pagination.superadmin_pagination', ['pageData' => $players])
        </div>
    </div>
@endsection