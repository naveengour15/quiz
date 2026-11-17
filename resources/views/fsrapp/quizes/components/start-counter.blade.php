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
        <div
            class="px-3 py-1.5 rounded-xl bg-gradient-to-r from-red-500/20 via-amber-500/25 to-red-500/20 border-2 border-amber-400/80 text-center shadow-[0_0_12px_rgba(245,158,11,0.25)] space-y-0.5">

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
        const quizId = {{ $id }};
        const serverNowMs = {{ $serverNowMs ? (int) $serverNowMs : 'Date.now()' }};
        const scheduledTimeMs = {{ $scheduledTimeMs ? (int) $scheduledTimeMs : 'Date.now()' }};

        let clockOffset = (typeof window.clockOffset === 'number' && !isNaN(window.clockOffset)) ? window.clockOffset : (serverNowMs - Date.now());
        let counterStartTime = scheduledTimeMs;
        let quizFlowStartTime = counterStartTime + 10000;

        const totalDuration = 10;
        const circumference = 276.46;
        let timerInterval = null;

        function getEffectiveClockOffset() {
            if (typeof window.clockOffset === 'number' && !isNaN(window.clockOffset)) {
                return window.clockOffset;
            }
            return clockOffset;
        }

        function updateCounterSync() {
            if (window.__isExitingQuiz || window.__isQuizCompleted) return;
            if (window.QuizSpa && window.QuizSpa.currentStage !== 'start-counter') {
                if (timerInterval) clearInterval(timerInterval);
                return;
            }

            const currentServerTime = Date.now() + getEffectiveClockOffset();
            if (currentServerTime < counterStartTime) {
                if (timerInterval) clearInterval(timerInterval);
                window.QuizSpa.switchStage('waiting');
                return;
            }

            const questionStartTime = window.getQuestionStartTimeMs ? window.getQuestionStartTimeMs() : (
                counterStartTime + 10000);

            const remainingMs = questionStartTime - currentServerTime;
            const remainingSec = Math.max(0, Math.ceil(remainingMs / 1000));

            const counterEl = document.getElementById('start-counter-num');
            const progressEl = document.getElementById('start-timer-progress');

            if (counterEl) counterEl.textContent = Math.min(10, remainingSec);

            if (progressEl) {
                const fraction = Math.max(0, Math.min(1, remainingSec / totalDuration));
                const offset = circumference - fraction * circumference;
                progressEl.style.strokeDashoffset = offset;
            }

            if (currentServerTime >= questionStartTime || remainingSec <= 0) {
                if (timerInterval) clearInterval(timerInterval);
                window.QuizSpa.switchStage('question');
            }
        }

        window.initStartCounterStage = function() {
            if (window.__isExitingQuiz) return;

            if (timerInterval) clearInterval(timerInterval);

            counterStartTime = window.getQuizScheduleTimeMs ? window.getQuizScheduleTimeMs() : (
                scheduledTimeMs || (Date.now() + getEffectiveClockOffset()));
            quizFlowStartTime = window.getQuestionStartTimeMs ? window.getQuestionStartTimeMs() : (
                counterStartTime + 10000);

            const currentServerTime = Date.now() + getEffectiveClockOffset();
            if (currentServerTime < counterStartTime) {
                window.QuizSpa.switchStage('waiting');
                return;
            }

            const counterKey = 'qx_start_counter_time_' + quizId;
            const qflowKey = 'qx_qflow_start_' + quizId;

            sessionStorage.setItem(counterKey, counterStartTime);
            localStorage.setItem(counterKey, counterStartTime);
            sessionStorage.setItem(qflowKey, quizFlowStartTime);
            localStorage.setItem(qflowKey, quizFlowStartTime);

            if (window.enforceQuizProtection) window.enforceQuizProtection();

            playCachedStartQuizSound();
            prefetchAndCacheQuestions();

            // Rely on window.clockOffset set on initial page load or WebSocket real-time events (Laravel Echo),
            // eliminating redundant high-frequency HTTP polling to the Laravel backend.
            updateCounterSync();
            timerInterval = setInterval(updateCounterSync, 100);

            window.QuizSpa.registerTimer(timerInterval);

            if (typeof window.hideGlobalAudioUnlockBanner === 'function') {
                window.hideGlobalAudioUnlockBanner();
            }

            const onCounterWakeUp = () => {
                if (!document.hidden && !window.__isExitingQuiz && !window.__isQuizCompleted && window
                    .QuizSpa && window.QuizSpa.currentStage === 'start-counter') {
                    updateCounterSync();
                }
            };
            document.addEventListener('visibilitychange', onCounterWakeUp);
            window.addEventListener('focus', onCounterWakeUp);
            window.addEventListener('pageshow', onCounterWakeUp);
        };

        async function prefetchAndCacheQuestions() {
            try {
                const encKey = 'qx_enc_questions_' + quizId;
                const cachedKey = 'qx_cached_questions_' + quizId;
                const cachedSwitchKey = 'qx_cached_switch_questions_' + quizId;
                const secretKey = window.QuizCipher ? window.QuizCipher.getKey(quizId) : ('FSRQ_' + quizId);
                const cachedSettingsKey = 'qx_quiz_settings_' + quizId;

                sessionStorage.removeItem('qx_anssheet_' + quizId);
                localStorage.removeItem('qx_anssheet_' + quizId);
                sessionStorage.removeItem('qx_used_5050_' + quizId);
                localStorage.removeItem('qx_used_5050_' + quizId);
                sessionStorage.removeItem('qx_used_switch_' + quizId);
                localStorage.removeItem('qx_used_switch_' + quizId);
                sessionStorage.removeItem('qx_any_lifeline_used_' + quizId);
                localStorage.removeItem('qx_any_lifeline_used_' + quizId);
                sessionStorage.removeItem('qx_switched_q_ids_' + quizId);
                localStorage.removeItem('qx_switched_q_ids_' + quizId);

                const sessionQuestions = (window.initialQuestions && window.initialQuestions.length > 0)
                    ? window.initialQuestions
                    : @json($initialQuestions ?? session("quiz_{$id}_questions", []));
                const sessionSwitchQuestions = (window.initialSwitchQuestions && window.initialSwitchQuestions.length > 0)
                    ? window.initialSwitchQuestions
                    : @json($initialSwitchQuestions ?? session("quiz_{$id}_switch_questions", []));
                const quizSettings = {
                    fifty_fifty: {{ (bool) ($quiz->fifty_fifty ?? true) ? 'true' : 'false' }},
                    switch_option: {{ (bool) ($quiz->switch_option ?? true) ? 'true' : 'false' }}
                };

                let questionsList = sessionQuestions;
                let switchQuestionsList = sessionSwitchQuestions;

                if ((!questionsList || questionsList.length === 0) && window.initialQuestions && window.initialQuestions.length > 0) {
                    questionsList = window.initialQuestions;
                    switchQuestionsList = window.initialSwitchQuestions || [];
                }

                if (!questionsList || questionsList.length === 0) {
                    try {
                        const res = await fetch(`/fsrapp/quizes/api/questions/${quizId}`);
                        const apiData = await res.json();
                        if (apiData && apiData.questions && apiData.questions.length > 0) {
                            questionsList = apiData.questions;
                            switchQuestionsList = apiData.switch_questions || [];
                        }
                    } catch (e) {}
                }

                // Randomize & lock question order for this device using QuizQuestionManager
                let prepared = {
                    questions: questionsList,
                    switch_questions: switchQuestionsList
                };
                if (window.QuizQuestionManager && typeof window.QuizQuestionManager
                    .prepareQuestionsForDevice === 'function') {
                    prepared = window.QuizQuestionManager.prepareQuestionsForDevice(quizId, questionsList,
                        switchQuestionsList);
                }

                questionsList = prepared.questions || [];
                switchQuestionsList = prepared.switch_questions || [];

                const json = {
                    success: true,
                    from_session: true,
                    total: (questionsList || []).length,
                    questions: questionsList,
                    switch_questions: switchQuestionsList,
                    quiz_settings: quizSettings
                };

                if (json && json.questions && json.questions.length > 0) {
                    const encryptedStr = window.QuizCipher ? window.QuizCipher.encrypt(json, secretKey) : JSON
                        .stringify(json);
                    if (encryptedStr) {
                        if (window.safeStorageSet) {
                            window.safeStorageSet(encKey, encryptedStr, quizId);
                        } else {
                            try { sessionStorage.setItem(encKey, encryptedStr); localStorage.setItem(encKey, encryptedStr); } catch (e) {}
                        }
                        window.__encryptedQuizPayload = encryptedStr;
                    }
                    const sanitizedQuestions = questionsList.map(q => { const { correct_answer, hindi_correct_answer, ...rest } = q; return rest; });
                    if (window.safeStorageSet) {
                        window.safeStorageSet(cachedKey, JSON.stringify(sanitizedQuestions), quizId);
                        window.safeStorageSet(cachedSwitchKey, JSON.stringify(switchQuestionsList), quizId);
                    } else {
                        try {
                            sessionStorage.setItem(cachedKey, JSON.stringify(sanitizedQuestions));
                            localStorage.setItem(cachedKey, JSON.stringify(sanitizedQuestions));
                            sessionStorage.setItem(cachedSwitchKey, JSON.stringify(switchQuestionsList));
                            localStorage.setItem(cachedSwitchKey, JSON.stringify(switchQuestionsList));
                        } catch (e) {}
                    }
                    if (window.preloadAllQuestionAudios) {
                        window.preloadAllQuestionAudios(json.questions);
                    }
                }
                if (json.quiz_settings) {
                    if (window.safeStorageSet) {
                        window.safeStorageSet(cachedSettingsKey, JSON.stringify(json.quiz_settings), quizId);
                    } else {
                        try {
                            sessionStorage.setItem(cachedSettingsKey, JSON.stringify(json.quiz_settings));
                            localStorage.setItem(cachedSettingsKey, JSON.stringify(json.quiz_settings));
                        } catch (e) {}
                    }
                }
            } catch (e) {
                console.log('Prefetch questions error:', e);
            }
        }

        function playCachedStartQuizSound() {
            if (window.isGlobalMuted && window.isGlobalMuted()) return;
            try {
                const soundUrl = (typeof window.getSoundUrl === 'function' ? window.getSoundUrl('start_quiz_sound') : null) ||
                    "https://chaloonline.s3.ap-south-1.amazonaws.com/sounds/start_quiz_sound_1787214208.mp3";
                if (soundUrl && window.playQuizAudio) {
                    window.playQuizAudio(soundUrl, 0.8, false, 'sfx');
                }
            } catch (e) {}
        }

        window.playStartCounterSound = playCachedStartQuizSound;
        window.playCachedStartQuizSound = playCachedStartQuizSound;
    })();
</script>
