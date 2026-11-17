@extends('superadmin.layouts.app')
<link rel="stylesheet" href="{{ asset('assets/css/custom.css') }}">
@push('styles')
    <style>
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(15, 23, 42, 0.4);
            backdrop-filter: blur(4px);
            z-index: 999;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .modal.active {
            display: flex;
        }

        .modal-body {
            background: var(--surface);
            border-radius: 20px;
            width: 100%;
            max-width: 780px;
            max-height: 90vh;
            display: flex;
            flex-direction: column;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            animation: modalIn 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        .modal-scroll {
            padding: 20px 20px;
            overflow-y: auto;
            flex: 1;
        }

        .modal-section-title {
            font-size: 13px;
            font-weight: 700;
            color: var(--indigo);
            text-transform: uppercase;
            letter-spacing: .05em;
            margin: 24px 0 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .modal-section-title::after {
            content: "";
            flex: 1;
            height: 1px;
            background: var(--line);
        }

        .modal-section-title:first-child {
            margin-top: 0;
        }

        /* Detail view grids */
        .detail-grid {
            display: grid;
           grid-template-columns: repeat(2, 1fr);
            gap: 16px;
        }

        .detail-card {
            background: var(--paper);
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 12px 16px;
        }
        
        .detail-card .lbl {
            font-size: 11px;
            font-weight: 700;
            color: var(--ink-faint);
            margin-bottom: 4px;
        }

        .detail-card .val {
            font-size: 13px;
            font-weight: 600;
            color: var(--ink);
            word-break: break-word;
        }

        .tenant-avatar-lg {
            width: 60px;
            height: 60px;
            border-radius: 16px;
            background: #EDEBFC;
            color: var(--indigo);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: 700;
            object-fit: cover;
        }

        .detail-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 24px;
        }
        
        .detail-header h3 {
            font-size: 18px;
            margin-bottom: 4px;
        }

        .detail-header-tags {
            display: flex;
            gap: 8px;
        }
        .switch {
            position: relative;
            display: inline-block;
            width: 48px;
            height: 24px;
        }

        .switch input {
            display: none;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            inset: 0;
            background: #ccc;
            transition: .3s;
            border-radius: 24px;
        }

        .slider:before {
            position: absolute;
            content: "";
            width: 18px;
            height: 18px;
            left: 3px;
            top: 3px;
            background: #fff;
            transition: .3s;
            border-radius: 50%;
        }

        .switch input:checked + .slider {
            background: #28a745;
        }

        .switch input:checked + .slider:before {
            transform: translateX(24px);
        }

        .retailer-action-btn{
            display: flex;
            gap: 10px;
        }

        .retailers-btn-box{
            display: flex;
            gap: 10px;
        }

          input {
            border: 1px solid var(--line);
            border-radius: 9px;
            padding: 9px 11px;
            font-family: var(--font-body);
            font-size: 12.5px;
            background: var(--paper);
            color: var(--ink);
        }

        @media(max-width:991px){
            .cell-user {
                text-align: end;
                flex-direction: row-reverse;
            }
        }

         @media(max-width:575px){
           .detail-header{
              flex-direction: column;
              align-items: start;
           }

           .detail-grid{
                grid-template-columns: repeat(1, 1fr);
           }

           .modal-scroll-02{
            padding: 16px 10px !important;
           }
         }
    </style>
@endpush

@section('content')
    <div class="fh" style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 24px; flex-wrap:wrap; gap:10px;">
        <div>
            <h2 style="font-family: var(--font-display); font-size: 24px; font-weight: 700;">Retailer</h2>
            <div style="font-size:12px; color:var(--ink-faint); margin-top:4px;">Manage system Retailer</div>
        </div>
        <div class="retailers-btn-box">
            <button class="btn btn-primary" onclick="openTenantModal()">+ Add Retailer</button>
            <button class="btn btn-primary" onclick="importTenantModal()">+ Import Retailer</button>
        </div>


    </div>

    {{-- Search / Filter Toolbar --}}
    <div class="toolbar search-filter-box">
        <form method="GET" action="{{ route('superadmin.tenants.index') }}" style="width:100%;">
            <div class="retailers-search-filter-box">
                <div class="retailers-search-filter-wrapper">
                    <div class="input-field">
                        <label class="lbl" for="search">Search</label>
                         <input type="text" name="search" class="tb-input search-field" placeholder="Search name, slug, email, phone..." value="{{ request('search') }}">
                    </div>
                    <div class="input-field">
                         <label class="lbl" for="search">Status</label>
                        <select name="status" class="tb-select status-field" onchange="this.form.submit()">
                            <option value="">All Status</option>
                            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="retailers-filter-btn">
                    <button type="submit" class="btn btn-primary" style="font-size:12px; padding: 8px 16px;">Filter</button>
                      <a href="{{ route('superadmin.tenants.index') }}" class="btn btn-ghost" style="font-size:11px;">Clear</a>
                </div>
            </div>
        </form>
    </div>

    {{-- Tenants Table --}}
    <div class="table-wrap table-wrap-res-01">
        <div class="retailer-table-box">
            <table class="retailer-table-wrapper">
                <thead>
                    <tr>
                        <th>Retailer Info</th>
                        <th>Retailer Details</th>
                        <th>Status</th>
                        <th>Created On</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tenants as $tenant)
                        <tr>
                            <td data-label="Retailer Info">
                                <div class="cell-user">
                                    @if($tenant->logo)
                                        {{-- <img src="{{ Storage::url($tenant->logo) }}" class="cell-av" alt=""> --}}
                                        <img src="{{ Storage::disk('s3')->url($tenant->logo) }}" class="cell-av">
                                    @else
                                        <div class="cell-av">{{ strtoupper(substr($tenant->name, 0, 2)) }}</div>
                                    @endif
                                    <div>
                                        <b>{{ $tenant->name }}</b>
                                        <span>{{ $tenant->email ?? 'No email' }} | +91 {{ $tenant->phone ?? 'No phone' }}</span>
                                    </div>
                                </div>
                            </td>
                            <td data-label="Retailer Details" class="mono">
                                <div class="retailers-detail-box">
                                    Store Name : {{ $tenant->name }}
                                    <p>Retailer ID : {{ $tenant->id }}</p>
                                    <p>Contact Phone : {{ $tenant->phone }}</p>
                                    <p>Contact Email : {{ $tenant->email }}</p>
                                    <p>Domain : {{ $tenant->domain }}</p>
                                    <p>Sub Domain : {{ $tenant->sub_domain }}</p>
                                    <p>Theme : {{ $tenant->theme }}</p>
                                </div>
                            </td>
                        
                            <td data-label="Status"> 
                                <div style="display:flex;align-items:center;gap:10px;">
                                    <label class="switch">
                                        <input
                                            type="checkbox"
                                            class="tenant-status-toggle"
                                            data-id="{{ $tenant->id }}"
                                            {{ $tenant->status == 'active' ? 'checked' : '' }}>
                                        <span class="slider"></span>
                                    </label>

                                    <span class="status-text">
                                        {{ ucfirst($tenant->status) }}
                                    </span>
                                </div>
                            </td>
                            <td data-label="Created On" class="mono">{{ $tenant->created_at->format('d M, Y') }}</td>
                            <td data-label="Actions" class="row-actions" style="justify-content: flex-end;">
                                <div class="retailer-action-btn">
                                    <button type="button" class="icon-btn" title="View Details" onclick='openViewModal(@json($tenant))'>👁</button>
                                    <a class="icon-btn" title="Edit Tenant" href="{{ route('superadmin.tenants.index', ['edit' => $tenant->id]) }}">✎</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="text-align: center; color: var(--ink-faint); padding: 32px;">No tenants found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="pagination">
            @include('common.pagination.superadmin_pagination', ['pageData' => $tenants])
        </div>
    </div>

    {{-- Add/Edit Tenant Modal --}}
    <div class="modal {{ $editing || $errors->any() ? 'active' : '' }}" id="tenantModal">
        <div class="modal-body">
            <div class="fh" style="padding: 24px 32px 20px; border-bottom: 1px solid var(--line); display:flex; justify-content:space-between; align-items:center;">
                <h3 style="font-size:18px; font-weight:700;">{{ $editing ? 'Edit Retailer' : 'Add Retailer' }}</h3>
                <button type="button" class="icon-btn" onclick="closeTenantModal()" style="border:none;background:transparent;box-shadow:none;">✕</button>
            </div>
            <form method="POST" enctype="multipart/form-data" action="{{ $editing ? route('superadmin.tenants.update', $editing) : route('superadmin.tenants.store') }}" style="display:flex; flex-direction:column; flex:1; overflow:hidden;">
                @csrf
                @if ($editing)
                    @method('PUT')
                @endif
                
                <div class="modal-scroll">
                    
                    {{-- 1. Basic Info --}}
                    <div class="modal-section-title">1. Basic Info</div>
                    <div class="form-grid form-grid-02">
                        <div class="field full">
                            <label>Retailer Name <span class="req">*</span></label>
                            <input type="text" name="name" value="{{ old('name', $editing->name ?? '') }}" required>
                        </div>
                        <div class="field">
                            <label>Email</label>
                            <input type="email" name="email" value="{{ old('email', $editing->email ?? '') }}">
                        </div>
                        <div class="field">
                            <label>Phone</label>
                            <input type="text" name="phone" value="{{ old('phone', $editing->phone ?? '') }}"  maxlength="10" minlength="10" pattern="[0-9]{10}" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0,10);">
                        </div>
                        <div class="field">
                            <label>Slug <span class="hint">(Leave blank for auto-generate)</span></label>
                            <input type="text" name="slug" value="{{ old('slug', $editing->slug ?? '') }}">
                        </div>
                        <div class="field">
                            <label>Status</label>
                            <select name="status">
                                <option value="active" @selected(old('status', $editing->status ?? 'active') === 'active')>Active</option>
                                <option value="inactive" @selected(old('status', $editing->status ?? 'active') === 'inactive')>Inactive</option>
                            </select>
                        </div>
                        <div class="field full">
                            <label>Logo (Optional)</label>
                            <input type="file" name="logo" accept="image/*">
                            @if($editing && $editing->logo)
                                <div style="font-size:11px; margin-top:4px;"><a href="{{ Storage::disk('s3')->url($editing->logo) }}" target="_blank">View current logo</a></div>
                            @endif
                        </div>
                    </div>

                    {{-- 2. Compliance --}}
                    <div class="modal-section-title">2. Compliance & Taxation</div>
                    <div class="form-grid form-grid-02">
                        <div class="field">
                            <label>PAN Number</label>
                            <input type="text" name="pan_number" value="{{ old('pan_number', $editing->pan_number ?? '') }}">
                        </div>
                        <div class="field">
                            <label>GSTIN</label>
                            <input type="text" name="gstin" value="{{ old('gstin', $editing->gstin ?? '') }}">
                        </div>
                    </div>

                    {{-- 3. Address & Location --}}
                    <div class="modal-section-title">3. Location</div>
                    <div class="form-grid form-grid-02">
                        <div class="field">
                            <label>State</label>
                            <select name="state" id="stateSelect" onchange="fetchCities(this.options[this.selectedIndex].getAttribute('data-id'))">
                                <option value="">Select State</option>
                                @if(isset($states))
                                    @foreach($states as $state)
                                        <option value="{{ $state->name }}" data-id="{{ $state->id }}" @selected(old('state', $editing->state ?? '') === $state->name)>{{ $state->name }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>

                        <div class="field">
                            <label>City</label>
                            <select name="city" id="citySelect">
                                @if($editing && $editing->city)
                                    <option value="{{ $editing->city }}">{{ $editing->city }}</option>
                                @else
                                    <option value="">Select City</option>
                                @endif
                            </select>
                        </div>

                        <div class="field">
                            <label>Pincode</label>
                            <input type="text" name="pincode" value="{{ old('pincode', $editing->pincode ?? '') }}">
                        </div>

                        <div class="field full">
                            <label>Address <span class="hint">(Lat/Long will auto-generate)</span></label>
                            <textarea name="address" id="addressInput" placeholder="Full street address..." onblur="geocodeAddress()">{{ old('address', $editing->address ?? '') }}</textarea>
                        </div>

                        <div class="field">
                            <label>Latitude (Lat)</label>
                            <input type="text" name="latitude" id="latInput" value="{{ old('latitude', $editing->latitude ?? '') }}" placeholder="Auto generated" readonly style="background-color: var(--line);">
                        </div>

                        <div class="field">
                            <label>Longitude (Long)</label>
                            <input type="text" name="longitude" id="longInput" value="{{ old('longitude', $editing->longitude ?? '') }}" placeholder="Auto generated" readonly style="background-color: var(--line);">
                        </div>
                    </div>

                </div>

                <div class="form-foot" style="padding: 16px 32px; background: #FBFAFF; border-top: 1px solid var(--line);">
                    @if ($editing)
                        <a class="btn btn-ghost" href="{{ route('superadmin.tenants.index') }}">Cancel</a>
                    @else
                        <button class="btn btn-ghost" type="button" onclick="closeTenantModal()">Cancel</button>
                    @endif
                    <button class="btn btn-primary" type="submit">Save Tenant</button>
                </div>
            </form>
        </div>
    </div>

    {{-- View Details Modal --}}
    <div class="modal" id="viewTenantModal">
        <div class="modal-body">
            <div class="fh" style="padding: 20px 20px 20px 20px; border-bottom: 1px solid var(--line); display:flex; justify-content:space-between; align-items:center;">
                <h3 style="font-size:18px; font-weight:700;">Retailer Details</h3>
                <button type="button" class="icon-btn" onclick="closeViewModal()" style="border:none;background:transparent;box-shadow:none;">✕</button>
            </div>
            
            <div class="modal-scroll modal-scroll-02">
                
                <div class="detail-header">
                    <div id="vPhotoContainer"></div>
                    <div>
                        <h3 id="vName">Name</h3>
                        <div class="detail-header-tags">
                            <span class="badge neutral" id="vSlug">Slug</span>
                            <span class="badge" id="vStatusBadge">Status</span>
                        </div>
                    </div>
                </div>

                <div class="modal-section-title">Contact Information</div>
                <div class="detail-grid">
                    <div class="detail-card"><div class="lbl">Email Address</div><div class="val" id="vEmail">—</div></div>
                    <div class="detail-card"><div class="lbl">Phone Number</div><div class="val" id="vPhone">—</div></div>
                </div>

                <div class="modal-section-title">Compliance & Taxation</div>
                <div class="detail-grid">
                    <div class="detail-card"><div class="lbl">PAN Number</div><div class="val" id="vPan">—</div></div>
                    <div class="detail-card"><div class="lbl">GSTIN</div><div class="val" id="vGst">—</div></div>
                </div>

                <div class="modal-section-title">Location</div>
                <div class="detail-grid">
                    <div class="detail-card"><div class="lbl">State</div><div class="val" id="vState">—</div></div>
                    <div class="detail-card"><div class="lbl">City</div><div class="val" id="vCity">—</div></div>
                    <div class="detail-card"><div class="lbl">Pincode</div><div class="val" id="vPincode">—</div></div>
                    <div class="detail-card"><div class="lbl">Address</div><div class="val" id="vAddress">—</div></div>
                    
                    <div class="detail-card"><div class="lbl">Latitude (Lat)</div><div class="val" id="vLat">—</div></div>
                    <div class="detail-card"><div class="lbl">Longitude (Long)</div><div class="val" id="vLong">—</div></div>
                </div>
            </div>

            <div class="form-foot" style="padding: 16px 32px; background: #FBFAFF; border-top: 1px solid var(--line);">
                <button class="btn btn-ghost" type="button" onclick="closeViewModal()">Close</button>
            </div>
        </div>
    </div>

    
   {{-- import Tenant Modal --}}
    <div class="modal" id="importTenantModal">
        <div class="modal-body">
            <div class="fh" style="padding: 20px 20px 20px 20px; border-bottom: 1px solid var(--line); display:flex; justify-content:space-between; align-items:center;">
                <h3 style="font-size:18px; font-weight:700;">Import Retailers</h3>
                <button type="button" class="icon-btn" onclick="closeImportTenantModal()" style="border:none;background:transparent;box-shadow:none;">✕</button>
            </div>
            
            <div class="modal-scroll modal-scroll-02">
               <form action="{{ route('superadmin.tenants.import') }}"  method="POST" enctype="multipart/form-data">
                
                    @csrf
                    <div class="modal-scroll" style="padding: 10px 10px;">
                    
                        <div class="field full mb-3">
                            <label class="form-label">
                                Tenant Excel File
                            </label>

                            <input
                                type="file"
                                name="file"
                                class="form-control"
                                accept=".xlsx,.xls,.csv"
                                required
                            >
                        </div>

                    </div>

                    <div class="form-foot" style="padding: 16px 32px; background: #FBFAFF; border-top: 1px solid var(--line);">
                       
                        <button class="btn btn-ghost" type="button" onclick="closeImportTenantModal()">Cancel</button>
                        <button class="btn btn-primary" type="submit">Save Tenant</button>
                    </div>
                    
                </form>
            </div>

        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function openTenantModal() {
            document.getElementById('tenantModal').classList.add('active');
        }
        function closeTenantModal() {
            document.getElementById('tenantModal').classList.remove('active');
            if (window.location.search.includes('edit=')) {
                window.location.href = "{{ route('superadmin.tenants.index') }}";
            }
        }

        
        function importTenantModal() {
            document.getElementById('importTenantModal').classList.add('active');
        }
        function closeImportTenantModal() {
            document.getElementById('importTenantModal').classList.remove('active');
            if (window.location.search.includes('edit=')) {
                window.location.href = "{{ route('superadmin.tenants.index') }}";
            }
        }

        function openViewModal(tenant) {
            document.getElementById('vName').innerText = tenant.name || '—';
            document.getElementById('vSlug').innerText = tenant.slug || '—';
            document.getElementById('vStatusBadge').innerText = (tenant.status || 'inactive').toUpperCase();
            document.getElementById('vStatusBadge').className = 'badge ' + (tenant.status === 'active' ? 'success' : 'danger');
            
            // Photo / Logo
            const photoBox = document.getElementById('vPhotoContainer');
            if (tenant.logo) {
                // photoBox.innerHTML = `<img src="/storage/${tenant.logo}" class="tenant-avatar-lg" alt="${tenant.name}">`;
                photoBox.innerHTML = `<img src="{{ Storage::disk('s3')->url('') }}${tenant.logo}" class="tenant-avatar-lg" alt="${tenant.name}">`;
            } else {
                const initials = (tenant.name || 'T').substring(0, 2).toUpperCase();
                photoBox.innerHTML = `<div class="tenant-avatar-lg">${initials}</div>`;
            }

            // Contact
            document.getElementById('vEmail').innerText = tenant.email || '—';
            document.getElementById('vPhone').innerText = tenant.phone || '—';
            
            // Compliance
            document.getElementById('vPan').innerText = tenant.pan_number || '—';
            document.getElementById('vGst').innerText = tenant.gstin || '—';
            
            // Address & Location
            document.getElementById('vState').innerText = tenant.state || '—';
            document.getElementById('vCity').innerText = tenant.city || '—';
            document.getElementById('vPincode').innerText = tenant.pincode || '—';
            document.getElementById('vAddress').innerText = tenant.address || '—';
            document.getElementById('vLat').innerText = tenant.latitude || '—';
            document.getElementById('vLong').innerText = tenant.longitude || '—';
            
            document.getElementById('viewTenantModal').classList.add('active');
        }

        function closeViewModal() {
            document.getElementById('viewTenantModal').classList.remove('active');
        }

        function fetchCities(stateId) {
            const citySelect = document.getElementById('citySelect');
            if (!stateId) {
                citySelect.innerHTML = '<option value="">Select City</option>';
                return;
            }

            citySelect.innerHTML = '<option value="">Loading..</option>';
            fetch("{{ url('superadmin/tenants/cities') }}/" + stateId)
                .then(res => res.json())
                .then(data => {
                    citySelect.innerHTML = '<option value="">Select City</option>';
                    data.forEach(city => {
                        const option = document.createElement('option');
                        option.value = city.name;
                        option.textContent = city.name;
                        const selectedCity = "{{ $editing->city ?? '' }}";
                        if (selectedCity === city.name) {
                            option.selected = true;
                        }
                        citySelect.appendChild(option);
                    });
                })
                .catch(err => {
                    citySelect.innerHTML = '<option value="">Error loading cities</option>';
                    console.error('Error fetching cities:', err);
                });
        }

        let geocodeTimer;
        function geocodeAddress() {
            const address = document.getElementById('addressInput').value;
            if (!address || address.length < 5) return;

            clearTimeout(geocodeTimer);
            geocodeTimer = setTimeout(() => {
                fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(address)}`)
                    .then(res => res.json())
                    .then(data => {
                        if (data && data.length > 0) {
                            document.getElementById('latInput').value = data[0].lat;
                            document.getElementById('longInput').value = data[0].lon;
                        }
                    })
                    .catch(err => console.error('Geocoding error:', err));
            }, 500);
        }

        // On Load initialization
        document.addEventListener('DOMContentLoaded', function() {
            // If editing and state is selected, load cities for that state
            const stateSelect = document.getElementById('stateSelect');
            if (stateSelect && stateSelect.value) {
                const selectedOption = stateSelect.options[stateSelect.selectedIndex];
                const stateId = selectedOption.getAttribute('data-id');
                if (stateId) {
                    fetchCities(stateId);
                }
            }
        });

        

        document.querySelectorAll('.tenant-status-toggle').forEach(function(toggle){

            toggle.addEventListener('change', function(){

                let checkbox = this;

                fetch("{{ route('superadmin.tenants.changeStatus') }}", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "Accept": "application/json",
                        "X-CSRF-TOKEN": "{{ csrf_token() }}"
                    },
                    body: JSON.stringify({
                        id: checkbox.dataset.id,
                        status: checkbox.checked ? "active" : "inactive"
                    })
                })
                .then(res => res.json())
                .then(data => {

                    if(data.success){

                        checkbox.closest("td")
                            .querySelector(".status-text")
                            .innerText = checkbox.checked ? "Active" : "Inactive";

                        showAlert(
                            'success',
                            'Success',
                            'Tenant status updated successfully.'
                        );

                    }else{

                        checkbox.checked = !checkbox.checked;

                        showAlert(
                            'warning',
                            'Warning',
                            'Tenant status could not be updated.'
                        );
                    }

                })
                .catch(() => {

                    checkbox.checked = !checkbox.checked;

                    showAlert(
                        'error',
                        'Error',
                        'Something went wrong.'
                    );

                });

            });

        });
    </script>

   
@endpush
