<main class="min-h-screen px-container-margin pt-6 pb-4 kbc-stage text-white relative">
    <div class="kbc-spotlight"></div>
    <div class="kbc-stage-rings"><span></span><span></span><span></span><span></span></div>

    <!-- Top Header Controls -->
    <div class="relative z-[1] flex items-center justify-between w-full mb-3">
        <a href="{{ route('fsrapp.home') }}" class="kbc-icon-badge flex items-center justify-center" title="Home">
            <span class="material-symbols-outlined text-[18px]">home</span>
        </a>
        <div id="score-summary"
            class="font-label-caps bg-mint/20 px-3 py-1 rounded-full border border-mint/40 text-mint text-[11px] font-bold">
            Score: Loading...
        </div>
        <button type="button" id="kbc-mute-toggle" class="kbc-icon-badge" onclick="toggleGlobalMute()">
            <span class="material-symbols-outlined text-[18px]">volume_up</span>
        </button>
    </div>

    <!-- Top Refresh Warning Note -->
    {{-- <div class="relative z-[2] w-full max-w-md mx-auto mb-3">
        <div
            class="flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-gradient-to-r from-amber-500/30 via-red-500/25 to-amber-500/30 border-2 border-amber-400/80 text-amber-200 text-xs font-extrabold shadow-[0_0_15px_rgba(245,158,11,0.4)] text-center leading-snug animate-pulse">
            <span class="text-base animate-bounce">⚠️</span>
            <span class="tracking-wide uppercase text-[#ffe9b0]">Do not refresh or reload this page!</span>
        </div>
    </div> --}}
    <!-- Center Answer Sheet Content -->
    <div class="relative z-[1] w-full max-w-md mx-auto space-y-4 pb-12">
        <div class="space-y-1 text-center">

            <h1 class="font-display text-xl font-black text-white kbc-title-glow uppercase tracking-wider">
                Your Performance Sheet
            </h1>
        </div>

        <!-- Summary Stats Card -->
        <div class="glass-card rounded-2xl p-4 border border-white/10 flex items-center justify-around text-center">
            <div>
                <p class="font-label-caps text-[10px] text-on-surface-variant uppercase font-bold">TOTAL</p>
                <p id="stat-total" class="font-display text-xl font-black text-white">--</p>
            </div>
            <div class="h-8 w-px bg-white/10"></div>
            <div>
                <p class="font-label-caps text-[10px] text-mint uppercase font-bold">CORRECT</p>
                <p id="stat-correct" class="font-display text-xl font-black text-mint">--</p>
            </div>
            <div class="h-8 w-px bg-white/10"></div>
            <div>
                <p class="font-label-caps text-[10px] text-tertiary uppercase font-bold">TOTAL TIME</p>
                <p id="stat-time" class="font-display text-xl font-black text-tertiary">--</p>
            </div>
        </div>
        <!-- ===== Last Quiz Leaderboard Component ===== -->
        <div id="anssheet-leaderboard-wrap" class="hidden" style="display: none;">
            @include('fsrapp.home.components.last-quiz-leaderboard', [
                'lastQuizData' => $lastQuizData ?? [],
                'alwaysShow' => true,
            ])
        </div>

        @include('fsrapp.home.components.banners', [
            'banners' => $banners,
            'pageType' => 'quiz_answer_sheet',
            'disableClick' => true,
        ])
        <!-- Question Answer Sheet Scrollable List -->
        <div id="sheet-list" class="space-y-3 pr-0.5">
            <div class="p-6 text-center text-on-surface-variant text-sm">
                <span class="material-symbols-outlined animate-spin text-primary text-2xl mb-2">sync</span>
                <p>Loading your answer sheet...</p>
            </div>
        </div>
        <!-- Redirect Indicator -->
        <div class="text-center pt-2 pb-6">
            {{-- <p class="font-label-caps text-xs text-on-surface-variant font-medium">
                Redirecting to Leaderboard in <span id="auto-redirect-timer"
                    class="text-primary font-bold">30s</span>
            </p> --}}
            <button onclick="window.location.href='/fsrapp/home'"
                class="mt-2 w-full max-w-[220px] mx-auto py-3 px-6 rounded-full bg-primary text-on-primary font-action font-bold hover:brightness-110 active:scale-95 transition-all shadow-lg flex items-center justify-center gap-2">
                <span class="material-symbols-outlined text-[20px]">home</span>
                Go to Home
            </button>
        </div>


    </div>
</main>

