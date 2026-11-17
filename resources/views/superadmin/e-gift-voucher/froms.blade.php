@extends('superadmin.layouts.app')
@section('title', 'E-Gift Froms')
<link rel="stylesheet" href="{{ asset('assets/css/custom.css') }}">

@section('content')
<div class="panel active">
    <div class="page-head" style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px;">
        <div>
            <h1>E-Gift Froms Management</h1>
            <p style="color: #64748b; font-size: 14px;">Manage available e-gift froms (e.g., Flipkart, Amazon).</p>
        </div>
        <button type="button" onclick="openFromModal()" class="btn btn-primary" style="padding: 10px 20px; background: #6366f1; color: white; border-radius: 8px; border: none; font-weight: 700; cursor: pointer;">
            + Add New From
        </button>
    </div>

    <!-- Search & Filter -->
    <div class="search-filter-box" style="background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 20px; margin-bottom: 24px;">
        <form method="GET" style="display: flex; gap: 16px; align-items: end; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 200px;">
                <label style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; display: block; margin-bottom: 6px;">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search from name..." style="width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 8px; box-sizing: border-box;">
            </div>
            <div style="min-width: 150px;">
                <label style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; display: block; margin-bottom: 6px;">Status</label>
                <select name="status" style="width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 8px; box-sizing: border-box;">
                    <option value="">All Status</option>
                    <option value="active" @selected(request('status')=='active')>Active</option>
                    <option value="inactive" @selected(request('status')=='inactive')>Inactive</option>
                </select>
            </div>
            <button type="submit" style="padding: 10px 20px; background: #6366f1; color: white; border-radius: 8px; border: none; font-weight: 700; cursor: pointer;">Filter</button>
            <a href="{{ route('superadmin.e_gift_froms.index') }}" style="padding: 10px 20px; background: transparent; border: 1px solid #e2e8f0; border-radius: 8px; text-decoration: none; color: #475569; font-weight: 600; display: flex; align-items: center;">Reset</a>
        </form>
    </div>

    <!-- Froms Table -->
    <div class="table-wrap" style="overflow-x: auto; background: #fff; border-radius: 16px; border: 1px solid #e2e8f0;">
        <table style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead>
                <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                    <th style="padding: 16px; font-size: 12px; font-weight: 700; color: #64748b;">ID</th>
                    <th style="padding: 16px; font-size: 12px; font-weight: 700; color: #64748b;">From Name</th>
                    <th style="padding: 16px; font-size: 12px; font-weight: 700; color: #64748b;">Status</th>
                    <th style="padding: 16px; font-size: 12px; font-weight: 700; color: #64748b;">Created At</th>
                    <th style="padding: 16px; font-size: 12px; font-weight: 700; color: #64748b; text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($forms as $form)
                <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.2s;">
                    <td style="padding: 16px; color: #64748b;">{{ $form->id }}</td>
                    <td style="padding: 16px; font-weight: 700; color: #0f172a;">{{ $form->name }}</td>
                    <td style="padding: 16px;">
                        @if($form->status == 'active')
                            <span style="display: inline-block; padding: 4px 12px; border-radius: 9999px; background: #dcfce7; color: #166534; font-size: 12px; font-weight: 600;">Active</span>
                        @else
                            <span style="display: inline-block; padding: 4px 12px; border-radius: 9999px; background: #f1f5f9; color: #64748b; font-size: 12px; font-weight: 600;">Inactive</span>
                        @endif
                    </td>
                    <td style="padding: 16px; color: #64748b; font-size: 13px;">{{ \Carbon\Carbon::parse($form->created_at)->format('d M Y') }}</td>
                    <td style="padding: 16px; text-align: right;">
                        <div style="display: flex; gap: 8px; justify-content: flex-end;">
                            <button type="button" onclick="editFrom({{ $form->id }}, '{{ addslashes($form->name) }}', '{{ $form->status }}')" style="padding: 6px 12px; background: #f59e0b; color: white; border-radius: 6px; border: none; font-size: 12px; font-weight: 600; cursor: pointer;">Edit</button>
                            
                            <form action="{{ route('superadmin.e_gift_froms.destroy', $form->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this from?');" style="display: inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" style="padding: 6px 12px; background: #ef4444; color: white; border-radius: 6px; border: none; font-size: 12px; font-weight: 600; cursor: pointer;">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" style="text-align: center; padding: 40px; color: #94a3b8;">No froms found matching your filters.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    
    <div class="pagination">
        @include('common.pagination.superadmin_pagination', ['pageData' => $forms])
    </div>
