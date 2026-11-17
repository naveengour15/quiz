<main class="min-h-screen px-container-margin pt-4 pb-6 flex flex-col kbc-stage text-white relative">
    <div class="kbc-spotlight"></div>
    <div class="kbc-stage-rings"><span></span><span></span><span></span><span></span></div>

    <!-- Top Header & Mute Controls -->
    <div class="relative z-[1] flex items-center justify-between w-full mb-2">
        <a href="{{ route('fsrapp.home') }}" class="kbc-icon-badge flex items-center justify-center" title="Back to Home">
            <span class="material-symbols-outlined text-[18px]">arrow_back</span>
        </a>
        <span class="font-label-caps kbc-title-glow text-[13px] tracking-widest font-bold">FSRQUIZ</span>
        <button type="button" id="kbc-mute-toggle" class="kbc-icon-badge" onclick="toggleGlobalMute()">
            <span class="material-symbols-outlined text-[18px]">volume_up</span>
        </button>
    </div>

    <!-- Top Refresh & Rules Warning Note -->
    {{-- <div class="relative z-[2] w-full max-w-md mx-auto mb-1">
        <div class="px-3 py-1.5 rounded-xl bg-gradient-to-r from-red-500/20 via-amber-500/25 to-red-500/20 border-2 border-amber-400/80 text-center shadow-[0_0_12px_rgba(245,158,11,0.25)] space-y-0.5">
            <p class="text-[10px] text-[#ffe9b0] font-bold leading-tight">
                <span class="text-sm">⚠️ Quiz Fair Play Rules & Guidelines ⚠️</span>
                <br>Quiz ke dauran in baton ka vishesh dhyan rakhein:
                <br> Page Refresh ya Back Button dabane par aap quiz se bahar (OUT) ho jayenge.
                <br> Question ke dauran Tab Switch ya lagatar 5 second se jyada Internet band rehne par OUT kar diya jayega.
                <br><strong> Good Luck for the Quiz! 🎯</strong>
            </p>
        </div>
    </div> --}}

    <!-- Center KBC Waiting Content -->
    <div class="relative z-[1] w-full max-w-md mx-auto text-center mt-5 mb-2 space-y-3.5">
        <div class="space-y-1">
            <h1 class="font-display text-xl font-black text-white kbc-title-glow uppercase tracking-wide">
                {{ $quiz->title ?? 'Waiting Room' }}
            </h1>
        </div>

        <div class="relative w-36 h-36 mx-auto flex items-center justify-center">
            <!-- Pulsing Outer Ring -->
            <div class="absolute inset-0 rounded-full border-4 border-primary/30 animate-ping opacity-75" style="will-change: transform; contain: strict;"></div>
            <div class="absolute inset-2 rounded-full border-4 border-secondary/40 animate-spin border-t-primary" style="will-change: transform; contain: strict;"></div>

            <div class="relative z-10 text-center">
                <span class="material-symbols-outlined text-3xl text-primary animate-pulse" style="will-change: transform;">hourglass_top</span>
                <p class="font-label-caps text-[9px] text-on-surface-variant mt-0.5">STARTING IN</p>
                <p id="fff-waiting-counter" class="font-display text-2xl font-black text-tertiary" style="font-variant-numeric: tabular-nums;">
                    @if (!empty($pendingTime) && $pendingTime > 0)
                        @if ($pendingTime >= 3600)
                            {{ floor($pendingTime / 3600) }}h {{ floor(($pendingTime % 3600) / 60) }}m
                            {{ $pendingTime % 60 }}s
                        @elseif($pendingTime >= 60)
                            {{ floor($pendingTime / 60) }}m {{ $pendingTime % 60 }}s
                        @else
                            {{ $pendingTime }}s
                        @endif
                    @else
                        {{ !empty($scheduledTimeMs) ? '0s' : '5s' }}
                    @endif
                </p>
            </div>
        </div>

        @include('fsrapp.home.components.banners', [
            'banners' => $banners,
            'pageType' => 'before_quiz_start_count',
            'disableClick' => true,
        ])
    </div>
</main>

