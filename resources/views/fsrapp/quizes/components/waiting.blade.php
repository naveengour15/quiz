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
                <p id="waiting-counter-text" class="font-display text-2xl font-black text-tertiary" style="font-variant-numeric: tabular-nums;">
                    @if (!empty($pendingTime) && $pendingTime > 0)
                        @if ($pendingTime >= 3600)
                            {{ floor($pendingTime / 3600) }}h {{ floor(($pendingTime % 3600) / 60) }}m {{ $pendingTime % 60 }}s
                        @elseif($pendingTime >= 60)
                            {{ floor($pendingTime / 60) }}:{{ sprintf('%02d', $pendingTime % 60) }}
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
        const quizId = {{ $id }};
        const initialScheduledTimeMs = {{ $scheduledTimeMs ? (int) $scheduledTimeMs : 'null' }};
        const initialServerNowMs = {{ $serverNowMs ? (int) $serverNowMs : 'Date.now()' }};
        const fallbackPendingSec = {{ max(1, (int) ($pendingTime ?? 5)) }};
        
        let waitingTimerInterval = null;
        let counterEl = null;

        function formatTime(sec) {
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
                return `${m}:${s < 10 ? '0' : ''}${s}`;
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
            if (typeof window.getQuizScheduleTimeMs === 'function') {
                const sched = window.getQuizScheduleTimeMs();
                if (sched && sched > 0) return sched;
            }
            if (initialScheduledTimeMs && initialScheduledTimeMs > 0) {
                return initialScheduledTimeMs;
            }
            // Fallback: calculate target time from pending seconds once
            const nowServer = Date.now() + getEffectiveClockOffset();
            const computedSched = nowServer + (fallbackPendingSec * 1000);
            window.scheduledTimeMs = computedSched;
            return computedSched;
        }

        function showWaitingAudioBanner() {
            if (typeof window.showGlobalAudioUnlockBanner === 'function') {
                window.showGlobalAudioUnlockBanner();
            }
        }

        function hideWaitingAudioBanner() {
            if (typeof window.hideGlobalAudioUnlockBanner === 'function') {
                window.hideGlobalAudioUnlockBanner();
            }
        }

        function cleanupWaitingStage() {
            hideWaitingAudioBanner();
            if (waitingTimerInterval) {
                clearInterval(waitingTimerInterval);
                waitingTimerInterval = null;
            }
        }

        // Accurate NTP-style background server sync for waiting room
        async function syncWaitingServerTime() {
            try {
                const t0 = Date.now();
                const res = await fetch(`/fsrapp/quizes/api/status/${quizId}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                const t1 = Date.now();
                if (res.ok) {
                    const data = await res.json();
                    if (data && data.server_now_ms) {
                        const roundTripLatency = Math.max(0, (t1 - t0) / 2);
                        const serverTimeAtResponse = data.server_now_ms + roundTripLatency;
                        window.clockOffset = serverTimeAtResponse - t1;
                        if (data.scheduled_time_ms) {
                            window.scheduledTimeMs = data.scheduled_time_ms;
                        }
                        if (window.QuizSpa && window.QuizSpa.currentStage === 'waiting') {
                            checkWaitingTimer();
                        }
                    }
                }
            } catch (e) {}
        }

        function checkWaitingTimer() {
            if (window.__isExitingQuiz || window.__isQuizCompleted) return;
            if (window.QuizSpa && window.QuizSpa.currentStage !== 'waiting') {
                cleanupWaitingStage();
                return;
            }

            if (!counterEl) {
                counterEl = document.getElementById('waiting-counter-text');
            }

            const schedMs = getEffectiveScheduledTimeMs();
            const nowServerTime = Date.now() + getEffectiveClockOffset();
            const diffMs = schedMs - nowServerTime;
            const remainingSec = Math.max(0, Math.ceil(diffMs / 1000));

            // 1 Minute Before Quiz Start Alert Auto-Play (58s - 61s window)
            if (diffMs <= 61000 && diffMs >= 58000) {
                if (window.hasPlayedOneMinSoundForQuiz && !window.hasPlayedOneMinSoundForQuiz(quizId)) {
                    if (window.playOneMinBeforeSound) {
                        window.playOneMinBeforeSound(quizId);
                    }
                }
            } else if (diffMs < 58000) {
                if (window.markOneMinSoundPlayedForQuiz) {
                    window.markOneMinSoundPlayedForQuiz(quizId);
                }
            }

            if (diffMs > 0) {
                if (counterEl) {
                    const formatted = formatTime(remainingSec);
                    if (counterEl.textContent !== formatted) {
                        counterEl.textContent = formatted;
                    }
                }
            } else {
                // Scheduled start time reached -> switch to start-counter or current timeline stage
                cleanupWaitingStage();
                if (counterEl && counterEl.textContent !== '0s') {
                    counterEl.textContent = '0s';
                }
                const nextStage = typeof determineTimelineStage === 'function' ? determineTimelineStage() : 'start-counter';
                if (window.QuizSpa) {
                    window.QuizSpa.switchStage(nextStage);
                }
            }
        }

        window.initWaitingStage = function() {
            cleanupWaitingStage();
            counterEl = document.getElementById('waiting-counter-text');

            if (window.enforceQuizProtection) window.enforceQuizProtection();
            if (window.updateSpeakerIcon) window.updateSpeakerIcon();
            if (typeof window.preloadAllQuizSounds === 'function') window.preloadAllQuizSounds();

            // Initial audio play attempt + fallback check to guarantee banner shows only if sound is blocked and not playing
            setTimeout(() => {
                if (window.QuizSpa && window.QuizSpa.currentStage === 'waiting') {
                    const bg = window._bgChannel || window.activeQuizAudio;
                    const isPlaying = bg && !bg.paused && bg.currentTime > 0;
                    if (!isPlaying && (typeof window.isGlobalMuted !== 'function' || !window.isGlobalMuted())) {
                        showWaitingAudioBanner();
                    }
                }
            }, 300);

            const schedMs = getEffectiveScheduledTimeMs();
            const currentServerTime = Date.now() + getEffectiveClockOffset();
            const initialDiffMs = schedMs - currentServerTime;

            // If scheduled time already passed, seamlessly switch to current timeline stage
            if (initialDiffMs <= 0) {
                cleanupWaitingStage();
                const nextStage = typeof determineTimelineStage === 'function' ? determineTimelineStage() : 'start-counter';
                if (window.QuizSpa) {
                    window.QuizSpa.switchStage(nextStage);
                }
                return;
            }

            // Audio playback initialization
            if (initialDiffMs < 58000) {
                if (window.markOneMinSoundPlayedForQuiz) {
                    window.markOneMinSoundPlayedForQuiz(quizId);
                }
                playCachedBackgroundSound();
            } else if (initialDiffMs <= 61000 && initialDiffMs >= 58000) {
                if (window.hasPlayedOneMinSoundForQuiz && !window.hasPlayedOneMinSoundForQuiz(quizId)) {
                    if (window.playOneMinBeforeSound) {
                        window.playOneMinBeforeSound(quizId);
                    }
                } else {
                    playCachedBackgroundSound();
                }
            } else {
                playCachedBackgroundSound();
            }

            // Run check and start 100ms interval
            checkWaitingTimer();
            waitingTimerInterval = setInterval(checkWaitingTimer, 100);
            if (window.QuizSpa) {
                window.QuizSpa.registerTimer(waitingTimerInterval);
            }

            // Background NTP sync with server
            syncWaitingServerTime();

            const onWakeUp = () => {
                if (!document.hidden && !window.__isExitingQuiz && !window.__isQuizCompleted && window.QuizSpa && window.QuizSpa.currentStage === 'waiting') {
                    checkWaitingTimer();
                }
            };
            document.addEventListener('visibilitychange', onWakeUp);
            window.addEventListener('focus', onWakeUp);
            window.addEventListener('pageshow', onWakeUp);
        };

        window.skipToStart = function() {
            cleanupWaitingStage();
            const nextStage = typeof determineTimelineStage === 'function' ? determineTimelineStage() : 'start-counter';
            if (window.QuizSpa) {
                window.QuizSpa.switchStage(nextStage);
            }
        };

        function playCachedBackgroundSound() {
            if (typeof window.isGlobalMuted === 'function' && window.isGlobalMuted()) {
                hideWaitingAudioBanner();
                return;
            }
            try {
                const soundUrl = (typeof window.getSoundUrl === 'function' ? window.getSoundUrl('background_sound') : null) ||
                    "https://chaloonline.s3.ap-south-1.amazonaws.com/sounds/background_sound_1789460372.mp3";
                if (soundUrl && window.playQuizAudio) {
                    window.playQuizAudio(soundUrl, 0.5, true, 'bg');
                }
                if (window.updateSpeakerIcon) window.updateSpeakerIcon();
            } catch (e) {
                console.warn('Waiting page sound error:', e);
            }
        }

        window.playWaitingSound = playCachedBackgroundSound;
        window.playCachedBackgroundSound = playCachedBackgroundSound;
    })();
</script>