<script>
    (function() {
        const quizId = {{ $id }};
        const questionLimit = {{ !empty($quiz->question_limit) ? (int) $quiz->question_limit : 10 }};
        const isMainQuiz = @json(($quiz->visibility ?? '') !== 'fastest_finger_first');
        const baseMainQuizCountdownSeconds = {{ \App\Services\QuizTimeline::answerSheetCountdownSeconds((int) ($qCount ?? (!empty($quiz->question_limit) ? $quiz->question_limit : 10))) }};
        const mainQuizQuestionSlotSeconds = {{ \App\Services\QuizTimeline::QUESTION_SLOT_SECONDS }};
        const anssheetReleaseTimeMs = {{ $anssheetReleaseTimeMs ? (int) $anssheetReleaseTimeMs : 'null' }};
        const serverNowMs = {{ $serverNowMs ? (int) $serverNowMs : 'Date.now()' }};
        const initialSheetData = @json($initialSheetData ?? null);
        let clockOffset = serverNowMs - Date.now();
        let answerSheetInterval = null;
        let isFetchingSheet = false;
        let mainQuizReleaseTimeMs = null;

        window.initAnswerSheetStage = function() {
            if (window.__isExitingQuiz) return;
            if (window.checkAndHandleQuizExpiry && window.checkAndHandleQuizExpiry()) return;

            if (answerSheetInterval) clearInterval(answerSheetInterval);

            // Release quiz protection so touch gestures can scroll freely
            if (window.releaseQuizProtection) window.releaseQuizProtection();
            window._quizProtectionActive = false;
            document.documentElement.style.overflowY = 'auto';
            document.body.style.overflowY = 'auto';
            document.documentElement.style.overscrollBehaviorY = 'auto';
            document.body.style.overscrollBehaviorY = 'auto';

            // Prime history entry and back button listener for direct home redirect
            const trapAnswerSheetHistory = () => {
                try {
                    history.pushState({
                        stage: 'answer-sheet',
                        trapped: true
                    }, document.title, location.href);
                } catch (e) {}
            };
            trapAnswerSheetHistory();

            window.addEventListener('popstate', function handleAnswerSheetPopstate(e) {
                window.location.replace('/fsrapp/home');
            });

            // Ensure all counter and background audios are stopped
            if (window.stopAllQuizAudios) window.stopAllQuizAudios();

            checkAndLoadAnswerSheet();
        };

        function formatRemainingTime(sec) {
            if (sec <= 0) return '0s';
            const m = Math.floor(sec / 60);
            const s = sec % 60;
            if (m > 0) {
                return `${m}m ${s < 10 ? '0' : ''}${s}s`;
            }
            return `${s}s`;
        }

        let isAnssheetRendered = false;

        function getEffectiveReleaseTimeMs() {
            if (isMainQuiz) {
                if (mainQuizReleaseTimeMs) return mainQuizReleaseTimeMs;

                const releaseKey = 'qx_anssheet_release_at_v2_' + quizId;
                const switchUsed = sessionStorage.getItem('qx_used_switch_' + quizId) === 'true' ||
                    localStorage.getItem('qx_used_switch_' + quizId) === 'true';
                const durationSeconds = Math.max(0, baseMainQuizCountdownSeconds - (switchUsed
                    ? mainQuizQuestionSlotSeconds
                    : 0));

                try {
                    const savedReleaseTime = Number(localStorage.getItem(releaseKey) || sessionStorage.getItem(releaseKey));
                    if (Number.isFinite(savedReleaseTime) && savedReleaseTime > 0) {
                        mainQuizReleaseTimeMs = savedReleaseTime;
                        return mainQuizReleaseTimeMs;
                    }

                    mainQuizReleaseTimeMs = Date.now() + (window.clockOffset || 0) + (durationSeconds * 1000);
                    localStorage.setItem(releaseKey, String(mainQuizReleaseTimeMs));
                    sessionStorage.setItem(releaseKey, String(mainQuizReleaseTimeMs));
                    return mainQuizReleaseTimeMs;
                } catch (error) {
                    console.warn('[Answer Sheet] Could not persist countdown deadline:', error);
                    mainQuizReleaseTimeMs = Date.now() + (window.clockOffset || 0) + (durationSeconds * 1000);
                    return mainQuizReleaseTimeMs;
                }
            }

            if (anssheetReleaseTimeMs && anssheetReleaseTimeMs > 1000000000) {
                return anssheetReleaseTimeMs;
            }

            const sched = typeof window.getQuizScheduleTimeMs === 'function'
                ? window.getQuizScheduleTimeMs()
                : (window.scheduledTimeMs || Date.now());
            const baseQ = window.totalQuestionsCount || questionLimit || 10;
            const isSwitchUsed = sessionStorage.getItem('qx_used_switch_' + quizId) === 'true' ||
                localStorage.getItem('qx_used_switch_' + quizId) === 'true';
            const userQCount = isSwitchUsed ? baseQ + 1 : baseQ;
            const questionStartTime = window.getQuestionStartTimeMs
                ? window.getQuestionStartTimeMs()
                : sched + 10000;

            return questionStartTime + (userQCount * 17000) + 10000;
        }

        function playAnssheetCountdownSound() {
            if (isAnssheetRendered) return;
            if (typeof window.isGlobalMuted === 'function' && window.isGlobalMuted()) return;
            try {
                const soundUrl = (typeof window.getSoundUrl === 'function' ? window.getSoundUrl('counter_sound') : null) ||
                    "https://chaloonline.s3.ap-south-1.amazonaws.com/sounds/counter_sound_1789460529.mp3";
                if (soundUrl && window.playQuizAudio) {
                    window.playQuizAudio(soundUrl, 0.75, true, 'sfx');
                }
            } catch(e) {}
        }

        function playAnssheetAppearSound() {
            if (typeof window.isGlobalMuted === 'function' && window.isGlobalMuted()) return;
            try {
                if (window.stopAllQuizAudios) {
                    window.stopAllQuizAudios();
                }
                const soundUrl = (typeof window.getSoundUrl === 'function' ? window.getSoundUrl('answer_sheet_appear_sound') : null) ||
                    "https://chaloonline.s3.ap-south-1.amazonaws.com/sounds/answer_sheet_appear_sound_1787214584.mp3";
                if (soundUrl && window.playQuizAudio) {
                    window.playQuizAudio(soundUrl, 0.85, false, 'sfx');
                }
            } catch(e) {}
        }

        window.playAnswerSheetSound = function() {
            if (isAnssheetRendered) {
                playAnssheetAppearSound();
            } else {
                playAnssheetCountdownSound();
            }
        };
        window.playCachedAnswerSheetSound = window.playAnswerSheetSound;

        let prefetchedSheetData = null;
        let prefetchPromise = null;

        function isClientAnssheetValidAndComplete(sheet, expectedCount) {
            if (!Array.isArray(sheet)) return false;
            if (expectedCount > 0 && sheet.length < expectedCount) return false;
            if (sheet.length === 0) return false;

            for (let i = 0; i < sheet.length; i++) {
                const item = sheet[i];
                if (!item || !item.question_id) return false;
                if (!item.question || typeof item.question !== 'string' || item.question.trim() === '') return false;
                if (!Array.isArray(item.options) || item.options.length < 2) return false;
                if (item.correct_answer === undefined || item.correct_answer === null || String(item.correct_answer).trim() === '') {
                    return false;
                }
            }
            return true;
        }

        window.prefetchAnswerSheet = function() {
            if (prefetchedSheetData) return Promise.resolve(prefetchedSheetData);

            if (initialSheetData && initialSheetData.success) {
                if (initialSheetData.sheet && Array.isArray(initialSheetData.sheet) && initialSheetData.sheet.length > 0) {
                    prefetchedSheetData = initialSheetData.sheet;
                    return Promise.resolve(prefetchedSheetData);
                } else if (initialSheetData.answers && Array.isArray(initialSheetData.answers) && initialSheetData.answers.length > 0) {
                    const mapped = initialSheetData.answers.map(item => ({
                        question_id: item.question_id,
                        question: item.question?.question_text || item.question || 'Question',
                        hindi_question: item.question?.hindi_question || item.hindi_question || null,
                        question_audio: item.question_audio || item.question?.question_audio || item.audio_url || null,
                        media_url: item.media_url || item.question?.media_url || null,
                        options: item.question?.options || item.options || [],
                        correct_answer: item.correct_answer || item.question?.correct_answer || '',
                        user_answer: item.user_answer || item.answer || '',
                        is_correct: !!item.is_correct,
                        total_time: parseFloat(item.total_time || item.time_taken || 0)
                    }));
                    prefetchedSheetData = mapped;
                    return Promise.resolve(prefetchedSheetData);
                }
            }

            return Promise.resolve(null);
        };

        function showWaitingState(targetReleaseMs) {
            if (answerSheetInterval) clearInterval(answerSheetInterval);
            isAnssheetRendered = false;

            // Play counter sound while countdown is in progress
            playAnssheetCountdownSound();

            const listEl = document.getElementById('sheet-list');
            const scoreEl = document.getElementById('score-summary');
            if (scoreEl) scoreEl.textContent = 'Score: Evaluating...';

            const statTotal = document.getElementById('stat-total');
            const statCorrect = document.getElementById('stat-correct');
            const statTime = document.getElementById('stat-time');
            if (statTotal) statTotal.textContent = '--';
            if (statCorrect) statCorrect.textContent = '--';
            if (statTime) statTime.textContent = '--';

            const effectiveTargetMs = (targetReleaseMs && targetReleaseMs > 1000000000)
                ? targetReleaseMs
                : getEffectiveReleaseTimeMs();

            function updateWaitingUi() {
                const nowServerTime = Date.now() + (window.clockOffset || 0);
                const diffMs = effectiveTargetMs - nowServerTime;
                const totalRemainingSec = Math.ceil(diffMs / 1000);

                if (diffMs <= 0 || totalRemainingSec <= 0) {
                    if (answerSheetInterval) clearInterval(answerSheetInterval);

                    const expectedCount = typeof window.getEffectiveTotalQuestionsCount === 'function'
                        ? window.getEffectiveTotalQuestionsCount()
                        : (questionLimit || 10);

                    if (typeof window.finalizeAndValidateClientAnssheet === 'function') {
                        window.finalizeAndValidateClientAnssheet();
                    }

                    // Check local client sheet: If 100% complete & valid, render and BYPASS server call completely
                    const sheetKey = 'qx_anssheet_' + quizId;
                    let localData = null;
                    try {
                        const raw = sessionStorage.getItem(sheetKey) || localStorage.getItem(sheetKey);
                        if (raw) localData = JSON.parse(raw);
                    } catch (e) {}

                    if (isClientAnssheetValidAndComplete(localData, expectedCount)) {
                        console.log('[Answer Sheet] Displaying instantly from complete local storage cache. Server API bypassed.');
                        renderAnswerSheet(localData);
                        return;
                    }

                    // Only if local client sheet is missing or incomplete, fetch from server
                    console.warn('[Answer Sheet] Local cache missing/incomplete. Fetching complete data from server...');
                    fetchAnswerSheet();
                    return;
                }

                const remainingSec = Math.max(1, totalRemainingSec);
                playAnssheetCountdownSound();
                if (listEl) {
                    listEl.innerHTML = `
                        <div class="glass-card kbc-card rounded-2xl p-6 text-center space-y-3 border border-amber-400/30 bg-amber-500/5 shadow-[0_0_20px_rgba(245,158,11,0.15)]">
                            <div class="relative w-14 h-14 mx-auto flex items-center justify-center">
                                <span class="material-symbols-outlined animate-spin text-amber-400 text-4xl">hourglass_top</span>
                            </div>
                            <h3 class="font-display text-base font-bold text-amber-200">Evaluating Your Performance</h3>
                            <p class="text-xs text-on-surface-variant leading-relaxed">
                                Answer sheet will be displayed once countdown finishes.
                            </p>
                            <div class="pt-2">
                                <span class="inline-block px-4 py-1.5 rounded-full bg-amber-500/20 border border-amber-400/40 text-amber-300 font-display text-lg font-black tracking-wider shadow-inner">
                                    Releasing in <span id="anssheet-waiting-timer-num">${remainingSec}s</span>
                                </span>
                            </div>
                        </div>
                    `;
                }

                const timerNumEl = document.getElementById('anssheet-waiting-timer-num');
                if (timerNumEl) {
                    timerNumEl.textContent = `${remainingSec}s`;
                }
            }

            updateWaitingUi();
            answerSheetInterval = setInterval(updateWaitingUi, 1000);
            window.QuizSpa.registerTimer(answerSheetInterval);
        }

        function checkAndLoadAnswerSheet() {
            // Immediately start background prefetch on arrival or page reload
            window.prefetchAnswerSheet();

            const currentServerTime = Date.now() + clockOffset;
            const effectiveReleaseTimeMs = getEffectiveReleaseTimeMs();
            if (currentServerTime < effectiveReleaseTimeMs) {
                showWaitingState(effectiveReleaseTimeMs);
                return;
            }

            // FAST-PATH 1: If initialSheetData has answers (loaded directly by server from Redis/DB), RENDER IMMEDIATELY!
            if (initialSheetData && initialSheetData.success) {
                if (initialSheetData.sheet && Array.isArray(initialSheetData.sheet) && initialSheetData.sheet.length > 0) {
                    renderAnswerSheet(initialSheetData.sheet);
                    return;
                } else if (initialSheetData.answers && Array.isArray(initialSheetData.answers) && initialSheetData.answers.length > 0) {
                    const mapped = initialSheetData.answers.map(item => ({
                        question_id: item.question_id,
                        question: item.question?.question_text || item.question || 'Question',
                        hindi_question: item.question?.hindi_question || item.hindi_question || null,
                        question_audio: item.question_audio || item.question?.question_audio || item.audio_url || null,
                        media_url: item.media_url || item.question?.media_url || null,
                        options: item.question?.options || item.options || [],
                        correct_answer: item.correct_answer || item.question?.correct_answer || '',
                        user_answer: item.user_answer || item.answer || '',
                        is_correct: !!item.is_correct,
                        total_time: parseFloat(item.total_time || item.time_taken || 0)
                    }));
                    renderAnswerSheet(mapped);
                    return;
                }
            }

            // FAST-PATH 2: If prefetchedSheetData has answers, RENDER IMMEDIATELY!
            if (prefetchedSheetData && Array.isArray(prefetchedSheetData) && prefetchedSheetData.length > 0) {
                renderAnswerSheet(prefetchedSheetData);
                return;
            }

            const baseQ = window.totalQuestionsCount || questionLimit || 10;
            const isSwitchUsed = sessionStorage.getItem('qx_used_switch_' + quizId) === 'true' ||
                localStorage.getItem('qx_used_switch_' + quizId) === 'true';
            const userQCount = isSwitchUsed ? (baseQ + 1) : baseQ;

            // Release answer data only after the scheduled answer-sheet time.
            if (currentServerTime >= effectiveReleaseTimeMs) {
                if (typeof window.finalizeAndValidateClientAnssheet === 'function') {
                    window.finalizeAndValidateClientAnssheet();
                }

                const sheetKey = 'qx_anssheet_' + quizId;
                let clientData = null;
                try {
                    const raw = sessionStorage.getItem(sheetKey) || localStorage.getItem(sheetKey);
                    if (raw) clientData = JSON.parse(raw);
                } catch (e) {}

                if (isClientAnssheetValidAndComplete(clientData, userQCount)) {
                    console.log('[Answer Sheet] Displaying instantly from complete local storage cache. Server API bypassed.');
                    renderAnswerSheet(clientData);
                    return;
                }

                // Only if local cache is incomplete or missing, fetch from server
                console.warn('[Answer Sheet] Local cache missing/incomplete. Fetching complete data from server...');
                fetchAnswerSheet();
                return;
            }
        }

        // Handle mobile screen off / sleep wake-up & app resume
        function handleResumeOnAnswerSheet() {
            if (window.QuizSpa && window.QuizSpa.currentStage !== 'answer-sheet') return;
            const hasCards = document.querySelectorAll('#sheet-list .kbc-card').length > 0;
            if (!hasCards) {
                isFetchingSheet = false;
                if (answerSheetInterval) {
                    clearInterval(answerSheetInterval);
                    answerSheetInterval = null;
                }
                checkAndLoadAnswerSheet();
            }
            if (typeof window.startLastQuizStatusPolling === 'function') {
                window.startLastQuizStatusPolling();
            }
        }

        document.addEventListener('visibilitychange', function() {
            if (document.visibilityState === 'visible') {
                handleResumeOnAnswerSheet();
            }
        });
        window.addEventListener('pageshow', handleResumeOnAnswerSheet);
        window.addEventListener('focus', handleResumeOnAnswerSheet);

        window.proceedToFinalCounter = function() {
            if (answerSheetInterval) clearInterval(answerSheetInterval);
            window.QuizSpa.switchStage('final-counter');
        };

        // ✅ Function to remove temporary Question cache and Quiz session data
        function clearCompletedQuizCacheAndSession() {
            // Only clear session when quiz is completed
            if (window.quizCompleteStatus !== 'completed') {
                return;
            }

            try {
                // 1. Remove Temporary Question Timers & Shuffled Order Cache
                sessionStorage.removeItem('qx_enc_questions_' + quizId);
                localStorage.removeItem('qx_enc_questions_' + quizId);
                sessionStorage.removeItem('qx_quiz_settings_' + quizId);
                localStorage.removeItem('qx_quiz_settings_' + quizId);
                sessionStorage.removeItem('qx_device_q_order_' + quizId);
                localStorage.removeItem('qx_device_q_order_' + quizId);
                sessionStorage.removeItem('qx_shuffled_order_' + quizId);
                localStorage.removeItem('qx_shuffled_order_' + quizId);
                delete window.__encryptedQuizPayload;

                const clearKey = 'qx_session_cleared_' + quizId;
                if (sessionStorage.getItem(clearKey)) return;
                sessionStorage.setItem(clearKey, '1');

                // 2. Request Backend Cache Cleanup
                const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
                fetch(`/fsrapp/quizes/api/clear-session/${quizId}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf || '',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                }).catch(() => {});
            } catch (e) {
                console.warn('[Quiz Complete] Cache/Session cleanup error:', e);
            }
        }
        window.clearCompletedQuizCacheAndSession = clearCompletedQuizCacheAndSession;

        function playCachedAnswerSheetSound() {
            if (typeof window.playAnswerSheetSound === 'function') {
                window.playAnswerSheetSound();
            }
        }

        async function fetchAnswerSheet() {
            if (prefetchedSheetData && Array.isArray(prefetchedSheetData) && prefetchedSheetData.length > 0) {
                renderAnswerSheet(prefetchedSheetData);
                return;
            }
            if (isFetchingSheet) return;
            isFetchingSheet = true;

            const listEl = document.getElementById('sheet-list');
            const currentServerTime = Date.now() + clockOffset;
            const isReleased = !anssheetReleaseTimeMs || (currentServerTime >= anssheetReleaseTimeMs);

            const sheetKey = 'qx_anssheet_' + quizId;
            let clientData = [];
            try {
                const raw = sessionStorage.getItem(sheetKey) || localStorage.getItem(sheetKey);
                if (raw) clientData = JSON.parse(raw);
            } catch (e) {}

            const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

            try {
                const response = await fetch(`/fsrapp/quizes/api/answer-sheet/${quizId}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf || '',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        answers: Array.isArray(clientData) ? clientData : []
                    })
                });
                const data = await response.json();

                if (data && data.locked) {
                    isFetchingSheet = false;
                    const waitSec = Math.max(1, data.remaining_seconds || 1);
                    const nowServerTime = Date.now() + clockOffset;
                    const newReleaseMs = data.release_time_ms || (nowServerTime + waitSec * 1000);
                    showWaitingState(newReleaseMs);
                    return;
                }

                if (data && data.success && data.answers) {
                    const mapped = data.answers.map(item => ({
                        question_id: item.question_id,
                        question: item.question?.question_text || item.question || 'Question',
                        hindi_question: item.question?.hindi_question || item.hindi_question ||
                            null,
                        question_audio: item.question_audio || item.question?.question_audio || item
                            .audio_url || null,
                        media_url: item.media_url || item.question?.media_url || null,
                        options: item.question?.options || item.options || [],
                        correct_answer: item.correct_answer || item.question?.correct_answer || '',
                        user_answer: item.user_answer || item.answer || '',
                        is_correct: !!item.is_correct,
                        total_time: parseFloat(item.total_time || item.time_taken || 0)
                    }));
                    renderAnswerSheet(mapped);
                    try {
                        sessionStorage.setItem(sheetKey, JSON.stringify(mapped));
                        localStorage.setItem(sheetKey, JSON.stringify(mapped));
                    } catch(e) {}
                } else if (data && data.success && data.sheet) {
                    renderAnswerSheet(data.sheet);
                    try {
                        sessionStorage.setItem(sheetKey, JSON.stringify(data.sheet));
                        localStorage.setItem(sheetKey, JSON.stringify(data.sheet));
                    } catch(e) {}
                } else if (isReleased) {
                    const sheetKey = 'qx_anssheet_' + quizId;
                    let clientData = null;
                    try {
                        const raw = sessionStorage.getItem(sheetKey) || localStorage.getItem(sheetKey);
                        if (raw) clientData = JSON.parse(raw);
                    } catch (e) {}
                    if (clientData && Array.isArray(clientData) && clientData.length > 0) {
                        renderAnswerSheet(clientData);
                    }
                }
            } catch (e) {
                console.error('Fetch answer sheet failed:', e);
                const sheetKey = 'qx_anssheet_' + quizId;
                let clientData = null;
                try {
                    const raw = sessionStorage.getItem(sheetKey) || localStorage.getItem(sheetKey);
                    if (raw) clientData = JSON.parse(raw);
                } catch (err) {}

                if (clientData && Array.isArray(clientData) && clientData.length > 0) {
                    renderAnswerSheet(clientData);
                    // Silently retry in 5s to sync with server without interrupting user view
                    setTimeout(fetchAnswerSheet, 5000);
                } else if (listEl) {
                    listEl.innerHTML = `
                        <div class="p-6 text-center text-on-surface-variant text-sm space-y-2">
                            <span class="material-symbols-outlined text-error text-3xl">error</span>
                            <p class="text-white font-medium">Failed to load answer sheet. Retrying...</p>
                        </div>
                    `;
                    setTimeout(fetchAnswerSheet, 3000);
                }
            } finally {
                isFetchingSheet = false;
            }
        }

        let activeQuestionAudio = null;
        let activeQuestionBtn = null;

        window.toggleQuestionSheetAudio = function(btn) {
            const audioUrl = btn.getAttribute('data-audio-url');
            if (!audioUrl) return;

            if (activeQuestionBtn === btn && activeQuestionAudio && !activeQuestionAudio.paused) {
                window.stopQuestionSheetAudio();
                return;
            }

            window.stopQuestionSheetAudio();

            try {
                const audio = new Audio(audioUrl);
                audio.volume = 1.0;
                activeQuestionAudio = audio;
                activeQuestionBtn = btn;

                btn.classList.add('playing', 'bg-primary', 'text-on-primary',
                    'shadow-[0_0_15px_rgba(168,85,247,0.7)]', 'animate-pulse');
                btn.classList.remove('bg-primary/20', 'text-primary');
                const icon = btn.querySelector('.q-audio-icon');
                if (icon) icon.textContent = 'pause';
                const label = btn.querySelector('.q-audio-label');
                if (label) label.textContent = 'Playing';

                audio.play().catch(e => {
                    console.warn('Failed to play question audio:', e);
                    window.stopQuestionSheetAudio();
                });

                audio.onended = function() {
                    window.stopQuestionSheetAudio();
                };
                audio.onerror = function() {
                    window.stopQuestionSheetAudio();
                };
            } catch (e) {
                console.error('Question audio error:', e);
                window.stopQuestionSheetAudio();
            }
        };

        window.stopQuestionSheetAudio = function() {
            if (activeQuestionAudio) {
                try {
                    activeQuestionAudio.pause();
                    activeQuestionAudio.currentTime = 0;
                } catch (e) {}
                activeQuestionAudio = null;
            }
            if (activeQuestionBtn) {
                activeQuestionBtn.classList.remove('playing', 'bg-primary', 'text-on-primary',
                    'shadow-[0_0_15px_rgba(168,85,247,0.7)]', 'animate-pulse');
                activeQuestionBtn.classList.add('bg-primary/20', 'text-primary');
                const icon = activeQuestionBtn.querySelector('.q-audio-icon');
                if (icon) icon.textContent = 'volume_up';
                const label = activeQuestionBtn.querySelector('.q-audio-label');
                if (label) label.textContent = 'Audio';
                activeQuestionBtn = null;
            }
        };

        function resolveMatchingOption(targetVal, options) {
            if (targetVal === undefined || targetVal === null || targetVal === '') return '';
            if (!options || !Array.isArray(options) || options.length === 0) return String(targetVal);

            const targetStr = String(targetVal).replace(/[\u00A0\u1680\u180E\u2000-\u200B\u202F\u205F\u3000]/g, ' ')
                .trim();
            const normTarget = targetStr.replace(/\s+/g, ' ').toLowerCase();

            // 1. Direct exact or case-insensitive match
            for (let opt of options) {
                const optStr = String(opt).replace(/[\u00A0\u1680\u180E\u2000-\u200B\u202F\u205F\u3000]/g, ' ')
                    .trim();
                const normOpt = optStr.replace(/\s+/g, ' ').toLowerCase();
                if (normTarget === normOpt) return optStr;
            }

            // 2. Option prefix stripped match (e.g. 'A. Option', '1. Option', '(A) Option', 'Option A: Text')
            const cleanedPrefix = normTarget.replace(
                /^(?:(?:option|opt)[\s_]*[a-f\d]+[\s:\-\.]*|[a-f\d]+[\.\)\:\-]\s*|\([a-f\d]+\)\s*)/i, '').trim();
            if (cleanedPrefix !== '' && cleanedPrefix !== normTarget) {
                for (let opt of options) {
                    const optStr = String(opt).replace(/[\u00A0\u1680\u180E\u2000-\u200B\u202F\u205F\u3000]/g, ' ')
                        .trim();
                    const normOpt = optStr.replace(/\s+/g, ' ').toLowerCase();
                    if (cleanedPrefix === normOpt) return optStr;
                }
            }

            // 3. Option letter/key match: 'a', 'b', 'c', 'd', 'e', 'f' or 'option a', 'opt_1'
            const cleanedLetter = normTarget.replace(/^(?:option|opt)[\s_]*/i, '').trim();
            const letters = {
                'a': 0,
                'b': 1,
                'c': 2,
                'd': 3,
                'e': 4,
                'f': 5
            };
            if (letters.hasOwnProperty(cleanedLetter) && options[letters[cleanedLetter]] !== undefined) {
                return String(options[letters[cleanedLetter]]);
            }

            // 4. Numeric index (0, 1, 2, 3...)
            if (/^\d+$/.test(cleanedLetter)) {
                const idx = parseInt(cleanedLetter, 10);
                if (options[idx] !== undefined) return String(options[idx]);
                if (idx > 0 && options[idx - 1] !== undefined) return String(options[idx - 1]);
            }

            // 5. Bilingual base (Hindi/English) and inner parenthetical matches
            const targetBase = targetStr.replace(/\s*\(.*?\)\s*$/g, '').trim().toLowerCase();
            const tMatch = targetStr.match(/\((.*?)\)/);
            const targetInner = tMatch && tMatch[1] ? tMatch[1].trim().toLowerCase() : '';

            for (let opt of options) {
                const optStr = String(opt).replace(/[\u00A0\u1680\u180E\u2000-\u200B\u202F\u205F\u3000]/g, ' ')
                    .trim();
                const normOpt = optStr.replace(/\s+/g, ' ').toLowerCase();
                const optBase = optStr.replace(/\s*\(.*?\)\s*$/g, '').trim().toLowerCase();
                const oMatch = optStr.match(/\((.*?)\)/);
                const optInner = oMatch && oMatch[1] ? oMatch[1].trim().toLowerCase() : '';

                if ((normTarget !== '' && normTarget === optBase) ||
                    (optInner !== '' && normTarget === optInner) ||
                    (targetBase !== '' && targetBase === optBase) ||
                    (targetBase !== '' && optInner !== '' && targetBase === optInner) ||
                    (targetInner !== '' && targetInner === optBase) ||
                    (targetInner !== '' && optInner !== '' && targetInner === optInner) ||
                    (targetBase !== '' && normOpt !== '' && targetBase === normOpt) ||
                    (targetInner !== '' && normOpt !== '' && targetInner === normOpt)
                ) {
                    return optStr;
                }
            }

            return targetStr;
        }

        function renderAnswerSheet(rawItems) {
            isAnssheetRendered = true;
            if (answerSheetInterval) {
                clearInterval(answerSheetInterval);
                answerSheetInterval = null;
            }

            // Stop counter sound and play answer sheet appear sound
            playAnssheetAppearSound();

            let items = Array.isArray(rawItems) ? rawItems : [];

            // Exclude switched-out questions
            let switchedIds = [];
            try {
                const rawSwitched = sessionStorage.getItem('qx_switched_q_ids_' + quizId) || localStorage.getItem(
                    'qx_switched_q_ids_' + quizId);
                if (rawSwitched) switchedIds = JSON.parse(rawSwitched);
            } catch (e) {}

            if (Array.isArray(switchedIds) && switchedIds.length > 0) {
                items = items.filter(item => {
                    const qId = item.question_id;
                    return !switchedIds.includes(qId) && !switchedIds.includes(String(qId)) && !switchedIds
                        .includes(Number(qId));
                });
            }

            // Cap items to questionLimit
            if (questionLimit > 0 && items.length > questionLimit) {
                items = items.slice(0, questionLimit);
            }

            const listEl = document.getElementById('sheet-list');
            listEl.innerHTML = '';

            let correctCount = 0;
            let attemptedCount = 0;
            let totalTime = 0;

            items.forEach((item, idx) => {
                const optionsArr = item.options || [];
                const matchedUserAns = resolveMatchingOption(item.user_answer, optionsArr);
                const matchedCorrectAns = resolveMatchingOption(item.correct_answer, optionsArr);

                const rawAnsStr = item.user_answer !== null && item.user_answer !== undefined ? String(item.user_answer).trim() : '';
                const isAttempted = rawAnsStr !== '' && rawAnsStr.toLowerCase() !== 'null' && rawAnsStr.toLowerCase() !== 'undefined';

                let isItemCorrect = false;
                if (isAttempted && item.correct_answer && item.user_answer) {
                    const normU = String(matchedUserAns || item.user_answer).replace(
                            /[\u00A0\u1680\u180E\u2000-\u200B\u202F\u205F\u3000]/g, ' ')
                        .replace(/\s+/g, ' ').trim().toLowerCase();
                    const normC = String(matchedCorrectAns || item.correct_answer).replace(
                            /[\u00A0\u1680\u180E\u2000-\u200B\u202F\u205F\u3000]/g, ' ')
                        .replace(/\s+/g, ' ').trim().toLowerCase();
                    if (normU === normC) {
                        isItemCorrect = true;
                    } else {
                        const uBase = normU.replace(/\s*\(.*?\)\s*$/g, '').trim();
                        const cBase = normC.replace(/\s*\(.*?\)\s*$/g, '').trim();
                        if (uBase && cBase && uBase === cBase) isItemCorrect = true;
                    }
                }

                if (isAttempted) {
                    attemptedCount++;
                    totalTime += parseFloat(item.total_time || 0);
                }
                if (isItemCorrect) correctCount++;

                const statusLabel = isItemCorrect ? 'Correct' : (isAttempted ? 'Incorrect' : 'Not Attempted');
                const statusColor = isItemCorrect ? 'text-mint' : (isAttempted ? 'text-error' : 'text-amber-300');
                const statusIcon = isItemCorrect ? 'check_circle' : (isAttempted ? 'cancel' : 'help_outline');

                const card = document.createElement('div');
                card.className =
                    `glass-card kbc-card rounded-2xl p-4 space-y-2 border ${
                        isItemCorrect ? 'border-mint/40 bg-mint/5' : (isAttempted ? 'border-error/40 bg-error/5' : 'border-amber-500/30 bg-amber-500/5')
                    }`;

                let questionHtml = '';
                if (item.hindi_question && String(item.hindi_question).trim().length > 0) {
                    questionHtml = `
                    <p class="font-headline-md text-sm font-semibold text-white leading-snug">${escapeHtml(String(item.hindi_question).trim())}</p>
                    ${item.question ? `<p class="text-amber-200/90 text-xs font-medium mt-1 pt-1 border-t border-white/10 leading-relaxed font-sans">${escapeHtml(item.question)}</p>` : ''}
                `;
                } else {
                    questionHtml = `
                    <p class="font-headline-md text-sm font-semibold text-white leading-snug">${escapeHtml(item.question || ('Question ' + (idx + 1)))}</p>
                `;
                }

                let audioBtnHtml = '';
                const qAudio = item.question_audio || item.audio_url || null;
                if (qAudio && String(qAudio).trim().length > 0) {
                    audioBtnHtml = `
                        <button type="button" class="q-audio-play-btn inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-primary/20 hover:bg-primary/30 border border-primary/40 text-primary transition-all active:scale-95 text-xs font-bold shrink-0 cursor-pointer shadow-sm" data-audio-url="${escapeHtml(qAudio)}" onclick="window.toggleQuestionSheetAudio(this)" title="Play Question Audio">
                            <span class="material-symbols-outlined text-[15px] q-audio-icon">volume_up</span>
                            <span class="q-audio-label text-[10px] uppercase font-bold tracking-wider">Audio</span>
                        </button>
                    `;
                }

                let mediaImgHtml = '';
                const qMedia = item.media_url || item.image_url || null;
                if (qMedia && String(qMedia).trim().length > 0) {
                    mediaImgHtml = `
                        <div class="q-img-wrap my-2.5 rounded-xl overflow-hidden border border-white/10 bg-black/40 shadow-inner max-h-52 flex items-center justify-center pointer-events-none select-none">
                            <img src="${escapeHtml(qMedia)}" alt="Question Image" class="w-full h-auto max-h-52 object-contain rounded-xl select-none pointer-events-none" loading="lazy" onerror="this.closest('.q-img-wrap').style.display='none'">
                        </div>
                    `;
                }

                card.innerHTML = `
                <div class="flex justify-between items-center gap-2">
                    <span class="font-label-caps text-xs font-bold ${statusColor} flex items-center gap-1">
                        <span class="material-symbols-outlined text-sm">${statusIcon}</span>
                        Q${idx + 1}. ${statusLabel}
                    </span>
                    <div class="flex items-center gap-2">
                        ${audioBtnHtml}
                        <span class="font-label-caps text-[10px] text-on-surface-variant font-mono bg-white/5 px-2 py-0.5 rounded border border-white/5">${isAttempted ? (item.total_time ? Number(item.total_time).toFixed(3) + 's' : '-') : 'Skipped'}</span>
                    </div>
                </div>
                ${mediaImgHtml}
                ${questionHtml}
                <div class="text-xs space-y-1.5 pt-2 border-t border-white/10">
                    <p class="text-on-surface-variant flex items-center gap-1.5">
                        <span class="text-mint font-semibold">Correct Answer:</span>
                        <span class="font-bold text-mint">${escapeHtml(matchedCorrectAns || 'N/A')}</span>
                    </p>
                    <p class="text-on-surface-variant flex items-center gap-1.5">
                        <span>Your Answer:</span>
                        <span class="font-bold ${isItemCorrect ? 'text-mint' : (isAttempted ? 'text-error' : 'text-amber-300/80')}">${escapeHtml(isAttempted ? (matchedUserAns || 'Not Answered') : 'Not Attempted (उत्तर नहीं दिया)')}</span>
                    </p>
                </div>
            `;
                listEl.appendChild(card);
            });

            const totalQ = items.length;
            const statTotalEl = document.getElementById('stat-total');
            if (statTotalEl) statTotalEl.textContent = `${attemptedCount}/${totalQ}`;
            const statCorrectEl = document.getElementById('stat-correct');
            if (statCorrectEl) statCorrectEl.textContent = correctCount;
            const statTimeEl = document.getElementById('stat-time');
            if (statTimeEl) statTimeEl.textContent = totalTime.toFixed(3) + 's';
            const scoreSummaryEl = document.getElementById('score-summary');
            if (scoreSummaryEl) scoreSummaryEl.textContent = `Score: ${correctCount}/${totalQ}`;

            // Show leaderboard banner now that answer sheet is generated
            const lbWrap = document.getElementById('anssheet-leaderboard-wrap');
            if (lbWrap) {
                lbWrap.classList.remove('hidden');
                lbWrap.style.display = 'block';
            }

            // Note: Quiz Result & Leaderboard processing is automatically triggered once upon question answer time finish

            // Trigger Leaderboard status polling only after Answer Sheet has been generated
            if (typeof window.startLastQuizStatusPolling === 'function') {
                window.startLastQuizStatusPolling();
            }

            // Mark answer sheet as generated for direct future reloads
            try {
                sessionStorage.setItem('qx_anssheet_generated_' + quizId, 'true');
                localStorage.setItem('qx_anssheet_generated_' + quizId, 'true');
                sessionStorage.setItem('qx_completed_' + quizId, 'true');
                localStorage.setItem('qx_completed_' + quizId, 'true');
            } catch (e) {}

            // Note: Cache/Session cleanup will happen only after leaderboard is generated
        }

        function escapeHtml(str) {
            return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g,
                '&quot;');
        }
    })();
</script>
