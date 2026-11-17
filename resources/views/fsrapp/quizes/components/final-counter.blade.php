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

    <!-- Top Refresh Warning Note -->
    {{-- <div class="relative z-[2] w-full max-w-md mx-auto mb-1">
        <div
            class="flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-xl bg-gradient-to-r from-amber-500/30 via-red-500/25 to-amber-500/30 border-2 border-amber-400/80 text-amber-200 text-xs font-extrabold shadow-[0_0_12px_rgba(245,158,11,0.3)] text-center leading-tight animate-pulse">
            <span class="text-sm animate-bounce">⚠️</span>
            <span class="tracking-wide uppercase text-[10px] text-[#ffe9b0]">Do not refresh or reload this page!</span>
        </div>
    </div> --}}

    <!-- Center Counter Display -->
    <div class="relative z-[1] w-full max-w-md mx-auto text-center mt-1 mb-2 space-y-3.5">
        <div class="space-y-1">

            <h1 class="font-display text-xl font-black text-white kbc-title-glow uppercase tracking-wider">
                Preparing Leaderboard!
            </h1>
            <p class="font-body-md text-xs text-on-surface-variant">Processing final rankings across all players</p>
        </div>

        <!-- KBC SVG Dial Wrap -->
        <div class="relative w-40 h-40 mx-auto flex items-center justify-center">
            <svg class="w-full h-full transform -rotate-90" viewBox="0 0 100 100">
                <circle cx="50" cy="50" r="44" stroke="currentColor" stroke-width="7"
                    class="text-surface-container-high" fill="transparent" />
                <circle id="final-timer-progress" cx="50" cy="50" r="44" stroke="currentColor"
                    stroke-width="7" class="text-tertiary transition-all duration-1000 ease-linear" fill="transparent"
                    stroke-dasharray="276.46" stroke-dashoffset="0" stroke-linecap="round" />
            </svg>

            <div class="absolute inset-0 flex flex-col items-center justify-center">
                <span id="final-counter-num"
                    class="font-display text-5xl font-black text-white drop-shadow-[0_0_15px_rgba(244,191,50,0.8)]">10</span>
                <span
                    class="font-label-caps text-[9px] uppercase tracking-widest text-tertiary font-bold mt-0.5">SECONDS</span>
            </div>
        </div>

    </div>
</main>

<script>
    (function() {
        let finalCountdown = 10;
        const totalDuration = 10;
        const circumference = 276.46;
        let finalTimerInterval = null;

        window.initFinalCounterStage = function() {
            if (finalTimerInterval) clearInterval(finalTimerInterval);
            finalCountdown = 10;

            if (window.enforceQuizProtection) window.enforceQuizProtection();
            playCachedBackgroundSound();

            const counterEl = document.getElementById('final-counter-num');
            const progressEl = document.getElementById('final-timer-progress');

            if (counterEl) counterEl.textContent = finalCountdown;
            if (progressEl) progressEl.style.strokeDashoffset = '0';

            finalTimerInterval = setInterval(() => {
                finalCountdown--;
                if (counterEl) counterEl.textContent = finalCountdown;

                if (progressEl) {
                    const offset = circumference - (finalCountdown / totalDuration) * circumference;
                    progressEl.style.strokeDashoffset = offset;
                }

                if (finalCountdown <= 0) {
                    clearInterval(finalTimerInterval);
                    window.QuizSpa.switchStage('leaderboard');
                }
            }, 1000);

            window.QuizSpa.registerTimer(finalTimerInterval);
        };

        window.skipToLeaderboard = function() {
            if (finalTimerInterval) clearInterval(finalTimerInterval);
            window.QuizSpa.switchStage('leaderboard');
        };

        function playCachedBackgroundSound() {
            if (window.isGlobalMuted && window.isGlobalMuted()) return;
            try {
                const cached = sessionStorage.getItem('qx_sound_settings') ||
                    sessionStorage.getItem('qx_cached_sound_urls') ||
                    localStorage.getItem('qx_sound_settings') ||
                    localStorage.getItem('qx_cached_sound_urls');
                if (cached) {
                    const sounds = JSON.parse(cached);
                    const soundUrl = sounds.background_sound || sounds.default;
                    if (soundUrl && window.playQuizAudio) {
                        window.playQuizAudio(soundUrl, 0.5, true);
                    }
                }
            } catch (e) {}
        }

        window.playFinalCounterSound = playCachedBackgroundSound;
    })();
</script>
