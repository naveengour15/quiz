<!-- ===== App Updation Blocking Popup / Modal Component ===== -->
@php
    $updateList = isset($appUpdations) ? (is_iterable($appUpdations) ? $appUpdations : ($appUpdations ? collect([$appUpdations]) : collect([]))) : collect([]);
@endphp
@if ($updateList->count() > 0)
    <style>
        /* Body & HTML Scroll Lock when App Update Modal is Active */
        html.fsr-update-locked,
        body.fsr-update-locked {
            overflow: hidden !important;
            height: 100vh !important;
            touch-action: none !important;
            -webkit-overflow-scrolling: auto !important;
        }

        /* Ambient Backdrop Overlay (Allows home page to be visible underneath) */
        .fsr-update-backdrop {
            position: fixed;
            inset: 0;
            z-index: 99999;
            background: rgba(10, 5, 24, 0.6);
            backdrop-filter: blur(2.5px);
            -webkit-backdrop-filter: blur(2.5px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
            user-select: none;
            -webkit-user-select: none;
            touch-action: none;
        }

        /* Modal Dialog Card in FSR Cyber Violet/Gold Theme */
        .fsr-update-modal-card {
            background:
                radial-gradient(120% 160% at 15% 0%, rgba(217, 70, 239, 0.22) 0%, transparent 55%),
                radial-gradient(120% 160% at 85% 20%, rgba(124, 58, 237, 0.25) 0%, transparent 55%),
                linear-gradient(145deg, #1c0f3d 0%, #150a2f 60%, #0d051e 100%);
            border: 1px solid rgba(244, 191, 50, 0.35);
            box-shadow: 0 25px 60px -10px rgba(0, 0, 0, 0.85),
                        0 0 28px rgba(217, 70, 239, 0.25),
                        0 0 14px rgba(244, 191, 50, 0.18),
                        inset 0 1px 1px rgba(255, 255, 255, 0.18);
            border-radius: 26px;
            padding: 24px 20px;
            position: relative;
            overflow: hidden;
            max-width: 380px;
            width: 100%;
            margin: auto;
            animation: modalScaleIn 0.35s cubic-bezier(0.16, 1, 0.3, 1) forwards;
            pointer-events: auto;
        }

        @keyframes modalScaleIn {
            0% {
                opacity: 0;
                transform: scale(0.92) translateY(12px);
            }
            100% {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        /* Animated Glowing Edge Shimmer */
        .fsr-update-modal-card::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(244, 191, 50, 0.15) 0%, rgba(217, 70, 239, 0.1) 35%, transparent 65%);
            animation: updateShimmerMove 7s linear infinite;
            pointer-events: none;
        }

        @keyframes updateShimmerMove {
            0% { transform: translate(-25%, -25%); }
            50% { transform: translate(25%, 25%); }
            100% { transform: translate(-25%, -25%); }
        }

        /* App Update Icon with Radar Ring */
        .fsr-update-icon-wrap {
            position: relative;
            width: 64px;
            height: 64px;
            margin: 0 auto 16px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .fsr-update-icon-pulse {
            position: absolute;
            inset: -4px;
            border-radius: 999px;
            background: radial-gradient(circle, rgba(244, 191, 50, 0.45), transparent 70%);
            animation: radarPulse 2s ease-out infinite;
        }

        @keyframes radarPulse {
            0% { transform: scale(0.85); opacity: 1; }
            100% { transform: scale(1.4); opacity: 0; }
        }

        .fsr-update-icon-box {
            width: 56px;
            height: 56px;
            border-radius: 18px;
            background: linear-gradient(135deg, #f4bf32 0%, #d946ef 60%, #7c3aed 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #1a0b3d;
            box-shadow: 0 8px 22px rgba(217, 70, 239, 0.4), 0 0 14px rgba(244, 191, 50, 0.3);
            position: relative;
            z-index: 2;
        }

        /* Header Badges */
        .fsr-update-badge-pill {
            background: rgba(244, 191, 50, 0.15);
            color: #ffe9b0;
            border: 1px solid rgba(244, 191, 50, 0.4);
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 0.8px;
            padding: 4px 11px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            box-shadow: 0 0 10px rgba(244, 191, 50, 0.15);
        }

        /* Message Box Inset */
        .fsr-update-msg-box {
            background: rgba(13, 6, 32, 0.65);
            border: 1px solid rgba(216, 180, 254, 0.18);
            border-radius: 16px;
            padding: 13px 15px;
            margin-top: 14px;
            max-height: 140px;
            overflow-y: auto;
        }

        /* Custom Scrollbar for Message Box */
        .fsr-update-msg-box::-webkit-scrollbar {
            width: 4px;
        }
        .fsr-update-msg-box::-webkit-scrollbar-thumb {
            background: rgba(244, 191, 50, 0.35);
            border-radius: 4px;
        }

        /* Action Button */
        .fsr-update-action-btn {
            background: linear-gradient(135deg, #f4bf32 0%, #d946ef 60%, #7c3aed 100%);
            color: #1a0b3d;
            border: none;
            box-shadow: 0 8px 24px rgba(217, 70, 239, 0.35), 0 0 16px rgba(244, 191, 50, 0.25);
            font-size: 14px;
            font-weight: 800;
            letter-spacing: 0.4px;
            padding: 13px 20px;
            border-radius: 999px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            transition: all 0.2s ease;
            position: relative;
            overflow: hidden;
        }

        .fsr-update-action-btn:hover:not(:disabled) {
            transform: translateY(-1px);
            box-shadow: 0 10px 28px rgba(217, 70, 239, 0.45), 0 0 20px rgba(244, 191, 50, 0.35);
            filter: brightness(1.08);
        }

        .fsr-update-action-btn:active:not(:disabled) {
            transform: scale(0.98);
        }

        .fsr-update-action-btn:disabled {
            cursor: not-allowed;
            opacity: 0.85;
            box-shadow: none;
        }

        /* Progress Bar Section */
        .fsr-update-progress-wrap {
            margin-top: 16px;
            padding-top: 14px;
            border-top: 1px dashed rgba(244, 191, 50, 0.25);
            animation: fadeInProgress 0.3s ease;
        }

        @keyframes fadeInProgress {
            from { opacity: 0; transform: translateY(4px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .fsr-update-progress-track {
            width: 100%;
            height: 8px;
            background: rgba(13, 6, 32, 0.85);
            border: 1px solid rgba(216, 180, 254, 0.18);
            border-radius: 999px;
            overflow: hidden;
            position: relative;
        }

        .fsr-update-progress-bar {
            width: 0%;
            height: 100%;
            background: linear-gradient(90deg, #f4bf32, #d946ef, #7c3aed);
            border-radius: 999px;
            transition: width 5s linear;
            box-shadow: 0 0 14px rgba(217, 70, 239, 0.7);
        }

        @keyframes spinIcon {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .animate-spin-icon {
            animation: spinIcon 1s linear infinite;
        }
    </style>

    <!-- Blocking Fullscreen Popup / Modal -->
    <div id="app-updation-wrapper" class="fsr-update-backdrop">
        <div class="w-full max-w-sm relative z-10 space-y-4" onclick="event.stopPropagation();">
            @foreach ($updateList as $appUpdation)
                <div id="app-updation-card-{{ $appUpdation->id }}" class="fsr-update-modal-card">

                    <!-- Top Centered Icon with Glow -->
                    <div class="fsr-update-icon-wrap">
                        <div class="fsr-update-icon-pulse"></div>
                        <div class="fsr-update-icon-box">
                            <span class="material-symbols-outlined text-[30px] font-bold">system_update</span>
                        </div>
                    </div>

                    <!-- Header Badges -->
                    <div class="flex items-center justify-center gap-2 mb-2.5 flex-wrap">
                        <span class="fsr-update-badge-pill">
                            <span class="material-symbols-outlined text-[13px] text-[#f4bf32]">verified</span>
                            <span>APP UPDATE REQUIRED</span>
                        </span>
                        @if ($appUpdation->created_at)
                            <span class="text-[10.5px] text-[#d8b4fe] font-mono font-medium">
                                {{ $appUpdation->created_at->diffForHumans() }}
                            </span>
                        @endif
                    </div>

                    <!-- Subject / Title -->
                    <h3 class="text-[16px] font-extrabold text-white text-center leading-snug tracking-wide">
                        {{ $appUpdation->subject }}
                    </h3>

                    <!-- Message Body -->
                    @if (!empty($appUpdation->message))
                        <div class="fsr-update-msg-box">
                            <p class="text-[12.5px] leading-relaxed text-[#e8ddff] font-medium">
                                {!! nl2br(e($appUpdation->message)) !!}
                            </p>
                        </div>
                    @endif

                    <!-- Action Button Container -->
                    <div class="mt-5">
                        <button type="button" id="app-update-btn-{{ $appUpdation->id }}"
                            onclick="closeFsrAppUpdation({{ $appUpdation->id }})" class="fsr-update-action-btn"
                            title="Update Now">
                            <span class="material-symbols-outlined text-[18px]">autorenew</span>
                            <span>Update Now</span>
                        </button>
                    </div>

                    <!-- 5-Second Processing Progress Bar -->
                    <div id="app-update-progress-wrap-{{ $appUpdation->id }}" class="fsr-update-progress-wrap"
                        style="display:none;">
                        <div class="flex items-center justify-between text-[11.5px] font-bold text-[#e8ddff] mb-2">
                            <span class="flex items-center gap-1.5">
                                <span
                                    class="material-symbols-outlined text-[15px] animate-spin-icon text-[#f4bf32]">sync</span>
                                <span id="app-update-status-text-{{ $appUpdation->id }}">Updating application
                                    resources...</span>
                            </span>
                            <span id="app-update-timer-{{ $appUpdation->id }}"
                                class="font-mono text-[11.5px] text-[#ffe9b0]">5s</span>
                        </div>
                        <div class="fsr-update-progress-track">
                            <div id="app-update-progress-bar-{{ $appUpdation->id }}" class="fsr-update-progress-bar">
                            </div>
                        </div>
                    </div>

                </div>
            @endforeach
        </div>
    </div>

    <script>
        // Enforce Background Scroll Lock & Prevent All Interaction
        function lockFsrUpdateModal() {
            document.documentElement.classList.add('fsr-update-locked');
            document.body.classList.add('fsr-update-locked');

            const wrapper = document.getElementById('app-updation-wrapper');
            if (wrapper) {
                wrapper.addEventListener('touchmove', function(e) {
                    // Prevent background page scrolling on touch devices
                    if (!e.target.closest('.fsr-update-msg-box')) {
                        e.preventDefault();
                    }
                }, { passive: false });

                wrapper.addEventListener('wheel', function(e) {
                    if (!e.target.closest('.fsr-update-msg-box')) {
                        e.preventDefault();
                    }
                }, { passive: false });
            }
        }

        function unlockFsrUpdateModal() {
            document.documentElement.classList.remove('fsr-update-locked');
            document.body.classList.remove('fsr-update-locked');
        }

        // Initialize scroll lock on page load
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', lockFsrUpdateModal);
        } else {
            lockFsrUpdateModal();
        }

        async function clearAllQuizDeviceData() {
            try {
                const isPreservedKey = (key) => {
                    if (!key) return true;
                    const k = String(key).trim().toLowerCase();

                    // 1. Preserve Language preferences
                    if (
                        k === 'locale' ||
                        k === 'lang' ||
                        k === 'language' ||
                        k === 'app_lang' ||
                        k === 'current-lang-code' ||
                        k.includes('lang') ||
                        k.includes('locale')
                    ) {
                        return true;
                    }

                    // 2. Preserve Auth & Login credentials
                    if (
                        k.includes('auth') ||
                        k.includes('token') ||
                        k.includes('login') ||
                        k.includes('user_id') ||
                        k.includes('fsr_auth') ||
                        k.includes('fsr_user')
                    ) {
                        return true;
                    }

                    return false;
                };

                // 1. Clear all non-preserved localStorage across entire website
                const localKeysToRemove = [];
                for (let i = 0; i < localStorage.length; i++) {
                    const key = localStorage.key(i);
                    if (key && !isPreservedKey(key)) {
                        localKeysToRemove.push(key);
                    }
                }
                localKeysToRemove.forEach(k => {
                    try { localStorage.removeItem(k); } catch(e) {}
                });

                // 2. Clear all non-preserved sessionStorage across entire website
                const sessionKeysToRemove = [];
                for (let i = 0; i < sessionStorage.length; i++) {
                    const key = sessionStorage.key(i);
                    if (key && !isPreservedKey(key)) {
                        sessionKeysToRemove.push(key);
                    }
                }
                sessionKeysToRemove.forEach(k => {
                    try { sessionStorage.removeItem(k); } catch(e) {}
                });

                // 3. Clear all browser Cache Storage (Cache API)
                if ('caches' in window) {
                    const cacheNames = await caches.keys();
                    await Promise.all(
                        cacheNames.map(cacheName => caches.delete(cacheName))
                    );
                }

                // 4. Clear sound and audio cache instances
                if (window._preloadedUrls) window._preloadedUrls = {};
                if (window._preloadedQuestionAudios) window._preloadedQuestionAudios = {};
                if (window.soundsCache) window.soundsCache = {};
            } catch (error) {
                console.error('App device cache cleanup failed:', error);
            }
        }

        async function closeFsrAppUpdation(id) {
            const btn = document.getElementById('app-update-btn-' + id);
            const progressWrap = document.getElementById('app-update-progress-wrap-' + id);
            const progressBar = document.getElementById('app-update-progress-bar-' + id);
            const statusText = document.getElementById('app-update-status-text-' + id);
            const timerText = document.getElementById('app-update-timer-' + id);
            const card = document.getElementById('app-updation-card-' + id);

            // 1. Disable button & show updating state
            if (btn) {
                btn.disabled = true;
                btn.innerHTML =
                    '<span class="material-symbols-outlined text-[16px] animate-spin-icon">sync</span> <span>Updating Application...</span>';
                btn.style.pointerEvents = 'none';
            }

            // 2. Reveal 5-Second Processing Bar
            if (progressWrap && progressBar) {
                progressWrap.style.display = 'block';
                // Trigger width transition smoothly
                requestAnimationFrame(() => {
                    requestAnimationFrame(() => {
                        progressBar.style.width = '100%';
                    });
                });
            }

            // 3. Dynamic step status updates based on timer
            const statusStages = {
                5: 'Initializing update...',
                4: 'Purging local cache...',
                3: 'Downloading application assets...',
                2: 'Synchronizing latest data...',
                1: 'Finalizing updates...',
                0: 'Update completed successfully!'
            };

            let remainingSeconds = 5;
            if (statusText) statusText.innerText = statusStages[5];

            const interval = setInterval(() => {
                remainingSeconds--;
                if (remainingSeconds > 0) {
                    if (timerText) timerText.innerText = remainingSeconds + 's';
                    if (statusText && statusStages[remainingSeconds]) {
                        statusText.innerText = statusStages[remainingSeconds];
                    }
                } else {
                    if (timerText) timerText.innerText = 'Done!';
                    if (statusText) statusText.innerText = statusStages[0];
                    clearInterval(interval);
                }
            }, 1000);

            // 4. Concurrently run cache cleanup and server close API call
            try {
                await clearAllQuizDeviceData();
                await fetch("{{ url('/fsrapp/app-updation') }}/" + id + "/close", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });
            } catch (err) {
                console.error('Error during app update cleanup/close:', err);
            }

            // 5. Wait for full 5 seconds (5000ms), then smoothly close popup & reload page
            setTimeout(() => {
                if (card) {
                    card.style.transition = 'all 0.35s ease';
                    card.style.opacity = '0';
                    card.style.transform = 'scale(0.9) translateY(-15px)';
                }

                setTimeout(() => {
                    const wrapper = document.getElementById('app-updation-wrapper');
                    if (wrapper) {
                        wrapper.style.transition = 'opacity 0.3s ease';
                        wrapper.style.opacity = '0';
                        setTimeout(() => {
                            wrapper.remove();
                            unlockFsrUpdateModal();
                        }, 300);
                    } else {
                        unlockFsrUpdateModal();
                    }
                }, 300);

                // Reload page after update to apply fresh application state
                setTimeout(() => {
                    window.location.reload();
                }, 450);
            }, 5000);
        }
    </script>
@endif
