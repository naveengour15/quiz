@extends('superadmin.layouts.app')

@section('title', 'Quiz Settings')
<link rel="stylesheet" href="{{ asset('assets/css/custom.css') }}">

@push('styles')
<style>

    .form-grid-box{
        display: grid;
        grid-template-columns: repeat(4,1fr);
        gap: 10px;
    }
    .sound-settings-grid {
        display: grid;
        grid-template-columns: repeat(2,1fr);
        gap: 14px;
    }

    .sound-card {
        background: var(--paper);
        border: 1px solid var(--line);
        border-radius: 12px;
        padding: 16px 18px;
        display: flex;
        align-items: center;
        gap: 16px;
        transition: all 0.2s;
    }

    .sound-card:hover {
        border-color: var(--indigo);
        box-shadow: 0 4px 12px rgba(79, 70, 229, 0.08);
    }

    .sound-icon-box {
        width: 44px;
        height: 44px;
        border-radius: 10px;
        background: linear-gradient(135deg, #ede9fe, #ddd6fe);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        flex-shrink: 0;
    }

    .sound-info {
        flex: 1;
        min-width: 0;
    }

    .sound-info .lbl {
        font-size: 13px;
        font-weight: 700;
        color: var(--ink);
        margin-bottom: 4px;
    }

    .sound-info .desc {
        font-size: 11px;
        color: var(--ink-soft);
        margin-bottom: 8px;
    }

    .sound-player {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    .sound-player audio {
        height: 32px;
        max-width: 260px;
        border-radius: 16px;
    }

    .sound-empty-state {
        font-size: 11px;
        color: var(--ink-faint);
        font-style: italic;
        padding: 6px 12px;
        background: #f8fafc;
        border-radius: 6px;
        border: 1px dashed #cbd5e1;
    }

    .sound-actions {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-shrink: 0;
    }

    .sound-upload-input {
        display: none;
    }

    .sound-upload-btn {
        background: var(--paper);
        border: 1px solid var(--line);
        color: var(--ink);
        padding: 7px 14px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .sound-upload-btn:hover {
        background: #f3f4f6;
        border-color: var(--indigo);
        color: var(--indigo);
    }

    .sound-update-btn {
        background: var(--indigo);
        color: white;
        border: none;
        padding: 7px 14px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        display: none;
        align-items: center;
        gap: 6px;
    }

    .sound-update-btn:hover {
        background: #4338ca;
    }

    .sound-update-btn.show {
        display: inline-flex;
    }

    .sound-update-btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    .sound-update-btn .mini-spinner {
        width: 12px;
        height: 12px;
        border: 2px solid rgba(255, 255, 255, 0.3);
        border-top-color: white;
        border-radius: 50%;
        animation: spinMini 0.7s linear infinite;
        display: none;
    }

    .sound-update-btn.loading .mini-spinner {
        display: inline-block;
    }

    .sound-update-btn.loading .btn-text {
        display: none;
    }

    @keyframes spinMini {
        to { transform: rotate(360deg); }
    }

    .sound-file-name {
        font-size: 11px;
        color: var(--indigo);
        font-weight: 600;
        padding: 3px 8px;
        background: #eef2ff;
        border-radius: 4px;
        max-width: 150px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        display: none;
    }

    .sound-file-name.show {
        display: inline-block;
    }

    .sound-toast {
        position: fixed;
        bottom: 24px;
        right: 24px;
        background: #10b981;
        color: white;
        padding: 12px 20px;
        border-radius: 10px;
        font-size: 13px;
        font-weight: 600;
        box-shadow: 0 10px 30px rgba(16, 185, 129, 0.3);
        z-index: 9999;
        display: flex;
        align-items: center;
        gap: 8px;
        opacity: 0;
        transform: translateY(20px);
        transition: all 0.3s;
    }

    .sound-toast.show {
        opacity: 1;
        transform: translateY(0);
    }

    .sound-toast.error {
        background: #ef4444;
        box-shadow: 0 10px 30px rgba(239, 68, 68, 0.3);
    }

    @media (max-width: 991px) {
        .form-grid-box {
            grid-template-columns: repeat(3, 1fr);
        }
    }

    @media (max-width: 768px) {
        .sound-card {
            flex-direction: column;
            align-items: flex-start;
        }
        
        .sound-actions {
            width: 100%;
            justify-content: flex-start;
        }
        
        .sound-player audio {
            max-width: 100%;
        }
    }

    @media (max-width: 400px) {
        .sound-settings-grid {
            grid-template-columns: repeat(1, 1fr);
        }
    }
</style>
@endpush

@section('content')

<div class="panel active">
    <div class="page-head">
        <div>
            <h1>⚙️ Quiz Settings</h1>
            <p style="color:var(--ink-soft); font-size:13px; margin-top:4px;">Configure quiz limits and sound effects</p>
        </div>
    </div>

    {{-- ========== SECTION 1: QUIZ LIMITS ========== --}}
    <div class="form-card">
        <div class="fh">
            <h3>📊 Quiz Limits</h3>
        </div>

        <form method="POST" action="{{ route('superadmin.quiz-settings.update') }}">
            @csrf
            @method('PUT')

            <div class="form-grid-box form-grid-02">

                <div class="field">
                    <label>Superadmin Question Limit <span class="req">*</span></label>
                    <input type="number" name="superadmin_que_limit"
                        value="{{ old('superadmin_que_limit', $setting->superadmin_que_limit ?? '') }}" required>
                </div>

                <div class="field">
                    <label>Superadmin Quiz Limit <span class="req">*</span></label>
                    <input type="number" name="superadmin_quiz_limit"
                        value="{{ old('superadmin_quiz_limit', $setting->superadmin_quiz_limit ?? '') }}" required>
                </div>

                <div class="field">
                    <label>Superadmin Referral Limit <span class="req">*</span></label>
                    <input type="number" name="superadmin_referral_limit"
                        value="{{ old('superadmin_referral_limit', $setting->superadmin_referral_limit ?? '') }}" required>
                </div>

                <div class="field">
                    <label>User Question Limit</label>
                    <input type="number" name="user_que_limit"
                        value="{{ old('user_que_limit', $setting->user_que_limit ?? '') }}">
                </div>

                <div class="field">
                    <label>User Quiz Limit</label>
                    <input type="number" name="user_quiz_limit"
                        value="{{ old('user_quiz_limit', $setting->user_quiz_limit ?? '') }}">
                </div>

                <div class="field">
                    <label>User Referral Limit</label>
                    <input type="number" name="user_referral_limit"
                        value="{{ old('user_referral_limit', $setting->user_referral_limit ?? '') }}">
                </div>

                <div class="field">
                    <label>Retailer Question Limit</label>
                    <input type="number" name="retailer_que_limit"
                        value="{{ old('retailer_que_limit', $setting->retailer_que_limit ?? '') }}">
                </div>

                <div class="field">
                    <label>Retailer Quiz Limit</label>
                    <input type="number" name="retailer_quiz_limit"
                        value="{{ old('retailer_quiz_limit', $setting->retailer_quiz_limit ?? '') }}">
                </div>

                <div class="field">
                    <label>Retailer Referral Limit</label>
                    <input type="number" name="retailer_referral_limit"
                        value="{{ old('retailer_referral_limit', $setting->retailer_referral_limit ?? '') }}">
                </div>

            </div>

            <div class="form-foot">
                <button class="btn btn-primary" type="submit">💾 Save Settings</button>
            </div>
        </form>
    </div>

    {{-- ========== SECTION 2: SOUND SETTINGS (8 New Fields) ========== --}}
    <div class="form-card" style="margin-top: 20px;">
        <div class="fh" style="display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap;">
            <h3>🔊 Sound Settings</h3>
            <span style="font-size:11px; color:var(--ink-soft);">Supported: MP3, WAV, OGG, M4A (Max 5MB)</span>
        </div>

        <div class="sound-settings-grid">
            
            @php
                $soundFields = [
                    'background_sound'          => ['icon' => '🎶', 'label' => 'Background Sound',          'desc' => 'Ambient background sound during quiz'],
                    'question_appear_sound'     => ['icon' => '❓', 'label' => 'Question Appear',           'desc' => 'Plays when a new question appears'],
                    'start_quiz_sound'          => ['icon' => '🚀', 'label' => 'Start Quiz',                'desc' => 'Fanfare sound when quiz begins'],
                    'answer_appear_sound'       => ['icon' => '💡', 'label' => 'Answer Appear',             'desc' => 'Sound when answer options appear'],
                    'quiz_end_sound'            => ['icon' => '🏁', 'label' => 'Quiz End',                  'desc' => 'Plays when quiz finishes'],
                    'answer_sheet_appear_sound' => ['icon' => '📋', 'label' => 'Answer Sheet Appear',       'desc' => 'Sound when answer sheet is displayed'],
                    'leaderboard_appear_sound'  => ['icon' => '🏆', 'label' => 'Leaderboard Appear',        'desc' => 'Triumph sound when leaderboard shows'],
                    'counter_sound'             => ['icon' => '⏰', 'label' => 'Counter / Tick',            'desc' => 'Tick sound for countdown timer'],
                ];
            @endphp

            @foreach($soundFields as $field => $meta)
                @php
                    $currentPath = $setting->{$field} ?? null;
                    $currentUrl  = $currentPath ? Storage::disk('public')->url($currentPath) : null;
                @endphp
                
                <div class="sound-card" data-field="{{ $field }}">
                    
                    {{-- Icon --}}
                    <div class="sound-icon-box">{{ $meta['icon'] }}</div>

                    {{-- Info + Player --}}
                    <div class="sound-info">
                        <div class="lbl">{{ $meta['label'] }}</div>
                        <div class="desc">{{ $meta['desc'] }}</div>
                        
                        <div class="sound-player">
                            @if($currentUrl)
                                <audio controls id="audio-{{ $field }}" src="{{ $currentUrl }}"></audio>
                            @else
                                <div class="sound-empty-state" id="empty-{{ $field }}">
                                    🔇 No sound uploaded yet
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="sound-actions">
                        <span class="sound-file-name" id="fileName-{{ $field }}"></span>
                        
                        <input 
                            type="file" 
                            class="sound-upload-input" 
                            id="input-{{ $field }}"
                            accept="audio/mpeg,audio/wav,audio/ogg,audio/mp4,audio/x-m4a,.mp3,.wav,.ogg,.m4a"
                            data-field="{{ $field }}"
                        >
                        
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

{{-- Toast Notification --}}
<div class="sound-toast" id="soundToast"></div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {

    // ============ Toast Function ============
    function showToast(message, type = 'success') {
        const toast = document.getElementById('soundToast');
        toast.textContent = (type === 'success' ? '✅ ' : '❌ ') + message;
        toast.className = 'sound-toast ' + (type === 'error' ? 'error' : '') + ' show';
        
        setTimeout(() => {
            toast.classList.remove('show');
        }, 3500);
    }

    // ============ File Input Change Handler ============
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

            // Validate file type
            const validTypes = ['audio/mpeg', 'audio/wav', 'audio/ogg', 'audio/mp4', 'audio/x-m4a'];
            if (!validTypes.includes(file.type)) {
                showToast('Please upload a valid audio file (MP3, WAV, OGG, M4A)', 'error');
                this.value = '';
                return;
            }

            // Validate file size (5MB)
            if (file.size > 5 * 1024 * 1024) {
                showToast('File size must be less than 5MB', 'error');
                this.value = '';
                return;
            }

            // Show file name + update button
            fileName.textContent = '📎 ' + file.name;
            fileName.classList.add('show');
            updateBtn.classList.add('show');
        });
    });

    // ============ Update Button Click (AJAX) ============
    document.querySelectorAll('.sound-update-btn').forEach(btn => {
        btn.addEventListener('click', async function() {
            const field = this.dataset.field;
            const input = document.getElementById('input-' + field);
            const file = input.files[0];

            if (!file) {
                showToast('Please select a file first', 'error');
                return;
            }

            // Loading state
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
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    showToast(data.message, 'success');

                    // Audio player update / create
                    let audioPlayer = document.getElementById('audio-' + field);
                    const emptyState = document.getElementById('empty-' + field);
                    const playerContainer = document.querySelector(`.sound-card[data-field="${field}"] .sound-player`);

                    if (audioPlayer) {
                        // Existing player — update src
                        audioPlayer.src = data.url + '?t=' + Date.now(); // cache bust
                        audioPlayer.load();
                    } else {
                        // No player — create one
                        if (emptyState) emptyState.remove();
                        
                        audioPlayer = document.createElement('audio');
                        audioPlayer.controls = true;
                        audioPlayer.id = 'audio-' + field;
                        audioPlayer.src = data.url;
                        playerContainer.appendChild(audioPlayer);
                    }

                    // Change button text to "Change"
                    const uploadBtn = document.querySelector(`.sound-card[data-field="${field}"] .sound-upload-btn`);
                    if (uploadBtn) uploadBtn.innerHTML = '🔄 Change';

                    // Reset states
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

});
</script>
@endpush