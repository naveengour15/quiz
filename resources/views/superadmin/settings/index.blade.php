@extends('superadmin.layouts.app')
@section('title', 'Settings')
<link rel="stylesheet" href="{{ asset('assets/css/custom.css') }}">
@section('content')

    @push('styles')
        <style>
            /* ================================ SETTINGS PAGE ================================= */
            .settings-wrapper {
                width: 100%;
            }

            .settings-page-head {
                margin-bottom: 20px;
            }

            .settings-page-head h1 {
                margin: 0;
                font-size: 26px;
                font-weight: 700;
                color: var(--ink);
            }

            .settings-page-head p {
                margin: 6px 0 0;
                color: var(--ink-faint);
                font-size: 13px;
            }

            /* ================================ SETTINGS LAYOUT ================================= */
            .settings-layout {
                display: flex;
                align-items: flex-start;
                gap: 20px;
                width: 100%;
            }

            /* ================================ LEFT VERTICAL TABS ================================= */
            .settings-sidebar {
                width: 240px;
                min-width: 240px;
                background: var(--paper);
                border: 1px solid var(--line);
                border-radius: 14px;
                padding: 10px;
            }

            .settings-sidebar .tab-btn {
                width: 100%;
                display: flex;
                align-items: center;
                gap: 10px;
                border: none;
                outline: none;
                background: transparent;
                color: var(--ink);
                padding: 13px 14px;
                margin: 0 0 6px;
                border-radius: 10px;
                font-family: inherit;
                font-size: 13px;
                font-weight: 600;
                text-align: left;
                cursor: pointer;
                transition: all .25s ease;
            }

            .settings-sidebar .tab-btn:last-child {
                margin-bottom: 0;
            }

            .settings-sidebar .tab-btn i {
                width: 18px;
                text-align: center;
                font-size: 14px;
            }

            .settings-sidebar .tab-btn:hover {
                /* background: rgba(var(--brand-rgb), 0.08); */
                background: var(--indigo);
                color: var(--brand);
            }

            .settings-sidebar .tab-btn.active {
                background: var(--indigo);
                color: #fff;
            }

            .settings-sidebar .tab-btn.active:hover {
                color: #fff;
            }

            /* ================================ RIGHT CONTENT ================================= */
            .settings-content {
                flex: 1;
                min-width: 0;
            }

            .tab-content {
                display: none;
            }

            .tab-content.active {
                display: block;
            }

            /* ================================ FORM CARD ================================= */
            .settings-wrapper .form-card {
                background: var(--paper);
                border: 1px solid var(--line);
                border-radius: 14px;
                padding: 22px;
                margin-bottom: 18px;
            }

            .settings-wrapper .fh {
                margin-bottom: 20px;
                padding-bottom: 14px;
                border-bottom: 1px solid var(--line);
            }

            .settings-wrapper .fh h3 {
                margin: 0;
                color: var(--ink);
                font-size: 18px;
                font-weight: 700;
            }

            /* ================================ FORM GRID ================================= */
            .setting-grid-form {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 18px;
            }

            .field {
                min-width: 0;
            }

            .field label {
                display: block;
                margin-bottom: 7px;
                color: var(--ink);
                font-size: 13px;
                font-weight: 600;
            }

            .field input,
            .field select {
                width: 100%;
                box-sizing: border-box;
                border: 1px solid var(--line);
                border-radius: 9px;
                padding: 10px 12px;
                background: var(--paper);
                color: var(--ink);
                font-family: inherit;
                font-size: 13px;
                outline: none;
                transition: border-color .2s, box-shadow .2s;
            }

            .field input:focus,
            .field select:focus {
                border-color: var(--brand);
                box-shadow: 0 0 0 3px rgba(var(--brand-rgb), 0.10);
            }

            /* ================================ FORM FOOT ================================= */
            .form-foot {
                display: flex;
                justify-content: flex-end;
                align-items: center;
                margin-top: 22px;
                padding-top: 18px;
                border-top: 1px solid var(--line);
            }

            .settings-wrapper .btn-primary {
                border: none;
                outline: none;
                background: var(--indigo);
                color: #fff;
                padding: 10px 20px;
                border-radius: 9px;
                font-family: inherit;
                font-size: 13px;
                font-weight: 600;
                cursor: pointer;
                transition: all .2s ease;
            }

            .settings-wrapper .btn-primary:hover {
                opacity: .90;
                transform: translateY(-1px);
            }

            .settings-wrapper .btn-primary:active {
                transform: translateY(0);
            }

            /* ================================ GENERAL SETTINGS NOTE ================================= */
            .settings-note {
                color: var(--ink-faint);
                font-size: 12px;
                line-height: 1.6;
                margin-top: 8px;
            }

            .settings-note code {
                font-size: 11px;
                background: rgba(var(--brand-rgb), 0.06);
                padding: 2px 5px;
                border-radius: 4px;
            }

            /* ================================ PASSWORD INFO ================================= */
            .password-info {
                display: flex;
                align-items: flex-start;
                gap: 10px;
                padding: 12px 14px;
                margin-bottom: 20px;
                border: 1px solid rgba(var(--brand-rgb), 0.15);
                background: rgba(var(--brand-rgb), 0.05);
                border-radius: 9px;
                color: var(--ink-faint);
                font-size: 12px;
                line-height: 1.5;
            }

            .password-info i {
                color: var(--brand);
                margin-top: 2px;
            }

            /* ================================ VALIDATION ERROR ================================= */
            .text-danger {
                display: block;
                color: #dc3545;
                font-size: 11px;
                margin-top: 5px;
            }

            /* ================================ RESPONSIVE - TABLET ================================= */
            @media (max-width: 900px) {
                .settings-layout {
                    gap: 15px;
                }

                .settings-sidebar {
                    width: 210px;
                    min-width: 210px;
                }

                .setting-grid-form {
                    grid-template-columns: 1fr;
                }
            }

            /* ================================ RESPONSIVE - MOBILE ================================= */
            @media (max-width: 700px) {
                .settings-layout {
                    flex-direction: column;
                }

                .settings-sidebar {
                    width: 100%;
                    min-width: 100%;
                    box-sizing: border-box;
                    display: flex;
                    gap: 8px;
                    overflow-x: auto;
                    padding: 8px;
                }

                .settings-sidebar .tab-btn {
                    width: auto;
                    min-width: 180px;
                    margin: 0;
                    justify-content: center;
                    text-align: center;
                    white-space: nowrap;
                }

                .settings-content {
                    width: 100%;
                }

                .settings-wrapper .form-card {
                    padding: 16px;
                }

                .form-foot {
                    justify-content: stretch;
                }

                .settings-wrapper .btn-primary {
                    width: 100%;
                }
            }

            /* ================================ SMALL MOBILE ================================= */
            @media (max-width: 480px) {
                .settings-page-head h1 {
                    font-size: 22px;
                }

                .settings-page-head p {
                    font-size: 12px;
                }

                .settings-sidebar .tab-btn {
                    min-width: 160px;
                    padding: 11px 12px;
                    font-size: 12px;
                }

                .settings-wrapper .fh h3 {
                    font-size: 16px;
                }

                .field label {
                    font-size: 12px;
                }

                .field input,
                .field select {
                    font-size: 12px;
                    padding: 9px 10px;
                }
            }


            /* ================= OTP MESSAGE SETTINGS ================= */

            .otp-setting-row {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 20px;
                padding: 18px 0;
                border-bottom: 1px solid var(--line);
            }

            .otp-setting-row:last-of-type {
                border-bottom: none;
            }

            .otp-setting-info h4 {
                margin: 0 0 5px;
                color: var(--ink);
                font-size: 14px;
                font-weight: 700;
            }

            .otp-setting-info p {
                margin: 0;
                color: var(--ink-faint);
                font-size: 12px;
            }

            /* Toggle */

            .otp-switch {
                position: relative;
                display: inline-block;
                width: 48px;
                height: 26px;
                min-width: 48px;
                cursor: pointer;
            }

            .otp-switch input {
                opacity: 0;
                width: 0;
                height: 0;
            }

            .otp-slider {
                position: absolute;
                inset: 0;
                background: #ccc;
                border-radius: 30px;
                transition: .25s ease;
            }

            .otp-slider::before {
                content: "";
                position: absolute;
                width: 20px;
                height: 20px;
                left: 3px;
                top: 3px;
                background: #fff;
                border-radius: 50%;
                transition: .25s ease;
                box-shadow: 0 1px 4px rgba(0, 0, 0, .20);
            }

            .otp-switch input:checked+.otp-slider {
                background: #28a745;
            }

            .otp-switch input:checked+.otp-slider::before {
                transform: translateX(22px);
            }

            .otp-switch input:disabled+.otp-slider {
                opacity: .6;
                cursor: not-allowed;
            }

            #otpSettingMessage {
                margin-top: 15px;
            }

            #otpSettingMessage .otp-success,
            #otpSettingMessage .otp-error {
                padding: 9px 12px;
                border-radius: 8px;
                font-size: 12px;
            }

            #otpSettingMessage .otp-success {
                color: #198754;
                background: rgba(25, 135, 84, .08);
            }

            #otpSettingMessage .otp-error {
                color: #dc3545;
                background: rgba(220, 53, 69, .08);
            }


            @media (max-width: 700px) {

                .otp-setting-row {
                    align-items: flex-start;
                }

                .otp-setting-info {
                    min-width: 0;
                }

                .otp-setting-info h4 {
                    font-size: 13px;
                }

                .otp-setting-info p {
                    font-size: 11px;
                }

            }
        </style>
    @endpush
    {{-- <div class="panel active">
        <div class="page-head">
            <div>
                <h1>Settings</h1>
                <p>Platform-wide configuration. Add new keys any time — they save automatically.</p>
            </div>
        </div>

        <div class="form-card">
            <div class="fh">
                <h3>General Settings</h3>
            </div>
            <form method="POST" action="{{ route('superadmin.settings.update') }}">
                @csrf
                @method('PUT')
                <div class="setting-grid-form">
                    <div class="field"><label>Platform Name</label><input type="text" name="platform_name"
                            value="{{ $settings['platform_name'] ?? 'QuizHub AI' }}"></div>
                    <div class="field"><label>Support Email</label><input type="email" name="support_email"
                            value="{{ $settings['support_email'] ?? '' }}"></div>
                    <div class="field"><label>Support Phone</label><input type="text" name="support_phone"
                            value="{{ $settings['support_phone'] ?? '' }}"></div>
                    <div class="field"><label>Default Language</label>
                        <select name="default_language">
                            <option @selected(($settings['default_language'] ?? 'English') === 'English')>English</option>
                            <option @selected(($settings['default_language'] ?? '') === 'Hindi')>Hindi</option>
                        </select>
                    </div>
                    <div class="field"><label>Maintenance Mode</label>
                        <select name="maintenance_mode">
                            <option value="0" @selected(($settings['maintenance_mode'] ?? '0') == '0')>Off</option>
                            <option value="1" @selected(($settings['maintenance_mode'] ?? '') == '1')>On</option>
                        </select>
                    </div>
                    <div class="field"><label>Referral Bonus (coins)</label><input type="number" name="referral_bonus"
                            value="{{ $settings['referral_bonus'] ?? 0 }}"></div>
                </div>
                <div class="form-foot"><button class="btn btn-primary" type="submit">Save Settings</button></div>
            </form>
        </div>

        <p style="color:var(--ink-faint);font-size:12px;">Add a new field here anytime — <code>SettingController</code>
            saves any submitted key/value pair automatically, no migration needed.</p>
    </div> --}}

    <div class="panel active settings-wrapper">

        <div class="settings-page-head">
            <h1>Settings</h1>
            <p> Platform-wide configuration. Add new keys any time — they save automatically. </p>
        </div>

        <div class="settings-layout">
            <div class="settings-sidebar">
                <button type="button" class="tab-btn {{ session('active_tab', 'general') == 'general' ? 'active' : '' }}"
                    data-tab="general"> <i class="fas fa-cog"></i> <span>General Settings</span>
                </button>

                <button type="button" class="tab-btn {{ session('active_tab') == 'password' ? 'active' : '' }}"
                    data-tab="password">
                    <i class="fas fa-key"></i>
                    <span>Change Password</span>
                </button>

                <button type="button" class="tab-btn {{ session('active_tab') == 'otp-message' ? 'active' : '' }}"
                    data-tab="otp-message">
                    <i class="fas fa-comment-dots"></i>
                    <span>OTP Message</span>
                </button>

                <button type="button" class="tab-btn {{ session('active_tab') == 'processing-fee' ? 'active' : '' }}"
                    data-tab="processing-fee">
                    <i class="fas fa-percentage"></i>
                    <span>Processing Fee</span>
                </button>

                <button type="button" class="tab-btn {{ session('active_tab') == 'load-test' ? 'active' : '' }}"
                    data-tab="load-test">
                    <i class="fas fa-bolt"></i>
                    <span>Load Testing</span>
                </button>
            </div>

            <div class="settings-content">
                {{-- <div class="tab-content active" id="general"> --}}
                <div class="tab-content {{ session('active_tab', 'general') == 'general' ? 'active' : '' }}" id="general">
                    <div class="form-card">
                        <div class="fh">
                            <h3>General Settings</h3>
                        </div>
                        <form method="POST" action="{{ route('superadmin.settings.update') }}">
                            @csrf
                            @method('PUT')
                            <div class="setting-grid-form">
                                <div class="field"><label>Platform Name</label><input type="text" name="platform_name"
                                        value="{{ $settings['platform_name'] ?? 'QuizHub AI' }}"></div>
                                <div class="field"><label>Support Email</label><input type="email" name="support_email"
                                        value="{{ $settings['support_email'] ?? '' }}"></div>
                                <div class="field"><label>Support Phone</label><input type="text" name="support_phone"
                                        value="{{ $settings['support_phone'] ?? '' }}"></div>
                                <div class="field"><label>Default Language</label>
                                    <select name="default_language">
                                        <option @selected(($settings['default_language'] ?? 'English') === 'English')>English</option>
                                        <option @selected(($settings['default_language'] ?? '') === 'Hindi')>Hindi</option>
                                    </select>
                                </div>
                                <div class="field"><label>Maintenance Mode</label>
                                    <select name="maintenance_mode">
                                        <option value="0" @selected(($settings['maintenance_mode'] ?? '0') == '0')>Off</option>
                                        <option value="1" @selected(($settings['maintenance_mode'] ?? '') == '1')>On</option>
                                    </select>
                                </div>
                                <div class="field"><label>Referral Bonus (coins)</label><input type="number"
                                        name="referral_bonus" value="{{ $settings['referral_bonus'] ?? 0 }}"></div>
                            </div>
                            <div class="form-foot"><button class="btn btn-primary" type="submit">Save Settings</button>
                            </div>
                        </form>
                    </div>
                    <p style="color:var(--ink-faint);font-size:12px;">Add a new field here anytime —
                        <code>SettingController</code> saves any submitted key/value pair automatically, no migration
                        needed.
                    </p>
                </div>

                {{-- <div class="tab-content" id="password"> --}}
                <div class="tab-content {{ session('active_tab') == 'password' ? 'active' : '' }}" id="password">
                    <div class="form-card">
                        <div class="fh">
                            <h3>Change Super Admin Password</h3>
                        </div>

                        <div class="password-info"> <i class="fas fa-info-circle"></i> </div>
                        <form method="POST" action="{{ route('superadmin.settings.changePassword') }}">
                            @csrf
                            @method('PUT')
                            <div class="setting-grid-form">
                                <div class="field">
                                    <label> Old Password <span class="req">*</span> </label>
                                    <input type="password" name="old_password" placeholder="Enter old password"
                                        autocomplete="current-password" required>
                                    @error('old_password')
                                        <small class="text-danger">
                                            {{ $message }}
                                        </small>
                                    @enderror
                                </div>

                                <div class="field">
                                    <label>New Password <span class="req">*</span></label>
                                    <input type="password" name="new_password" placeholder="Minimum 6 characters"
                                        autocomplete="new-password" required>
                                    @error('new_password')
                                        <small class="text-danger">
                                            {{ $message }}
                                        </small>
                                    @enderror
                                </div>

                                <div class="field">
                                    <label>Confirm Password <span class="req">*</span></label>
                                    <input type="password" name="new_password_confirmation"
                                        placeholder="Re-enter new password" autocomplete="new-password" required>
                                    @error('new_password_confirmation')
                                        <small class="text-danger">
                                            {{ $message }}
                                        </small>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-foot">
                                <button type="submit" class="btn btn-primary"> Update Password</button>
                            </div>
                        </form>
                    </div>
                </div>


                <div class="tab-content {{ session('active_tab') == 'otp-message' ? 'active' : '' }}" id="otp-message">
                    <div class="form-card">
                        <div class="fh">
                            <h3>OTP Message Settings</h3>
                        </div>
                        <!-- WhatsApp OTP -->
                        <div class="otp-setting-row">
                            <div class="otp-setting-info">
                                <h4>WhatsApp OTP</h4>
                                <p>Enable or disable OTP messages through WhatsApp.</p>
                            </div>
                            <label class="otp-switch">
                                <input type="checkbox" class="otp-toggle" id="whatsappToggle" data-type="whatsapp">
                                <span class="otp-slider"></span>
                            </label>
                        </div>

                        <!-- SMS OTP -->
                        <div class="otp-setting-row">
                            <div class="otp-setting-info">
                                <h4>SMS OTP</h4>
                                <p>Enable or disable OTP messages through SMS.</p>
                            </div>

                            <label class="otp-switch">
                                <input type="checkbox" class="otp-toggle" id="smsToggle"data-type="sms">
                                <span class="otp-slider"></span>
                            </label>
                        </div>
                        <div id="otpSettingMessage"></div>
                    </div>
                </div>

                <div class="tab-content {{ session('active_tab') == 'processing-fee' ? 'active' : '' }}"
                    id="processing-fee">
                    <div class="form-card">
                        <div class="fh">
                            <h3>Processing Fee Settings (%)</h3>
                        </div>

                        <div class="password-info">
                            <i class="fas fa-info-circle"></i>
                            <span>Configure processing fee deductions for user redemptions. All fee values are in
                                <strong>Percentage (%)</strong>.</span>
                        </div>

                        <form method="POST" action="{{ route('superadmin.settings.processing-fee.update') }}">
                            @csrf
                            @method('PUT')

                            <div class="setting-grid-form">
                                <div class="field">
                                    <label>Mobile Recharge Processing Fee (%) <span class="req">*</span></label>
                                    <div style="position: relative;">
                                        <input type="number" step="0.01" min="0" max="100"
                                            name="recharge" value="{{ old('recharge', $processingFee->recharge ?? 0) }}"
                                            placeholder="e.g. 2.50" required style="padding-right: 36px;">
                                        <span
                                            style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); font-weight: 700; color: var(--ink-faint); pointer-events: none;">%</span>
                                    </div>
                                    @error('recharge')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                    <p class="settings-note">Processing fee percentage applied on Mobile Recharge
                                        redemption requests.</p>
                                </div>

                                <div class="field">
                                    <label>Electricity Bill Processing Fee (%) <span class="req">*</span></label>
                                    <div style="position: relative;">
                                        <input type="number" step="0.01" min="0" max="100"
                                            name="electricity_bill"
                                            value="{{ old('electricity_bill', $processingFee->electricity_bill ?? 0) }}"
                                            placeholder="e.g. 1.50" required style="padding-right: 36px;">
                                        <span
                                            style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); font-weight: 700; color: var(--ink-faint); pointer-events: none;">%</span>
                                    </div>
                                    @error('electricity_bill')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                    <p class="settings-note">Processing fee percentage applied on Electricity Bill payment
                                        requests.</p>
                                </div>

                                <div class="field">
                                    <label>E-Gift Voucher Processing Fee (%) <span class="req">*</span></label>
                                    <div style="position: relative;">
                                        <input type="number" step="0.01" min="0" max="100"
                                            name="voucher" value="{{ old('voucher', $processingFee->voucher ?? 0) }}"
                                            placeholder="e.g. 3.00" required style="padding-right: 36px;">
                                        <span
                                            style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); font-weight: 700; color: var(--ink-faint); pointer-events: none;">%</span>
                                    </div>
                                    @error('voucher')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                    <p class="settings-note">Processing fee percentage applied on E-Gift Voucher claim
                                        requests.</p>
                                </div>
                            </div>

                            <div class="form-foot">
                                <button type="submit" class="btn btn-primary">Save Processing Fee Settings</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="tab-content {{ session('active_tab') == 'load-test' ? 'active' : '' }}" id="load-test">
                    <div class="form-card">
                        <div class="fh">
                            <h3>Load Testing (Simulate Virtual Users)</h3>
                        </div>

                        <div class="password-info">
                            <i class="fas fa-info-circle"></i>
                            <span>Replay selected quiz lifecycle operations for a simulated user pool and measure throughput, latency, and sampled CPU/RAM. Operations run in batches inside the application; this is not parallel browser or HTTP traffic.</span>
                        </div>

                        <form id="loadTestForm" method="POST" action="{{ route('superadmin.load-test.run') }}">
                            @csrf
                            <div class="setting-grid-form">
                                <div class="field">
                                    <label>Virtual Users (VUs) <span class="req">*</span></label>
                                    <input type="number" id="vus" name="vus" min="1" max="10000"
                                        placeholder="e.g. 50" value="50" required>
                                    <p class="settings-note">Simulated user pool size (1–10,000); command operations are run in batches.</p>
                                </div>

                                <div class="field">
                                    <label>Duration (seconds) <span class="req">*</span></label>
                                    <input type="number" id="duration" name="duration" min="5" max="600"
                                        placeholder="e.g. 60" value="60" required>
                                    <p class="settings-note">Target test duration (5–600 seconds).</p>
                                </div>

                                <div class="field" style="grid-column: 1 / -1;">
                                    <label>Target Quiz</label>
                                    <select name="quiz_id" id="quiz_id" style="width: 100%; padding: 10px 14px; border-radius: 8px; border: 1px solid var(--line); background: var(--bg); color: var(--ink);">
                                        <option value="">-- Auto: Latest Active Quiz With Questions --</option>
                                        <option value="auto_normal">🎯 [AUTO] Latest Normal Multi-Question Quiz</option>
                                        <option value="auto_fff">⚡ [AUTO] Latest Fastest Finger First (FFF) Quiz</option>
                                        <optgroup label="Select Specific Quiz">
                                            @foreach($quizzes ?? [] as $qz)
                                                @php $mode = ($qz->visibility === 'fastest_finger_first') ? '⚡ FFF' : '🎯 Normal'; @endphp
                                                <option value="{{ $qz->id }}">#{{ $qz->id }} [{{ $mode }}] - {{ \Illuminate\Support\Str::limit($qz->title, 45) }} ({{ $qz->scheduled_at ? \Carbon\Carbon::parse($qz->scheduled_at)->format('d M Y, h:i A') : 'No Schedule' }})</option>
                                            @endforeach
                                        </optgroup>
                                    </select>
                                    <p class="settings-note">Select Normal or FFF quiz to test, or choose specific quiz. Engine auto-routes to appropriate architecture.</p>
                                </div>
                            </div>

                            <div style="margin-top: 18px; padding: 16px; border: 1px solid var(--line); border-radius: 10px; background: rgba(0,0,0,0.02);">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                                    <label style="font-weight: 700; font-size: 13px; color: var(--ink); margin: 0;">
                                        <i class="fas fa-tasks" style="color: var(--indigo); margin-right: 6px;"></i> Quiz Lifecycle Modules (Normal & FFF):
                                    </label>
                                    <div style="display: flex; gap: 8px;">
                                        <button type="button" onclick="document.querySelectorAll('input[name=\'stages[]\']').forEach(cb => cb.checked = true);" class="btn" style="padding: 4px 10px; font-size: 11px; background: #e0e7ff; color: #4338ca; border: none; border-radius: 6px; cursor: pointer;">Select All</button>
                                        <button type="button" onclick="document.querySelectorAll('input[name=\'stages[]\']').forEach(cb => cb.checked = false);" class="btn" style="padding: 4px 10px; font-size: 11px; background: #f3f4f6; color: #4b5563; border: none; border-radius: 6px; cursor: pointer;">Clear All</button>
                                    </div>
                                </div>
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 12px;">
                                    <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--ink); cursor: pointer; padding: 6px 10px; border-radius: 8px; border: 1px solid var(--line); background: var(--card);">
                                        <input type="checkbox" name="stages[]" value="join" checked style="accent-color: #4f46e5; width: 16px; height: 16px;">
                                        <span><strong>1. User Quiz Joining</strong> <small style="display: block; color: var(--ink-faint); font-size: 11px;">Enrollment & slot check throughput</small></span>
                                    </label>
                                    <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--ink); cursor: pointer; padding: 6px 10px; border-radius: 8px; border: 1px solid var(--line); background: var(--card);">
                                        <input type="checkbox" name="stages[]" value="waiting" checked style="accent-color: #4f46e5; width: 16px; height: 16px;">
                                        <span><strong>2. Waiting Page & Status</strong> <small style="display: block; color: var(--ink-faint); font-size: 11px;">Waiting room status polling & entry</small></span>
                                    </label>
                                    <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--ink); cursor: pointer; padding: 6px 10px; border-radius: 8px; border: 1px solid var(--line); background: var(--card);">
                                        <input type="checkbox" name="stages[]" value="start_counter" checked style="accent-color: #4f46e5; width: 16px; height: 16px;">
                                        <span><strong>3. Start Counter</strong> <small style="display: block; color: var(--ink-faint); font-size: 11px;">10-second countdown calculation</small></span>
                                    </label>
                                    <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--ink); cursor: pointer; padding: 6px 10px; border-radius: 8px; border: 1px solid var(--line); background: var(--card);">
                                        <input type="checkbox" name="stages[]" value="questions" checked style="accent-color: #4f46e5; width: 16px; height: 16px;">
                                        <span><strong>4. Questions Load</strong> <small style="display: block; color: var(--ink-faint); font-size: 11px;">Preload & questions options fetch</small></span>
                                    </label>
                                    <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--ink); cursor: pointer; padding: 6px 10px; border-radius: 8px; border: 1px solid var(--line); background: var(--card);">
                                        <input type="checkbox" name="stages[]" value="sounds" checked style="accent-color: #4f46e5; width: 16px; height: 16px;">
                                        <span><strong>5. Sound Load</strong> <small style="display: block; color: var(--ink-faint); font-size: 11px;">Audio URLs & cache responsiveness</small></span>
                                    </label>
                                    <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--ink); cursor: pointer; padding: 6px 10px; border-radius: 8px; border: 1px solid var(--line); background: var(--card);">
                                        <input type="checkbox" name="stages[]" value="answers" checked style="accent-color: #4f46e5; width: 16px; height: 16px;">
                                        <span><strong>6. Answer Submissions</strong> <small style="display: block; color: var(--ink-faint); font-size: 11px;">User answers ingestion & scoring</small></span>
                                    </label>
                                    <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--ink); cursor: pointer; padding: 6px 10px; border-radius: 8px; border: 1px solid var(--line); background: var(--card);">
                                        <input type="checkbox" name="stages[]" value="review_counter" checked style="accent-color: #4f46e5; width: 16px; height: 16px;">
                                        <span><strong>7. Review Counter</strong> <small style="display: block; color: var(--ink-faint); font-size: 11px;">10-second review phase wait timer</small></span>
                                    </label>
                                    <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--ink); cursor: pointer; padding: 6px 10px; border-radius: 8px; border: 1px solid var(--line); background: var(--card);">
                                        <input type="checkbox" name="stages[]" value="answersheet" checked style="accent-color: #4f46e5; width: 16px; height: 16px;">
                                        <span><strong>8. Answer Sheet Page</strong> <small style="display: block; color: var(--ink-faint); font-size: 11px;">User answer sheet review & score</small></span>
                                    </label>
                                    <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--ink); cursor: pointer; padding: 6px 10px; border-radius: 8px; border: 1px solid var(--line); background: var(--card); border-left: 3px solid #f59e0b;">
                                        <input type="checkbox" name="stages[]" value="update_play_data" checked style="accent-color: #f59e0b; width: 16px; height: 16px;">
                                        <span><strong>9. Update Play Data (Admin Trigger)</strong> <small style="display: block; color: var(--ink-faint); font-size: 11px;">Hit /quizzes/{id}/update-play-data calculation</small></span>
                                    </label>
                                    <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--ink); cursor: pointer; padding: 6px 10px; border-radius: 8px; border: 1px solid var(--line); background: var(--card); border-left: 3px solid #10b981;">
                                        <input type="checkbox" name="stages[]" value="leaderboard" checked style="accent-color: #10b981; width: 16px; height: 16px;">
                                        <span><strong>10. Leaderboard Page</strong> <small style="display: block; color: var(--ink-faint); font-size: 11px;">Final ranks, rewards & winner board load</small></span>
                                    </label>
                                </div>
                            </div>

                            <div class="form-foot" style="margin-top: 20px;">
                                <button type="submit" id="btnRunLoadTest" class="btn btn-primary">
                                    <i class="fas fa-play" style="margin-right: 6px;"></i> Run Load Test
                                </button>
                            </div>
                        </form>
                        <p id="loadTestValidationError" role="alert" style="display: none; margin: 12px 0 0; color: #dc2626; font-size: 13px;"></p>

                        <!-- Progress Bar & Status Section -->
                        <div id="loadTestProgressSection" style="display: none; margin-top: 25px; padding: 20px; border: 1px solid var(--line); border-radius: 12px; background: rgba(var(--brand-rgb), 0.03);">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <i class="fas fa-spinner fa-spin" style="color: var(--indigo); font-size: 18px;"></i>
                                    <span id="loadTestStatusText" style="font-weight: 600; font-size: 14px; color: var(--ink);">Executing Load Test... Please wait.</span>
                                </div>
                                <span id="loadTestTimerText" style="font-weight: 700; font-size: 14px; color: var(--indigo);">0%</span>
                            </div>

                            <!-- Animated Line Bar -->
                            <div style="width: 100%; height: 12px; background: #e5e7eb; border-radius: 10px; overflow: hidden; position: relative;">
                                <div id="loadTestProgressBar" style="width: 0%; height: 100%; background: linear-gradient(90deg, #4f46e5, #06b6d4); border-radius: 10px; transition: width 0.4s ease;"></div>
                            </div>
                            <p style="margin: 8px 0 0 0; font-size: 12px; color: var(--ink-faint);">Progress is an estimate based on target duration. Selected modules run in batches; CPU and RAM are sampled during execution. Output appears after completion.</p>
                        </div>

                        <!-- Result Section -->
                        <div id="loadTestResultSection" style="display: none; margin-top: 25px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                                <h4 style="margin: 0; font-size: 16px; font-weight: 700; color: var(--ink);">
                                    <i class="fas fa-chart-line" style="color: #10b981; margin-right: 6px;"></i> Execution Output & Summary:
                                </h4>
                                <a id="loadTestDownloadLink" href="#" download class="btn btn-primary" style="display: none; text-decoration: none; padding: 8px 14px; font-size: 12px; border-radius: 8px;">
                                    <i class="fas fa-download"></i> Download Report (JSON)
                                </a>
                            </div>

                            <div style="background: #111827; color: #f3f4f6; border-radius: 10px; padding: 18px; box-shadow: inset 0 2px 6px rgba(0,0,0,0.4); border: 1px solid #374151;">
                                <pre id="loadTestOutputPre" style="white-space: pre-wrap; word-break: break-all; font-family: 'Consolas', 'Courier New', monospace; font-size: 13px; line-height: 1.6; color: #4ade80; margin: 0; max-height: 500px; overflow-y: auto;"></pre>
                            </div>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </div>


    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tabButtons = document.querySelectorAll('.settings-sidebar .tab-btn');
            const tabContents = document.querySelectorAll('.settings-content .tab-content');
            tabButtons.forEach(function(button) {
                button.addEventListener('click', function() {
                    const targetTab = this.getAttribute('data-tab');
                    // Remove active from all buttons 
                    tabButtons.forEach(function(btn) {
                        btn.classList.remove('active');
                    });
                    // Hide all tab contents
                    tabContents.forEach(function(content) {
                        content.classList.remove('active');

                    });
                    // Activate clicked button
                    this.classList.add('active');
                    // Show selected tab 
                    const targetContent = document.getElementById(targetTab);
                    if (targetContent) {
                        targetContent.classList.add('active');
                    }
                });
            });
        });
    </script>



