@extends('superadmin.layouts.app')
@section('title', 'Bullet News Management')

@section('content')
@push('styles')
<style>
    /* Modern Toggle Switch */
    .switch { position: relative; display: inline-block; width: 44px; height: 24px; }
    .switch input { opacity: 0; width: 0; height: 0; }
    .slider {
        position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0;
        background-color: #cbd5e1; transition: .3s; border-radius: 24px;
    }
    .slider:before {
        position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px;
        background-color: white; transition: .3s; border-radius: 50%; box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }
    input:checked + .slider { background-color: #10b981; }
    input:checked + .slider:before { transform: translateX(20px); }
    .status-label { font-size: 12px; font-weight: 600; margin-left: 8px; vertical-align: middle; color: #166534; }
    .status-label.inactive { color: #991b1b; }

    /* Modern Table Styles */
    .news-table { width: 100%; border-collapse: separate; border-spacing: 0; }
    .news-table thead th {
        padding: 14px 16px; font-size: 11px; font-weight: 700; color: #64748b;
        text-transform: uppercase; letter-spacing: 0.5px; text-align: left;
        border-bottom: 2px solid #e2e8f0; background: #f8fafc;
    }
    .news-table tbody td {
        padding: 16px; border-bottom: 1px solid #f1f5f9; vertical-align: middle;
    }
    .news-table tbody tr:hover { background: #f8fafc; transition: background 0.2s; }

    /* Image Box */
    .news-img-box {
        width: 56px; height: 56px; border-radius: 10px; overflow: hidden;
        background: #f1f5f9; display: flex; align-items: center; justify-content: center;
        border: 1px solid #e2e8f0;
    }
    .news-img-box img { width: 100%; height: 100%; object-fit: cover; }
    .news-img-box svg { width: 28px; height: 28px; color: #94a3b8; }

    /* Title & Description */
    .news-title { font-weight: 600; color: #0f172a; font-size: 14px; margin-bottom: 0; }
    .news-desc {
        font-size: 12px; color: #64748b; font-style: italic;
        background: #f8fafc; padding: 4px 10px; border-radius: 6px;
        border-left: 3px solid #cbd5e1; display: inline-block; max-width: 280px;
    }

    /* Action Buttons (Solid Colors like Voucher Form, Side-by-Side) */
    .btn-action {
        display: inline-flex; align-items: center; gap: 4px;
        padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 600;
        border: none; cursor: pointer; transition: all 0.2s; text-decoration: none;
    }
    .btn-edit { background: #f59e0b; color: white; }
    .btn-edit:hover { background: #d97706; }
    .btn-delete { background: #ef4444; color: white; }
    .btn-delete:hover { background: #dc2626; }
</style>
@endpush

<div class="panel active">
    <div class="page-head" style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px;">
        <div>
            <h1>📰 Bullet News Management</h1>
            <p style="color: #64748b; font-size: 14px;">Manage quick daily updates and announcements.</p>
        </div>
        <button type="button" onclick="openAddModal()" style="padding: 10px 20px; background: #6366f1; color: white; border-radius: 8px; border: none; cursor: pointer; font-weight: 600; display: flex; align-items: center; gap: 6px;">
            <span>+</span> Add Bullet News
        </button>
    </div>

  

    <!-- Search -->
    <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; margin-bottom: 20px;">
        <form method="GET" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: flex-end;">
            
            <!-- Search Input -->
            <div style="flex: 1; min-width: 200px;">
                <label style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; display: block; margin-bottom: 6px;">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search from title..." style="width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 6px; box-sizing: border-box;">
            </div>

            <!-- ✅ From Date Input -->
            <div style="flex: 1; min-width: 150px;">
                <label style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; display: block; margin-bottom: 6px;">From Date</label>
                <input type="date" name="from_date" value="{{ request('from_date', \Carbon\Carbon::now()->format('Y-m-d')) }}" style="width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 6px; box-sizing: border-box;">
            </div>

            <!-- ✅ To Date Input -->
            <div style="flex: 1; min-width: 150px;">
                <label style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; display: block; margin-bottom: 6px;">To Date</label>
                <input type="date" name="to_date" value="{{ request('to_date', \Carbon\Carbon::now()->format('Y-m-d')) }}" style="width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 6px; box-sizing: border-box;">
            </div>

            <!-- Buttons -->
            <div style="display: flex; gap: 10px; align-items: flex-end;">
                <button type="submit" style="padding: 0 20px; background: #6366f1; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; height: 42px;">Filter</button>
                <a href="{{ route('superadmin.news.index') }}" style="padding: 0 20px; background: #fff; color: #475569; border: 1px solid #e2e8f0; border-radius: 6px; text-decoration: none; font-weight: 600; height: 42px; display: inline-flex; align-items: center;">Reset</a>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div style="overflow-x: auto; background: #fff; border-radius: 12px; border: 1px solid #e2e8f0;">
        <table class="news-table">
            <thead>
                <tr>
                    <th style="width: 80px;">Image</th>
                    <th>Title</th>
                    <th style="width: 250px;">Description</th>
                    <th style="width: 120px;">Status</th>
                    <th style="width: 160px;">Date</th>
                    <th style="width: 160px; text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($news as $item)
                    <tr>
                        <td>
                            @if($item->image)
                                <div class="news-img-box">
                                    <img src="{{ Storage::disk('s3')->url($item->image) }}" alt="News">
                                </div>
                            @else
                                <div class="news-img-box">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                                        <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                                        <line x1="8" y1="7" x2="16" y2="7"></line>
                                        <line x1="8" y1="11" x2="14" y2="11"></line>
                                    </svg>
                                </div>
                            @endif
                        </td>
                        <td>
                            <div class="news-title">{{ Str::limit($item->title, 40) }}</div>
                        </td>
                        <td>
                            <div class="news-desc">{{ Str::limit($item->description ?? 'No description', 20) }}</div>
                        </td>
                        <td>
                            <label class="switch">
                                <input type="checkbox" class="status-toggle" data-id="{{ $item->id }}" data-label="label-{{ $item->id }}" {{ $item->status == 'active' ? 'checked' : '' }}>
                                <span class="slider"></span>
                            </label>
                            <span id="label-{{ $item->id }}" class="status-label {{ $item->status }}">{{ ucfirst($item->status) }}</span>
                        </td>
                        <td style="font-size: 13px; color: #64748b; white-space: nowrap;">
                            {{ $item->created_at ? \Carbon\Carbon::parse($item->created_at)->format('d M Y, h:i A') : 'N/A' }}
                        </td>
                        <td style="text-align: right;">
                            <div style="display: flex; gap: 8px; justify-content: flex-end;">
                                @php $imgUrl = $item->image ? Storage::disk('s3')->url($item->image) : ''; @endphp
                                
                                <!-- Edit Button -->
                                <button type="button" onclick="openEditModal({{ $item->id }}, '{{ addslashes($item->title) }}', '{{ addslashes($item->description ?? '') }}', '{{ $imgUrl }}')" 
                                        class="btn-action btn-edit">
                                     Edit
                                </button>
                                
                                <!-- Delete Button -->
                                <form action="{{ route('superadmin.news.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this news?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-action btn-delete">
                                         Delete
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 40px; color: #94a3b8;">No bullet news records found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination" style="margin-top: 20px;">
        @include('common.pagination.superadmin_pagination', ['pageData' => $news])
    </div>
</div>

{{-- ✅ Include the Modal from separate file --}}
@include('superadmin.news._form_modal')

@push('scripts')
<script>
    // Toggle Status AJAX Logic
    const toggleUrlTemplate = "{{ route('superadmin.news.toggleStatus', ':id') }}";
    document.querySelectorAll('.status-toggle').forEach(toggle => {
        toggle.addEventListener('change', function() {
            const checkbox = this;
            const newsId = checkbox.dataset.id;
            const labelId = checkbox.dataset.label;
            const labelEl = document.getElementById(labelId);
            const wasChecked = checkbox.checked;
            const newStatus = wasChecked ? 'active' : 'inactive';

            labelEl.textContent = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
            labelEl.className = 'status-label ' + newStatus;

            fetch(toggleUrlTemplate.replace(':id', newsId), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
            })
            .then(res => res.json())
            .then(data => {
                if (!data.success) {
                    checkbox.checked = !wasChecked;
                    labelEl.textContent = wasChecked ? 'Inactive' : 'Active';
                    labelEl.className = 'status-label ' + (wasChecked ? 'inactive' : 'active');
                }
            })
            .catch(err => {
                checkbox.checked = !wasChecked;
                labelEl.textContent = wasChecked ? 'Inactive' : 'Active';
                labelEl.className = 'status-label ' + (wasChecked ? 'inactive' : 'active');
            });
        });
    });
</script>
@endpush
@endsection