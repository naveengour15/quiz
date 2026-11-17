<main class="min-h-screen px-container-margin pt-4 pb-6 flex flex-col kbc-stage text-white relative">
    <div class="kbc-spotlight"></div>
    <div class="kbc-stage-rings"><span></span><span></span><span></span><span></span></div>

    <!-- Top Header Controls -->
    <div class="relative z-[1] flex items-center justify-between w-full mb-2">
        <div class="w-8"></div>
        <span class="font-label-caps kbc-title-glow text-[13px] tracking-widest font-bold">FSRQUIZ</span>
        <button type="button" id="kbc-mute-toggle" class="kbc-icon-badge" onclick="toggleGlobalMute()">
            <span class="material-symbols-outlined text-[18px]">volume_up</span>
        </button>
    </div>

    <!-- Top Refresh & Rules Warning Note -->
    {{-- <div class="relative z-[2] w-full max-w-md mx-auto mb-1">
        <div class="px-3 py-1.5 rounded-xl bg-gradient-to-r from-red-500/20 via-amber-500/25 to-red-500/20 border-2 border-amber-400/80 text-center shadow-[0_0_12px_rgba(245,158,11,0.25)] space-y-0.5">
            <p class="text-[10px] text-[#ffe9b0] font-bold leading-tight">
                <span class="text-sm">⚠️</span> Page Refresh ya Back Button dabane par <strong>Quiz se OUT</strong> kar diya jayega!
            </p>
        </div>
    </div> --}}

    <!-- Center Counter Display -->
    <div class="relative z-[1] w-full max-w-md mx-auto text-center mt-1 mb-2 space-y-3.5">
        <div class="space-y-1">
            <h1 class="font-display text-xl font-black text-white kbc-title-glow uppercase tracking-wider">
                {{ __('app.quiz_starting_soon') }}
            </h1>
        </div>

        <!-- KBC SVG Dial Wrap -->
        <div class="relative w-40 h-40 mx-auto flex items-center justify-center">
            <svg class="w-full h-full transform -rotate-90" viewBox="0 0 100 100">
                <circle cx="50" cy="50" r="44" stroke="currentColor" stroke-width="7"
                    class="text-surface-container-high" fill="transparent" />
                <circle id="start-timer-progress" cx="50" cy="50" r="44" stroke="currentColor"
                    stroke-width="7" class="text-primary transition-all duration-1000 ease-linear" fill="transparent"
                    stroke-dasharray="276.46" stroke-dashoffset="0" stroke-linecap="round" />
            </svg>

            <div class="absolute inset-0 flex flex-col items-center justify-center">
                <span id="start-counter-num"
                    class="font-display text-5xl font-black text-white drop-shadow-[0_0_15px_rgba(210,187,255,0.8)]">10</span>
                <span
                    class="font-label-caps text-[9px] uppercase tracking-widest text-primary font-bold mt-0.5">SECONDS</span>
            </div>
        </div>

        @include('fsrapp.home.components.banners', [
            'banners' => $banners,
            'pageType' => 'quiz_start_10s_count',
            'disableClick' => true,
        ])
    </div>
</main>

<script>
    (function() {
        const circumference = 276.46;
        const totalDuration = 10;

        window.startFffStartCounter = function() {
            if (window._fffStartCounterInterval) {
                clearInterval(window._fffStartCounterInterval);
                window._fffStartCounterInterval = null;
            }

            // Stop previous audios and play standard quiz start counter sound once
            if (window.stopAllQuizAudios) window.stopAllQuizAudios();
            playFffStartQuizSound();

            if (typeof window.hideGlobalAudioUnlockBanner === 'function') {
                window.hideGlobalAudioUnlockBanner();
            }

            const progressEl = document.getElementById('start-timer-progress');
            const numEl = document.getElementById('start-counter-num');

            function updateStartCounterSync() {
                if (window.FffSpa && window.FffSpa.currentStage !== 'start-counter') {
                    if (window._fffStartCounterInterval) clearInterval(window._fffStartCounterInterval);
                    return;
                }

                const currentServerTime = Date.now() + (window.clockOffset || 0);
                const schedTime = typeof window.getFffQuizScheduleTimeMs === 'function' ? window.getFffQuizScheduleTimeMs() : Date.now();
                if (currentServerTime < schedTime) {
                    if (window._fffStartCounterInterval) clearInterval(window._fffStartCounterInterval);
                    if (window.FffSpa) window.FffSpa.showStage('waiting');
                    return;
                }

                const qflowStartTime = typeof window.getFffQuestionStartTimeMs === 'function' ? window.getFffQuestionStartTimeMs() : (schedTime + 10000);
                const remainingMs = qflowStartTime - currentServerTime;
                const remainingSec = Math.max(0, Math.ceil(remainingMs / 1000));

                if (numEl) numEl.textContent = Math.min(10, remainingSec);

                if (progressEl) {
                    const fraction = Math.max(0, Math.min(1, remainingSec / totalDuration));
                    const offset = circumference - fraction * circumference;
                    progressEl.style.strokeDashoffset = offset;
                }

                if (currentServerTime >= qflowStartTime || remainingSec <= 0) {
                    if (window._fffStartCounterInterval) clearInterval(window._fffStartCounterInterval);
                    const nextStage = typeof determineFffTimelineStage === 'function' ? determineFffTimelineStage() : 'question';
                    if (window.FffSpa) window.FffSpa.showStage(nextStage);
                }
            }

            updateStartCounterSync();
            window._fffStartCounterInterval = setInterval(updateStartCounterSync, 100);
            if (window.FffSpa && typeof window.FffSpa.registerTimer === 'function') {
                window.FffSpa.registerTimer(window._fffStartCounterInterval);
            }
        };

        function playFffStartQuizSound() {
            if (typeof window.isGlobalMuted === 'function' && window.isGlobalMuted()) return;
            try {
                const soundUrl = (typeof window.getSoundUrl === 'function' ? window.getSoundUrl('start_quiz_sound') : null) ||
                    "https://chaloonline.s3.ap-south-1.amazonaws.com/sounds/start_quiz_sound_1787231006.mp3";
                if (soundUrl && window.playQuizAudio) {
                    window.playQuizAudio(soundUrl, 0.8, false, 'sfx');
                }
            } catch (e) {}
        }

        window.playStartCounterSound = playFffStartQuizSound;
        window.playCachedStartQuizSound = playFffStartQuizSound;

        const onStartCounterWakeUp = () => {
            if (!document.hidden && window.FffSpa && window.FffSpa.currentStage === 'start-counter') {
                window.startFffStartCounter();
            }
        };
        document.addEventListener('visibilitychange', onStartCounterWakeUp);
        window.addEventListener('focus', onStartCounterWakeUp);
        window.addEventListener('pageshow', onStartCounterWakeUp);
    })();
</script>
