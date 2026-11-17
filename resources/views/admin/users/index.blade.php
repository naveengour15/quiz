@extends('admin.layouts.app')
@section('title', 'Users')

@push('styles')
    <style>
        .user-status-change {
            min-width: 110px;
            padding: 6px 10px;
            border: 1px solid #dcdfe6;
            border-radius: 8px;
            background: #fff;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
        }

        .user-status-change:focus {
            outline: none;
            border-color: var(--indigo);
        }

        .modal {
            position: fixed;
            inset: 0;
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            visibility: hidden;
            opacity: 0;
            transition: visibility 0.25s ease, opacity 0.25s ease;
        }

        .modal.active {
            visibility: visible;
            opacity: 1;
        }

        .modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.65);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
        }

        .modal-content {
            position: relative;
            z-index: 10000;
            width: 100%;
            max-width: 860px;
            margin: 20px;
            max-height: 90vh;
            overflow-y: auto;
            background: var(--surface, #ffffff);
            border-radius: 16px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            transform: translateY(15px) scale(0.96);
            transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .modal.active .modal-content {
            transform: translateY(0) scale(1);
        }

        .modal-section-title {
            font-family: var(--font-mono);
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--indigo);
            margin: 18px 0 10px;
            padding-bottom: 6px;
            border-bottom: 1px solid var(--line);
            grid-column: 1 / -1;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 14px;
            margin-top: 10px;
        }

        .detail-card {
            background: var(--paper);
            border: 1px solid var(--line);
            border-radius: 10px;
            padding: 10px 14px;
        }

        .detail-card .lbl {
            font-size: 10.5px;
            font-weight: 700;
            color: var(--ink-faint);
            text-transform: uppercase;
            letter-spacing: .04em;
            margin-bottom: 3px;
        }

        .detail-card .val {
            font-size: 13px;
            font-weight: 600;
            color: var(--ink);
            word-break: break-word;
        }

        .user-avatar-lg {
            width: 70px;
            height: 70px;
            border-radius: 16px;
            object-fit: cover;
            background: linear-gradient(135deg, var(--indigo), #7C3AED);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: 800;
            font-family: var(--font-display);
        }

        .user-thumb {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            object-fit: cover;
        }

        .user-profile-img-box{
            display: flex; 
            gap: 16px; 
            align-items: center; 
            padding: 16px; 
            background: var(--paper); 
            border-radius: 14px; 
            margin-bottom: 18px; 
            border: 1px solid var(--line);
        }

        @media(max-width:991px){
            .cell-user {
                flex-direction: row-reverse;
            }

            .user-info-box {
                align-items: end;
                display: flex;
                justify-content: end;
                flex-direction: column;
            }
        }

            @media(max-width:767px){
                .detail-grid {
                    grid-template-columns: repeat(2, 1fr);
                }
            }

           @media(max-width:575px){
                .user-profile-img-box{
                   flex-direction: column;
                    align-items: start;
                }
                 .detail-grid {
                    grid-template-columns: repeat(1, 1fr);
                }
                .detail-card{
                    grid-column: span 1 !important;
                }
            }
    </style>
@endpush

@section('content')
    <div class="panel active">
        <div class="page-head" style="display:flex;justify-content:space-between;align-items:center;">
            <div>
                <h1>Users</h1>
                <p>Search, review, and manage users belonging to your tenant.</p>
            </div>
            <button type="button" class="btn btn-primary" onclick="openUserModal()">+ Add User</button>
        </div>

        <div class="search-filter-box">
            <form method="GET" class="toolbar">
                <div class="users-search-filter-box">
                    <div class="users-search-filter-wrapper">
                        <div class="input-field">
                            <label class="lbl" for="search">Search</label>
                             <input class="tb-input search-field" name="search" value="{{ request('search') }}" placeholder="Search name, email, phone, code…">
                        </div>

                        <div class="input-field">
                            <label class="lbl" for="search">Status</label>
                            <select class="tb-select status-field" name="status" onchange="this.form.submit()">
                                <option value="">All Statuses</option>
                                <option value="active" @selected(request('status') === 'active')>Active</option>
                                <option value="suspended" @selected(request('status') === 'suspended')>Suspended</option>
                            </select>
                        </div>
                    </div>

                    <div class="users-filter-btn">
                        <button class="btn btn-primary" type="submit">Filter</button>
                        <a href="{{ request()->url() }}" class="btn btn-ghost">Reset</a>
                    </div>
                </div>
            </form>
        </div>

        <div class="table-wrap table-wrap-res-01">
            <div class="users-table-box">
                <table class="users-table-wrapper">
                    <thead>
                        <tr>
                            <th>User info</th>
                            <th>Gender</th>
                            <th>DOB</th>
                            <th>Ref Code</th>
                            <th>Create Limit</th>
                            <th>Play Limit</th>
                            <th>Quiz Leave</th>
                            <th>Last Quiz Rank</th>
                            <th>Wallet Points</th>
                            <th>Last Login</th>
                            <th>Status</th>
                            <th style="text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                            <tr>
                                <td data-label="User Info">
                                    <div class="cell-user">
                                        @if($user->profile_photo)
                                            <img src="{{ Storage::disk('s3')->url($user->profile_photo) }}" class="user-thumb" alt="{{ $user->name }}">
                                        @else
                                            <div class="cell-av">{{ strtoupper(substr($user->name, 0, 2)) }}</div>
                                        @endif
                                        <div class="user-info-box">
                                            <span class="badge info" style="font-size: 9px; padding: 1px 6px;">{{ ucfirst($user->role ?? 'user') }}</span>
                                            <b style="margin-top:5px; display:block;">{{ $user->name }}</b>
                                            <p style="color:var(--ink-soft); margin-top:3px;">{{ $user->email ?? '—' }}</p>
                                            <p style="color:var(--ink-soft); margin-top:2px;">{{ $user->mobile ?? '—' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td data-label="Gender">{{ ucfirst($user->gender ?? '—') }}</td>
                                <td data-label="DOB" class="mono">{{ $user->date_of_birth ? \Carbon\Carbon::parse($user->date_of_birth)->format('d M Y') : '—' }}</td>
                                <td data-label="Ref Code" class="mono"><span class="badge info" style="font-size:10px;">{{ $user->referral_code ?? '—' }}</span></td>
                                <td data-label="Create Limit" class="mono" style="text-align:center;">{{ $user->create_quiz_limit ?? 0 }}</td>
                                <td data-label="Play Limit" class="mono" style="text-align:center;">{{ $user->available_quiz_limit ?? 0 }}</td>
                                <td data-label="Quiz Leave" class="mono" style="text-align:center;">{{ $user->quiz_leave ?? 0 }}</td>
                                <td data-label="Last Quiz Rank" class="mono" style="text-align:center;">{{ $user->quiz_rank ?? 0 }}</td>
                                <td data-label="Wallet Points" class="mono">{{ number_format($user->wallet_balance ?? 0, 2) }}</td>
                                <td data-label="Last Login" class="mono">
                                    <div class="last-login-box">
                                       {{ $user->last_login_at?->diffForHumans() ?? '—' }}
                                    </div>
                                </td>
                                <td data-label="Status">
                                    <div>
                                        <select class="user-status-change" data-id="{{ $user->id }}">
                                            <option value="1" {{ $user->is_active ? 'selected' : '' }}>Active</option>
                                            <option value="0" {{ !$user->is_active ? 'selected' : '' }}>Suspended</option>
                                        </select>
                                    </div>
                                </td>
                                <td data-label="Actions" class="row-actions" style="justify-content: flex-end;">
                                    <div class="users-action-box">
                                        <button type="button" class="icon-btn" title="View Details" onclick='openViewModal(@json($user))'>👁</button>
                                        <a class="icon-btn" title="Edit User" href="{{ route('admin.users.index', ['edit' => $user->id]) }}">✎</a>
                                        <form method="POST" action="{{ route('admin.users.destroy', $user) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button class="icon-btn danger" title="Suspend User" type="submit">⊘</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="12" style="text-align:center;color:var(--ink-faint);padding: 24px;">No users found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="pagination">
                @include('common.pagination.superadmin_pagination', ['pageData' => $users])
            </div> 
        </div>
    </div>

    {{-- ADD / EDIT USER MODAL --}}
    <div id="userModal" class="modal {{ $editing || $errors->any() ? 'active' : '' }}">
        <div class="modal-backdrop" onclick="closeUserModal()"></div>
        <div class="modal-content form-card" style="margin-bottom:0;">
            <div class="fh">
                <h3>{{ $editing ? 'Edit User' : 'Add User' }}</h3>
                <button type="button" class="icon-btn" onclick="closeUserModal()" style="border:none;background:transparent;box-shadow:none;">✕</button>
            </div>
            <form method="POST"
                action="{{ $editing ? route('admin.users.update', $editing) : route('admin.users.store') }}" enctype="multipart/form-data">
                @csrf
                @if ($editing)
                    @method('PUT')
                @endif
                <div class="form-grid form-grid-02">
                    
                    {{-- 1. Basic Profile Info --}}
                    <div class="modal-section-title">1. Basic Profile Info</div>

                    <div class="field">
                        <label>Full Name <span class="req">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $editing->name ?? '') }}" placeholder="Enter full name" required>
                    </div>

                    <div class="field">
                        <label>Email Address</label>
                        <input type="email" name="email" value="{{ old('email', $editing->email ?? '') }}" placeholder="name@example.com">
                    </div>

                    <div class="field">
                        <label>Mobile Number</label>
                        <input type="text" name="mobile" value="{{ old('mobile', $editing->mobile ?? '') }}" placeholder="10-digit mobile number" maxlength="10" minlength="10"  pattern="[0-9]{10}" inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10);">
                    </div>

                    <div class="field">
                        <label>Alternative Mobile</label>
                        <input type="text" name="alternative_mobile" value="{{ old('alternative_mobile', $editing->alternative_mobile ?? '') }}" placeholder="Alternate phone number" maxlength="10" minlength="10"  pattern="[0-9]{10}" inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10);">
                    </div>

                    <div class="field">
                        <label>Gender</label>
                        <select name="gender">
                            <option value="">Select Gender</option>
                            <option value="male" @selected(old('gender', $editing->gender ?? '') === 'male')>Male</option>
                            <option value="female" @selected(old('gender', $editing->gender ?? '') === 'female')>Female</option>
                            <option value="other" @selected(old('gender', $editing->gender ?? '') === 'other')>Other</option>
                        </select>
                    </div>

                    <div class="field">
                        <label>Date of Birth</label>
                        <input type="date" name="date_of_birth" value="{{ old('date_of_birth', $editing?->date_of_birth ? \Carbon\Carbon::parse($editing->date_of_birth)->format('Y-m-d') : '') }}">
                    </div>

                    <div class="field full">
                        <label>Profile Photo</label>
                        <input type="file" name="profile_photo" accept="image/*">
                        @if($editing?->profile_photo)
                            <div class="desc" style="margin-top:4px;">Current photo: <a href="{{ Storage::disk('s3')->url($editing->profile_photo) }}" target="_blank">View existing image</a></div>
                        @endif
                    </div>

                    {{-- 2. Account & Security --}}
                    <div class="modal-section-title">2. Account & Security</div>

                    <div class="field">
                        <label>Role <span class="req">*</span></label>
                        <select name="role" required>
                            @foreach (['user', 'admin', 'teacher', 'moderator'] as $role)
                                <option value="{{ $role }}" @selected(old('role', $editing->role ?? 'user') === $role)>{{ ucfirst($role) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="field">
                        <label>Login Method</label>
                        <select name="login_method" id="loginMethodSelect" onchange="togglePasswordField()">
                            <option value="otp" @selected(old('login_method', $editing->login_method ?? 'otp') === 'otp')>OTP (Default)</option>
                            <option value="password" @selected(old('login_method', $editing->login_method ?? '') === 'password')>Password</option>
                            <option value="inactive" @selected(old('login_method', $editing->login_method ?? '') === 'inactive')>Inactive</option>
                        </select>
                    </div>

                    <div class="field" id="passwordFieldContainer" style="display: {{ old('login_method', $editing->login_method ?? 'otp') === 'password' ? 'flex' : 'none' }};">
                        <label>Password <span class="hint">{{ $editing ? '(Leave blank to keep unchanged)' : '(Default: 123456)' }}</span></label>
                        <input type="password" name="password" placeholder="{{ $editing ? '••••••••' : 'Enter password' }}">
                    </div>

                    <div class="field">
                        <label>Account Status <span class="req">*</span></label>
                        <select name="is_active" required>
                            <option value="1" @selected(old('is_active', $editing->is_active ?? 1) == 1)>Active</option>
                            <option value="0" @selected(old('is_active', $editing->is_active ?? 1) == 0)>Suspended</option>
                        </select>
                    </div>

                    <div class="field">
                        <label>Verify Mobile</label>
                        <select name="verify_mobile">
                            <option value="0" @selected(old('verify_mobile', $editing->verify_mobile ?? 0) == 0)>No / Unverified</option>
                            <option value="1" @selected(old('verify_mobile', $editing->verify_mobile ?? 0) == 1)>Yes / Verified</option>
                        </select>
                    </div>

                    <div class="field">
                        <label>Verify Email</label>
                        <select name="verify_email">
                            <option value="0" @selected(old('verify_email', $editing->verify_email ?? 0) == 0)>No / Unverified</option>
                            <option value="1" @selected(old('verify_email', $editing->verify_email ?? 0) == 1)>Yes / Verified</option>
                        </select>
                    </div>

                    {{-- 3. Address & Location --}}
                    <div class="modal-section-title">3. Address & Location</div>

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

                    <div class="field full">
                        <label>Address <span class="hint">(Lat/Long will auto-generate)</span></label>
                        <textarea name="address" id="addressInput" placeholder="Full street address..." onblur="geocodeAddress()">{{ old('address', $editing->address ?? '') }}</textarea>
                    </div>

                    <div class="field">
                        <label>Latitude (Lat)</label>
                        <input type="text" name="lat" id="latInput" value="{{ old('lat', $editing->lat ?? '') }}" placeholder="Auto generated" readonly style="background-color: var(--line);">
                    </div>

                    <div class="field">
                        <label>Longitude (Long)</label>
                        <input type="text" name="long" id="longInput" value="{{ old('long', $editing->long ?? '') }}" placeholder="Auto generated" readonly style="background-color: var(--line);">
                    </div>

                    {{-- 4. Quiz Limits & Wallet --}}
                    <div class="modal-section-title">4. Quiz Limits & Wallet</div>

                    <div class="field">
                        <label>Create Quiz Limit</label>
                        <input type="number" name="create_quiz_limit" value="{{ old('create_quiz_limit', $editing->create_quiz_limit ?? 0) }}" min="0">
                    </div>

                    <div class="field">
                        <label>Available Quiz Limit</label>
                        <input type="number" name="available_quiz_limit" value="{{ old('available_quiz_limit', $editing->available_quiz_limit ?? 0) }}" min="0">
                    </div>

                    <div class="field">
                        <label>Quiz Leave</label>
                        <input type="number" name="quiz_leave" value="{{ old('quiz_leave', $editing->quiz_leave ?? 0) }}" min="0">
                    </div>

                    <div class="field">
                        <label>Quiz Rank</label>
                        <input type="number" name="quiz_rank" value="{{ old('quiz_rank', $editing->quiz_rank ?? 0) }}" min="0">
                    </div>

                    <div class="field full">
                        <label>Wallet Points </label>
                        <input type="number" step="0.01" name="wallet_balance" value="{{ old('wallet_balance', $editing->wallet_balance ?? 0) }}" min="0">
                    </div>

                    {{-- 5. Personal Preferences --}}
                    <div class="modal-section-title">5. Personal Preferences</div>

                    <div class="field">
                        <label>Favorite Color</label>
                        <input type="text" name="favorite_color" value="{{ old('favorite_color', $editing->favorite_color ?? '') }}" placeholder="e.g. Blue, Red">
                    </div>

                    <div class="field">
                        <label>Hobbies</label>
                        <input type="text" name="hobbies" value="{{ old('hobbies', $editing->hobbies ?? '') }}" placeholder="Reading, Coding, Travel">
                    </div>

                    <div class="field">
                        <label>Games</label>
                        <input type="text" name="games" value="{{ old('games', $editing->games ?? '') }}" placeholder="Chess, Cricket, BGMI">
                    </div>

                    <div class="field">
                        <label>Interests</label>
                        <input type="text" name="interest" value="{{ old('interest', $editing->interest ?? '') }}" placeholder="Tech, Science, GK">
                    </div>

                </div>

                <div class="form-foot">
                    @if ($editing)
                        <a class="btn btn-ghost" href="{{ route('admin.users.index') }}">Cancel</a>
                    @endif
                    <button class="btn btn-primary" type="submit">Save User</button>
                </div>
            </form>
        </div>
    </div>

    {{-- VIEW USER FULL DETAILS MODAL --}}
    <div id="viewUserModal" class="modal">
        <div class="modal-backdrop" onclick="closeViewModal()"></div>
        <div class="modal-content form-card" style="margin-bottom:0; max-width: 860px;">
            <div class="fh" style="margin-bottom: 12px;">
                <h3>User Profile &amp; Full Details</h3>
                <button type="button" class="icon-btn" onclick="closeViewModal()" style="border:none;background:transparent;box-shadow:none;">✕</button>
            </div>

            <div class="user-profile-img-box">
                <div id="vPhotoContainer">
                    <div class="user-avatar-lg" id="vInitials">U</div>
                </div>
                <div style="flex:1;">
                    <h2 id="vName" style="font-family:var(--font-display); font-size:18px; font-weight:800; color:var(--ink);">User Name</h2>
                    <div style="display:flex; gap:8px; align-items:center; margin-top:4px; flex-wrap:wrap;">
                        <span id="vRoleBadge" class="badge info">User</span>
                        <span id="vStatusBadge" class="badge success">Active</span>
                        <span class="mono" style="font-size:11px; color:var(--ink-soft);">Referral Code: <b id="vRefCode" style="color:var(--indigo);">—</b></span>
                    </div>
                </div>
            </div>

            <div class="modal-section-title" style="margin-top:0;">Account &amp; Authentication</div>
            <div class="detail-grid">
                <div class="detail-card"><div class="lbl">Login Method</div><div class="val" id="vLoginMethod">otp</div></div>
                <div class="detail-card"><div class="lbl">Password</div><div class="val" id="vPassword">●●●●●●●● (Hashed)</div></div>
                <div class="detail-card"><div class="lbl">Role</div><div class="val" id="vRole">user</div></div>
                <div class="detail-card"><div class="lbl">Role ID</div><div class="val" id="vRoleId">—</div></div>
                <div class="detail-card"><div class="lbl">Tenant ID</div><div class="val" id="vTenantId">—</div></div>
                <div class="detail-card"><div class="lbl">Verify Mobile</div><div class="val" id="vVerifyMobile">No</div></div>
                <div class="detail-card"><div class="lbl">Verify Email</div><div class="val" id="vVerifyEmail">No</div></div>
            </div>

            <div class="modal-section-title">Contact &amp; Personal Info</div>
            <div class="detail-grid">
                <div class="detail-card"><div class="lbl">Email</div><div class="val" id="vEmail">—</div></div>
                <div class="detail-card"><div class="lbl">Mobile</div><div class="val" id="vMobile">—</div></div>
                <div class="detail-card"><div class="lbl">Alternative Mobile</div><div class="val" id="vAltMobile">—</div></div>
                <div class="detail-card"><div class="lbl">Gender</div><div class="val" id="vGender">—</div></div>
                <div class="detail-card"><div class="lbl">Date of Birth</div><div class="val" id="vDob">—</div></div>
                <div class="detail-card"><div class="lbl">Favorite Color</div><div class="val" id="vFavColor">—</div></div>
                <div class="detail-card"><div class="lbl">Hobbies</div><div class="val" id="vHobbies">—</div></div>
                <div class="detail-card"><div class="lbl">Games</div><div class="val" id="vGames">—</div></div>
                <div class="detail-card"><div class="lbl">Interest</div><div class="val" id="vInterest">—</div></div>
            </div>

            <div class="modal-section-title">Address &amp; Location</div>
            <div class="detail-grid">
                <div class="detail-card"><div class="lbl">State</div><div class="val" id="vState">—</div></div>
                <div class="detail-card"><div class="lbl">City</div><div class="val" id="vCity">—</div></div>
                <div class="detail-card"><div class="lbl">Latitude (Lat)</div><div class="val" id="vLat">—</div></div>
                <div class="detail-card"><div class="lbl">Longitude (Long)</div><div class="val" id="vLong">—</div></div>
                <div class="detail-card" style="grid-column: span 2;"><div class="lbl">Address</div><div class="val" id="vAddress">—</div></div>
            </div>

            <div class="modal-section-title">Quiz Stats &amp; Wallet Balance</div>
            <div class="detail-grid">
                <div class="detail-card"><div class="lbl">Create Quiz Limit</div><div class="val" id="vCreateLimit">0</div></div>
                <div class="detail-card"><div class="lbl">Available Quiz Limit</div><div class="val" id="vAvailLimit">0</div></div>
                <div class="detail-card"><div class="lbl">Quiz Leave</div><div class="val" id="vQuizLeave">0</div></div>
                <div class="detail-card"><div class="lbl">Quiz Rank</div><div class="val" id="vQuizRank">0</div></div>
                <div class="detail-card"><div class="lbl">Wallet Points</div><div class="val" id="vWallet" style="color:var(--emerald);">0.00</div></div>
                <div class="detail-card"><div class="lbl">Joined At</div><div class="val" id="vCreatedAt">—</div></div>
                <div class="detail-card"><div class="lbl">Last Login</div><div class="val" id="vLastLogin">—</div></div>
            </div>

            <div class="form-foot">
                <button class="btn btn-ghost" type="button" onclick="closeViewModal()">Close</button>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function openUserModal() {
            document.getElementById('userModal').classList.add('active');
        }

        function closeUserModal() {
            document.getElementById('userModal').classList.remove('active');
            if (window.location.search.includes('edit=')) {
                window.location.href = "{{ route('admin.users.index') }}";
            }
        }

        function openViewModal(user) {
            document.getElementById('vName').innerText = user.name || '—';
            document.getElementById('vRoleBadge').innerText = (user.role || 'user').toUpperCase();
            document.getElementById('vStatusBadge').innerText = user.is_active ? 'ACTIVE' : 'SUSPENDED';
            document.getElementById('vStatusBadge').className = 'badge ' + (user.is_active ? 'success' : 'danger');
            document.getElementById('vRefCode').innerText = user.referral_code || '—';

            const photoBox = document.getElementById('vPhotoContainer');
            if (user.profile_photo) {
                photoBox.innerHTML = `<img src="{{ Storage::disk('s3')->url('') }}${user.profile_photo}" class="user-avatar-lg" alt="${user.name}">`;
            } else {
                const initials = (user.name || 'U').substring(0, 2).toUpperCase();
                photoBox.innerHTML = `<div class="user-avatar-lg">${initials}</div>`;
            }

            document.getElementById('vLoginMethod').innerText = user.login_method || 'otp';
            document.getElementById('vPassword').innerText = '●●●●●●●● (Hashed)';
            document.getElementById('vRole').innerText = user.role || 'user';
            document.getElementById('vRoleId').innerText = user.role_id || '—';
            document.getElementById('vTenantId').innerText = user.tenant_id || '—';
            document.getElementById('vVerifyMobile').innerText = user.verify_mobile ? 'Yes' : 'No';
            document.getElementById('vVerifyEmail').innerText = user.verify_email ? 'Yes' : 'No';

            document.getElementById('vEmail').innerText = user.email || '—';
            document.getElementById('vMobile').innerText = user.mobile || '—';
            document.getElementById('vAltMobile').innerText = user.alternative_mobile || '—';
            document.getElementById('vGender').innerText = user.gender ? user.gender.charAt(0).toUpperCase() + user.gender.slice(1) : '—';
            document.getElementById('vDob').innerText = user.date_of_birth || '—';
            document.getElementById('vFavColor').innerText = user.favorite_color || '—';
            document.getElementById('vHobbies').innerText = user.hobbies || '—';
            document.getElementById('vGames').innerText = user.games || '—';
            document.getElementById('vInterest').innerText = user.interest || '—';

            document.getElementById('vState').innerText = user.state || '—';
            document.getElementById('vCity').innerText = user.city || '—';
            document.getElementById('vAddress').innerText = user.address || '—';
            document.getElementById('vLat').innerText = user.lat || '—';
            document.getElementById('vLong').innerText = user.long || '—';

            document.getElementById('vCreateLimit').innerText = user.create_quiz_limit || 0;
            document.getElementById('vAvailLimit').innerText = user.available_quiz_limit || 0;
            document.getElementById('vQuizLeave').innerText = user.quiz_leave || 0;
            document.getElementById('vQuizRank').innerText = user.quiz_rank || 0;
            document.getElementById('vWallet').innerText = '' + parseFloat(user.wallet_balance || 0).toFixed(2);
            document.getElementById('vCreatedAt').innerText = user.created_at ? new Date(user.created_at).toLocaleDateString() : '—';
            document.getElementById('vLastLogin').innerText = user.last_login_at ? new Date(user.last_login_at).toLocaleString() : '—';

            document.getElementById('viewUserModal').classList.add('active');
        }

        function closeViewModal() {
            document.getElementById('viewUserModal').classList.remove('active');
        }

        function togglePasswordField() {
            const method = document.getElementById('loginMethodSelect').value;
            const pwdContainer = document.getElementById('passwordFieldContainer');
            if (method === 'password') {
                pwdContainer.style.display = 'flex';
            } else {
                pwdContainer.style.display = 'none';
            }
        }

        function fetchCities(stateId) {
            const citySelect = document.getElementById('citySelect');
            if (!stateId) {
                citySelect.innerHTML = '<option value="">Select City</option>';
                return;
            }

            citySelect.innerHTML = '<option value="">Loading...</option>';
            
            fetch("{{ url('admin/users/cities') }}/" + stateId)
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

        document.addEventListener('DOMContentLoaded', function() {
            togglePasswordField();
            
            const stateSelect = document.getElementById('stateSelect');
            if (stateSelect && stateSelect.value) {
                const selectedOption = stateSelect.options[stateSelect.selectedIndex];
                const stateId = selectedOption.getAttribute('data-id');
                if (stateId) {
                    fetchCities(stateId);
                }
            }
        });

        document.addEventListener('change', function(e) {
            if (!e.target.classList.contains('user-status-change')) {
                return;
            }

            let status = e.target.value;

            fetch("{{ route('admin.users.changeStatus') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    id: e.target.dataset.id,
                    status: status
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showAlert('success', 'Success', 'User status updated successfully.');
                } else {
                    showAlert('warning', 'Warning', 'Unable to update status.');
                }
            })
            .catch(() => {
                showAlert('error', 'Error', 'Something went wrong.');
            });
        });
    </script>
@endpush
