@extends('superadmin.layouts.app')
@section('title', 'WhatsApp Notified Users')
<link rel="stylesheet" href="{{ asset('assets/css/custom.css') }}">
@push('styles')
    <style>
        /* ==========================================
           4-COLUMN STATS GRID (NEW)
           ========================================== */
        .stats-grid-4-col {
            display: grid;
            grid-template-columns: repeat(4, 1fr); /* 4 boxes in 1 row */
            gap: 16px;
            margin-bottom: 24px;
        }

        /* WhatsApp Active Colors */
        .stat-wa-active .stat-icon {
            background: #f0fdf4;
            color: #22c55e;
        }
        .stat-wa-active .stat-value {
            color: #166534;
        }

        /* WhatsApp Inactive Colors */
        .stat-wa-inactive .stat-icon {
            background: #fef2f2;
            color: #ef4444;
        }
        .stat-wa-inactive .stat-value {
            color: #991b1b;
        }

        /* Responsive: 2 columns on tablet, 1 on mobile */
        @media (max-width: 1024px) {
            .stats-grid-4-col {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        @media (max-width: 575px) {
            .stats-grid-4-col {
                grid-template-columns: 1fr;
            }
        }

        /* Existing Styles (Unchanged) */
        .search-filter-box {
            background: var(--surface, #ffffff);
            border: 1px solid var(--line, #e2e8f0);
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            margin-bottom: 24px;
        }
        .users-search-filter-box {
            width: 100%;
            display: grid;
            grid-template-columns: 1.5fr 1fr 1fr 1fr auto;
            gap: 16px;
            align-items: end;
        }
        .users-search-filter-wrapper { display: contents; }
        .input-field { display: flex; flex-direction: column; gap: 6px; }
        .input-field .lbl { font-size: 11px; font-weight: 700; color: var(--ink-faint, #64748b); text-transform: uppercase; letter-spacing: 0.05em; }
        .tb-input, .tb-select {
            width: 100%; padding: 10px 14px; border: 1px solid var(--line, #e2e8f0);
            border-radius: 10px; background: var(--paper, #f8fafc); font-size: 13px;
            font-weight: 500; color: var(--ink, #0f172a); transition: all 0.2s ease;
            outline: none; height: 42px; box-sizing: border-box;
        }
        .tb-input:focus, .tb-select:focus { border-color: var(--indigo, #6366f1); background: #ffffff; box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1); }
        .users-filter-btn { display: flex; align-items: stretch; gap: 10px; height: 42px; }
        .users-filter-btn .btn { min-width: 100px; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 700; padding: 0 20px; border-radius: 10px; height: 100%; transition: all 0.2s ease; }
        .users-reset-btn { background: transparent; border: 1px solid var(--line, #e2e8f0); color: var(--ink-soft, #475569); }
        .users-reset-btn:hover { background: var(--paper, #f8fafc); color: var(--ink, #0f172a); }

        @media (max-width: 1200px) {
            .users-search-filter-box { grid-template-columns: 1fr 1fr; }
            .users-filter-btn { grid-column: 1 / -1; justify-content: flex-end; }
        }
        @media (max-width: 767px) {
            .search-filter-box { padding: 16px; }
            .users-search-filter-box { grid-template-columns: 1fr; }
            .users-filter-btn { flex-direction: row; justify-content: stretch; }
            .users-filter-btn .btn { flex: 1; }
        }

        .stat-card {
            background: var(--surface, #ffffff);
            border: 1px solid var(--line, #e2e8f0);
            border-radius: 14px;
            padding: 18px 20px;
            display: flex;
            align-items: center;
            gap: 16px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 12px 24px -8px rgba(0, 0, 0, 0.08); }
        .stat-icon { width: 52px; height: 52px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; flex-shrink: 0; }
        .stat-info { display: flex; flex-direction: column; }
        .stat-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: var(--ink-faint, #64748b); }
        .stat-value { font-size: 26px; font-weight: 800; color: var(--ink, #0f172a); margin-top: 4px; line-height: 1; }
        
        .stat-active .stat-icon { background: #f0fdf4; color: #22c55e; }
        .stat-active .stat-value { color: #166534; }
        .stat-suspended .stat-icon { background: #fef2f2; color: #ef4444; }
        .stat-suspended .stat-value { color: #991b1b; }
                .total-count-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: var(--indigo, #6366f1);
            color: #ffffff;
            font-size: 15px;
            font-weight: 800;
            padding: 4px 16px;
            border-radius: 20px;
            min-width: 45px;
            box-shadow: 0 4px 6px -1px rgba(99, 102, 241, 0.3);
            transform: translateY(-2px);
        }

        /* Modern Toggle Switch */
            .toggle-switch {
                position: relative;
                display: inline-block;
                width: 44px;
                height: 24px;
            }

            .toggle-switch input {
                opacity: 0;
                width: 0;
                height: 0;
            }

            .toggle-slider {
                position: absolute;
                cursor: pointer;
                top: 0; left: 0; right: 0; bottom: 0;
                background-color: #cbd5e1; /* Gray when inactive */
                transition: .3s cubic-bezier(0.4, 0, 0.2, 1);
                border-radius: 24px;
            }

            .toggle-slider:before {
                position: absolute;
                content: "";
                height: 18px;
                width: 18px;
                left: 3px;
                bottom: 3px;
                background-color: white;
                transition: .3s cubic-bezier(0.4, 0, 0.2, 1);
                border-radius: 50%;
                box-shadow: 0 2px 4px rgba(0,0,0,0.15);
            }

            /* Active State (Green) */
            input:checked + .toggle-slider {
                background-color: #22c55e; /* Green-500 */
            }

            input:checked + .toggle-slider:before {
                transform: translateX(20px);
            }

            /* Loading State (Jab request server par ja rahi ho) */
            .toggle-switch.loading .toggle-slider {
                opacity: 0.6;
                cursor: not-allowed;
            }
            .toggle-switch.loading .toggle-slider:before {
                animation: pulse 1s infinite;
            }

            @keyframes pulse {
                0% { transform: translateX(0) scale(1); }
                50% { transform: translateX(20px) scale(0.9); }
                100% { transform: translateX(0) scale(1); }
            }
    </style>
@endpush
@section('content')
    <div class="panel active">
        <!-- Page Header -->
        <div class="page-head" style="display:flex; justify-content:space-between; align-items:center;">
            <div>
                <h1>WhatsApp Notified Users
                    <!-- Total Count Badge -->
                    <span class="total-count-badge">{{ $totalNotifiedUsers ?? 0 }}</span>
                </h1>
                <p>Users who have WhatsApp notification entries in the system.</p>
            </div>
            <a href="{{ route('superadmin.users.index') }}" class="btn btn-ghost">← Back to All Users</a>
        </div>

        <!-- ✅ NEW: 4 Stats Boxes in 1 Row -->
        <div class="stats-grid stats-grid-4-col">
            <!-- Active Users -->
            <div class="stat-card stat-active">
                <div class="stat-icon">✅</div>
                <div class="stat-info">
                    <span class="stat-label">Active Users</span>
                    <span class="stat-value">{{ $activeUsers ?? 0 }}</span>
                </div>
            </div>

            <!-- Suspended Users -->
            <div class="stat-card stat-suspended">
                <div class="stat-icon">🚫</div>
                <div class="stat-info">
                    <span class="stat-label">Suspended Users</span>
                    <span class="stat-value">{{ $suspendedUsers ?? 0 }}</span>
                </div>
            </div>

            <!-- WhatsApp Active -->
            <div class="stat-card stat-wa-active">
                <div class="stat-icon">🟢</div>
                <div class="stat-info">
                    <span class="stat-label">WhatsApp Active</span>
                    <span class="stat-value">{{ $waActiveUsers ?? 0 }}</span>
                </div>
            </div>

            <!-- WhatsApp Inactive -->
            <div class="stat-card stat-wa-inactive">
                <div class="stat-icon">🔴</div>
                <div class="stat-info">
                    <span class="stat-label">WhatsApp Inactive</span>
                    <span class="stat-value">{{ $waInactiveUsers ?? 0 }}</span>
                </div>
            </div>
        </div>

        <!-- Search & Filter Box -->
        <div class="search-filter-box">
            <form method="GET" class="toolbar">
                <div class="users-search-filter-box">
                    <div class="users-search-filter-wrapper">
                        <div class="input-field">
                            <label class="lbl" for="search">Search</label>
                            <input class="tb-input search-field" name="search" value="{{ request('search') }}" placeholder="Search name or mobile…">
                        </div>

                        <div class="input-field">
                            <label class="lbl" for="user_status">User Status</label>
                            <select class="tb-select" name="user_status" onchange="this.form.submit()">
                                <option value="">All Users</option>
                                <option value="active" @selected(request('user_status') === 'active')>Active</option>
                                <option value="suspended" @selected(request('user_status') === 'suspended')>Suspended</option>
                            </select>
                        </div>

                        <div class="input-field">
                            <label class="lbl" for="whatsapp_status">WhatsApp Status</label>
                            <select class="tb-select" name="whatsapp_status" onchange="this.form.submit()">
                                <option value="">All Statuses</option>
                                <option value="active" @selected(request('whatsapp_status') === 'active')>Active</option>
                                <option value="inactive" @selected(request('whatsapp_status') === 'inactive')>Inactive</option>
                            </select>
                        </div>

                        <div class="input-field">
                            <label class="lbl" for="date">Joining Date</label>
                            <input type="date" class="tb-input" name="date" id="date" value="{{ request('date') }}">
                        </div>
                    </div>
                    <div class="users-filter-btn">
                        <button class="btn btn-primary" type="submit">Filter</button>
                        <a href="{{ route('superadmin.users.whatsapp-notified') }}" class="btn btn-ghost users-reset-btn">Reset</a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Data Table -->
        <div class="table-wrap table-wrap-res-01">
            <div class="users-table-box">
                <table class="users-table-wrapper">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>User Name</th>
                            <th>Joining Date</th>
                            <th>User Status</th>
                            <th>WhatsApp Status</th>
                            <th>Update Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($notifiedUsers as $item)
                            @php
                                $user = $item->user; 
                                $waStatus = strtolower($item->status ?? 'inactive');
                            @endphp
                            <tr>
                                {{-- <td data-label="No" class="mono">{{ $loop->iteration }}</td> --}}
                                <td data-label="No" class="mono">
                                    {{ $notifiedUsers->total() - (($notifiedUsers->currentPage() - 1) * $notifiedUsers->perPage()) - $loop->index }}
                                </td>
                                  <!-- Merged User Name & Mobile No -->
                                <td data-label="User Details">
                                    <div style="display: flex; align-items: center; gap: 12px;">
                                        
                                        <div style="display: flex; flex-direction: column; gap: 2px;">
                                            <b style="font-size: 14px; color: var(--ink, #0f172a); line-height: 1.2;">
                                                {{ $user->name }}
                                            </b>
                                            <span style="font-size: 12px; color: var(--ink-faint, #64748b); font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;">
                                                {{ $user->mobile ?? '—' }}
                                            </span>
                                        </div>
                                    </div>
                                </td>
                               
                                <td data-label="Joining Date" class="mono">
                                    {{ \Carbon\Carbon::parse($user->created_at)->format('d M Y, h:i A') }}
                                </td>
                                <td data-label="User Status">
                                    @if($user->is_active == 1)
                                        <span class="badge success" style="font-size: 11px;">Active</span>
                                    @else
                                        <span class="badge danger" style="font-size: 11px;">Suspended</span>
                                    @endif
                                </td>
                                {{-- <td data-label="WhatsApp Status">
                                    @if($waStatus === 'active')
                                        <span class="badge success" style="font-size: 11px;">🟢 Active</span>
                                    @else
                                        <span class="badge warn" style="font-size: 11px;">🔴 Inactive</span>
                                    @endif
                                </td> --}}

                                <td data-label="WhatsApp Status">
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <!-- Toggle Switch -->
                                        <label class="toggle-switch">
                                            <input type="checkbox" 
                                                class="whatsapp-status-toggle" 
                                                data-id="{{ $item->id }}" 
                                                data-status="{{ $waStatus }}" 
                                                {{ $waStatus === 'active' ? 'checked' : '' }}>
                                            <span class="toggle-slider"></span>
                                        </label>
                                        
                                        <!-- Text Label -->
                                        <span class="status-text" style="font-size: 12px; font-weight: 700; color: {{ $waStatus === 'active' ? '#166534' : '#991b1b' }};">
                                            {{ $waStatus === 'active' ? 'Active' : 'Inactive' }}
                                        </span>
                                    </div>
                                </td>
                                <td data-label="Update Date" class="mono">
                                    {{ \Carbon\Carbon::parse($item->updated_at)->format('d M Y, h:i A') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <!-- Fixed colspan to 7 to match the number of headers -->
                                <td colspan="7" style="text-align:center; color:var(--ink-faint); padding: 40px;">
                                    <div style="font-size: 32px; margin-bottom: 10px;">📭</div>
                                    No WhatsApp notified users found matching your filters.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div class="pagination">
                @if ($notifiedUsers->hasPages())
                    @include('common.pagination.superadmin_pagination', ['pageData' => $notifiedUsers])
                @endif
            </div>
        </div>
    </div>
@endsection 

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggles = document.querySelectorAll('.whatsapp-status-toggle');
    
    toggles.forEach(toggle => {
        toggle.addEventListener('change', function() {
            const id = this.dataset.id;
            const currentStatus = this.dataset.status;
            const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
            
            const textSpan = this.closest('td').querySelector('.status-text');
            const switchLabel = this.closest('.toggle-switch');

            // 1. Loading state ON (User do baar click na kar sake)
            switchLabel.classList.add('loading');
            this.disabled = true;

            // 2. CSRF Token lena (Laravel security ke liye zaroori hai)
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || 
                              document.querySelector('input[name="_token"]')?.value;

            // 3. Route URL banana (Safe tareeka)
            const baseUrl = "{{ route('superadmin.users.whatsapp-notified.toggle', ['id' => 'REPLACE_ID']) }}";
            // Note: Agar aapke route me 'superadmin.' prefix nahi hai, toh use hata dein
            const url = baseUrl.replace('REPLACE_ID', id);

            // 4. AJAX Request bhejna
            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ status: newStatus })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // UI Update karna success par
                    this.dataset.status = data.new_status;
                    if (data.new_status === 'active') {
                        textSpan.textContent = 'Active';
                        textSpan.style.color = '#166534'; // Green text
                    } else {
                        textSpan.textContent = 'Inactive';
                        textSpan.style.color = '#991b1b'; // Red text
                    }
                } else {
                    // Error par wapas revert karna
                    this.checked = !this.checked;
                    alert(data.message || 'Failed to update status.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                this.checked = !this.checked; // Network error par revert
                alert('Network error. Please try again.');
            })
            .finally(() => {
                // 5. Loading state OFF
                switchLabel.classList.remove('loading');
                this.disabled = false;
            });
        });
    });
});
</script>
@endpush