<script>
    (function() {
        const fallbackPendingSec = {{ max(1, (int) ($pendingTime ?? 5)) }};
        const initialServerNowMs = {{ $serverNowMs ?? 'Date.now()' }};
        const initialScheduledTimeMs = {{ $scheduledTimeMs ? (int) $scheduledTimeMs : 'null' }};
        let counterEl = null;

        function formatFffTime(sec) {
            if (sec < 0) sec = 0;
            if (sec >= 3600) {
                const h = Math.floor(sec / 3600);
                const m = Math.floor((sec % 3600) / 60);
                const s = sec % 60;
                return `${h}h ${m}m ${s < 10 ? '0' : ''}${s}s`;
            }
            if (sec >= 60) {
                const m = Math.floor(sec / 60);
                const s = sec % 60;
                return `${m}m ${s < 10 ? '0' : ''}${s}s`;
            }
            return `${sec}s`;
        }

        function getEffectiveClockOffset() {
            if (typeof window.clockOffset === 'number' && !isNaN(window.clockOffset)) {
                return window.clockOffset;
            }
            return initialServerNowMs - Date.now();
        }

        function getEffectiveScheduledTimeMs() {
            if (typeof window.scheduledTimeMs === 'number' && window.scheduledTimeMs > 0) {
                return window.scheduledTimeMs;
            }
            if (typeof window.getFffQuizScheduleTimeMs === 'function') {
                const sched = window.getFffQuizScheduleTimeMs();
                if (sched && sched > 0) return sched;
            }
            if (initialScheduledTimeMs && initialScheduledTimeMs > 0) {
                return initialScheduledTimeMs;
            }
            const nowServer = Date.now() + getEffectiveClockOffset();
            const computedSched = nowServer + (fallbackPendingSec * 1000);
            window.scheduledTimeMs = computedSched;
            return computedSched;
        }

        function showFffWaitingAudioBanner() {
            if (typeof window.showGlobalAudioUnlockBanner === 'function') {
                window.showGlobalAudioUnlockBanner();
            }
        }

        function hideFffWaitingAudioBanner() {
            if (typeof window.hideGlobalAudioUnlockBanner === 'function') {
                window.hideGlobalAudioUnlockBanner();
            }
        }

        function cleanupFffWaitingStage() {
            hideFffWaitingAudioBanner();
            if (window._fffWaitingInterval) {
                clearInterval(window._fffWaitingInterval);
                window._fffWaitingInterval = null;
            }
        }

        function checkFffWaitingTimer() {
            if (window.FffSpa && window.FffSpa.currentStage !== 'waiting') {
                cleanupFffWaitingStage();
                return;
            }

            if (!counterEl) {
                counterEl = document.getElementById('fff-waiting-counter');
            }

            const currentQuizId = window.fffQuizId || null;
            const schedMs = getEffectiveScheduledTimeMs();
            const nowServer = Date.now() + getEffectiveClockOffset();
            const diffMs = schedMs - nowServer;
            const remainingSec = Math.max(0, Math.ceil(diffMs / 1000));

            // 1 Minute Before Alert Sound (56s - 61s window)
            if (diffMs <= 61000 && diffMs >= 56000) {
                if (window.hasPlayedOneMinSoundForQuiz && !window.hasPlayedOneMinSoundForQuiz(currentQuizId)) {
                    if (window.playOneMinBeforeSound) {
                        window.playOneMinBeforeSound(currentQuizId);
                    }
                }
            } else if (diffMs < 56000) {
                if (window.markOneMinSoundPlayedForQuiz) {
                    window.markOneMinSoundPlayedForQuiz(currentQuizId);
                }
            }

            if (diffMs > 0) {
                if (counterEl) {
                    const formatted = formatFffTime(remainingSec);
                    if (counterEl.textContent !== formatted) {
                        counterEl.textContent = formatted;
                    }
                }
            } else {
                // Scheduled start time arrived
                cleanupFffWaitingStage();
                if (counterEl && counterEl.textContent !== '0s') {
                    counterEl.textContent = '0s';
                }
                const nextStage = typeof determineFffTimelineStage === 'function' ? determineFffTimelineStage() : 'start-counter';
                if (window.FffSpa) {
                    window.FffSpa.showStage(nextStage);
                }
            }
        }

        window.initFffWaitingTimer = function() {
            cleanupFffWaitingStage();
            counterEl = document.getElementById('fff-waiting-counter');

            if (window.updateSpeakerIcon) {
                window.updateSpeakerIcon();
            }
            if (typeof window.preloadAllQuizSounds === 'function') {
                window.preloadAllQuizSounds();
            }
            if (typeof window.preloadOneMinBeforeSound === 'function') {
                window.preloadOneMinBeforeSound();
            }

            const currentQuizId = window.fffQuizId || null;
            const schedMs = getEffectiveScheduledTimeMs();
            const currentServerTime = Date.now() + getEffectiveClockOffset();
            const initialDiffMs = schedMs - currentServerTime;

            // Reset played state if quiz is still > 61s in the future
            if (initialDiffMs > 61000 && currentQuizId) {
                try {
                    sessionStorage.removeItem('qx_one_min_played_' + currentQuizId);
                    localStorage.removeItem('qx_one_min_played_' + currentQuizId);
                    if (window._oneMinAlertPlayed) delete window._oneMinAlertPlayed[currentQuizId];
                } catch(e) {}
            }

            // Initial audio play fallback check to guarantee banner shows only if sound is blocked and not playing
            setTimeout(() => {
                if (window.FffSpa && window.FffSpa.currentStage === 'waiting') {
                    const bg = window._bgChannel || window.activeQuizAudio;
                    const isPlaying = bg && !bg.paused && bg.currentTime > 0;
                    if (!isPlaying && (typeof window.isGlobalMuted !== 'function' || !window.isGlobalMuted())) {
                        showFffWaitingAudioBanner();
                    }
                }
            }, 300);

            // If scheduled time has already passed
            if (initialDiffMs <= 0) {
                cleanupFffWaitingStage();
                const nextStage = typeof determineFffTimelineStage === 'function' ? determineFffTimelineStage() : 'start-counter';
                if (window.FffSpa) {
                    window.FffSpa.showStage(nextStage);
                }
                return;
            }

            if (initialDiffMs < 56000) {
                if (window.markOneMinSoundPlayedForQuiz) {
                    window.markOneMinSoundPlayedForQuiz(currentQuizId);
                }
                playFffWaitingSound();
            } else if (initialDiffMs <= 61000 && initialDiffMs >= 56000) {
                if (window.hasPlayedOneMinSoundForQuiz && !window.hasPlayedOneMinSoundForQuiz(currentQuizId)) {
                    if (window.playOneMinBeforeSound) {
                        window.playOneMinBeforeSound(currentQuizId);
                    }
                } else {
                    playFffWaitingSound();
                }
            } else {
                playFffWaitingSound();
            }

            checkFffWaitingTimer();
            window._fffWaitingInterval = setInterval(checkFffWaitingTimer, 100);

            const onFffWakeUp = () => {
                if (!document.hidden && window.FffSpa && window.FffSpa.currentStage === 'waiting') {
                    checkFffWaitingTimer();
                }
            };
            document.addEventListener('visibilitychange', onFffWakeUp);
            window.addEventListener('focus', onFffWakeUp);
            window.addEventListener('pageshow', onFffWakeUp);
        };

        function playFffWaitingSound() {
            if (typeof window.isGlobalMuted === 'function' && window.isGlobalMuted()) {
                hideFffWaitingAudioBanner();
                return;
            }
            try {
                const soundUrl = (typeof window.getSoundUrl === 'function' ? window.getSoundUrl('background_sound') : null) ||
                    "https://chaloonline.s3.ap-south-1.amazonaws.com/sounds/background_sound_1787214373.mp3";
                if (soundUrl && window.playQuizAudio) {
                    window.playQuizAudio(soundUrl, 0.5, true, 'bg');
                }
                if (window.updateSpeakerIcon) window.updateSpeakerIcon();
            } catch (e) {
                console.warn('FFF Waiting page sound error:', e);
            }
        }

        window.playWaitingSound = playFffWaitingSound;
        window.playCachedBackgroundSound = playFffWaitingSound;
        window.playFffWaitingSound = playFffWaitingSound;
    })();
</script>
