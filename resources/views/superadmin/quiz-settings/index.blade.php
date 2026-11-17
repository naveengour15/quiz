@extends('superadmin.layouts.app')

@section('title', 'Quiz Settings')
<link rel="stylesheet" href="{{ asset('assets/css/custom.css') }}">

@push('styles')
<style>
    .form-grid-box{ display: grid; grid-template-columns: repeat(4,1fr); gap: 10px; }
    .sound-settings-grid { display: grid; grid-template-columns: repeat(2,1fr); gap: 14px; }
    .sound-card { background: var(--paper); border: 1px solid var(--line); border-radius: 12px; padding: 16px 18px; display: flex; align-items: center; gap: 16px; transition: all 0.2s; }
    .sound-card:hover { border-color: var(--indigo); box-shadow: 0 4px 12px rgba(79, 70, 229, 0.08); }
    .sound-icon-box { width: 44px; height: 44px; border-radius: 10px; background: linear-gradient(135deg, #ede9fe, #ddd6fe); display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0; }
    .sound-info { flex: 1; min-width: 0; }
    .sound-info .lbl { font-size: 13px; font-weight: 700; color: var(--ink); margin-bottom: 4px; }
    .sound-info .desc { font-size: 11px; color: var(--ink-soft); margin-bottom: 8px; }
    .sound-player { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
    .sound-player audio { height: 32px; max-width: 260px; border-radius: 16px; }
    .sound-empty-state { font-size: 11px; color: var(--ink-faint); font-style: italic; padding: 6px 12px; background: #f8fafc; border-radius: 6px; border: 1px dashed #cbd5e1; }
    .sound-actions { display: flex; align-items: center; gap: 8px; flex-shrink: 0; }
    .sound-upload-input { display: none; }
    .sound-upload-btn { background: var(--paper); border: 1px solid var(--line); color: var(--ink); padding: 7px 14px; border-radius: 8px; font-size: 12px; font-weight: 600; cursor: pointer; transition: all 0.2s; display: inline-flex; align-items: center; gap: 6px; }
    .sound-upload-btn:hover { background: #f3f4f6; border-color: var(--indigo); color: var(--indigo); }
    .sound-update-btn { background: var(--indigo); color: white; border: none; padding: 7px 14px; border-radius: 8px; font-size: 12px; font-weight: 600; cursor: pointer; transition: all 0.2s; display: none; align-items: center; gap: 6px; }
    .sound-update-btn:hover { background: #4338ca; }
    .sound-update-btn.show { display: inline-flex; }
    .sound-update-btn:disabled { opacity: 0.6; cursor: not-allowed; }
    .sound-update-btn .mini-spinner { width: 12px; height: 12px; border: 2px solid rgba(255, 255, 255, 0.3); border-top-color: white; border-radius: 50%; animation: spinMini 0.7s linear infinite; display: none; }
    .sound-update-btn.loading .mini-spinner { display: inline-block; }
    .sound-update-btn.loading .btn-text { display: none; }
    @keyframes spinMini { to { transform: rotate(360deg); } }
    .sound-file-name { font-size: 11px; color: var(--indigo); font-weight: 600; padding: 3px 8px; background: #eef2ff; border-radius: 4px; max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; display: none; }
    .sound-file-name.show { display: inline-block; }
    .sound-toast { position: fixed; bottom: 24px; right: 24px; background: #10b981; color: white; padding: 12px 20px; border-radius: 10px; font-size: 13px; font-weight: 600; box-shadow: 0 10px 30px rgba(16, 185, 129, 0.3); z-index: 9999; display: flex; align-items: center; gap: 8px; opacity: 0; transform: translateY(20px); transition: all 0.3s; }
    .sound-toast.show { opacity: 1; transform: translateY(0); }
    .sound-toast.error { background: #ef4444; box-shadow: 0 10px 30px rgba(239, 68, 68, 0.3); }
    @media (max-width: 991px) { .form-grid-box { grid-template-columns: repeat(3, 1fr); } }
    @media (max-width: 768px) { .sound-card { flex-direction: column; align-items: flex-start; } .sound-actions { width: 100%; justify-content: flex-start; } .sound-player audio { max-width: 100%; } }
    @media (max-width: 400px) { .sound-settings-grid { grid-template-columns: repeat(1, 1fr); } }

        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 44px;
            height: 24px;
            cursor: pointer;
        }
        
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .toggle-slider {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #cbd5e1;
            border-radius: 24px;
            transition: 0.4s ease;
        }
        
        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            border-radius: 50%;
            transition: 0.4s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        
        .day-toggle-checkbox:checked + .toggle-slider {
            background-color: #10b981 !important;
        }
        
        .day-toggle-checkbox:checked + .toggle-slider:before {
            transform: translateX(20px);
        }

        /* ✅ Responsive Design */
        @media (max-width: 768px) {
            .days-grid {
                grid-template-columns: repeat(3, 1fr) !important;
            }
        }
        
        @media (max-width: 480px) {
            .days-grid {
                grid-template-columns: repeat(2, 1fr) !important;
            }
            .day-card {
                padding: 12px 10px !important;
            }
            .day-card span {
                font-size: 12px !important;
            }
        }
            
</style>
@endpush

@section('content')
<div class="panel active">
       <div class="page-head" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
        <!-- Left Side: Title -->
        <div>
            <h1>⚙️ Quiz Settings</h1>
            <p style="color:var(--ink-soft); font-size:13px; margin-top:4px;">Configure quiz limits, sound effects, and redeem settings</p>
        </div>
        
        <!-- Right Side: Manage Off Days Button -->
        <a href="{{ route('superadmin.redeem-off-days.index') }}" style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; background: #6366f1; color: white; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 14px; transition: all 0.2s; box-shadow: 0 2px 4px rgba(99, 102, 241, 0.2);" onmouseover="this.style.background='#4f46e5'; this.style.transform='translateY(-1px)'" onmouseout="this.style.background='#6366f1'; this.style.transform='translateY(0)'">
            <span>📅</span> Manage Redeem Off Days
        </a>
    </div>

    {{-- ========== SECTION 1: QUIZ LIMITS ========== --}}
    <div class="form-card">
        <div class="fh"><h3>📊 Quiz Limits</h3></div>
        <form method="POST" action="{{ route('superadmin.quiz-settings.update') }}">
            @csrf
            @method('PUT')
            <div class="form-grid-box form-grid-02">
                <div class="field">
                    <label>Superadmin Question Limit <span class="req">*</span></label>
                    <input type="number" name="superadmin_que_limit" value="{{ old('superadmin_que_limit', $setting->superadmin_que_limit ?? '') }}" required>
                </div>
                <div class="field">
                    <label>Superadmin Quiz Limit <span class="req">*</span></label>
                    <input type="number" name="superadmin_quiz_limit" value="{{ old('superadmin_quiz_limit', $setting->superadmin_quiz_limit ?? '') }}" required>
                </div>
                <div class="field">
                    <label>Superadmin Referral Limit <span class="req">*</span></label>
                    <input type="number" name="superadmin_referral_limit" value="{{ old('superadmin_referral_limit', $setting->superadmin_referral_limit ?? '') }}" required>
                </div>
                <div class="field">
                    <label>User Question Limit</label>
                    <input type="number" name="user_que_limit" value="{{ old('user_que_limit', $setting->user_que_limit ?? '') }}">
                </div>
                <div class="field">
                    <label>User Quiz Limit</label>
                    <input type="number" name="user_quiz_limit" value="{{ old('user_quiz_limit', $setting->user_quiz_limit ?? '') }}">
                </div>
                <div class="field">
                    <label>User Referral Limit</label>
                    <input type="number" name="user_referral_limit" value="{{ old('user_referral_limit', $setting->user_referral_limit ?? '') }}">
                </div>
                <div class="field">
                    <label>Retailer Question Limit</label>
                    <input type="number" name="retailer_que_limit" value="{{ old('retailer_que_limit', $setting->retailer_que_limit ?? '') }}">
                </div>
                <div class="field">
                    <label>Retailer Quiz Limit</label>
                    <input type="number" name="retailer_quiz_limit" value="{{ old('retailer_quiz_limit', $setting->retailer_quiz_limit ?? '') }}">
                </div>
                <div class="field">
                    <label>Retailer Referral Limit</label>
                    <input type="number" name="retailer_referral_limit" value="{{ old('retailer_referral_limit', $setting->retailer_referral_limit ?? '') }}">
                </div>
                <div class="field">
                    <label>Profile Update Point</label>
                    <input type="number" name="profile_update_point" value="{{ old('profile_update_point', $setting->profile_update_point ?? 20) }}">
                </div>

               <div class="field">
                    <label>Redeem Request Start Time</label>
                    <input type="time" name="redeem_request_start_time"
                        value="{{ old('redeem_request_start_time', \Carbon\Carbon::parse($setting->redeem_request_start_time ?? '11:00:00')->format('H:i')) }}">
                </div>

                <div class="field">
                    <label>Redeem Request End Time</label>
                    <input type="time" name="redeem_request_end_time"
                        value="{{ old('redeem_request_end_time', \Carbon\Carbon::parse($setting->redeem_request_end_time ?? '21:00:00')->format('H:i')) }}">
                </div>

                
                
            </div>
             <!-- ✅ NEW: Base URL Field -->
              <div class="field">
                <label>Base URL</label>
                <input type="url" name="base_url"
                    value="{{ old('base_url', $setting->base_url ?? '') }}" 
                    placeholder="https://yourdomain.com">
            </div>

           {{-- ========== SECTION: REDEEM REQUEST DAYS ========== --}}
            {{-- <div class="form-card" style="margin-top: 24px;">
                <div class="fh">
                    <h3>📅 Redeem Request Allowed Days</h3>
                    <p style="font-size: 12px; color: var(--ink-soft); margin-top: 4px;">
                        Jin dino par switch <b style="color: #10b981;">ON (Green)</b> hoga, sirf unhi dino par users redeem request kar payenge.
                    </p>
                </div>

                @php
                    // ✅ Sirf database se value lo, koi hardcoded default array nahi
                    $savedDays = $setting->redeem_request_days ?? [];
                @endphp

                @if(!empty($savedDays))
                    <div class="days-grid" style="
                        display: grid; 
                        grid-template-columns: repeat(4, 1fr); 
                        gap: 12px; 
                        margin-top: 16px;
                    ">
                        @foreach($savedDays as $key => $dayData)
                            @php
                                // Database ki exact value use karo
                                $dayName = $dayData['day'] ?? 'Unknown';
                                $currentStatus = $dayData['status'] ?? 'on';
                            @endphp
                            
                            <div class="day-card" style="
                                display: flex; 
                                align-items: center; 
                                justify-content: space-between; 
                                padding: 14px 12px; 
                                background: var(--paper, #ffffff); 
                                border: 1px solid var(--line, #e2e8f0); 
                                border-radius: 10px; 
                                transition: all 0.2s ease;
                                box-shadow: 0 1px 3px rgba(0,0,0,0.04);
                            " onmouseover="this.style.borderColor='var(--indigo, #4f46e5)'; this.style.boxShadow='0 4px 12px rgba(79, 70, 229, 0.08)'" onmouseout="this.style.borderColor='var(--line, #e2e8f0)'; this.style.boxShadow='0 1px 3px rgba(0,0,0,0.04)'">
                                
                                <!-- Day Name (Database se aaya hua) -->
                                <span style="font-size: 13px; font-weight: 600; color: var(--ink, #334155);">{{ $dayName }}</span>
                                
                                <!-- Toggle Switch -->
                                <label class="toggle-switch">
                                    <!-- ✅ Hidden input taaki 'day' ka naam wapis same submit ho -->
                                    <input type="hidden" name="redeem_request_days[{{ $key }}][day]" value="{{ $dayName }}">
                                    
                                    <!-- ✅ Hidden input taaki agar unchecked ho toh 'off' submit ho -->
                                    <input type="hidden" name="redeem_request_days[{{ $key }}][status]" value="off">
                                    
                                    <!-- Actual Checkbox -->
                                    <input type="checkbox" 
                                        name="redeem_request_days[{{ $key }}][status]" 
                                        value="on" 
                                        {{ $currentStatus === 'on' ? 'checked' : '' }}
                                        class="day-toggle-checkbox">
                                    
                                    <!-- Slider -->
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p style="padding: 16px; color: var(--ink-soft); text-align: center; background: var(--paper); border-radius: 8px; border: 1px dashed var(--line);">
                        ⚠️ Database mein abhi koi redeem days configure nahi hain.
                    </p>
                @endif
            </div> --}}
            {{-- ========================================== --}}
           
            {{-- ========== SECTION: QUIZ START REMINDER NOTIFICATIONS ========== --}}
            <div style="margin-top: 24px; padding-top: 20px; border-top: 1px solid var(--line, #e2e8f0);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 10px;">
                    <div>
                        <h4 style="margin: 0; font-size: 15px; font-weight: 700; color: var(--ink, #1e293b);">
                            🔔 Quiz Start Reminder Notifications (Notification Stages)
                        </h4>
                        <p style="font-size: 12px; color: var(--ink-soft, #64748b); margin: 4px 0 0;">
                            Scheduled Quiz shuru hone se pehle users ko push notification bhejne ke stages (Quiz Title aur Join Quiz button ke sath). Yahan se aap stages add ya remove kar sakte hain.
                        </p>
                    </div>
                    <button type="button" id="add-reminder-btn" style="display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; background: #6366f1; color: white; border: none; border-radius: 8px; font-size: 12px; font-weight: 600; cursor: pointer; transition: all 0.2s; box-shadow: 0 2px 4px rgba(99, 102, 241, 0.2);">
                        <span>➕</span> Add Notification Stage
                    </button>
                </div>

                @php
                    $reminderIntervals = $setting ? $setting->getReminderIntervals() : [
                        ['value' => 15, 'unit' => 'minute'],
                        ['value' => 10, 'unit' => 'minute'],
                        ['value' => 5,  'unit' => 'minute'],
                        ['value' => 3,  'unit' => 'minute'],
                        ['value' => 1,  'unit' => 'minute'],
                        ['value' => 30, 'unit' => 'second'],
                    ];
                @endphp

                <div id="reminder-intervals-container" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 12px; margin-top: 14px;">
                    @foreach($reminderIntervals as $idx => $interval)
                        <div class="reminder-stage-card" style="display: flex; align-items: center; gap: 10px; background: var(--paper, #fff); border: 1px solid var(--line, #e2e8f0); border-radius: 10px; padding: 10px 14px; box-shadow: 0 1px 3px rgba(0,0,0,0.03);">
                            <span style="font-size: 16px;">⏱️</span>
                            <input type="number" name="reminder_intervals[{{ $idx }}][value]" value="{{ $interval['value'] ?? '' }}" min="1" max="3600" required style="width: 75px; padding: 6px 10px; border: 1px solid var(--line, #cbd5e1); border-radius: 6px; font-size: 13px; font-weight: 600; text-align: center;">
                            <select name="reminder_intervals[{{ $idx }}][unit]" style="flex: 1; padding: 6px 10px; border: 1px solid var(--line, #cbd5e1); border-radius: 6px; font-size: 12px; font-weight: 600; background: white; cursor: pointer;">
                                <option value="minute" {{ ($interval['unit'] ?? '') === 'minute' ? 'selected' : '' }}>Minutes Pehle</option>
                                <option value="second" {{ ($interval['unit'] ?? '') === 'second' ? 'selected' : '' }}>Seconds Pehle</option>
                            </select>
                            <button type="button" class="remove-stage-btn" style="background: #fee2e2; color: #ef4444; border: 1px solid #fca5a5; width: 32px; height: 32px; border-radius: 8px; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 14px; transition: all 0.2s;" title="Remove this stage">
                                🗑️
                            </button>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="form-foot">
                <button class="btn btn-primary" type="submit">💾 Save Settings</button>
            </div>
        </form>
    </div>

    {{-- ========== SECTION 2: SOUND SETTINGS ========== --}}
    <div class="form-card" style="margin-top: 20px;">
        <div class="fh" style="display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap;">
            <h3>🔊 Sound Settings</h3>
            <span style="font-size:11px; color:var(--ink-soft);">Supported: MP3, WAV, OGG, M4A (Max 5MB) | Stored on S3</span>
        </div>

        <div class="sound-settings-grid">
            @php
                $soundFields = [
                    'background_sound'          => ['icon' => '🎶', 'label' => 'Background Sound',          'desc' => 'Ambient background sound during quiz'],
                    'one_min_before'            => ['icon' => '⏳', 'label' => 'One Minute Before',          'desc' => 'Sound played one minute before the quiz Start'],
                    'start_quiz_sound'          => ['icon' => '🚀', 'label' => 'Start Quiz',                'desc' => 'Fanfare sound when quiz begins'],
                    'question_appear_sound'     => ['icon' => '❓', 'label' => 'Question Appear',           'desc' => 'Plays when a new question appears'],
                    'say_ans_appear_sound'       => ['icon' => '💡', 'label' => 'Say Answer Appear',         'desc' => 'Sound when say answer appear'],
                    'answer_appear_sound'       => ['icon' => '💡', 'label' => 'Answer Appear',             'desc' => 'Sound when answer options appear'],
                    // ✅ NEW: Answer Select Sound
                    'ans_select_sound'          => ['icon' => '👆', 'label' => 'Answer Select', 'desc' => 'Sound when a user selects an answer'],
                    
                    'quiz_end_sound'            => ['icon' => '🏁', 'label' => 'Quiz End',                  'desc' => 'Plays when quiz finishes'],
                    'answer_sheet_appear_sound' => ['icon' => '📋', 'label' => 'Answer Sheet Appear',       'desc' => 'Sound when answer sheet is displayed'],
                    'leaderboard_appear_sound'  => ['icon' => '🏆', 'label' => 'Leaderboard Appear',        'desc' => 'Triumph sound when leaderboard shows'],
                    'counter_sound'             => ['icon' => '⏰', 'label' => 'Counter / Tick',            'desc' => 'Tick sound for countdown timer'],
                ];
                
                // S3 disk use karein URL generate karne ke liye
                $disk = 's3'; 
            @endphp

            @foreach($soundFields as $field => $meta)
                @php
                    $currentPath = $setting->{$field} ?? null;
                    $currentUrl  = $currentPath ? Storage::disk($disk)->url($currentPath) : null;
                @endphp
                
                <div class="sound-card" data-field="{{ $field }}">
                    <div class="sound-icon-box">{{ $meta['icon'] }}</div>
                    <div class="sound-info">
                        <div class="lbl">{{ $meta['label'] }}</div>
                        <div class="desc">{{ $meta['desc'] }}</div>
                        <div class="sound-player">
                            @if($currentUrl)
                                <audio controls id="audio-{{ $field }}" src="{{ $currentUrl }}"></audio>
                            @else
                                <div class="sound-empty-state" id="empty-{{ $field }}">🔇 No sound uploaded yet</div>
                            @endif
                        </div>
                    </div>
                    <div class="sound-actions">
                        <span class="sound-file-name" id="fileName-{{ $field }}"></span>
                        <input type="file" class="sound-upload-input" id="input-{{ $field }}" accept=".mp3,.wav,.ogg,.m4a" data-field="{{ $field }}">
                        <button type="button" class="sound-upload-btn" onclick="document.getElementById('input-{{ $field }}').click()">
                            {{ $currentUrl ? '🔄 Change' : '📤 Upload' }}
                        </button>
                        <button type="button" class="sound-update-btn" id="updateBtn-{{ $field }}" data-field="{{ $field }}">
                            <span class="mini-spinner"></span>
                            <span class="btn-text">Update</span>
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

<div class="sound-toast" id="soundToast"></div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    function showToast(message, type = 'success') {
        const toast = document.getElementById('soundToast');
        toast.textContent = (type === 'success' ? '✅ ' : '❌ ') + message;
        toast.className = 'sound-toast ' + (type === 'error' ? 'error' : '') + ' show';
        setTimeout(() => toast.classList.remove('show'), 3500);
    }

    document.querySelectorAll('.sound-upload-input').forEach(input => {
        input.addEventListener('change', function() {
            const field = this.dataset.field;
            const file = this.files[0];
            const updateBtn = document.getElementById('updateBtn-' + field);
            const fileName = document.getElementById('fileName-' + field);

            if (!file) {
                updateBtn.classList.remove('show');
                fileName.classList.remove('show');
                return;
            }

            const validTypes = ['audio/mpeg', 'audio/wav', 'audio/ogg', 'audio/mp4', 'audio/x-m4a', 'audio/webm'];
            const fileNameLower = file.name.toLowerCase();
            const validExtensions = ['.mp3', '.wav', '.ogg', '.m4a'];
            const hasValidExtension = validExtensions.some(ext => fileNameLower.endsWith(ext));

            if (!validTypes.includes(file.type) && !hasValidExtension) {
                showToast('Please upload a valid audio file (MP3, WAV, OGG, M4A)', 'error');
                this.value = '';
                return;
            }

            if (file.size > 5 * 1024 * 1024) {
                showToast('File size must be less than 5MB', 'error');
                this.value = '';
                return;
            }

            fileName.textContent = '📎 ' + file.name;
            fileName.classList.add('show');
            updateBtn.classList.add('show');
        });
    });

    document.querySelectorAll('.sound-update-btn').forEach(btn => {
        btn.addEventListener('click', async function() {
            const field = this.dataset.field;
            const input = document.getElementById('input-' + field);
            const file = input.files[0];

            if (!file) {
                showToast('Please select a file first', 'error');
                return;
            }

            this.classList.add('loading');
            this.disabled = true;

            const formData = new FormData();
            formData.append('field', field);
            formData.append('audio_file', file);
            formData.append('_token', '{{ csrf_token() }}');

            try {
                const response = await fetch('{{ route("superadmin.quiz-settings.updateSound") }}', {
                    method: 'POST',
                    body: formData,
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                });

                const data = await response.json();

                if (data.success) {
                    showToast(data.message, 'success');

                    let audioPlayer = document.getElementById('audio-' + field);
                    const emptyState = document.getElementById('empty-' + field);
                    const playerContainer = document.querySelector(`.sound-card[data-field="${field}"] .sound-player`);
                    
                    const cacheBuster = '?t=' + Date.now();

                    if (audioPlayer) {
                        audioPlayer.src = data.url + cacheBuster;
                        audioPlayer.onerror = function(e) {
                            console.error('Audio Load Failed:', e);
                            console.log('Failing S3 URL:', audioPlayer.src);
                            showToast('Audio load failed! Check S3 bucket permissions (must be public).', 'error');
                        };
                        audioPlayer.load();
                    } else {
                        if (emptyState) emptyState.remove();
                        audioPlayer = document.createElement('audio');
                        audioPlayer.controls = true;
                        audioPlayer.id = 'audio-' + field;
                        audioPlayer.src = data.url + cacheBuster;
                        
                        audioPlayer.onerror = function(e) {
                            console.error('Audio Load Failed:', e);
                            console.log('Failing S3 URL:', audioPlayer.src);
                            showToast('Audio load failed! Check S3 bucket permissions (must be public).', 'error');
                        };
                        
                        playerContainer.appendChild(audioPlayer);
                    }

                    const uploadBtn = document.querySelector(`.sound-card[data-field="${field}"] .sound-upload-btn`);
                    if (uploadBtn) uploadBtn.innerHTML = '🔄 Change';

                    input.value = '';
                    document.getElementById('fileName-' + field).classList.remove('show');
                    this.classList.remove('show', 'loading');
                    this.disabled = false;

                } else {
                    showToast(data.message || 'Update failed', 'error');
                    this.classList.remove('loading');
                    this.disabled = false;
                }
            } catch (error) {
                console.error('Upload error:', error);
                showToast('Network error. Please try again.', 'error');
                this.classList.remove('loading');
                this.disabled = false;
            }
        });
    });

    // Dynamic Reminder Intervals Add/Remove
    const reminderContainer = document.getElementById('reminder-intervals-container');
    const addReminderBtn = document.getElementById('add-reminder-btn');

    if (addReminderBtn && reminderContainer) {
        addReminderBtn.addEventListener('click', function() {
            const nextIdx = 'new_' + Date.now();
            const card = document.createElement('div');
            card.className = 'reminder-stage-card';
            card.style.cssText = 'display: flex; align-items: center; gap: 10px; background: var(--paper, #fff); border: 1px solid var(--line, #e2e8f0); border-radius: 10px; padding: 10px 14px; box-shadow: 0 1px 3px rgba(0,0,0,0.03);';
            card.innerHTML = `
                <span style="font-size: 16px;">⏱️</span>
                <input type="number" name="reminder_intervals[${nextIdx}][value]" value="5" min="1" max="3600" required style="width: 75px; padding: 6px 10px; border: 1px solid var(--line, #cbd5e1); border-radius: 6px; font-size: 13px; font-weight: 600; text-align: center;">
                <select name="reminder_intervals[${nextIdx}][unit]" style="flex: 1; padding: 6px 10px; border: 1px solid var(--line, #cbd5e1); border-radius: 6px; font-size: 12px; font-weight: 600; background: white; cursor: pointer;">
                    <option value="minute" selected>Minutes Pehle</option>
                    <option value="second">Seconds Pehle</option>
                </select>
                <button type="button" class="remove-stage-btn" style="background: #fee2e2; color: #ef4444; border: 1px solid #fca5a5; width: 32px; height: 32px; border-radius: 8px; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 14px; transition: all 0.2s;" title="Remove this stage">
                    🗑️
                </button>
            `;
            reminderContainer.appendChild(card);
        });

        reminderContainer.addEventListener('click', function(e) {
            const removeBtn = e.target.closest('.remove-stage-btn');
            if (removeBtn) {
                const card = removeBtn.closest('.reminder-stage-card');
                if (card) {
                    card.remove();
                }
            }
        });
    }
});
</script>
@endpush