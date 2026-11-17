@extends('superadmin.layouts.app')
@section('title', 'Users')
<link rel="stylesheet" href="{{ asset('assets/css/custom.css') }}">
@section('content')
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

            /* Modal Styles */
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
                /* letter-spacing: .08em; */
                text-transform: uppercase;
                color: var(--indigo);
                margin: 18px 0 10px;
                padding-bottom: 6px;
                border-bottom: 1px solid var(--line);
                grid-column: 1 / -1;
            }

            /* Detail Modal Styles */
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
                width: 60px;
                height: 60px;
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

            .user-profile-img-box {
                display: flex;
                gap: 16px;
                align-items: center;
                padding: 16px;
                background: var(--paper);
                border-radius: 14px;
                margin-bottom: 18px;
                border: 1px solid var(--line);
            }

            .wallet-point-box {
                display: flex;
                flex-direction: column;
                gap: 6px;
            }

            .user-history-table {
                min-width: 900px;
            }

            .users-search-filter-box {
                width: 100%;
                display: grid;
                grid-template-columns: minmax(240px, 1.8fr) minmax(180px, 1fr) minmax(180px, 1fr) minmax(130px, 0.9fr);
                gap: 18px;
                align-items: end;
            }

            .users-search-filter-wrapper {
                display: contents;
            }

            .users-filter-btn {
                display: flex;
                align-items: stretch;
                width: 100%;
                gap: 8px;
            }

            .users-filter-btn .btn {
                width: 100%;
                min-height: 38px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 12px;
                font-weight: 700;
                padding: 9px 16px;
                border-radius: 9px;
            }

            .users-reset-btn {
                display: inline-flex;
            }

            .last-login-box {
                display: flex;
                flex-direction: column;
                gap: 6px;
            }


            @media(max-width:991px) {
                .users-search-filter-box {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                }

                .users-filter-btn {
                    width: 100%;
                }

                .cell-user {
                    flex-direction: row-reverse;
                }

                .user-info-box {
                    align-items: end;
                    display: flex;
                    justify-content: end;
                    flex-direction: column;
                }

                .wallet-point-box {
                    align-items: flex-end;
                }

                .user-history-table {
                    min-width: 100%;
                }
            }

            @media(max-width:767px) {
                .users-search-filter-box {
                    grid-template-columns: 1fr;
                }

                .users-filter-btn {
                    width: 100%;
                }

                .users-reset-btn {
                    display: inline-flex;
                    width: auto;
                    min-width: 110px;
                }

                .detail-grid {
                    grid-template-columns: repeat(2, 1fr);
                }

                .stats-grid-box-02 {
                    grid-template-columns: repeat(2, 1fr) !important;
                }
            }

            @media(max-width:575px) {
                .user-profile-img-box {
                    flex-direction: column;
                    align-items: start;
                }

                .detail-grid {
                    grid-template-columns: repeat(1, 1fr);
                }

                .users-filter-btn {
                    width: 100%;
                }

                .detail-card {
                    grid-column: span 1 !important;
                }

                .form-card-02 {
                    padding: 14px;
                }

                .stats-grid-box-02 {
                    grid-template-columns: repeat(1, 1fr) !important;
                }
            }

            /* ==========================================
                                                            USER HISTORY TABS
                                                            ========================================== */

            .history-tabs {
                display: flex;
                gap: 6px;
                padding: 0px 0px 0;
                border-bottom: 1px solid var(--line);
            }

            .history-tab {
                border: none;
                background: transparent;
                padding: 10px 18px;
                font-size: 13px;
                font-weight: 700;
                color: var(--ink-soft);
                cursor: pointer;
                border-bottom: 2px solid transparent;
                transition: all .2s ease;
            }

            .history-tab:hover {
                color: var(--indigo);
            }

            .history-tab.active {
                color: var(--indigo);
                border-bottom-color: var(--indigo);
            }

            .history-empty {
                text-align: center;
                padding: 30px 0;
                color: var(--ink-faint);
            }

            .history-tab-content {
                margin-top: 15px;
            }

            /* *********box css****** */

            /* ==========================================
                                                                USER STATS BOXES
                                                                ========================================== */
            .stats-grid {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 16px;
                margin-bottom: 24px;
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

            .stat-card:hover {
                transform: translateY(-3px);
                box-shadow: 0 12px 24px -8px rgba(0, 0, 0, 0.08);
            }

            .stat-icon {
                width: 52px;
                height: 52px;
                border-radius: 12px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 24px;
                flex-shrink: 0;
            }

            .stat-info {
                display: flex;
                flex-direction: column;
            }

            .stat-label {
                font-size: 11px;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 0.06em;
                color: var(--ink-faint, #64748b);
            }

            .stat-value {
                font-size: 26px;
                font-weight: 800;
                color: var(--ink, #0f172a);
                margin-top: 4px;
                line-height: 1;
            }

            /* Total Users - Blue */
            .stat-total .stat-icon {
                background: #eff6ff;
                color: #3b82f6;
            }

            .stat-total .stat-value {
                color: #1e40af;
            }

            /* Active Users - Green */
            .stat-active .stat-icon {
                background: #f0fdf4;
                color: #22c55e;
            }

            .stat-active .stat-value {
                color: #166534;
            }

            /* Suspended Users - Red */
            .stat-suspended .stat-icon {
                background: #fef2f2;
                color: #ef4444;
            }

            .stat-suspended .stat-value {
                color: #991b1b;
            }

            /* Mobile Responsive */
            @media (max-width: 768px) {
                .stats-grid {
                    grid-template-columns: 1fr;
                }
            }

            .user-tags {
                display: flex;
                flex-wrap: wrap;
                gap: 7px;
                margin-top: 4px;
            }

            .user-tag {
                display: inline-flex;
                align-items: center;
                gap: 7px;
                padding: 7px 14px;
                border-radius: 18px;
                font-size: 12px;
                font-weight: 700;
                line-height: 1;
                white-space: nowrap;
            }

            /* Hobbies */
            .user-tag.hobby {
                color: #000;
                background: linear-gradient(135deg, #e5e5e5, #d6d6d6);
                border: 1px solid #b8b8b8;
                box-shadow: 0 0 8px rgba(0, 0, 0, .10);
            }

            .user-tag.hobby .tag-icon {
                color: #ff8fc7;
            }

            /* Games */
            .user-tag.game {
                color: #000;
                background: linear-gradient(135deg, #e5e5e5, #d6d6d6);
                border: 1px solid #b8b8b8;
                box-shadow: 0 0 8px rgba(0, 0, 0, .10);
            }

            .user-tag.game .tag-icon {
               color: #ff8fc7;
            }

            /* Interests */
            .user-tag.interest {
               color: #000;
                background: linear-gradient(135deg, #e5e5e5, #d6d6d6);
                border: 1px solid #b8b8b8;
                box-shadow: 0 0 8px rgba(0, 0, 0, .10);
            }

            .user-tag.interest .tag-icon {
              color: #ff8fc7;
            }

            .tag-icon {
                font-size: 16px;
                line-height: 1;
            }

           .user-tag.color {
                color: #000;
                background: linear-gradient(135deg, #e5e5e5, #d6d6d6);
                border: 1px solid #b8b8b8;
                box-shadow: 0 0 8px rgba(0, 0, 0, .10);
            }

            .user-tag.color .tag-icon {
                color: #ff8fc7;
            }
        
            .profile-photo-modal {
                display: none;
                position: fixed;
                inset: 0;
                z-index: 99999;
                background: rgba(0, 0, 0, 0.82);
                align-items: center;
                justify-content: center;
                padding: 20px;
            }

            .profile-photo-modal.show {
                display: flex;
            }

            .profile-photo-modal-content {
                position: relative;
                max-width: 90vw;
                max-height: 90vh;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
            }

            .profile-photo-modal-content img {
                max-width: 85vw;
                max-height: 78vh;
                width: auto;
                height: auto;
                object-fit: contain;
                border-radius: 12px;
                background: #fff;
                padding: 4px;
                box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
            }

            .profile-photo-close {
                position: absolute;
                top: -18px;
                right: -18px;
                width: 38px;
                height: 38px;
                border: none;
                border-radius: 50%;
                background: #fff;
                color: #222;
                font-size: 28px;
                line-height: 35px;
                cursor: pointer;
                z-index: 2;
            }

            .profile-photo-name {
                margin-top: 12px;
                color: #fff;
                font-size: 14px;
                font-weight: 600;
                text-align: center;
            }

            body.photo-modal-open {
                overflow: hidden;
            }

     
            /* Toggle Switch Base Styles */
            .switch {
                position: relative;
                display: inline-block;
                width: 50px;
                height: 26px;
            }
            
            .switch input {
                opacity: 0;
                width: 0;
                height: 0;
            }
            
            /* Slider Background (OFF State - Gray) */
            .slider {
                position: absolute;
                cursor: pointer;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-color: #cbd5e1; /* Gray when OFF */
                transition: .3s;
                border-radius: 26px;
            }
            
            /* Slider Circle (White Dot) */
            .slider:before {
                position: absolute;
                content: "";
                height: 20px;
                width: 20px;
                left: 3px;
                bottom: 3px;
                background-color: white;
                transition: .3s;
                border-radius: 50%;
                box-shadow: 0 2px 4px rgba(236, 17, 17, 0.2);
            }
            
            /* ON State - Green Background */
            input:checked + .slider {
                background-color: #10b981 !important; /* Green when ON */
            }
            
            /* ON State - Move Circle to Right */
            input:checked + .slider:before {
                transform: translateX(24px);
            }
            
            /* Focus State */
            input:focus + .slider {
                box-shadow: 0 0 1px #10b981;
            }
            
            /* Disabled State */
            input:disabled + .slider {
                opacity: 0.5;
                cursor: not-allowed;
            }

        </style>
    @endpush

    <div class="panel active">
        <div class="page-head" style="display:flex;justify-content:space-between;align-items:center;">
            <div>
                <h1>Users</h1>
                <p>Search, review, and manage every user account on the platform.</p>
            </div>
            {{-- <button type="button" class="btn btn-primary" onclick="openUserModal()">+ Add User</button> --}}
            <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                <!-- Naya Bulk Update Button -->
                <button type="button" class="btn btn-secondary" onclick="openBulkPlayLimitModal()">
                    ⚡ Update All Users Limit
                </button>

                <!-- Referral Refresh Button -->
                <button type="button" id="btnReferralRefresh" class="btn btn-secondary" onclick="refreshReferralRewards()">
                    🔄 Referral
                </button>

                <button type="button" class="btn btn-primary" onclick="openUserModal()">+ Add User</button>
            </div>
        </div>

        {{-- ✅ NEW: 3 Stats Boxes Yahan Add Kiye Hain --}}
        <div class="stats-grid stats-grid-box-02">
            <!-- Total Users Box -->
            <div class="stat-card stat-total">
                <div class="stat-icon">👥</div>
                <div class="stat-info">
                    <span class="stat-label">Total Users</span>
                    <span class="stat-value">{{ $totalUsers ?? 0 }}</span>
                </div>
            </div>

            <!-- Active Users Box -->
            <div class="stat-card stat-active">
                <div class="stat-icon">✅</div>
                <div class="stat-info">
                    <span class="stat-label">Active Users</span>
                    <span class="stat-value">{{ $activeUsers ?? 0 }}</span>
                </div>
            </div>

            <!-- Suspended Users Box -->
            <div class="stat-card stat-suspended">
                <div class="stat-icon">🚫</div>
                <div class="stat-info">
                    <span class="stat-label">Suspended Users</span>
                    <span class="stat-value">{{ $suspendedUsers ?? 0 }}</span>
                </div>
            </div>

                <!-- Pending Profile -->
                <div class="stat-card stat-pending-profile">
                    <div class="stat-icon">🟠</div>
                    <div class="stat-info">
                        <span class="stat-label">Pending Profile</span>
                        <span class="stat-value">{{ $pendingProfiles ?? 0 }}</span>
                    </div>
                </div>

                <!-- Complete Profile -->
                <div class="stat-card stat-complete-profile">
                    <div class="stat-icon">🟢</div>
                    <div class="stat-info">
                        <span class="stat-label">Complete Profile</span>
                        <span class="stat-value">{{ $completeProfiles ?? 0 }}</span>
                    </div>
                </div>
        </div>
        {{-- ✅ Stats Boxes End --}}

        <div class="search-filter-box">
            <form method="GET" class="toolbar">
                <div class="users-search-filter-box">
                    <div class="users-search-filter-wrapper">
                        <div class="input-field">
                            <label class="lbl" for="search">Search</label>
                            <input class="tb-input search-field" name="search" value="{{ request('search') }}"
                                placeholder="Search name, email, phone, code…">
                        </div>
                        <!-- <select class="tb-select" name="role" onchange="this.form.submit()">
                                                    <option value="">All Roles</option>
                                                    @foreach (['user', 'admin', 'superadmin', 'teacher', 'moderator'] as $role)
    <option value="{{ $role }}" @selected(request('role') === $role)>{{ ucfirst($role) }}</option>
    @endforeach
                                                </select> -->

                        <div class="input-field">
                            <label class="lbl status-field" for="search">Status</label>
                            <select class="tb-select" name="status" onchange="this.form.submit()">
                                <option value="">All Statuses</option>
                                <option value="active" @selected(request('status') === 'active')>Active</option>
                                <option value="suspended" @selected(request('status') === 'suspended')>Suspended</option>
                            </select>
                        </div>

                      <div class="input-field">
                            <label class="lbl">Profile Status</label>

                            <select class="tb-select" name="profile_status" onchange="this.form.submit()">
                                <option value="">All Profiles</option>
                                <option value="pending" @selected(request('profile_status') == 'pending')>Pending</option>
                                <option value="complete" @selected(request('profile_status') == 'complete')>Complete</option>
                            </select>
                        </div>

                        <div class="input-field">
                            <label class="lbl">Quiz Limit</label>

                            <select class="tb-select" name="quiz_limit_sort" onchange="this.form.submit()">
                                <option value="">Default</option>

                                <option value="low_high"
                                    @selected(request('quiz_limit_sort') === 'low_high')>
                                    Low to High
                                </option>

                                <option value="high_low"
                                    @selected(request('quiz_limit_sort') === 'high_low')>
                                    High to Low
                                </option>
                            </select>
                        </div>

                        <div class="input-field">
                            <label class="lbl">Gender</label>

                            <select class="tb-select" name="gender" onchange="this.form.submit()">
                                <option value="">All</option>
                                <option value="male" @selected(request('gender') == 'male')>Male</option>
                                <option value="female" @selected(request('gender') == 'female')>Female</option>
                                <option value="not_selected" @selected(request('gender') == 'not_selected')>Not Selected</option>
                            </select>
                        </div>
                    </div>
                    <div class="users-filter-btn">
                        <button class="btn btn-primary" type="submit">Filter</button>
                        <a href="{{ request()->url() }}" class="btn btn-ghost users-reset-btn">Reset</a>
                    </div>
                </div>
            </form>
        </div>

        <div class="table-wrap table-wrap-res-01">
            <div class="users-table-box">
                <table class="users-table-wrapper">
                    <thead>
                        <tr>
                             {{-- <th>Photo</th>  --}}
                            <th>User info</th>
                            {{-- Table Head mein add karein --}}
                            
                          
                            {{-- <th>Gender</th>
                            <th>DOB</th> --}}
                            <th>Ref Code</th>
                            {{-- <th>Create Limit</th> --}}
                            <th>On/Off</th>
                            <th >
                                Limits & Rank
                            </th>
                            <th >
                             Play Counts
                            </th>

                            {{-- <th>Play Limit</th>
                            <th>Quiz Leave</th>
                            <th>Last Quiz Rank</th> --}}
                            <th>Wallet Points</th>
                            {{-- <th>No. of Quiz</th>
                            <th>Practice Quiz</th> --}}
                            <th>Last Login</th>
                            <th>Status</th>
                            <th style="text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                            <tr>
                                {{-- <td>                      
                                   @if ($user->profile_photo)
                                            <a href="{{ asset('storage/' . $user->profile_photo) }}" target="_blank" class="badge info">View Photo</a>
                                        @else
                                            <span class="mono" style="color:var(--ink-faint);">No Photo</span>
                                            @endif
                               </td>  --}}
                                <td data-label="User Info">
                                    <div class="cell-user">
                                        {{-- @if ($user->profile_photo)
                                           
                                            <img src="{{ Storage::disk('s3')->url($user->profile_photo) }}"
                                                class="user-thumb" alt="{{ $user->name }}">
                                        @else
                                            <div class="cell-av">{{ strtoupper(substr($user->name, 0, 2)) }}</div>
                                        @endif --}}

                                           @if ($user->profile_photo)
                                                <img src="{{ Storage::disk('s3')->url($user->profile_photo) }}"
                                                    class="user-thumb"
                                                    alt="{{ $user->name }}"
                                                    style="cursor:pointer;"
                                                    onclick="openProfilePhoto('{{ Storage::disk('s3')->url($user->profile_photo) }}', '{{ $user->name }}')">
                                            @else
                                                <div class="cell-av">
                                                    {{ strtoupper(substr($user->name, 0, 2)) }}
                                                </div>
                                            @endif
                                        <div class="user-info-box">
                                            <span class="badge info"
                                                style="font-size: 9px; padding: 1px 6px;">{{ ucfirst($user->role ?? 'user') }}</span>
                                            <b style=" margin-top:5px;">{{ $user->name }}</b>


                                            <p style=" color:var(--ink-soft); margin-top:5px;"> {{ $user->mobile ?? '—' }}
                                            </p>
                                            <p data-label="Gender">{{ ucfirst($user->gender ?? '—') }}</p>
                                            <p data-label="DOB" class="mono"> {{ $user->date_of_birth ? \Carbon\Carbon::parse($user->date_of_birth)->format('d M Y') : '—' }}</p>
 
                                          

                                            {{-- ✅ STRICT REFERRAL VALIDATION LOGIC --}}
                                            @if ($user->referral_user_id && $user->referrer && $user->referrer->referral_code === $user->referral_code_to)
                                                {{-- Condition Match Hui: Name aur Mobile dikhao --}}
                                                <p style="color:var(--ink-soft); margin-top:5px;">
                                                    {{-- <span class="badge"
                                                        style="font-size:10px; background: rgba(16, 185, 129, 0.1); color: #059669; border: 1px solid rgba(16, 185, 129, 0.2);">
                                                        Ref: {{ $user->referrer->name }}
                                                        ({{ $user->referrer->mobile ?? 'No Mobile' }})
                                                    </span> --}}

                                                     {{-- <button type="button"
                                                        class="badge ref-code-edit-btn"
                                                        data-user-id="{{ $user->id }}"
                                                        data-user-name="{{ $user->name }}"
                                                        data-referral-code="{{ $user->referral_code }}"
                                                        data-update-url="{{ route('superadmin.users.updateReferralCode', $user->id) }}"
                                                        style="
                                                            font-size:10px;
                                                            background:rgba(16,185,129,0.1);
                                                            color:#059669;
                                                            border:1px solid rgba(16,185,129,0.2);
                                                            cursor:pointer;
                                                        ">
                                                        Ref: {{ $user->referrer->name }}
                                                        ({{ $user->referrer->mobile ?? 'No Mobile' }})
                                                    </button> --}}

                                                     <span class="badge"
                                                            style="
                                                                font-size:10px;
                                                                background:rgba(16,185,129,0.1);
                                                                color:#059669;
                                                                border:1px solid rgba(16,185,129,0.2);
                                                            ">
                                                            Ref: {{ $user->referrer->name }}
                                                            ({{ $user->referrer->mobile ?? 'No Mobile' }})
                                                        </span>
                                                </p>
                                            @else
                                                {{-- Condition Fail ya Null: Sirf Code dikhao --}}
                                                <p style="color:var(--ink-soft); margin-top:5px;">
                                                    {{-- <span class="badge"
                                                        style="font-size:10px; background: #f6f3f3; color: #ee5e5e; border: 1px solid #e5e7eb;">
                                                        Ref:Superadmin ({{ $user->referral_code_to ?? '—' }})
                                                    </span> --}}

                                                     {{-- <button type="button"
                                                        class="badge ref-code-edit-btn"
                                                        data-user-id="{{ $user->id }}"
                                                        data-user-name="{{ $user->name }}"
                                                        data-referral-code="{{ $user->referral_code }}"
                                                        data-update-url="{{ route('superadmin.users.updateReferralCode', $user->id) }}"
                                                        style="
                                                            font-size:10px;
                                                            background:#f6f3f3;
                                                            color:#ee5e5e;
                                                            border:1px solid #e5e7eb;
                                                            cursor:pointer;
                                                        ">
                                                        Ref:Superadmin ({{ $user->referral_code_to ?? '—' }})
                                                    </button> --}}


                                                        @if ($user->referral_code_to === 'SANDE687')

                                                            {{-- ONLY SANDE687 CAN BE EDITED --}}
                                                            <button type="button"
                                                                class="badge ref-code-edit-btn"
                                                                data-user-id="{{ $user->id }}"
                                                                data-user-name="{{ $user->name }}"
                                                                data-referral-code-to="{{ $user->referral_code_to }}"
                                                                data-update-url="{{ route('superadmin.users.updateReferralCode', $user->id) }}"
                                                                style="
                                                                    font-size:10px;
                                                                    background:#fff1f2;
                                                                    color:#dc2626;
                                                                    border:1px solid #fecdd3;
                                                                    cursor:pointer;
                                                                "
                                                                title="Change referral code">

                                                                Ref:Superadmin ({{ $user->referral_code_to }})

                                                                <i class="ti ti-pencil"
                                                                    style="font-size:11px; margin-left:3px;">
                                                                </i>
                                                            </button>

                                                        @else

                                                            {{-- Other referral codes: NO POPUP --}}
                                                            <span class="badge"
                                                                style="
                                                                    font-size:10px;
                                                                    background:#f6f3f3;
                                                                    color:#ee5e5e;
                                                                    border:1px solid #e5e7eb;
                                                                ">
                                                                Ref:Superadmin ({{ $user->referral_code_to ?? '—' }})
                                                                
                                                            </span>
                                                            {{-- <span class="badge"
                                                                style="font-size:10px; background: rgba(16, 185, 129, 0.1); color: #059669; border: 1px solid rgba(16, 185, 129, 0.2);">
                                                                Ref: {{ $user->referrer->name ?? '—' }}
                                                                ({{ $user->referrer->mobile ?? 'No Mobile' }})
                                                        </span> --}}

                                                        @endif
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                               

                           
                                {{-- <td data-label="Gender">{{ ucfirst($user->gender ?? '—') }}</td>
                                <td data-label="DOB" class="mono">
                                    {{ $user->date_of_birth ? \Carbon\Carbon::parse($user->date_of_birth)->format('d M Y') : '—' }}
                                </td> --}}

                                {{-- <td data-label="Ref Code" class="mono"><span class="badge info" style="font-size:10px;">{{ $user->referral_code ?? '—' }}</span></td> --}}
                                <td data-label="Referral History" class="text-center align-middle"
                                    style="padding: 12px 8px;">
                                    <div class="flex flex-col items-center justify-center gap-2 h-full">

                                        {{-- Referral Code Badge --}}
                                        <span class="badge info" style="font-size:10px; font-weight: 600;">
                                            {{ $user->referral_code ?? '—' }}
                                        </span>

                                        @php
                                            // ✅ Safest way to get count
                                            $refCount =
                                                $user->referralsAllowed_count ?? $user->referralsAllowed()->count();
                                        @endphp

                                        @if ($refCount > 0)
                                            <button type="button" class="btn btn-sm referral-history-btn"
                                                style="background-color: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; font-weight: 600; font-size: 11px; padding: 6px 12px; border-radius: 6px; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s;"
                                                onmouseover="this.style.backgroundColor='#d1fae5'; this.style.borderColor='#6ee7b7';"
                                                onmouseout="this.style.backgroundColor='#ecfdf5'; this.style.borderColor='#a7f3d0';"
                                                data-user-id="{{ $user->id }}"
                                                data-user-name="{{ addslashes($user->name) }}"
                                                data-history-url="{{ route('superadmin.users.referralHistory', $user->id) }}">
                                                <i class="ti ti-users" style="font-size: 14px;"></i>
                                                History ({{ $refCount }})
                                            </button>
                                        @else
                                            <span
                                                class="text-[10px] text-gray-400 italic font-medium bg-gray-50 px-2 py-1 rounded-md border border-gray-100">
                                                No referrals
                                            </span>
                                        @endif

                                    </div>
                                </td>
                                {{-- <td data-label="Create Limit" class="mono" style="text-align:center;">
                                    {{ $user->create_quiz_limit ?? 0 }}</td> --}}

                                 {{-- Table Body (tbody) ke andar, existing Status column ke bagal mein add karein --}}
                                <td data-label="On/Off" style="text-align: center;">
                                    <label class="switch">
                                        <input type="checkbox" 
                                            class="user-onoff-toggle" 
                                            data-id="{{ $user->id }}" 
                                            {{ $user->on_off === 'on' ? 'checked' : '' }}>
                                        <span class="slider"></span>
                                    </label>
                                </td>  
                                
                                {{-- ========================================== --}}
                                {{-- COLUMN 1: Limits & Rank --}}
                                {{-- ========================================== --}}
                                <td data-label="Limits & Rank" style="padding: 12px 16px; vertical-align: middle;">
                                    <div style="display: flex; flex-direction: column; gap: 6px;">
                                        
                                        

                                        {{-- Play Limit --}}
                                        <div style="display: flex; justify-content: space-between; align-items: center; font-size: 11px;">
                                            <span style="color: #64748b; font-weight: 600;">Play Limit:</span>
                                            <span style="padding: 2px 8px; background: #fee2e2; color: #991b1b; border-radius: 12px; font-weight: 700; border: 1px solid #fecaca;">
                                                {{ $user->available_quiz_limit ?? 0 }}
                                            </span>
                                        </div>

                                        {{-- Quiz Leave --}}
                                        <div style="display: flex; justify-content: space-between; align-items: center; font-size: 11px;">
                                            <span style="color: #64748b; font-weight: 600;">Leave:</span>
                                            <span style="font-weight: 700; color: #0f172a;">{{ $user->quiz_leave ?? 0 }}</span>
                                        </div>

                                        {{-- Last Quiz Rank --}}
                                        <div style="display: flex; justify-content: space-between; align-items: center; font-size: 11px;">
                                            <span style="color: #64748b; font-weight: 600;">Last Rank:</span>
                                            @if($user->quiz_rank && $user->quiz_rank > 0)
                                                <span style="padding: 2px 8px; background: #fef3c7; color: #92400e; border-radius: 12px; font-weight: 700; border: 1px solid #fde68a;">
                                                    #{{ $user->quiz_rank }}
                                                </span>
                                            @else
                                                <span style="font-weight: 700; color: #94a3b8;">—</span>
                                            @endif
                                        </div>

                                    </div>
                                </td>

                                {{-- ========================================== --}}
                                {{-- COLUMN 2: Points & Counts --}}
                                {{-- ========================================== --}}
                                <td data-label="Points & Counts" style="padding: 12px 16px; vertical-align: middle;">
                                    <div style="display: flex; flex-direction: column; gap: 6px;">
                                        
                                        

                                        {{-- Total Quizzes Played --}}
                                        <div style="display: flex; justify-content: space-between; align-items: center; font-size: 11px;">
                                            <span style="color: #64748b; font-weight: 600;"> Daily:</span>
                                             <span class="badge success" style="font-size:10px; font-weight: 600;">{{ $user->quiz_users_count ?? 0 }}</span>
                                        </div>

                                        {{-- Practice Quizzes Played --}}
                                        <div style="display: flex; justify-content: space-between; align-items: center; font-size: 11px;">
                                            <span style="color: #64748b; font-weight: 600;">Practice :</span>
                                             <span class="badge danger" style="font-size:10px; font-weight: 600;">{{ $user->practice_quiz_users_count ?? 0 }}</span>
                                        </div>

                                    </div>
                                </td>


                                {{-- <td data-label="Play Limit" class="mono" style="text-align:center;">
                                   <span class="badge danger" style="font-size:10px; font-weight: 600;">
                                           
                                      {{ $user->available_quiz_limit ?? 0 }}   </span> </td> --}}
                                {{-- <td data-label="Quiz Leave" class="mono" style="text-align:center;">
                                    {{ $user->quiz_leave ?? 0 }}</td> --}}
                                {{-- <td data-label="Last Quiz Rank" class="mono" style="text-align:center;">
                                    {{ $user->quiz_rank ?? 0 }}</td> --}}
                                <td data-label="Wallet Points" class="mono">
                                    <div class="wallet-point-box">
                                        {{ number_format($user->wallet_balance ?? 0, 2) }}
                                        <button type="button" class="btn btn-sm btn-primary wallet-history-btn"
                                            data-user-id="{{ $user->id }}" data-user-name="{{ e($user->name) }}"
                                            data-history-url="{{ route('superadmin.users.walletHistory', $user->id) }}">
                                            View
                                        </button>
                                    </div>
                                </td>
                                {{-- <td data-label="Wallet Points" class="mono"> <span class="badge success"
                                        style="font-size:10px;">{{ $user->quiz_users_count ?? 0 }}</span></td>
                                <td data-label="Wallet Points" class="mono"> <span class="badge success"
                                        style="font-size:10px;">{{ $user->practice_quiz_users_count ?? 0 }}</span></td>         --}}
                                <td data-label="Last Login"class="mono">
                                    <div class="last-login-box">
                                        {{ $user->last_login_at?->diffForHumans() ?? '—' }}
                                        @if ($user->last_login_at)
                                            <span class="badge info" style="font-size:10px;">
                                                Via {{ ucfirst($user->login_type ?? 'web') }}
                                            </span>
                                        @endif
                                        
                                        <button type="button"
                                            class="btn btn-sm btn-primary mt-2 leader-point-history-btn"
                                            data-user-id="{{ $user->id }}" data-user-name="{{ $user->name }}"
                                            data-history-url="{{ route('superadmin.users.leaderPointHistory', $user->id) }}">
                                            Leaderboard History ({{ number_format($user->leader_board_point ?? 0, 2) }})
                                              (Rank - {{ $user->leaderboard->rank ?? '-' }}
                                                 Reward - {{ number_format($user->leaderboard->points ?? 0, 2) }})
                                        </button>
                                    </div>
                                </td>
                                <td data-label="Status">
                                    <div class="user-status-table-box"
                                        style="display: flex; flex-direction:column; gap:10px;">
                                        <div>
                                            <select class="user-status-change" data-id="{{ $user->id }}">
                                                <option value="1" {{ $user->is_active ? 'selected' : '' }}>Active
                                                </option>
                                                <option value="0" {{ !$user->is_active ? 'selected' : '' }}>Suspended
                                                </option>
                                            </select>
                                        </div>
                                        <div>
                                            <span class="badge info" style="font-size:10px;">
                                                {{ $user->created_at ? $user->created_at->format('d M Y, h:i:s A') : '—' }}
                                            </span>
                                        </div>
                                        <div>
                                           <span class="badge {{ strtolower($user->profile_status) === 'pending' ? 'danger' : (strtolower($user->profile_status) === 'complete' ? 'success' : 'info') }}"
                                                style="font-size:10px; text-transform:none;"
                                            >
                                                Profile : {{ $user->profile_status ?? '—' }}
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td data-label="Actions" class="row-actions" style="justify-content: flex-end;">
                                    <div class="users-action-box">
                                        <button type="button" class="icon-btn" title="View Details"
                                            onclick='openViewModal(@json($user))'>👁</button>
                                        <a class="icon-btn" title="Edit User"
                                            href="{{ route('superadmin.users.index', ['edit' => $user->id]) }}">✎</a>
                                        <form method="POST" action="{{ route('superadmin.users.destroy', $user) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button class="icon-btn danger" title="Suspend User"
                                                type="submit">⊘</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="15" style="text-align:center;color:var(--ink-faint);padding: 24px;">No
                                    users found.</td>
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
                <button type="button" class="icon-btn" onclick="closeUserModal()"
                    style="border:none;background:transparent;box-shadow:none;">✕</button>
            </div>
            <form method="POST"
                action="{{ $editing ? route('superadmin.users.update', $editing) : route('superadmin.users.store') }}"
                enctype="multipart/form-data">
                @csrf
                @if ($editing)
                    @method('PUT')
                @endif
                <div class="form-grid form-grid-02">

                    {{-- 1. Basic Profile Info --}}
                    <div class="modal-section-title">1. Basic Profile Info</div>

                    <div class="field">
                        <label>Full Name <span class="req">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $editing->name ?? '') }}"
                            placeholder="Enter full name" required>
                    </div>

                    <div class="field">
                        <label>Email Address</label>
                        <input type="email" name="email" value="{{ old('email', $editing->email ?? '') }}"
                            placeholder="name@example.com">
                    </div>

                    <div class="field">
                        <label>Mobile Number</label>
                        <input type="text" name="mobile" value="{{ old('mobile', $editing->mobile ?? '') }}"
                            placeholder="10-digit mobile number" maxlength="10" minlength="10" pattern="[0-9]{10}"
                            inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10);">
                    </div>

                    <div class="field">
                        <label>Alternative Mobile</label>
                        <input type="text" name="alternative_mobile"
                            value="{{ old('alternative_mobile', $editing->alternative_mobile ?? '') }}"
                            placeholder="Alternate phone number" maxlength="10" minlength="10" pattern="[0-9]{10}"
                            inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10);">
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
                        <input type="date" name="date_of_birth"
                            value="{{ old('date_of_birth', $editing?->date_of_birth ? \Carbon\Carbon::parse($editing->date_of_birth)->format('Y-m-d') : '') }}">
                    </div>

                    <div class="field full">
                        <label>Profile Photo</label>
                        <input type="file" name="profile_photo" accept="image/*">
                        @if ($editing?->profile_photo)
                            <div class="desc" style="margin-top:4px;">Current photo: <a
                                    href="{{ Storage::disk('s3')->url($editing->profile_photo) }}" target="_blank">View
                                    existing image</a></div>
                        @endif
                    </div>

                    {{-- 2. Account & Security --}}
                    <div class="modal-section-title">2. Account & Security</div>

                    <div class="field">
                        <label>Role <span class="req">*</span></label>
                        <select name="role" required>
                            @foreach (['user', 'admin', 'superadmin', 'teacher', 'moderator'] as $role)
                                <option value="{{ $role }}" @selected(old('role', $editing->role ?? 'user') === $role)>{{ ucfirst($role) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- <div class="field">
                                                <label>Role ID</label>
                                                <input type="number" name="role_id" value="{{ old('role_id', $editing->role_id ?? '') }}" placeholder="Role ID number">
                                            </div> -->

                    <!-- <div class="field">
                                                <label>Tenant ID <span class="hint">(Default 1)</span></label>
                                                <input type="number" name="tenant_id" value="{{ old('tenant_id', $editing->tenant_id ?? 1) }}" placeholder="1">
                                            </div> -->

                    <div class="field">
                        <label>Login Method</label>
                        <select name="login_method" id="loginMethodSelect" onchange="togglePasswordField()">
                            <option value="otp" @selected(old('login_method', $editing->login_method ?? 'otp') === 'otp')>OTP (Default)</option>
                            <option value="password" @selected(old('login_method', $editing->login_method ?? '') === 'password')>Password</option>
                            <option value="inactive" @selected(old('login_method', $editing->login_method ?? '') === 'inactive')>Inactive</option>
                        </select>
                    </div>

                    <div class="field" id="passwordFieldContainer"
                        style="display: {{ old('login_method', $editing->login_method ?? 'otp') === 'password' ? 'flex' : 'none' }};">
                        <label>Password <span
                                class="hint">{{ $editing ? '(Leave blank to keep unchanged)' : '(Default: 123456)' }}</span></label>
                        <input type="password" name="password"
                            placeholder="{{ $editing ? '••••••••' : 'Enter password' }}">
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
                        <select name="state" id="stateSelect"
                            onchange="fetchCities(this.options[this.selectedIndex].getAttribute('data-id'))">
                            <option value="">Select State</option>
                            @if (isset($states))
                                @foreach ($states as $state)
                                    <option value="{{ $state->name }}" data-id="{{ $state->id }}"
                                        @selected(old('state', $editing->state ?? '') === $state->name)>{{ $state->name }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>

                    <div class="field">
                        <label>City</label>
                        <select name="city" id="citySelect">
                            @if ($editing && $editing->city)
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
                        <input type="text" name="lat" id="latInput"
                            value="{{ old('lat', $editing->lat ?? '') }}" placeholder="Auto generated" readonly
                            style="background-color: var(--line);">
                    </div>

                    <div class="field">
                        <label>Longitude (Long)</label>
                        <input type="text" name="long" id="longInput"
                            value="{{ old('long', $editing->long ?? '') }}" placeholder="Auto generated" readonly
                            style="background-color: var(--line);">
                    </div>

                    {{-- 4. Quiz Limits & Wallet --}}
                    <div class="modal-section-title">4. Quiz Limits & Wallet</div>

                    <div class="field">
                        <label>Create Quiz Limit</label>
                        <input type="number" name="create_quiz_limit"
                            value="{{ old('create_quiz_limit', $editing->create_quiz_limit ?? 0) }}" min="0">
                    </div>

                    <div class="field">
                        <label>Available Quiz Limit</label>
                        <input type="number" name="available_quiz_limit"
                            value="{{ old('available_quiz_limit', $editing->available_quiz_limit ?? 0) }}"
                            min="0">
                    </div>

                    <div class="field">
                        <label>Quiz Leave</label>
                        <input type="number" name="quiz_leave"
                            value="{{ old('quiz_leave', $editing->quiz_leave ?? 0) }}" min="0">
                    </div>

                    <div class="field">
                        <label>Quiz Rank</label>
                        <input type="number" name="quiz_rank" value="{{ old('quiz_rank', $editing->quiz_rank ?? 0) }}"
                            min="0">
                    </div>

                    <div class="field full">
                        <label>Wallet Points </label>
                        <input type="number" step="0.01" name="wallet_balance"
                            value="{{ old('wallet_balance', $editing->wallet_balance ?? 0) }}" min="0">
                    </div>

                    {{-- 5. Personal Preferences --}}
                    {{-- <div class="modal-section-title">5. Personal Preferences</div>

                    <div class="field">
                        <label>Favorite Color</label>
                        <input type="text" name="favorite_color"
                            value="{{ old('favorite_color', $editing->favorite_color ?? '') }}"
                            placeholder="e.g. Blue, Red">
                    </div>

                    <div class="field">
                        <label>Hobbies</label>
                        <input type="text" name="hobbies" value="{{ old('hobbies', $editing->hobbies ?? '') }}"
                            placeholder="Reading, Coding, Travel">
                    </div>

                    <div class="field">
                        <label>Games</label>
                        <input type="text" name="games" value="{{ old('games', $editing->games ?? '') }}"
                            placeholder="Chess, Cricket, BGMI">
                    </div>

                    <div class="field">
                        <label>Interests</label>
                        <input type="text" name="interest" value="{{ old('interest', $editing->interest ?? '') }}"
                            placeholder="Tech, Science, GK">
                    </div> --}}

                </div>

                <div class="form-foot">
                    @if ($editing)
                        <a class="btn btn-ghost" href="{{ route('superadmin.users.index') }}">Cancel</a>
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
                {{-- <h3>User Profile &amp; Full Details</h3> --}}
                  <h3>User Profile </h3>
                <button type="button" class="icon-btn" onclick="closeViewModal()"
                    style="border:none;background:transparent;box-shadow:none;">✕</button>
            </div>

            <div class="user-profile-img-box">
                <div id="vPhotoContainer">
                    <div class="user-avatar-lg" id="vInitials">U</div>
                </div>
                <div style="flex:1;">
                    <h2 id="vName"
                        style="font-family:var(--font-display); font-size:18px; font-weight:800; color:var(--ink);">User
                        Name</h2>
                    <div style="display:flex; gap:8px; align-items:center; margin-top:4px; flex-wrap:wrap;">
                        <span id="vRoleBadge" class="badge info">User</span>
                        <span id="vStatusBadge" class="badge success">Active</span>
                        <span class="mono" style="font-size:11px; color:var(--ink-soft);">Referral Code: <b
                                id="vRefCode" style="color:var(--indigo);">—</b></span>
                    </div>
                </div>
            </div>

            <div class="modal-section-title" style="margin-top:0;">Account &amp; Authentication</div>
            <div class="detail-grid">
                <div class="detail-card">
                    <div class="lbl">Login Method</div>
                    <div class="val" id="vLoginMethod">otp</div>
                </div>
                {{-- <div class="detail-card">
                    <div class="lbl">Password</div>
                    <div class="val" id="vPassword">●●●●●●●●</div>
                </div> --}}
                <div class="detail-card">
                    <div class="lbl">Role</div>
                    <div class="val" id="vRole">user</div>
                </div>
                <div class="detail-card">
                    <div class="lbl">Role ID</div>
                    <div class="val" id="vRoleId">—</div>
                </div>
                <div class="detail-card">
                    <div class="lbl">Tenant ID</div>
                    <div class="val" id="vTenantId">1</div>
                </div>
                <div class="detail-card">
                    <div class="lbl">Verify Mobile</div>
                    <div class="val" id="vVerifyMobile">No</div>
                </div>
                {{-- <div class="detail-card">
                    <div class="lbl">Verify Email</div>
                    <div class="val" id="vVerifyEmail">No</div>
                </div> --}}
            </div>

        

            <div class="modal-section-title">Address &amp; Location</div>
            <div class="detail-grid">
                <div class="detail-card">
                    <div class="lbl">State</div>
                    <div class="val" id="vState">—</div>
                </div>
                <div class="detail-card">
                    <div class="lbl">City</div>
                    <div class="val" id="vCity">—</div>
                </div>

                <div class="detail-card">
                    <div class="lbl">Latitude (Lat)</div>
                    <div class="val" id="vLat">—</div>
                </div>
                <div class="detail-card">
                    <div class="lbl">Longitude (Long)</div>
                    <div class="val" id="vLong">—</div>
                </div>
                <div class="detail-card" style="grid-column: span 2;">
                    <div class="lbl">Address</div>
                    <div class="val" id="vAddress">—</div>
                </div>
            </div>

            <div class="modal-section-title">Quiz Stats &amp; Wallet Balance</div>
            <div class="detail-grid">
                <div class="detail-card">
                    <div class="lbl">Create Quiz Limit</div>
                    <div class="val" id="vCreateLimit">0</div>
                </div>
                <div class="detail-card">
                    <div class="lbl">Available Quiz Limit</div>
                    <div class="val" id="vAvailLimit">0</div>
                </div>
                <div class="detail-card">
                    <div class="lbl">Quiz Leave</div>
                    <div class="val" id="vQuizLeave">0</div>
                </div>
                <div class="detail-card">
                    <div class="lbl">Quiz Rank</div>
                    <div class="val" id="vQuizRank">0</div>
                </div>
                <div class="detail-card">
                    <div class="lbl">Wallet Points</div>
                    <div class="val" id="vWallet" style="color:var(--emerald);">0.00</div>
                </div>
                <div class="detail-card">
                    <div class="lbl">Joined At</div>
                    <div class="val" id="vCreatedAt">—</div>
                </div>
                <div class="detail-card">
                    <div class="lbl">Last Login</div>
                    <div class="val" id="vLastLogin">—</div>
                    <div class="val" id="vLoginType">—</div>
                </div>
            </div>


            <div class="modal-section-title">Contact &amp; Personal Info</div>
            <div class="detail-grid">
                {{-- <div class="detail-card">
                    <div class="lbl">Email</div>
                    <div class="val" id="vEmail">—</div>
                </div> --}}
                <div class="detail-card">
                    <div class="lbl">Mobile</div>
                    <div class="val" id="vMobile">—</div>
                </div>
                <div class="detail-card">
                    <div class="lbl">Alternative Mobile</div>
                    <div class="val" id="vAltMobile">—</div>
                </div>
                <div class="detail-card">
                    <div class="lbl">Gender</div>
                    <div class="val" id="vGender">—</div>
                </div>
                <div class="detail-card">
                    <div class="lbl">Date of Birth</div>
                    <div class="val" id="vDob">—</div>
                </div>
                <div class="detail-card">
                    <div class="lbl">Favorite Color</div>
                    <div class="val" id="vFavColor">—</div>
                </div>
                <div class="detail-card">
                    <div class="lbl">Hobbies</div>
                    <div class="val" id="vHobbies"></div>
                </div>

                <div class="detail-card">
                    <div class="lbl">Games</div>
                    <div class="val" id="vGames"></div>
                </div>

                <div class="detail-card">
                    <div class="lbl">Interest</div>
                    <div class="val" id="vInterest"></div>
                </div>
            </div>

            <div class="form-foot">
                <button class="btn btn-ghost" type="button" onclick="closeViewModal()">Close</button>
            </div>
        </div>
    </div>

    {{-- LEADERBOARD POINT HISTORY MODAL --}}
    <div id="leaderPointHistoryModal" class="modal">

        <div class="modal-backdrop" onclick="closeLeaderPointHistoryModal()"></div>

        <div class="modal-content form-card" style="margin-bottom:0; max-width:860px;">

            <div class="fh">

                <h3>
                    Leaderboard Point History -
                    <span id="historyUserName"></span>
                </h3>

                <button type="button" class="icon-btn" onclick="closeLeaderPointHistoryModal()"
                    style="border:none;background:transparent;box-shadow:none;">
                    ✕
                </button>

            </div>


            <div>

                {{-- Loader --}}
                <div id="historyLoader" class="text-center" style="padding:30px 0;">

                    <div>
                        Loading...
                    </div>

                </div>


                {{-- History Content --}}
                <div id="historyContent" style="display:none;">
                    <div class="table-wrap table-wrap-res-01" style="margin-top:15px;">

                        <table class="users-table-wrapper user-history-table">

                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Quiz</th>
                                    <th>Point</th>
                                    <th>Type</th>
                                    <th>Date</th>
                                </tr>
                            </thead>

                            <tbody id="historyTableBody">
                            </tbody>
                        </table>
                    </div>
                </div>


                {{-- No History --}}
                <div id="noHistory" class="text-center" style="display:none;padding:30px 0;color:var(--ink-faint);">

                    No leaderboard point history found.
                </div>
            </div>
        </div>
    </div>

    {{-- USER WALLET / POINT / VOUCHER HISTORY MODAL --}}
    <div id="userHistoryModal" class="modal">
        <div class="modal-backdrop" onclick="closeUserHistoryModal()"></div>
        <div class="modal-content form-card form-card-02" style="margin-bottom:0; max-width:900px;">
            {{-- Header --}}
            <div class="fh">
                <h3>
                    User History -
                    {{-- <span id="historyUserName"></span> --}}
                    <span style="font-size:12px; color:var(--ink-soft); font-weight:600;">
                        Leaderboard Marks - <span id="pointHistoryCount">0</span>
                        &nbsp; | &nbsp;
                        Voucher - <span id="voucherHistoryCount">0</span>
                        {{-- &nbsp; | &nbsp;
                        Wallet - <span id="walletHistoryCount">0</span> --}}
                    </span>
                </h3>
                <button type="button" class="icon-btn" onclick="closeUserHistoryModal()"
                    style="border:none;background:transparent;box-shadow:none;"> ✕ </button>
            </div>

            {{-- Tabs --}}
            <div class="history-tabs">
                <button type="button" class="history-tab" data-tab="wallet">Wallet (<span id="walletTotal">0.00</span>)
                </button>
                <button type="button" class="history-tab" data-tab="voucher"> Voucher </button>
                <button type="button" class="history-tab active" data-tab="point"> Leaderboard Marks (<span
                        id="leaderboardMarksTotal">0.00</span>) </button>
            </div>


            <div>
                {{-- Loader --}}
                <div id="userHistoryLoader" class="text-center"style="padding:30px 0;">
                    Loading...
                </div>

                {{-- ================= POINT TAB ================= --}}
                <div id="pointHistoryTab" class="history-tab-content">
                    <div class="table-wrap table-wrap-res-01">
                        <table class="users-table-wrapper user-history-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Quiz</th>
                                    <th>Marks</th>
                                    <th>Reward Type</th>
                                    <th>Date & Time</th>
                                </tr>
                            </thead>
                            <tbody id="pointHistoryBody"></tbody>
                        </table>
                    </div>
                    <div id="pointHistoryEmpty" class="history-empty"style="display:none;">
                        No point history found.
                    </div>
                </div>

                {{-- ================= VOUCHER TAB ================= --}}
                <div id="voucherHistoryTab" class="history-tab-content" style="display:none;">
                    <div class="table-wrap table-wrap-res-01">
                        <table class="users-table-wrapper user-history-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Voucher Name</th>
                                    <th>Partner</th>
                                    <th>Points</th>
                                    <th>Code</th>
                                    {{-- <th>Status</th> --}}
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody id="voucherHistoryBody"></tbody>
                        </table>
                    </div>
                    <div id="voucherHistoryEmpty" class="history-empty" style="display:none;">
                        No voucher history found.
                    </div>
                </div>

                {{-- ================= WALLET TAB ================= --}}
                <div id="walletHistoryTab" class="history-tab-content" style="display:none;">
                    <div class="table-wrap table-wrap-res-01">
                        <table class="users-table-wrapper user-history-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Points</th>
                                    <th>Type</th>
                                    <th>Remarks</th>
                                    <th>Date & Time</th>
                                </tr>
                            </thead>
                            <tbody id="walletHistoryBody"></tbody>
                        </table>
                    </div>

                    <div id="walletHistoryEmpty" class="history-empty" style="display:none;">
                        No wallet history found.
                    </div>
                </div>
            </div>
        </div>
    </div>
    {{-- ******UDPATE BULK LIMIT USER QUIZ****** --}}
    {{-- BULK UPDATE PLAY LIMIT MODAL --}}
    <div id="bulkPlayLimitModal" class="modal bulk-modal-custom">
        <div class="modal-backdrop" onclick="closeBulkPlayLimitModal()"></div>
        <div class="modal-content form-card"
            style="max-width: 480px; margin: 20px; border-radius: 16px; overflow: hidden;">

            {{-- Header with Icon --}}
            <div class="fh"
                style="background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); border-bottom: 1px solid #e2e8f0; padding: 20px 24px;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div
                        style="width: 40px; height: 40px; background: #eff6ff; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px;">
                        ⚡
                    </div>
                    <div>
                        <h3 style="margin: 0; font-size: 18px; font-weight: 700; color: #0f172a;">Update All Users Limit
                        </h3>
                        <p style="margin: 2px 0 0; font-size: 12px; color: #64748b;">Bulk action for all registered users
                        </p>
                    </div>
                </div>
                <button type="button" class="icon-btn" onclick="closeBulkPlayLimitModal()"
                    style="border:none; background:transparent; font-size: 18px; color: #64748b; transition: color 0.2s;"
                    onmouseover="this.style.color='#ef4444'" onmouseout="this.style.color='#64748b'">✕</button>
            </div>

            <form id="bulkPlayLimitForm" onsubmit="submitBulkPlayLimit(event)" style="padding: 24px;">
                @csrf

                {{-- 1. Add Limit Field --}}
                <div class="field full" style="margin-bottom: 20px;">
                    <label style="font-weight: 600; color: #334155; margin-bottom: 8px; display: block;">
                        Add Play Limit <span class="req" style="color: #ef4444;">*</span>
                    </label>
                    <div style="position: relative;">
                        <input type="number" name="add_limit" id="bulkAddLimitInput" min="1" placeholder="e.g., 5" required
                            style="width: 100%; padding: 12px 16px; border: 2px solid #e2e8f0; border-radius: 10px; font-size: 16px; font-weight: 600; color: #0f172a; outline: none; transition: all 0.2s;"
                            onfocus="this.style.borderColor='#3b82f6'; this.style.boxShadow='0 0 0 3px rgba(59, 130, 246, 0.1)'"
                            onblur="this.style.borderColor='#e2e8f0'; this.style.boxShadow='none'">
                        <span style="position: absolute; right: 16px; top: 50%; transform: translateY(-50%); font-size: 13px; font-weight: 600; color: #94a3b8; pointer-events: none;">Limits</span>
                    </div>
                </div>

                {{-- 2. NEW: Max Current Limit Field --}}
                <div class="field full" style="margin-bottom: 20px;">
                    <label style="font-weight: 600; color: #334155; margin-bottom: 8px; display: block;">
                        Target Users: Current Limit <= (Optional)
                    </label>
                    <div style="position: relative;">
                        <input type="number" name="max_current_limit" id="bulkMaxLimitInput" min="0" placeholder="e.g., 15 (Leave blank for ALL active/on users)"
                            style="width: 100%; padding: 12px 16px; border: 2px solid #e2e8f0; border-radius: 10px; font-size: 16px; font-weight: 600; color: #0f172a; outline: none; transition: all 0.2s;"
                            onfocus="this.style.borderColor='#10b981'; this.style.boxShadow='0 0 0 3px rgba(16, 185, 129, 0.1)'"
                            onblur="this.style.borderColor='#e2e8f0'; this.style.boxShadow='none'">
                        <span style="position: absolute; right: 16px; top: 50%; transform: translateY(-50%); font-size: 13px; font-weight: 600; color: #94a3b8; pointer-events: none;">Max Limit</span>
                    </div>
                    <small style="color: #64748b; font-size: 12px; margin-top: 6px; display: block;">
                        💡 Example: Enter <strong>15</strong> to only update users who currently have 15 or fewer limits.
                    </small>
                </div>

                {{-- Styled Warning Box --}}
                <div style="background: #fff7ed; border: 1px solid #fdba74; border-radius: 10px; padding: 14px 16px; display: flex; gap: 12px; align-items: flex-start; margin-bottom: 24px;">
                    <span style="font-size: 18px; flex-shrink: 0;">⚠️</span>
                    <div>
                        <p style="margin: 0; font-size: 13px; font-weight: 600; color: #9a3412; line-height: 1.4;">
                            Warning: Smart Bulk Action
                        </p>
                        <p style="margin: 4px 0 0; font-size: 12px; color: #c2410c; line-height: 1.4;">
                            This will <strong>ADD</strong> limits ONLY to users who are <strong>Active</strong>, <strong>ON</strong>, and have a current limit <strong>less than or equal to</strong> the value above.
                        </p>
                    </div>
                </div>

                {{-- Footer Buttons --}}
                <div class="form-foot" style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 0; padding-top: 16px; border-top: 1px solid #f1f5f9;">
                    <button type="button" class="btn btn-ghost" onclick="closeBulkPlayLimitModal()" style="padding: 10px 20px; border-radius: 8px; font-weight: 600; color: #475569; border: 1px solid #cbd5e1;">
                        Cancel
                    </button>
                    <button type="submit" id="submitBulkPlayLimitBtn" style="padding: 10px 24px; border-radius: 8px; font-weight: 600; background: #2563eb; color: white; border: none; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: all 0.2s;" onmouseover="this.style.background='#1d4ed8'; this.style.transform='translateY(-1px)'" onmouseout="this.style.background='#2563eb'; this.style.transform='translateY(0)'">
                        <span>⚡</span> Update Targeted Users
                    </button>
                </div>
            </form>
        </div>
    </div>
     <div id="profilePhotoModal" class="profile-photo-modal" onclick="closeProfilePhoto(event)">
        <div class="profile-photo-modal-content">
            <button type="button"
                class="profile-photo-close"
                onclick="closeProfilePhoto()">
                &times;
            </button>

            <img id="profilePhotoPreview"
                src=""
                alt="Profile Photo">

            <div id="profilePhotoName" class="profile-photo-name"></div>
        </div>
    </div>

    <!-- ================= REFERRAL CODE EDIT MODAL ================= -->
      <!-- Referral Code Change Modal -->
        <div id="referralCodeEditModal"
            style="
                display:none;
                position:fixed;
                inset:0;
                z-index:99999;
                background:rgba(0,0,0,.65);
                align-items:center;
                justify-content:center;
                padding:20px;
            ">

            <div style="
                width:100%;
                max-width:420px;
                background:#fff;
                border-radius:16px;
                padding:22px;
                box-shadow:0 20px 50px rgba(0,0,0,.3);
            ">

                <div style="
                    display:flex;
                    justify-content:space-between;
                    align-items:center;
                    margin-bottom:20px;
                ">

                    <div>
                        <h3 style="margin:0;">
                            Change Referral
                        </h3>

                        <p id="referralEditUserName"
                            style="
                                margin:5px 0 0;
                                color:#64748b;
                                font-size:13px;
                            ">
                        </p>
                    </div>

                    <button type="button"
                        id="closeReferralCodeModal"
                        style="
                            border:0;
                            background:#f1f5f9;
                            width:32px;
                            height:32px;
                            border-radius:50%;
                            cursor:pointer;
                            font-size:18px;
                        ">
                        ×
                    </button>

                </div>


                <form id="referralCodeEditForm">

                    <div style="margin-bottom:15px;">

                        <label style="
                            display:block;
                            font-size:13px;
                            font-weight:600;
                            margin-bottom:7px;
                        ">
                            Current Referral Code
                        </label>

                        <input type="text"
                            id="currentReferralCode"
                            readonly
                            style="
                                width:100%;
                                height:44px;
                                padding:0 13px;
                                border:1px solid #e5e7eb;
                                border-radius:10px;
                                background:#f8fafc;
                                color:#64748b;
                                font-size:14px;
                            ">

                    </div>


                    <div style="margin-bottom:15px;">

                        <label style="
                            display:block;
                            font-size:13px;
                            font-weight:600;
                            margin-bottom:7px;
                        ">
                            New Referral Code
                        </label>

                        <input type="text"
                            id="newReferralCode"
                            name="referral_code_to"
                            maxlength="100"
                            autocomplete="off"
                            required
                            placeholder="Enter new referral code"
                            style="
                                width:100%;
                                height:44px;
                                padding:0 13px;
                                border:1px solid #d1d5db;
                                border-radius:10px;
                                outline:none;
                                font-size:14px;
                            ">

                    </div>


                    <div id="referralCodeError"
                        style="
                            display:none;
                            color:#dc2626;
                            font-size:12px;
                            margin-bottom:10px;
                        ">
                    </div>


                    <div style="
                        display:flex;
                        justify-content:flex-end;
                        gap:10px;
                        margin-top:20px;
                    ">

                        <button type="button"
                            id="cancelReferralCodeModal"
                            class="btn btn-sm">
                            Cancel
                        </button>

                        <button type="submit"
                            id="saveReferralCodeBtn"
                            class="btn btn-sm btn-primary">
                            Save
                        </button>

                    </div>

                </form>

            </div>
        </div>
    @include('superadmin.users.user-referral-history-modal')

   
@endsection

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <script>
        function renderUserTags(value, type) {
            if (!value) {
                return '<span style="color:var(--ink-soft);">—</span>';
            }
            let items = value;
            // JSON string hai to parse karo
            if (typeof value === 'string') {
                try {
                    items = JSON.parse(value);
                } catch (e) {
                    // Simple comma separated value
                    items = value
                        .split(',')
                        .map(item => item.trim())
                        .filter(Boolean);
                }
            }

            // Single object
            if (!Array.isArray(items)) {
                items = [items];
            }

            if (!items.length) {
                return '<span style="color:var(--ink-soft);">—</span>';
            }

           let icon = '✦';

            if (type === 'game') {
                icon = '🎮';
            } else if (type === 'interest') {
                icon = '♡';
            } else if (type === 'color') {
                icon = '🎨';
            }

            return `
                <div class="user-tags">
                    ${items.map(item => {
                        let name = '';

                        if (typeof item === 'object' && item !== null) {
                            name = item.name || item.value || item.label || '';
                        } else {
                            name = item;
                        }

                        if (!name) return '';

                        return `
                            <span class="user-tag ${type}">
                                <span class="tag-icon">${icon}</span>
                                <span>${escapeHtml(name)}</span>
                            </span>
                        `;
                    }).join('')}
                </div>
            `;
        }

        function openUserModal() {
            document.getElementById('userModal').classList.add('active');
        }

        function closeUserModal() {
            document.getElementById('userModal').classList.remove('active');
            if (window.location.search.includes('edit=')) {
                window.location.href = "{{ route('superadmin.users.index') }}";
            }
        }

        function openViewModal(user) {
            document.getElementById('vName').innerText = user.name || '—';
            document.getElementById('vRoleBadge').innerText = (user.role || 'user').toUpperCase();
            document.getElementById('vStatusBadge').innerText = user.is_active ? 'ACTIVE' : 'SUSPENDED';
            document.getElementById('vStatusBadge').className = 'badge ' + (user.is_active ? 'success' : 'danger');
            document.getElementById('vRefCode').innerText = user.referral_code || '—';

            // Profile Photo / Avatar
            const photoBox = document.getElementById('vPhotoContainer');
            if (user.profile_photo) {
                // photoBox.innerHTML = `<img src="/storage/${user.profile_photo}" class="user-avatar-lg" alt="${user.name}">`;

                photoBox.innerHTML =
                    `<img src="{{ Storage::disk('s3')->url('') }}${user.profile_photo}"class="user-avatar-lg" alt="${user.name}">`;
            } else {
                const initials = (user.name || 'U').substring(0, 2).toUpperCase();
                photoBox.innerHTML = `<div class="user-avatar-lg">${initials}</div>`;
            }

            // Account & Auth
            document.getElementById('vLoginMethod').innerText = user.login_method || 'otp';
            // document.getElementById('vPassword').innerText = '●●●●●●●● (Hashed)';
            document.getElementById('vRole').innerText = user.role || 'user';
            document.getElementById('vRoleId').innerText = user.role_id || '—';
            document.getElementById('vTenantId').innerText = user.tenant_id || '1';
            document.getElementById('vVerifyMobile').innerText = user.verify_mobile ? 'Yes' : 'No';
            // document.getElementById('vVerifyEmail').innerText = user.verify_email ? 'Yes' : 'No';

            // Contact & Personal
            // document.getElementById('vEmail').innerText = user.email || '—';
            document.getElementById('vMobile').innerText = user.mobile || '—';
            document.getElementById('vAltMobile').innerText = user.alternative_mobile || '—';
            document.getElementById('vGender').innerText = user.gender ? user.gender.charAt(0).toUpperCase() + user.gender
                .slice(1) : '—';
            document.getElementById('vDob').innerText = user.date_of_birth || '—';
            // document.getElementById('vFavColor').innerText = user.favorite_color || '—';
            // document.getElementById('vHobbies').innerText = user.hobbies || '—';
            // document.getElementById('vGames').innerText = user.games || '—';
            // document.getElementById('vInterest').innerText = user.interest || '—';
            document.getElementById('vFavColor').innerHTML = renderUserTags(user.favorite_color, 'color');
            document.getElementById('vHobbies').innerHTML =renderUserTags(user.hobbies, 'hobby');
            document.getElementById('vGames').innerHTML =renderUserTags(user.games, 'game');
            document.getElementById('vInterest').innerHTML =renderUserTags(user.interest, 'interest');

            // Address & Location
            document.getElementById('vState').innerText = user.state || '—';
            document.getElementById('vCity').innerText = user.city || '—';
            document.getElementById('vAddress').innerText = user.address || '—';
            document.getElementById('vLat').innerText = user.lat || '—';
            document.getElementById('vLong').innerText = user.long || '—';

            // Stats & Wallet
            document.getElementById('vCreateLimit').innerText = user.create_quiz_limit || 0;
            document.getElementById('vAvailLimit').innerText = user.available_quiz_limit || 0;
            document.getElementById('vQuizLeave').innerText = user.quiz_leave || 0;
            document.getElementById('vQuizRank').innerText = user.quiz_rank || 0;
            document.getElementById('vWallet').innerText = '' + parseFloat(user.wallet_balance || 0).toFixed(2);
            document.getElementById('vCreatedAt').innerText = user.created_at ? new Date(user.created_at)
                .toLocaleDateString() : '—';
            document.getElementById('vLastLogin').innerText = user.last_login_at ? new Date(user.last_login_at)
                .toLocaleString() : '—';
            document.getElementById('vLoginType').innerText = user.last_login_at
                ? 'Via ' + (user.login_type || 'web').toUpperCase()
                : '—';

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

            fetch("{{ url('superadmin/users/cities') }}/" + stateId)
                .then(res => res.json())
                .then(data => {
                    citySelect.innerHTML = '<option value="">Select City</option>';
                    data.forEach(city => {
                        const option = document.createElement('option');
                        option.value = city.name;
                        option.textContent = city.name;

                        // Check if we are editing and this is the selected city
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
            }, 500); // 500ms debounce
        }

        // On Load initialization
        document.addEventListener('DOMContentLoaded', function() {
            togglePasswordField();

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

        // Status change listener
        document.addEventListener('change', function(e) {
            if (!e.target.classList.contains('user-status-change')) {
                return;
            }

            let status = e.target.value;

            fetch("{{ route('superadmin.users.changeStatus') }}", {
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

    {{-- leaderboard history --}}
    <script>
        // ==========================================// LEADERBOARD POINT HISTORY// ==========================================

        document.addEventListener('DOMContentLoaded', function() {
            document.addEventListener('click', function(e) {
                const button = e.target.closest('.leader-point-history-btn');
                if (!button) {
                    return;
                }
                e.preventDefault();

                const userId = button.dataset.userId;
                const userName = button.dataset.userName;
                const historyUrl = button.dataset.historyUrl;

                // ------------------------------------------// Set user name/ ------------------------------------------

                document.getElementById('historyUserName').innerText = userName || 'User';


                // ------------------------------------------// Reset modal // ------------------------------------------

                document.getElementById('historyLoader').style.display = 'block';
                document.getElementById('historyContent').style.display = 'none';
                document.getElementById('noHistory').style.display = 'none';
                document.getElementById('historyTableBody').innerHTML = '';

                // ------------------------------------------ // OPEN CUSTOM MODAL// ------------------------------------------

                const modal = document.getElementById('leaderPointHistoryModal');

                if (!modal) {
                    console.error(
                        'leaderPointHistoryModal not found'
                    );
                    return;
                }

                modal.classList.add('active');

                // ------------------------------------------// GET HISTORY // ------------------------------------------

                fetch(historyUrl, {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }

                    })

                    .then(async response => {

                        const data = await response.json();
                        return {
                            ok: response.ok,
                            data: data
                        };

                    })

                    .then(result => {
                        const response = result.data;
                        // Hide loader
                        document.getElementById('historyLoader').style.display = 'none';
                        if (
                            !result.ok ||
                            !response.success ||
                            !response.history ||
                            response.history.length === 0
                        ) {
                            document.getElementById('noHistory').style.display = 'block';
                            return;
                        }

                        // ------------------------------------------// BUILD TABLE/ ------------------------------------------

                        let html = '';

                        response.history.forEach(function(item, index) {
                            const point = parseFloat(item.point || 0);
                            const pointClass = item.type === 'credit' ? 'text-success' :
                                'text-danger';
                            const pointSign = item.type === 'credit' ? '+' : '-';
                            const type = item.type ? item.type.charAt(0).toUpperCase() + item
                                .type.slice(1) : '-';
                            const quizName = item.quiz && item.quiz.title ? item.quiz.title :
                                '-';
                            const date = item.created_at ? new Date(item.created_at)
                                .toLocaleString() : '-';

                            html += `
                        <tr>
                            <td data-label="#">
                                ${index + 1}
                            </td>

                            <td data-label="Quiz">
                                ${escapeHtml(quizName)}
                            </td>

                            <td data-label="Point" class="${pointClass}" style="font-weight:700;">
                                <div style="font-weight: 500; display: inline-flex; border-radius:6px; color: #15803D; padding: 4px 6px;">
                                   ${pointSign}${point.toFixed(2)}
                                </div>
                            </td>

                            <td data-label="Type">
                                <div style="background:#ECFDF3; font-weight: 500; display: inline-flex; border-radius:6px; color: #15803D; padding: 4px 6px;">
                                   ${type}
                                </div>
                            </td>

                            <td data-label="Date">
                                ${date}
                            </td>

                        </tr>

                    `;

                        });

                        // ------------------------------------------/ SHOW TABLE/ ------------------------------------------

                        document.getElementById('historyTableBody').innerHTML = html;
                        document.getElementById('historyContent').style.display = 'block';

                    })

                    // -----------------------------------------// ERROR// ------------------------------------------

                    .catch(function(error) {
                        document.getElementById('historyLoader').style.display = 'none';
                        document.getElementById('historyTableBody')
                            .innerHTML = `
                        <tr>
                            <td colspan="5" style="text-align:center;color:#dc2626;padding:20px;"> Failed to load leaderboard point history.</td>
                        </tr>

                    `;
                        document.getElementById('historyContent').style.display = 'block';
                    });
            });
        });

        // =========================================// CLOSE LEADERBOARD HISTORY MODAL// ==========================================

        function closeLeaderPointHistoryModal() {
            const modal = document.getElementById('leaderPointHistoryModal');
            if (modal) {
                modal.classList.remove('active');
            }
        }

        // =========================================// ESCAPE HTML// ==========================================

        function escapeHtml(value) {

            if (value === null || value === undefined) {
                return '';
            }

            return String(value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');

        }
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {

            /*--------- OPEN HISTORY MODAL --- */

            document.addEventListener('click', function(e) {

                const button = e.target.closest('.wallet-history-btn');

                if (!button) {
                    return;
                }

                e.preventDefault();

                const userId = button.dataset.userId;
                const userName = button.dataset.userName;
                const historyUrl = button.dataset.historyUrl;

                document.getElementById('historyUserName').innerText = userName || 'User';

                /*|-------------Reset tabs|-------------*/

                document.querySelectorAll('.history-tab').forEach(function(tab) {
                    tab.classList.remove('active');
                });

                const firstTab = document.querySelector('.history-tab[data-tab="point"]');

                if (firstTab) {
                    firstTab.classList.add('active');
                }

                document.querySelectorAll('.history-tab-content').forEach(function(content) {
                    content.style.display = 'none';
                });

                document.getElementById('pointHistoryTab').style.display = 'block';

                /*|---------------------Reset data|------------*/

                document.getElementById('pointHistoryBody').innerHTML = '';
                document.getElementById('voucherHistoryBody').innerHTML = '';
                document.getElementById('walletHistoryBody').innerHTML = '';

                document.getElementById('pointHistoryEmpty').style.display = 'none';
                document.getElementById('voucherHistoryEmpty').style.display = 'none';
                document.getElementById('walletHistoryEmpty').style.display = 'none';

                document.getElementById('pointHistoryCount').innerText = '0';
                document.getElementById('voucherHistoryCount').innerText = '0';
                // document.getElementById('walletHistoryCount').innerText = '0';

                document.getElementById('walletTotal').innerText = '0.00';
                document.getElementById('leaderboardMarksTotal').innerText = '0.00';

                /*|------------Show Loader|---------*/

                document.getElementById('userHistoryLoader').style.display = 'block';


                /*|-----------------------Open Modal|------------*/

                const modal = document.getElementById('userHistoryModal');
                if (!modal) {
                    console.error('userHistoryModal not found');
                    return;
                }
                modal.classList.add('active');

                /*|--------------------------Fetch History|-----*/

                fetch(historyUrl, {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })

                    .then(async response => {
                        const data = await response.json();
                        return {
                            ok: response.ok,
                            data: data
                        };

                    })

                    .then(result => {
                        document.getElementById('userHistoryLoader').style.display = 'none';
                        const response = result.data;
                        if (!result.ok || !response.success) {
                            showHistoryError();
                            return;
                        }

                        // USER NAME FROM BACKEND RESPONSE
                        if (response.user && response.user.name) {
                            document.getElementById('historyUserName').textContent =
                                response.user.name;
                        }

                        /*|--------------------Render Point History|-----------*/
                        // renderPointHistory(response.points || []);
                        /*|--------------------- Render Voucher History|-----*/
                        // renderVoucherHistory(response.vouchers || []);
                        /*|---------------Render Wallet History|---*/
                        // renderWalletHistory(response.wallet || []);

                        const points = response.points || [];
                        const vouchers = response.vouchers || [];
                        const wallet = response.wallet || [];

                        renderPointHistory(points);
                        renderVoucherHistory(vouchers);
                        renderWalletHistory(wallet);

                        document.getElementById('pointHistoryCount').innerText = points.length;
                        document.getElementById('voucherHistoryCount').innerText = vouchers.length;
                        //document.getElementById('walletHistoryCount').innerText = wallet.length;

                        /*   LEADERBOARD MARKS TOTAL */

                        const leaderboardMarksTotal = points.reduce(function(total, item) {
                            return total + parseFloat(item.reward || 0);
                        }, 0);

                        document.getElementById('leaderboardMarksTotal').innerText =
                            leaderboardMarksTotal.toFixed(2);


                        /*  WALLET TOTAL */

                        const walletTotal = wallet.reduce(function(total, item) {

                            const amount = parseFloat(item.amount || 0);

                            // Credit = plus
                            // Debit = minus
                            if (String(item.type).toLowerCase() === 'credit') {
                                return total + amount;
                            }

                            return total - amount;

                        }, 0);

                        document.getElementById('walletTotal').innerText = walletTotal.toFixed(2);

                    })

                    .catch(function(error) {
                        console.error('History error:', error);
                        document.getElementById('userHistoryLoader').style.display = 'none';
                        showHistoryError();
                    });

            });


            /* |---------------------TAB CLICK|----*/

            document.addEventListener('click', function(e) {
                const tab = e.target.closest('.history-tab');
                if (!tab) {
                    return;
                }
                const tabName = tab.dataset.tab;
                /*| Remove active*/

                document.querySelectorAll('.history-tab').forEach(function(item) {
                    item.classList.remove('active');
                });

                tab.classList.add('active');

                /*| Hide all content*/

                document.querySelectorAll('.history-tab-content').forEach(function(content) {
                    content.style.display = 'none';
                });

                /*| Show selected content*/

                if (tabName === 'point') {
                    document.getElementById('pointHistoryTab').style.display = 'block';
                }

                if (tabName === 'voucher') {
                    document.getElementById('voucherHistoryTab').style.display = 'block';
                }

                if (tabName === 'wallet') {
                    document.getElementById('walletHistoryTab').style.display = 'block';
                }

            });

        });


        /*|-------------- POINT HISTORY-*/

        function renderPointHistory(history) {
            const tbody = document.getElementById('pointHistoryBody');
            tbody.innerHTML = '';
            if (!history || history.length === 0) {
                document.getElementById('pointHistoryEmpty').style.display = 'block';
                return;
            }

            document.getElementById('pointHistoryEmpty').style.display = 'none';
            history.forEach(function(item, index) {

                const quizName = item.quiz && item.quiz.title ? item.quiz.title : '-';
                const reward = item.reward !== null && item.reward !== undefined ? item.reward : '-';
                const rewardType = item.reward_type ? item.reward_type : '-';
                const date = item.created_at ? new Date(item.created_at).toLocaleString() : '-';

                tbody.innerHTML += `
                <tr>
                    <td data-label="#"> ${index + 1}</td>
                    <td data-label="Quiz">${escapeHtml(quizName)}</td>
                    <td data-label="Reward" style="font-weight:700; color:#16A34A;">${escapeHtml(String(reward))}</td>
                    <td data-label="Reward Type"> 
                        <div style="background:#ECFDF3; font-weight: 500; display: inline-flex; border-radius:6px; color: #15803D; padding: 4px 6px;">
                            ${escapeHtml(String(rewardType))}
                        </div>
                    </td>
                    <td data-label="Date & Time" class="mono">${escapeHtml(date)}</td>
                </tr>
            `;
            });
        }

        /*-------------VOUCHER HISTORY--*/

        function renderVoucherHistory(history) {
            const tbody = document.getElementById('voucherHistoryBody');
            tbody.innerHTML = '';
            if (!history || history.length === 0) {
                document.getElementById('voucherHistoryEmpty').style.display = 'block';
                return;
            }

            document.getElementById('voucherHistoryEmpty').style.display = 'none';
            history.forEach(function(item, index) {

                const voucherHistory = item.voucher_history || {};
                const voucher = voucherHistory.voucher || {};
                const voucherName = voucher.name || '-';
                const partner = voucher.partner || '-';
                const point = voucherHistory.voucher_point !== null && voucherHistory.voucher_point !== undefined ?
                    voucherHistory.voucher_point : '-';
                const code = voucherHistory.code || '-';

                const date = voucherHistory.assign_date ?
                    new Date(voucherHistory.assign_date).toLocaleString() :
                    (
                        item.created_at ?
                        new Date(item.created_at).toLocaleString() :
                        '-'
                    );




                tbody.innerHTML += `

                <tr>
                    <td data-label="#">  ${index + 1} </td>
                    <td data-label="Voucher Name"> ${escapeHtml(voucherName)} </td>
                    <td data-label="Partner"> ${escapeHtml(partner)} </td>
                    <td data-label="point" style="font-weight:700;">   
                        <div style="background:#ECFDF3; font-weight: 500; display: inline-flex; border-radius:6px; color: #15803D; padding: 4px 6px;">
                           ${escapeHtml(String(point))} 
                        </div>
                        </td>
                    <td data-label="Code" class="mono"> ${escapeHtml(code)} </td>
                 
                    <td data-label="Date" class="mono">  ${escapeHtml(date)} </td>
                </tr>

            `;

            });
        }
        /*|------WALLET HISTORY|-----*/

        function renderWalletHistory(history) {
            const tbody = document.getElementById('walletHistoryBody');
            tbody.innerHTML = '';

            if (!history || history.length === 0) {
                document.getElementById('walletHistoryEmpty').style.display = 'block';
                return;
            }

            document.getElementById('walletHistoryEmpty').style.display = 'none';

            history.forEach(function(item, index) {

                const amount = parseFloat(item.amount || 0);

                const type = item.type ? item.type : '-';

                const date = item.created_at ? new Date(item.created_at).toLocaleString() : '-';

                const amountClass = String(type).toLowerCase() === 'credit' ? 'text-success' : 'text-danger';

                const sign = String(type).toLowerCase() === 'credit' ? '+' : '-';

                tbody.innerHTML += `
                <tr>
                    <td data-label="#">${index + 1}</td>
                    <td data-label="Amount" class="${amountClass}"style="font-weight:700;color:#16A34A;"> ${sign}${amount.toFixed(2)}</td>
                    <td data-label="Type">
                        <div style="background:#ECFDF3; font-weight: 500; display: inline-flex; border-radius:6px;
                         color: #15803D; padding: 4px 6px;">
                            ${escapeHtml(
                                type.charAt(0).toUpperCase() +
                                type.slice(1)
                            )}
                            </div>
                    </td>
                     <td data-label="Remarks"> ${escapeHtml(item.remarks || '-')}</td>

                    <td data-label="Date & Time" class="mono"> ${escapeHtml(date)}</td>
                </tr>

            `;

            });
        }

        /*------CLOSE MODAL--*/

        function closeUserHistoryModal() {
            const modal = document.getElementById('userHistoryModal');
            if (modal) {
                modal.classList.remove('active');
            }
        }

        /*ERROR*/
        function showHistoryError() {
            document.getElementById('pointHistoryBody').innerHTML = `
            <tr>
                <td colspan="5"
                    style="text-align:center;color:#dc2626;padding:20px;">
                    Failed to load point history.
                </td>
            </tr>
        `;

            document.getElementById('voucherHistoryBody').innerHTML = `
            <tr>
                <td colspan="7"
                    style="text-align:center;color:#dc2626;padding:20px;">
                    Failed to load voucher history.
                </td>
            </tr>
        `;

            document.getElementById('walletHistoryBody').innerHTML = `
            <tr>
                <td colspan="5"
                    style="text-align:center;color:#dc2626;padding:20px;">
                    Failed to load wallet history.
                </td>
            </tr>
        `;
        }

        /*/-------------------- ESCAPE HTML--------*/

        function escapeHtml(value) {
            if (value === null || value === undefined) {
                return '';
            }

            return String(value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }
    </script>

    {{-- ******UDPATE BULK LIMIT USER QUIZ****** --}}
    <script>
        // 1. Modal Open karna
        function openBulkPlayLimitModal() {
            document.getElementById('bulkAddLimitInput').value = ''; // Reset input
            document.getElementById('bulkPlayLimitModal').classList.add('active');
        }

        // 2. Modal Close karna
        function closeBulkPlayLimitModal() {
            document.getElementById('bulkPlayLimitModal').classList.remove('active');
        }

        // 3. Form Submit karna (AJAX)
        function submitBulkPlayLimit(event) {
            event.preventDefault();

            const addLimit = document.getElementById('bulkAddLimitInput').value;
            const maxLimit = document.getElementById('bulkMaxLimitInput').value; // ✅ NEW
            const submitBtn = document.getElementById('submitBulkPlayLimitBtn');

            if (!addLimit || addLimit < 1) {
                showAlert('warning', 'Invalid Input', 'Please enter a valid number greater than 0.');
                return;
            }

            // ✅ Dynamic Confirmation Message
            let confirmMsg = `⚠️ Warning: Add ${addLimit} play limits to ALL Active & ON users?`;
            if (maxLimit !== "") {
                confirmMsg = `⚠️ Warning: Add ${addLimit} play limits ONLY to Active & ON users who currently have ≤ ${maxLimit} limits?`;
            }

            if (!confirm(confirmMsg)) {
                return;
            }

            submitBtn.disabled = true;
            submitBtn.innerText = 'Updating Users...';

            fetch("{{ route('superadmin.users.bulkUpdatePlayLimit') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        add_limit: parseInt(addLimit),
                        max_current_limit: maxLimit !== "" ? parseInt(maxLimit) : null // ✅ NEW
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        closeBulkPlayLimitModal();
                        showAlert('success', 'Success', data.message || 'Play limits updated successfully.');
                        setTimeout(() => { window.location.reload(); }, 1500);
                    } else {
                        showAlert('error', 'Error', data.message || 'Failed to update limits.');
                        submitBtn.disabled = false;
                        submitBtn.innerText = 'Update Targeted Users';
                    }
                })
                .catch(err => {
                    console.error(err);
                    showAlert('error', 'Error', 'Something went wrong. Please try again.');
                    submitBtn.disabled = false;
                    submitBtn.innerText = 'Update Targeted Users';
                });
        }
    </script>
    {{-- ******UDPATE BULK LIMIT USER QUIZ****** --}}

    {{-- ****** REFERRAL REFRESH ****** --}}
    <script>
        function refreshReferralRewards() {
            const btn = document.getElementById('btnReferralRefresh');
            const originalHtml = btn.innerHTML;

            btn.disabled = true;
            btn.innerHTML = '🔄 Processing...';

            fetch("{{ url('/api/referral-process-rewards') }}", {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    btn.disabled = false;
                    btn.innerHTML = originalHtml;

                    if (data.success) {
                        showAlert('success', 'Referral Rewards', data.message ||
                            'Referral rewards processed successfully.');
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    } else {
                        showAlert('error', 'Error', data.message || 'Failed to process referral rewards.');
                    }
                })
                .catch(err => {
                    console.error(err);
                    btn.disabled = false;
                    btn.innerHTML = originalHtml;
                    showAlert('error', 'Network Error', 'Failed to connect to server. Please try again.');
                });
        }
    </script>

    <script>
        function openProfilePhoto(imageUrl, userName) {
            const modal = document.getElementById('profilePhotoModal');
            const image = document.getElementById('profilePhotoPreview');
            const name = document.getElementById('profilePhotoName');

            image.src = imageUrl;
            image.alt = userName || 'Profile Photo';
            name.textContent = userName || '';

            modal.classList.add('show');
            document.body.classList.add('photo-modal-open');
        }

        function closeProfilePhoto(event) {
            // Sirf background click par close hoga
            if (event && event.target.id !== 'profilePhotoModal') {
                return;
            }

            const modal = document.getElementById('profilePhotoModal');
            const image = document.getElementById('profilePhotoPreview');

            modal.classList.remove('show');
            document.body.classList.remove('photo-modal-open');

            image.src = '';
        }

        // ESC press karke bhi close
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeProfilePhoto();
            }
        });
   </script>

    <script>
    document.addEventListener('DOMContentLoaded', function () {

        const modal = document.getElementById('referralCodeEditModal');

        const form = document.getElementById('referralCodeEditForm');

        const userName = document.getElementById('referralEditUserName');

        const currentCode =
            document.getElementById('currentReferralCode');

        const newCode =
            document.getElementById('newReferralCode');

        const errorBox =
            document.getElementById('referralCodeError');

        const saveBtn =
            document.getElementById('saveReferralCodeBtn');

        let updateUrl = null;
        let clickedButton = null;


        // Open popup ONLY for SANDE687
        document.addEventListener('click', function (e) {

            const button =
                e.target.closest('.ref-code-edit-btn');

            if (!button) {
                return;
            }

            clickedButton = button;

            userName.textContent =
                button.dataset.userName || '';

            currentCode.value =
                button.dataset.referralCodeTo || '';

            newCode.value = '';

            updateUrl =
                button.dataset.updateUrl || '';

            errorBox.style.display = 'none';
            errorBox.textContent = '';

            modal.style.display = 'flex';

            setTimeout(function () {
                newCode.focus();
            }, 100);
        });


        // Close
        function closeReferralModal() {

            modal.style.display = 'none';

            form.reset();

            errorBox.style.display = 'none';
            errorBox.textContent = '';

            clickedButton = null;
            updateUrl = null;
        }


        document.getElementById('closeReferralCodeModal')
            .addEventListener('click', closeReferralModal);


        document.getElementById('cancelReferralCodeModal')
            .addEventListener('click', closeReferralModal);


        // Outside click
        modal.addEventListener('click', function (e) {

            if (e.target === modal) {
                closeReferralModal();
            }

        });


        // Save
        form.addEventListener('submit', async function (e) {

            e.preventDefault();

            const referralCode =
                newCode.value.trim();

            errorBox.style.display = 'none';
            errorBox.textContent = '';


            if (!referralCode) {

                errorBox.textContent =
                    'Referral code is required.';

                errorBox.style.display = 'block';

                return;
            }


            saveBtn.disabled = true;
            saveBtn.textContent = 'Saving...';


            try {

                const response = await fetch(updateUrl, {

                    method: 'POST',

                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN':
                            document.querySelector(
                                'meta[name="csrf-token"]'
                            ).getAttribute('content')
                    },

                    body: JSON.stringify({
                        referral_code_to: referralCode
                    })

                });


                const data = await response.json();


                if (!response.ok) {

                    errorBox.textContent =
                        data.message ||
                        'Unable to update referral code.';

                    errorBox.style.display = 'block';

                    return;
                }


                /*
                * UI update
                */
                if (clickedButton) {

                    clickedButton.dataset.referralCodeTo =
                        data.referral_code_to;

                    clickedButton.innerHTML =
                        'Ref: ' +
                        (data.referrer_name || 'User') +
                        ' (' +
                        (data.referrer_mobile || 'No Mobile') +
                        ') ' +
                        '<i class="ti ti-pencil" style="font-size:11px; margin-left:3px;"></i>';

                }


                closeReferralModal();


                if (typeof showToast === 'function') {

                    showToast(
                        data.message ||
                        'Referral updated successfully.',
                        'success'
                    );

                } else {

                    alert(
                        data.message ||
                        'Referral updated successfully.'
                    );

                }

            } catch (error) {

                console.error(error);

                errorBox.textContent =
                    'Something went wrong. Please try again.';

                errorBox.style.display = 'block';

            } finally {

                saveBtn.disabled = false;
                saveBtn.textContent = 'Save';

            }

        });

    });
    </script>
    <script>
        // ==========================================
        // ✅ NEW: On/Off Toggle Listener
        // ==========================================
        document.addEventListener('change', function(e) {
            if (!e.target.classList.contains('user-onoff-toggle')) {
                return;
            }

            const checkbox = e.target;
            const userId = checkbox.dataset.id;
            const originalState = checkbox.checked; // State save kar lo taaki error par wapis la sakein

            // Double-click se bachane ke liye temporarily disable karo
            checkbox.disabled = true;

            fetch("{{ route('superadmin.users.toggleOnOff') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        id: userId
                    })
                })
                .then(res => res.json())
                .then(data => {
                    checkbox.disabled = false; // Re-enable
                    if (data.success) {
                        if (typeof showAlert === 'function') {
                            showAlert('success', 'Success', data.message || 'User On/Off status updated successfully.');
                        }
                    } else {
                        // Agar fail hua toh checkbox ko wapis original state mein lao
                        checkbox.checked = originalState;
                        if (typeof showAlert === 'function') {
                            showAlert('warning', 'Warning', 'Unable to update status.');
                        }
                    }
                })
                .catch(() => {
                    checkbox.disabled = false;
                    checkbox.checked = originalState; // Network error par bhi wapis lao
                    if (typeof showAlert === 'function') {
                        showAlert('error', 'Error', 'Something went wrong.');
                    }
                });
        });
    </script>
    {{-- ****** REFERRAL REFRESH ****** --}}
@endpush
