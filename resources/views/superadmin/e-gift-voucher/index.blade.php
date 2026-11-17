@extends('superadmin.layouts.app')
@section('title', 'E-Gift Vouchers')
<link rel="stylesheet" href="{{ asset('assets/css/custom.css') }}">

@section('content')
<div class="panel active">
    <div class="page-head" style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px;">
        <div>
            <h1>E-Gift Vouchers Management</h1>
            <p style="color: #64748b; font-size: 14px;">Manage shopping reward vouchers and their available stock.</p>
        </div>
        <!-- Button to open Add Modal -->
        <button type="button" onclick="openVoucherModal()" class="btn btn-primary" style="padding: 10px 20px; background: #6366f1; color: white; border-radius: 8px; border: none; font-weight: 700; cursor: pointer;">
            + Add Gift Voucher
        </button>
        
        <!-- Alternative: If you prefer a separate page route instead of a modal, uncomment the line below and remove the button above -->
        <!-- <a href="{{ route('superadmin.e_gift_voucher.create') }}" class="btn btn-primary" style="padding: 10px 20px; background: #6366f1; color: white; border-radius: 8px; border: none; font-weight: 700; cursor: pointer; text-decoration: none; display: inline-block;">+ Add Gift Voucher</a> -->
    </div>

    <!-- Search & Filter -->
    <div class="search-filter-box" style="background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 20px; margin-bottom: 24px;">
        <form method="GET" style="display: flex; gap: 16px; align-items: end; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 200px;">
                <label style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; display: block; margin-bottom: 6px;">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search voucher name or type..." style="width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 8px; box-sizing: border-box;">
            </div>
            <div style="min-width: 150px;">
                <label style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; display: block; margin-bottom: 6px;">Status</label>
                <select name="status" style="width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 8px; box-sizing: border-box;">
                    <option value="">All Status</option>
                    <option value="active" @selected(request('status')=='active')>Active</option>
                    <option value="inactive" @selected(request('status')=='inactive')>Inactive</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary" style="padding: 10px 20px; background: #6366f1; color: white; border-radius: 8px; border: none; font-weight: 700; cursor: pointer;">Filter</button>
            <a href="{{ route('superadmin.e_gift_voucher.index') }}" style="padding: 10px 20px; background: transparent; border: 1px solid #e2e8f0; border-radius: 8px; text-decoration: none; color: #475569; font-weight: 600; display: flex; align-items: center;">Reset</a>
        </form>
    </div>

    <!-- Vouchers Table -->
    <div class="table-wrap" style="overflow-x: auto; background: #fff; border-radius: 16px; border: 1px solid #e2e8f0;">
        <table style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead>
                <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                    <th style="padding: 16px; font-size: 12px; font-weight: 700; color: #64748b;">Image</th>
                    <th style="padding: 16px; font-size: 12px; font-weight: 700; color: #64748b;">Voucher Name</th>
                    <th style="padding: 16px; font-size: 12px; font-weight: 700; color: #64748b;">Type</th>
                    <th style="padding: 16px; font-size: 12px; font-weight: 700; color: #64748b;">Amount</th>
                    <th style="padding: 16px; font-size: 12px; font-weight: 700; color: #64748b;">Stock (Used / Total)</th>
                    <th style="padding: 16px; font-size: 12px; font-weight: 700; color: #64748b;">Status</th>
                    <th style="padding: 16px; font-size: 12px; font-weight: 700; color: #64748b;">Actions</th> <!-- Added Actions Column -->
                </tr>
            </thead>
            <tbody>
                @forelse($vouchers as $voucher)
                <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.2s;">
                    <td style="padding: 16px;">
                        @if($voucher->image)
                            {{-- <img src="{{ asset('storage/' . $voucher->image) }}" alt="Voucher" style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px; border: 1px solid #e2e8f0;"> --}}
                      
                        <img src="{{ Storage::disk('s3')->url($voucher->image) }}" alt="{{ $voucher->name }}"  style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px;">
                        @else
                            <div style="width: 50px; height: 50px; background: #f1f5f9; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 24px;">🎁</div>
                        @endif
                    </td>
                    <td style="padding: 16px; font-weight: 700; color: #0f172a;">{{ $voucher->name }}</td>
                    <td style="padding: 16px; color: #475569; text-transform: capitalize;">{{ $voucher->type }}</td>
                    <td style="padding: 16px; font-weight: 700; color: #6366f1;">₹{{ number_format($voucher->amount, 2) }}</td>
                    <td style="padding: 16px;">
                        <span style="font-weight: 600; color: #0f172a;">{{ $voucher->no_of_used }} / {{ $voucher->no_of_voucher }}</span>
                        <div style="font-size: 11px; color: #64748b;">Available: {{ $voucher->no_of_voucher - $voucher->no_of_used }}</div>
                    </td>
                    <td style="padding: 16px;">
                        @if($voucher->status == 'active')
                            <span style="display: inline-block; padding: 4px 12px; border-radius: 9999px; background: #dcfce7; color: #166534; font-size: 12px; font-weight: 600;">Active</span>
                        @else
                            <span style="display: inline-block; padding: 4px 12px; border-radius: 9999px; background: #f1f5f9; color: #64748b; font-size: 12px; font-weight: 600;">Inactive</span>
                        @endif
                    </td>
                    <td style="padding: 16px;">
                        <div style="display: flex; gap: 8px;">
                            <!-- Edit Button (Opens Modal with data) -->
                            <button type="button" onclick="editVoucher({{ $voucher->id }}, '{{ addslashes($voucher->name) }}', '{{ $voucher->type }}', '{{ addslashes($voucher->description) }}', {{ $voucher->no_of_voucher }}, {{ $voucher->amount }}, '{{ $voucher->status }}'  ,  '{{ $voucher->image ? Storage::disk('s3')->url($voucher->image) : '' }}')" style="padding: 6px 12px; background: #f59e0b; color: white; border-radius: 6px; border: none; font-size: 12px; font-weight: 600; cursor: pointer;">Edit</button>
                            
                            <!-- Soft Delete Button -->
                            <form action="{{ route('superadmin.e_gift_voucher.destroy', $voucher->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to soft delete this voucher?');" style="display: inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" style="padding: 6px 12px; background: #ef4444; color: white; border-radius: 6px; border: none; font-size: 12px; font-weight: 600; cursor: pointer;">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="text-align: center; padding: 40px; color: #94a3b8;">No vouchers found matching your filters.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    <div class="pagination">
            @include('common.pagination.superadmin_pagination', ['pageData' => $vouchers])
    </div>
</div>

<!-- Add/Edit Voucher Modal -->
<div id="voucherModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 16px; width: 90%; max-width: 900px; padding: 24px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); max-height: 90vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2 id="modalTitle" style="font-size: 20px; font-weight: 700; color: #0f172a; margin: 0;">Add Gift Voucher</h2>
            <button onclick="closeVoucherModal()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #64748b; line-height: 1;">&times;</button>
        </div>
        
        <form id="voucherForm" method="POST" action="{{ route('superadmin.e_gift_voucher.store') }}" enctype="multipart/form-data">
            @csrf
            <!-- Hidden field to handle PUT/PATCH method for updates -->
            <input type="hidden" name="_method" id="formMethod" value="POST">
            <input type="hidden" name="id" id="voucherId" value="">

            <!-- 4 Columns: Name, Type, Description, No. of Vouchers -->
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 16px;">
                <div>
                    <label style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; display: block; margin-bottom: 6px;">Voucher Name</label>
                    <input type="text" name="name" id="v_name" required style="width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px; box-sizing: border-box;">
                </div>
                
                <div>
                    <label style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; display: block; margin-bottom: 6px;">Type</label>
                    <select name="type" id="v_type" required style="width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px; box-sizing: border-box;">
                        <option value="">Select Type</option>
                        @foreach($giftFroms ?? [] as $from)
                            <option value="{{ $from->name }}">{{ $from->name }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div>
                    <label style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; display: block; margin-bottom: 6px;">Description</label>
                    <input type="text" name="description" id="v_description" style="width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px; box-sizing: border-box;">
                </div>
                
                <div>
                    <label style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; display: block; margin-bottom: 6px;">No. of Vouchers</label>
                    <input type="number" name="no_of_voucher" id="v_no_of_voucher" required min="1" style="width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px; box-sizing: border-box;">
                </div>
            </div>

            
            <!-- Additional Fields: Amount & Status -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px;">
                <div>
                    <label style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; display: block; margin-bottom: 6px;">Amount (₹)</label>
                    <input type="number" name="amount" id="v_amount" required min="0" step="0.01" style="width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px; box-sizing: border-box;">
                </div>

                    {{-- Image --}}
                <div>
                    <label style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; display: block; margin-bottom: 6px;">
                       Logo 
                    </label>

                    <input type="file" name="image" id="v_image" accept="image/jpeg,image/png,image/jpg,image/gif,image/webp"
                        style="width: 220px; padding: 7px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 13px; box-sizing: border-box;">

                    <small style="display: block; margin-top: 5px; color: #94a3b8;">
                        JPG, PNG, WEBP up to 1MB
                    </small>

                       {{-- Current Image --}}
                    <div id="currentVoucherImage" style="display: none; margin-top: 8px;">
                        <img id="currentVoucherImagePreview"
                            src=""
                            alt="Current Voucher Logo"
                            style="width: 55px; height: 55px; object-fit: cover; border-radius: 8px; border: 1px solid #e2e8f0;">

                        <div style="font-size: 11px; color: #64748b; margin-top: 3px;">
                            Current Logo
                        </div>
                    </div>
                </div>

                <!-- Status (Active/Inactive) -->
                <div>
                    <label style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; display: block; margin-bottom: 6px;">Status</label>
                    <div style="display: flex; gap: 24px; padding: 10px 0;">
                        <label style="display: flex; align-items: center; gap: 6px; cursor: pointer; font-size: 14px; color: #0f172a;">
                            <input type="radio" name="status" id="v_status_active" value="active" checked style="accent-color: #6366f1; width: 16px; height: 16px;"> Active
                        </label>
                        <label style="display: flex; align-items: center; gap: 6px; cursor: pointer; font-size: 14px; color: #0f172a;">
                            <input type="radio" name="status" id="v_status_inactive" value="inactive" style="accent-color: #6366f1; width: 16px; height: 16px;"> Inactive
                        </label>
                    </div>
                </div>
            </div>

          

            <div style="display: flex; justify-content: flex-end; gap: 12px; border-top: 1px solid #e2e8f0; padding-top: 20px;">
                <button type="button" onclick="closeVoucherModal()" style="padding: 10px 20px; background: transparent; border: 1px solid #e2e8f0; border-radius: 8px; color: #475569; font-weight: 600; cursor: pointer;">Cancel</button>
                <button type="submit" style="padding: 10px 20px; background: #6366f1; color: white; border-radius: 8px; border: none; font-weight: 700; cursor: pointer;">Save Voucher</button>
            </div>
        </form>
    </div>
</div>

<script>
// Open Modal for Adding New Voucher
function openVoucherModal() {
    document.getElementById('modalTitle').innerText = 'Add Gift Voucher';
    document.getElementById('formMethod').value = 'POST';
    document.getElementById('voucherForm').action = "{{ route('superadmin.e_gift_voucher.store') }}";
    document.getElementById('voucherForm').reset();
    document.getElementById('voucherId').value = '';
    document.getElementById('v_status_active').checked = true;
    document.getElementById('voucherModal').style.display = 'flex';
       // Hide old image preview
    document.getElementById('currentVoucherImage').style.display = 'none';
    document.getElementById('currentVoucherImagePreview').src = '';

}

// Close Modal
function closeVoucherModal() {
    document.getElementById('voucherModal').style.display = 'none';
}

// Open Modal for Editing Existing Voucher
function editVoucher(id, name, type, description, no_of_voucher, amount, status, imageUrl) {
    document.getElementById('modalTitle').innerText = 'Edit Gift Voucher';
    document.getElementById('formMethod').value = 'PUT'; // or 'PATCH'
    
    // Dynamically replace the ID in the update route
    let updateRoute = "{{ route('superadmin.e_gift_voucher.update', '__ID__') }}".replace('__ID__', id);
    document.getElementById('voucherForm').action = updateRoute;

    document.getElementById('voucherId').value = id;
    document.getElementById('v_name').value = name;
    document.getElementById('v_type').value = type;
    document.getElementById('v_description').value = description;
    document.getElementById('v_no_of_voucher').value = no_of_voucher;
    document.getElementById('v_amount').value = amount;
    
    if (status === 'active') {
        document.getElementById('v_status_active').checked = true;
    } else {
        document.getElementById('v_status_inactive').checked = true;
    }

    // Current voucher image
    const currentImageBox = document.getElementById('currentVoucherImage');
    const currentImage = document.getElementById('currentVoucherImagePreview');

    if (imageUrl) {
        currentImage.src = imageUrl;
        currentImageBox.style.display = 'block';
    } else {
        currentImage.src = '';
        currentImageBox.style.display = 'none';
    }

    // File input always empty in edit mode
    document.getElementById('v_image').value = '';
    
    document.getElementById('voucherModal').style.display = 'flex';
}

// Close modal when clicking outside the modal box
window.onclick = function(event) {
    let modal = document.getElementById('voucherModal');
    if (event.target == modal) {
        closeVoucherModal();
    }
}
</script>
@endsection