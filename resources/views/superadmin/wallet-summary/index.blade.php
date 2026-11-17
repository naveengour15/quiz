@extends('superadmin.layouts.app')
@section('title', 'Wallet Summary')

@section('content')
    @push('styles')
        <style>
            .stat-card {
                background: #fff; border: 1px solid #e2e8f0; border-radius: 12px;
                padding: 16px; display: flex; align-items: center; gap: 12px;
                transition: transform 0.2s ease, box-shadow 0.2s ease;
            }
            .stat-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
            .stat-icon { width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px; }
            
            .badge-credit { background-color: #dcfce7; color: #166534; border: 1px solid #86efac; }
            .badge-debit { background-color: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
            .badge-pending { background-color: #fef3c7; color: #92400e; border: 1px solid #fde68a; }

            /* ✅ Sorting Styles */
            .sortable-header { cursor: pointer; user-select: none; }
            .sortable-header a { text-decoration: none; color: inherit; display: flex; align-items: center; gap: 6px; transition: color 0.2s ease; }
            .sortable-header a:hover { color: #6366f1; }
            .sort-arrow { font-size: 14px; line-height: 1; }
            .sort-arrow.active { color: #6366f1; }
            .sort-arrow.inactive { opacity: 0.3; }
        </style>
    @endpush

    <div class="panel active">
        <div class="page-head" style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px;">
            <div>
                <h1>Wallet Summary</h1>
                <p style="color: #64748b; font-size: 14px; margin-top: 4px;">Aggregated wallet credit, redeemed, and pending stats per user.</p>
            </div>
        </div>

        <!-- ✅ TOP STATS CARDS -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
            <div class="stat-card">
                <div class="stat-icon" style="background: #dcfce7; color: #166534;">📈</div>
                <div>
                    <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Total Credit Added</div>
                    <div style="font-size: 22px; font-weight: 800; color: #166534;">₹{{ number_format($totalCredit ?? 0, 2) }}</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: #fee2e2; color: #991b1b;">📉</div>
                <div>
                    <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Total Redeemed (Approved)</div>
                    <div style="font-size: 22px; font-weight: 800; color: #991b1b;">₹{{ number_format($totalRedeemed ?? 0, 2) }}</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: #fef3c7; color: #92400e;">⏳</div>
                <div>
                    <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Total Pending Redeem</div>
                    <div style="font-size: 22px; font-weight: 800; color: #92400e;">₹{{ number_format($totalPending ?? 0, 2) }}</div>
                </div>
            </div>
            <!-- Total Cancelled / Rejected Card -->
            <div class="stat-card">
                <div class="stat-icon" style="background: #f3f4f6; color: #4b5563;">🚫</div>
                <div>
                    <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Total Cancelled / Rejected</div>
                    <div style="font-size: 22px; font-weight: 800; color: #4b5563;">₹{{ number_format($totalCancelled ?? 0, 2) }}</div>
                </div>
            </div>
            {{-- <div class="stat-card">
                <div class="stat-icon" style="background: #e0e7ff; color: #4338ca;">📋</div>
                <div>
                    <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Total Requests</div>
                    <div style="font-size: 22px; font-weight: 800; color: #4338ca;">{{ number_format($totalRequests ?? 0) }}</div>
                </div>
            </div> --}}
        </div>

        <!-- ✅ SEARCH & FILTER BOX -->
        <div class="search-filter-box" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 20px; margin-bottom: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
            <form method="GET" class="toolbar" style="display: flex; gap: 16px; align-items: end; flex-wrap: wrap;">
                <div class="input-field" style="display: flex; flex-direction: column; gap: 6px; flex: 1; min-width: 200px;">
                    <label class="lbl" style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Search User</label>
                    <input type="text" class="tb-input" name="search" value="{{ request('search') }}" placeholder="Name, Mobile or Email..." style="padding: 10px; border: 1px solid #e2e8f0; border-radius: 8px;">
                </div>

                <!-- ✅ NEW: Pending Filter Dropdown -->
                <div class="input-field" style="display: flex; flex-direction: column; gap: 6px; min-width: 180px;">
                    <label class="lbl" style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Pending Requests</label>
                    <select class="tb-select" name="filter_pending" style="padding: 10px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 13px; font-weight: 600;">
                        <option value="all" @selected(request('filter_pending') === 'all' || !request('filter_pending'))>All Users</option>
                        <option value="yes" @selected(request('filter_pending') === 'yes')>Has Pending Requests</option>
                    </select>
                </div>

                <div style="display: flex; gap: 10px;">
                    <button class="btn btn-primary" type="submit" style="padding: 10px 20px; background: #6366f1; color: white; border-radius: 8px; border: none; font-weight: 700; cursor: pointer;">Filter</button>
                    <a href="{{ route('superadmin.wallet-summary.index') }}" class="btn btn-ghost" style="padding: 10px 20px; background: transparent; border: 1px solid #e2e8f0; border-radius: 8px; text-decoration: none; color: #475569;">Reset</a>
                </div>
            </form>
        </div>

        <!-- ✅ USER SUMMARY TABLE WITH SORTING -->
        <div class="table-wrap table-wrap-res-01" style="overflow-x: auto; background: #fff; border-radius: 16px; border: 1px solid #e2e8f0;">
            <table style="width: 100%; border-collapse: collapse; text-align: left;">
                
                @php
                    // Sorting Helper Variables
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
                            <a href="{{ $getSortUrl('name') }}">User Details {!! $getArrow('name') !!}</a>
                        </th>
                        <th class="sortable-header" style="padding: 16px; font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase;">
                            <a href="{{ $getSortUrl('wallet_balance') }}">Current Balance {!! $getArrow('wallet_balance') !!}</a>
                        </th>
                        <th class="sortable-header" style="padding: 16px; font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase;">
                            <a href="{{ $getSortUrl('total_credit') }}">Total Credit {!! $getArrow('total_credit') !!}</a>
                        </th>
                        <th class="sortable-header" style="padding: 16px; font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase;">
                            <a href="{{ $getSortUrl('total_redeemed') }}">Total Redeemed {!! $getArrow('total_redeemed') !!}</a>
                        </th>
                        <th class="sortable-header" style="padding: 16px; font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase;">
                            <a href="{{ $getSortUrl('total_pending') }}">Pending Redeem {!! $getArrow('total_pending') !!}</a>
                        </th>
                        <th class="sortable-header" style="padding: 16px; font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; text-align: center;">
                            <a href="{{ $getSortUrl('total_requests') }}" style="justify-content: center;">Requests {!! $getArrow('total_requests') !!}</a>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.2s;">
                            <td style="padding: 16px;">
                                <div style="display: flex; flex-direction: column; gap: 2px;">
                                    <strong style="font-size: 14px; color: #0f172a;">{{ $user->name ?? 'Unknown' }}</strong>
                                    <small style="font-size: 12px; color: #64748b;">{{ $user->mobile ?? '—' }}</small>
                                    <small style="font-size: 11px; color: #94a3b8;">ID : {{ $user->id ?? '—' }}</small>
                                </div>
                            </td>
                            <td style="padding: 16px;">
                                <span style="font-size: 15px; font-weight: 800; color: #0f172a;">
                                   {{ number_format($user->wallet_balance ?? 0, 2) }}
                                </span>
                            </td>
                            <td style="padding: 16px;">
                                <span class="req-badge badge-credit" style="display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 13px; font-weight: 700;">
                                    {{ number_format($user->total_credit ?? 0, 2) }}
                                </span>
                            </td>
                            <td style="padding: 16px;">
                                <span class="req-badge badge-debit" style="display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 13px; font-weight: 700;">
                                    {{ number_format($user->total_redeemed ?? 0, 2) }}
                                </span>
                            </td>
                            <td style="padding: 16px;">
                                @if(($user->total_pending ?? 0) > 0)
                                    <span class="req-badge badge-pending" style="display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 13px; font-weight: 700;">
                                        {{ number_format($user->total_pending ?? 0, 2) }}
                                    </span>
                                @else
                                    <span style="color: #cbd5e1; font-size: 12px;">None</span>
                                @endif
                            </td>
                            <td style="padding: 16px; text-align: center;">
                                <span style="display: inline-flex; align-items: center; justify-content: center; min-width: 32px; height: 32px; padding: 0 8px; border-radius: 9999px; background: #e0e7ff; color: #4338ca; font-size: 14px; font-weight: 800;">
                                    {{ $user->total_requests ?? 0 }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <!-- ✅ Fixed colspan to 6 for the new column -->
                            <td colspan="6" style="text-align: center; padding: 40px; color: #94a3b8;">
                                <div style="font-size: 32px; margin-bottom: 10px;">📭</div>
                                No users found matching your search.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pagination" style="margin-top: 20px;">
            @include('common.pagination.superadmin_pagination', ['pageData' => $users])
        </div>
    </div>
@endsection