@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const whatsappToggle = document.getElementById('whatsappToggle');
            const smsToggle = document.getElementById('smsToggle');

            /*  Load OTP Settings */

            function loadOtpMessageSettings() {

                fetch("{{ route('superadmin.settings.otp-message') }}", {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {

                        if (data.success) {
                            if (whatsappToggle) {
                                whatsappToggle.checked =
                                    data.whatsapp === 'active';
                            }

                            if (smsToggle) {
                                smsToggle.checked =
                                    data.sms === 'active';
                            }

                        } else {
                            showAlert(
                                'warning',
                                'Warning',
                                'OTP message settings could not be loaded.'
                            );
                        }
                    })
                    .catch(() => {
                        showAlert(
                            'error',
                            'Failed',
                            'Server error. OTP settings could not be loaded.'
                        );
                    });
            }

            /* Update OTP Setting */

            function updateOtpSetting(toggle) {
                let previousState = !toggle.checked;
                let type = toggle.dataset.type;
                let status = toggle.checked ?
                    'active' :
                    'inactive';

                // Prevent multiple clicks during request
                toggle.disabled = true;
                fetch("{{ route('superadmin.settings.otp-message.update') }}", {
                        method: 'POST',
                        headers: {
                            "Content-Type": "application/json",
                            "X-CSRF-TOKEN": "{{ csrf_token() }}",
                            "Accept": "application/json",
                            "X-Requested-With": "XMLHttpRequest"
                        },

                        body: JSON.stringify({
                            type: type,
                            status: status
                        })

                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            if (whatsappToggle) {
                                whatsappToggle.checked = data.whatsapp === 'active';
                            }

                            if (smsToggle) {
                                smsToggle.checked = data.sms === 'active';
                            }

                            showAlert(
                                'success',
                                'Success',
                                data.message || (
                                    type === 'whatsapp' ?
                                    'WhatsApp OTP setting updated successfully.' :
                                    'SMS OTP setting updated successfully.'
                                )
                            );

                        } else {

                            toggle.checked = previousState;
                            showAlert(
                                'warning',
                                'Warning',
                                data.message || 'OTP setting could not be updated.'
                            );
                        }
                    })
                    .catch(() => {
                        // Server error hone par toggle previous state me
                        toggle.checked = previousState;
                        showAlert(
                            'error',
                            'Failed',
                            'Server error. Please try again.'
                        );

                    })
                    .finally(function() {
                        toggle.disabled = false;
                    });
            }

            /*  WhatsApp Toggle */

            if (whatsappToggle) {
                whatsappToggle.addEventListener('change', function() {
                    updateOtpSetting(this);
                });
            }

            /* SMS Toggle */

            if (smsToggle) {
                smsToggle.addEventListener('change', function() {
                    updateOtpSetting(this);
                });
            }

            /* Initial Load */
            loadOtpMessageSettings();

            /* ================= LOAD TESTING AJAX EXECUTION ================= */
            const loadTestForm = document.getElementById('loadTestForm');
            const btnRunLoadTest = document.getElementById('btnRunLoadTest');
            const progressSection = document.getElementById('loadTestProgressSection');
            const progressBar = document.getElementById('loadTestProgressBar');
            const timerText = document.getElementById('loadTestTimerText');
            const statusText = document.getElementById('loadTestStatusText');
            const resultSection = document.getElementById('loadTestResultSection');
            const outputPre = document.getElementById('loadTestOutputPre');
            const downloadLink = document.getElementById('loadTestDownloadLink');
            const validationError = document.getElementById('loadTestValidationError');

            if (loadTestForm) {
                let progressInterval = null;

                loadTestForm.addEventListener('submit', function(e) {
                    e.preventDefault();

                    validationError.style.display = 'none';
                    validationError.textContent = '';
                    const selectedStages = loadTestForm.querySelectorAll('input[name="stages[]"]:checked');
                    if (selectedStages.length === 0) {
                        validationError.textContent = 'Select at least one lifecycle module.';
                        validationError.style.display = 'block';
                        return;
                    }

                    const vusVal = document.getElementById('vus').value;
                    const durationVal = parseInt(document.getElementById('duration').value, 10) || 60;

                    // UI Reset & Setup
                    btnRunLoadTest.disabled = true;
                    btnRunLoadTest.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-right: 6px;"></i> Testing in progress...';
                    
                    progressSection.style.display = 'block';
                    resultSection.style.display = 'none';
                    downloadLink.style.display = 'none';
                    progressBar.style.width = '0%';
                    progressBar.style.background = 'linear-gradient(90deg, #4f46e5, #06b6d4)';
                    timerText.textContent = '0%';
                    statusText.textContent = `Running test (${vusVal} VUs, ${durationVal}s)... Please wait.`;

                    let elapsedSeconds = 0;
                    const totalDuration = durationVal;

                    if (progressInterval) clearInterval(progressInterval);

                    progressInterval = setInterval(function() {
                        elapsedSeconds += 0.5;
                        let percent = Math.min(95, Math.round((elapsedSeconds / totalDuration) * 100));
                        progressBar.style.width = percent + '%';
                        timerText.textContent = percent + '% (' + Math.round(elapsedSeconds) + 's / ' + totalDuration + 's)';
                    }, 500);

                    const formData = new FormData(loadTestForm);

                    fetch(loadTestForm.action, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        },
                        body: formData
                    })
                    .then(async response => {
                        const data = await response.json();
                        if (!response.ok || !data.success) {
                            const validationMessages = data.errors ? Object.values(data.errors).flat() : [];
                            throw new Error(validationMessages.join(' ') || data.message || data.output || 'Server returned error status ' + response.status);
                        }
                        return data;
                    })
                    .then(data => {
                        clearInterval(progressInterval);
                        progressBar.style.width = '100%';
                        timerText.textContent = '100% Completed';
                        statusText.textContent = 'Load Test Completed Successfully!';

                        setTimeout(function() {
                            progressSection.style.display = 'none';
                            resultSection.style.display = 'block';
                            outputPre.textContent = data.output || 'No output recorded.';

                            if (data.downloadUrl) {
                                downloadLink.href = data.downloadUrl;
                                downloadLink.style.display = 'inline-flex';
                            } else {
                                downloadLink.style.display = 'none';
                            }
                        }, 500);
                    })
                    .catch(err => {
                        clearInterval(progressInterval);
                        progressBar.style.width = '100%';
                        progressBar.style.background = '#ef4444';
                        timerText.textContent = 'Failed';
                        statusText.textContent = 'Error executing load test: ' + err.message;
                        outputPre.textContent = err.message;
                        resultSection.style.display = 'block';
                    })
                    .finally(() => {
                        btnRunLoadTest.disabled = false;
                        btnRunLoadTest.innerHTML = '<i class="fas fa-play" style="margin-right: 6px;"></i> Run Load Test';
                    });
                });
            }

        });
    </script>
@endpush
