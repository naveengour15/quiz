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
    <div class="relative z-[1] w-full max-w-md mx-auto text-center mt-5 mb-2 space-y-3.5">
        <div class="space-y-1">
            <h1 id="fff-review-heading" data-time-completed="{{ __('app.time_completed') }}"
                data-all-completed="{{ __('app.all_questions_completed') }}"
                class="font-display text-xl font-black text-white kbc-title-glow uppercase tracking-wider">
                {{ __('app.time_completed') }}
            </h1>
            <p class="font-body-md text-xs text-on-surface-variant">Generating your answer sheet</p>
        </div>

        <!-- KBC SVG Dial Wrap -->
        <div class="relative w-40 h-40 mx-auto flex items-center justify-center">
            <svg class="w-full h-full transform -rotate-90" viewBox="0 0 100 100">
                <circle cx="50" cy="50" r="44" stroke="currentColor" stroke-width="7"
                    class="text-surface-container-high" fill="transparent" />
                <circle id="fff-review-timer-progress" cx="50" cy="50" r="44" stroke="currentColor"
                    stroke-width="7" class="text-mint transition-all duration-1000 ease-linear" fill="transparent"
                    stroke-dasharray="276.46" stroke-dashoffset="0" stroke-linecap="round" />
            </svg>

            <div class="absolute inset-0 flex flex-col items-center justify-center">
                <span id="fff-review-counter-num"
                    class="font-display text-5xl font-black text-white drop-shadow-[0_0_15px_rgba(0,229,160,0.8)]">10</span>
                <span
                    class="font-label-caps text-[9px] uppercase tracking-widest text-mint font-bold mt-0.5">SECONDS</span>
            </div>
        </div>

        @include('fsrapp.home.components.banners', [
            'banners' => $banners,
            'pageType' => 'before_answer_count',
            'disableClick' => true,
        ])
    </div>
</main>

<script>
    (function() {
        const circumference = 276.46;
        const totalDuration = 10; // 10 second review counter

        window.startFffReviewCounter = function() {
            if (window.checkAndHandleFffQuizExpiry && window.checkAndHandleFffQuizExpiry()) return;

            if (window._fffReviewInterval) {
                clearInterval(window._fffReviewInterval);
                window._fffReviewInterval = null;
            }

            // Finalize & compile client-side answer sheet in local storage with 0 network calls
            if (typeof window.finalizeAndValidateFffAnssheet === 'function') {
                window.finalizeAndValidateFffAnssheet();
            }

            // Update heading dynamically based on finish reason and locale
            const headingEl = document.getElementById('fff-review-heading');
            if (headingEl) {
                const finishReason = window.__fffFinishReason || (window.fffQuizId ? sessionStorage.getItem(
                    'fff_finish_reason_' + window.fffQuizId) : null);
                if (finishReason === 'all_answered') {
                    headingEl.textContent = headingEl.dataset.allCompleted ||
                        "{{ __('app.all_questions_completed') }}";
                } else {
                    headingEl.textContent = headingEl.dataset.timeCompleted || "{{ __('app.time_completed') }}";
                }
            }

            // Stop previous and play counter sound matching standard quiz
            if (window.stopAllQuizAudios) window.stopAllQuizAudios();
            if (window.playNamedSound) {
                window.playNamedSound('counter_sound', 0.75, false, 'sfx');
            }

            const counterEl = document.getElementById('fff-review-counter-num');
            const progressEl = document.getElementById('fff-review-timer-progress');

            if (counterEl) counterEl.textContent = '10';
            if (progressEl) progressEl.style.strokeDashoffset = '0';

            const totalDuration = 10;
            const totalDurationMs = 10000;
            const reviewStartTime = Date.now();

            function updateReviewCounterSync() {
                if (window.FffSpa && window.FffSpa.currentStage !== 'review-counter') {
                    if (window._fffReviewInterval) clearInterval(window._fffReviewInterval);
                    return;
                }

                const elapsedMs = Date.now() - reviewStartTime;
                const remainingSec = Math.max(0, Math.ceil((totalDurationMs - elapsedMs) / 1000));

                if (counterEl) counterEl.textContent = remainingSec;

                if (progressEl) {
                    const fraction = Math.max(0, Math.min(1, remainingSec / totalDuration));
                    const offset = circumference - fraction * circumference;
                    progressEl.style.strokeDashoffset = offset;
                }

                if (elapsedMs >= totalDurationMs || remainingSec <= 0) {
                    if (window._fffReviewInterval) clearInterval(window._fffReviewInterval);
                    if (window.stopAllQuizAudios) window.stopAllQuizAudios();
                    try {
                        sessionStorage.setItem('fff_reviewed_' + window.fffQuizId, 'true');
                        localStorage.setItem('fff_reviewed_' + window.fffQuizId, 'true');
                    } catch(e) {}
                    if (window.FffSpa) {
                        window.FffSpa.showStage('answer-sheet');
                    }
                }
            }

            updateReviewCounterSync();
            window._fffReviewInterval = setInterval(updateReviewCounterSync, 100);
            if (window.FffSpa && typeof window.FffSpa.registerTimer === 'function') {
                window.FffSpa.registerTimer(window._fffReviewInterval);
            }
        };

        const onReviewWakeUp = () => {
            if (!document.hidden && window.FffSpa && window.FffSpa.currentStage === 'review-counter') {
                window.startFffReviewCounter();
            }
        };
        document.addEventListener('visibilitychange', onReviewWakeUp);
        window.addEventListener('focus', onReviewWakeUp);
        window.addEventListener('pageshow', onReviewWakeUp);
    })();
</script>