</div>

<!-- Add/Edit From Modal -->
<div id="fromModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 16px; width: 90%; max-width: 500px; padding: 24px; box-shadow: 0 10px 25px rgba(0,0,0,0.1);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2 id="modalTitle" style="font-size: 20px; font-weight: 700; color: #0f172a; margin: 0;">Add E-Gift From</h2>
            <button onclick="closeFromModal()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #64748b; line-height: 1;">&times;</button>
        </div>
        
        <form id="fromForm" method="POST" action="{{ route('superadmin.e_gift_froms.store') }}">
            @csrf
            <input type="hidden" name="_method" id="formMethod" value="POST">

            <div style="margin-bottom: 16px;">
                <label style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; display: block; margin-bottom: 6px;">From Name</label>
                <input type="text" name="name" id="f_name" required placeholder="e.g., Flipkart, Amazon" style="width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px; box-sizing: border-box;">
            </div>

            <div style="margin-bottom: 24px;">
                <label style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; display: block; margin-bottom: 6px;">Status</label>
                <div style="display: flex; gap: 24px; padding: 10px 0;">
                    <label style="display: flex; align-items: center; gap: 6px; cursor: pointer; font-size: 14px; color: #0f172a;">
                        <input type="radio" name="status" id="f_status_active" value="active" checked style="accent-color: #6366f1; width: 16px; height: 16px;"> Active
                    </label>
                    <label style="display: flex; align-items: center; gap: 6px; cursor: pointer; font-size: 14px; color: #0f172a;">
                        <input type="radio" name="status" id="f_status_inactive" value="inactive" style="accent-color: #6366f1; width: 16px; height: 16px;"> Inactive
                    </label>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 12px; border-top: 1px solid #e2e8f0; padding-top: 20px;">
                <button type="button" onclick="closeFromModal()" style="padding: 10px 20px; background: transparent; border: 1px solid #e2e8f0; border-radius: 8px; color: #475569; font-weight: 600; cursor: pointer;">Cancel</button>
                <button type="submit" style="padding: 10px 20px; background: #6366f1; color: white; border-radius: 8px; border: none; font-weight: 700; cursor: pointer;">Save From</button>
            </div>
        </form>
    </div>
</div>

<script>
function openFromModal() {
    document.getElementById('modalTitle').innerText = 'Add E-Gift From';
    document.getElementById('formMethod').value = 'POST';
    document.getElementById('fromForm').action = "{{ route('superadmin.e_gift_froms.store') }}";
    document.getElementById('fromForm').reset();
    document.getElementById('f_status_active').checked = true;
    document.getElementById('fromModal').style.display = 'flex';
}

function closeFromModal() {
    document.getElementById('fromModal').style.display = 'none';
}

function editFrom(id, name, status) {
    document.getElementById('modalTitle').innerText = 'Edit E-Gift From';
    document.getElementById('formMethod').value = 'PUT';
    
    let updateRoute = "{{ route('superadmin.e_gift_froms.update', '__ID__') }}".replace('__ID__', id);
    document.getElementById('fromForm').action = updateRoute;

    document.getElementById('f_name').value = name;
    
    if (status === 'active') {
        document.getElementById('f_status_active').checked = true;
    } else {
        document.getElementById('f_status_inactive').checked = true;
    }
    
    document.getElementById('fromModal').style.display = 'flex';
}

window.onclick = function(event) {
    let modal = document.getElementById('fromModal');
    if (event.target == modal) {
        closeFromModal();
    }
}
</script>
@endsection