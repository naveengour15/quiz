<main class="min-h-screen px-container-margin pt-6 pb-4 kbc-stage text-white relative" style="touch-action: pan-y !important; overflow-y: visible !important;">
    <div class="kbc-spotlight"></div>
    <div class="kbc-stage-rings"><span></span><span></span><span></span><span></span></div>

    <!-- Top Header Controls -->
    <div class="relative z-[1] flex items-center justify-between w-full mb-3">
        <a href="{{ route('fsrapp.home') }}" class="kbc-icon-badge flex items-center justify-center" title="Home">
            <span class="material-symbols-outlined text-[18px]">home</span>
        </a>
        <div id="fff-score-summary"
            class="font-label-caps bg-mint/20 px-3 py-1 rounded-full border border-mint/40 text-mint text-[11px] font-bold">
            Score: Evaluating...
        </div>
        <button type="button" id="kbc-mute-toggle" class="kbc-icon-badge" onclick="toggleGlobalMute()">
            <span class="material-symbols-outlined text-[18px]">volume_up</span>
        </button>
    </div>

    <!-- Center Answer Sheet Content -->
    <div class="relative z-[1] w-full max-w-md mx-auto space-y-4 pb-12">
        <div class="space-y-1 text-center">
            <h1 class="font-display text-xl font-black text-white kbc-title-glow uppercase tracking-wider">
                Your Performance Sheet
            </h1>
            <p class="font-body-md text-xs text-on-surface-variant">75 Seconds Speed Round Summary</p>
        </div>

        <!-- Summary Stats Card (Matching standard quiz design) -->
        <div class="glass-card rounded-2xl p-4 border border-white/10 flex items-center justify-around text-center">
            <div>
                <p class="font-label-caps text-[10px] text-on-surface-variant uppercase font-bold">TOTAL</p>
                <p id="fff-stat-total" class="font-display text-xl font-black text-white">--</p>
            </div>
            <div class="h-8 w-px bg-white/10"></div>
            <div>
                <p class="font-label-caps text-[10px] text-mint uppercase font-bold">CORRECT</p>
                <p id="fff-stat-correct" class="font-display text-xl font-black text-mint">--</p>
            </div>
            <div class="h-8 w-px bg-white/10"></div>
            <div>
                <p class="font-label-caps text-[10px] text-tertiary uppercase font-bold">TOTAL TIME</p>
                <p id="fff-stat-time" class="font-display text-xl font-black text-tertiary">--</p>
            </div>
        </div>

        <!-- ===== Last Quiz Leaderboard Component ===== -->
        <div id="fff-anssheet-leaderboard-wrap" class="hidden" style="display: none;">
            @include('fsrapp.home.components.last-quiz-leaderboard', [
                'lastQuizData' => $lastQuizData ?? [],
                'alwaysShow' => true,
            ])
        </div>

        <!-- Banner -->
        @include('fsrapp.home.components.banners', [
            'banners' => $banners,
            'pageType' => 'quiz_answer_sheet',
            'disableClick' => true,
        ])

        <!-- Question Answer Sheet Scrollable List -->
        <div id="fff-sheet-list" class="space-y-3 pr-0.5">
            <div class="p-6 text-center text-on-surface-variant text-sm">
                <span class="material-symbols-outlined animate-spin text-primary text-2xl mb-2">sync</span>
                <p>Loading your answer sheet...</p>
            </div>
        </div>

        <!-- Home Button (Matching standard quiz design) -->
        <div class="text-center pt-2 pb-6">
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
        let activeQuestionAudio = null;
        let activeQuestionBtn = null;
        let answerSheetInterval = null;
        let isFetchingSheet = false;

        function formatRemainingTime(sec) {
            if (sec <= 0) return '0s';
            const m = Math.floor(sec / 60);
            const s = sec % 60;
            if (m > 0) {
                return `${m}m ${s < 10 ? '0' : ''}${s}s`;
            }
            return `${s}s`;
        }

        window.loadFffAnswerSheet = function() {
            try {
                if (typeof window.releaseQuizProtection === 'function') window.releaseQuizProtection();
                document.documentElement.style.overflowY = 'auto';
                document.body.style.overflowY = 'auto';
                document.documentElement.style.overscrollBehaviorY = 'auto';
                document.body.style.overscrollBehaviorY = 'auto';
            } catch(e) {}

            if (window.checkAndHandleFffQuizExpiry && window.checkAndHandleFffQuizExpiry()) return;

            if (answerSheetInterval) {
                clearInterval(answerSheetInterval);
                answerSheetInterval = null;
            }

            if (window.stopAllQuizAudios) window.stopAllQuizAudios();

            // Prime history entry and back button listener for direct home redirect
            try {
                history.pushState({ stage: 'answer-sheet', quizId: window.fffQuizId }, document.title, location.href);
            } catch (e) {}

            window.addEventListener('popstate', function handleFffSheetPopstate(e) {
                window.location.replace('/fsrapp/home');
            });

            checkAndLoadAnswerSheet();
        };

        let isFffAnssheetRendered = false;

        function playFffAnssheetCountdownSound() {
            if (isFffAnssheetRendered) return;
            if (typeof window.isGlobalMuted === 'function' && window.isGlobalMuted()) return;
            try {
                const soundUrl = (typeof window.getSoundUrl === 'function' ? window.getSoundUrl('counter_sound') : null) ||
                    "https://chaloonline.s3.ap-south-1.amazonaws.com/sounds/counter_sound_1789460529.mp3";
                if (soundUrl && window.playQuizAudio) {
                    window.playQuizAudio(soundUrl, 0.75, true, 'sfx');
                }
            } catch(e) {}
        }

        function playFffAnssheetAppearSound() {
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

        window.playFffAnswerSheetSound = function() {
            if (isFffAnssheetRendered) {
                playFffAnssheetAppearSound();
            } else {
                playFffAnssheetCountdownSound();
            }
        };

        let prefetchedFffSheetData = null;
        let fffPrefetchPromise = null;

        function isFffSheetValidAndComplete(sheetObj, expectedCount) {
            if (!sheetObj || typeof sheetObj !== 'object') return false;
            const questions = sheetObj.questions;
            if (!Array.isArray(questions)) return false;
            if (expectedCount > 0 && questions.length < expectedCount) return false;
            if (questions.length === 0) return false;

            for (let i = 0; i < questions.length; i++) {
                const item = questions[i];
                if (!item || !item.question_id) return false;
                if (!item.question_text && !item.hindi_question) return false;
                if (!Array.isArray(item.options) || item.options.length < 2) return false;
                if (item.correct_answer === undefined || item.correct_answer === null || String(item.correct_answer).trim() === '') {
                    return false;
                }
            }
            return true;
        }

        window.prefetchFffAnswerSheet = function() {
            let clientAnswers = {};
            try {
                const ls = localStorage.getItem('fff_anssheet_' + window.fffQuizId);
                if (ls) Object.assign(clientAnswers, JSON.parse(ls) || {});
            } catch (e) {}
            try {
                const ss = sessionStorage.getItem('fff_anssheet_' + window.fffQuizId);
                if (ss) Object.assign(clientAnswers, JSON.parse(ss) || {});
            } catch (e) {}
            if (window.initialUserAnswers) Object.assign(clientAnswers, window.initialUserAnswers);
            if (window.__fffClientAnswers && typeof window.__fffClientAnswers === 'object') {
                Object.assign(clientAnswers, window.__fffClientAnswers);
            }
            const clientAttemptCount = Object.keys(clientAnswers).length;

            if (prefetchedFffSheetData && prefetchedFffSheetData._isServerVerified) {
                const pAttempts = (prefetchedFffSheetData.total_attempted !== undefined) ? prefetchedFffSheetData.total_attempted : (prefetchedFffSheetData.questions ? prefetchedFffSheetData.questions.filter(q => q.user_answer).length : 0);
                if (!clientAttemptCount || pAttempts >= clientAttemptCount) {
                    return Promise.resolve(prefetchedFffSheetData);
                }
            }

            // 1. Authoritative server sheet data from DB (only if complete and not stale)
            if (window.initialSheetData && (window.initialSheetData.success || window.initialSheetData.data || window.initialSheetData.questions)) {
                const sheetObj = window.initialSheetData.data || window.initialSheetData;
                if (sheetObj && (sheetObj.questions || Array.isArray(sheetObj.questions))) {
                    const sheetAttempts = (sheetObj.total_attempted !== undefined) ? sheetObj.total_attempted : (sheetObj.questions.filter(q => q.user_answer).length);
                    if (!clientAttemptCount || sheetAttempts >= clientAttemptCount) {
                        sheetObj._isServerVerified = true;
                        prefetchedFffSheetData = sheetObj;
                        return Promise.resolve(prefetchedFffSheetData);
                    }
                }
            }

            if (fffPrefetchPromise) return fffPrefetchPromise;

            const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
            const hasAnswers = Object.keys(clientAnswers).length > 0;

            fffPrefetchPromise = fetch('/fsrapp/fff/api/answer-sheet/' + window.fffQuizId, {
                method: hasAnswers ? 'POST' : 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf || '',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                ...(hasAnswers ? { body: JSON.stringify({ client_answers: clientAnswers }) } : {})
            })
            .then(res => res.json())
            .then(json => {
                if (json && (json.data || json.questions)) {
                    const dataObj = json.data || json;
                    dataObj._isServerVerified = true;
                    prefetchedFffSheetData = dataObj;
                    return dataObj;
                }
                return null;
            })
            .catch(err => {
                console.warn('[FFF Answer Sheet] Prefetch error:', err);
                return null;
            });

            return fffPrefetchPromise;
        };

        function showWaitingState(targetReleaseMs) {
            if (answerSheetInterval) clearInterval(answerSheetInterval);
            isFffAnssheetRendered = false;

            playFffAnssheetCountdownSound();

            const lbWrap = document.getElementById('fff-anssheet-leaderboard-wrap');
            if (lbWrap) {
                lbWrap.classList.add('hidden');
                lbWrap.style.display = 'none';
            }

            const listEl = document.getElementById('fff-sheet-list');
            const scoreEl = document.getElementById('fff-score-summary');
            if (scoreEl) scoreEl.textContent = 'Score: Evaluating...';

            const statTotal = document.getElementById('fff-stat-total');
            const statCorrect = document.getElementById('fff-stat-correct');
            const statTime = document.getElementById('fff-stat-time');
            if (statTotal) statTotal.textContent = '--';
            if (statCorrect) statCorrect.textContent = '--';
            if (statTime) statTime.textContent = '--';

            const maxCountdown = 30;
            const sched = typeof window.getFffQuizScheduleTimeMs === 'function' ? window.getFffQuizScheduleTimeMs() : Date.now();
            const accurateReleaseMs = sched + 125000; // 10s start + 75s questions + 10s review + 30s counter = 125s
            const effectiveTargetMs = (targetReleaseMs && targetReleaseMs > 1000000000 && Math.abs(targetReleaseMs - accurateReleaseMs) < 15000)
                ? accurateReleaseMs
                : (targetReleaseMs || accurateReleaseMs);

            let currentMode = null; // 'processing' | 'countdown'

            function updateWaitingUi() {
                const nowServerTime = Date.now() + (window.clockOffset || 0);
                const diffMs = effectiveTargetMs - nowServerTime;
                const totalRemainingSec = Math.ceil(diffMs / 1000);

                if (diffMs <= 0 || totalRemainingSec <= 0) {
                    if (answerSheetInterval) {
                        clearInterval(answerSheetInterval);
                        answerSheetInterval = null;
                    }

                    if (typeof window.finalizeAndValidateFffAnssheet === 'function') {
                        window.finalizeAndValidateFffAnssheet();
                    }

                    // Check client-recorded answers count to guard against stale partial sheet
                    let clientAnswers = {};
                    try {
                        const ls = localStorage.getItem('fff_anssheet_' + window.fffQuizId);
                        if (ls) Object.assign(clientAnswers, JSON.parse(ls) || {});
                    } catch (e) {}
                    try {
                        const ss = sessionStorage.getItem('fff_anssheet_' + window.fffQuizId);
                        if (ss) Object.assign(clientAnswers, JSON.parse(ss) || {});
                    } catch (e) {}
                    if (window.initialUserAnswers) Object.assign(clientAnswers, window.initialUserAnswers);
                    if (window.__fffClientAnswers) Object.assign(clientAnswers, window.__fffClientAnswers);
                    const clientAttempts = Object.keys(clientAnswers).length;

                    // 1. Authoritative server sheet data from DB (only if complete and not stale)
                    if (window.initialSheetData && (window.initialSheetData.success || window.initialSheetData.data || window.initialSheetData.questions)) {
                        const sheetObj = window.initialSheetData.data || window.initialSheetData;
                        if (sheetObj && (sheetObj.questions || Array.isArray(sheetObj.questions))) {
                            const sheetAttempts = (sheetObj.total_attempted !== undefined) ? sheetObj.total_attempted : (sheetObj.questions.filter(q => q.user_answer).length);
                            if (!clientAttempts || sheetAttempts >= clientAttempts) {
                                renderFffSheetData(sheetObj);
                                return;
                            }
                        }
                    }

                    if (prefetchedFffSheetData && prefetchedFffSheetData._isServerVerified && prefetchedFffSheetData.questions) {
                        const prefetchedAttempts = (prefetchedFffSheetData.total_attempted !== undefined) ? prefetchedFffSheetData.total_attempted : (prefetchedFffSheetData.questions.filter(q => q.user_answer).length);
                        if (!clientAttempts || prefetchedAttempts >= clientAttempts) {
                            renderFffSheetData(prefetchedFffSheetData);
                            return;
                        }
                    }

                    const expectedTotal = window.totalQuestionsCount || (window.fffQuestions ? window.fffQuestions.length : 0);
                    let localSheet = null;
                    try {
                        const raw = sessionStorage.getItem('fff_compiled_sheet_' + window.fffQuizId) ||
                                    localStorage.getItem('fff_compiled_sheet_' + window.fffQuizId);
                        if (raw) localSheet = JSON.parse(raw);
                    } catch (e) {}

                    if (isFffSheetValidAndComplete(localSheet, expectedTotal) && (localSheet.total_attempted > 0 || !window.isFffUserCompleted())) {
                        renderFffSheetData(localSheet);
                        fetchAnswerSheet(true);
                        return;
                    }

                    if (listEl) {
                        listEl.innerHTML = `
                            <div class="p-6 text-center text-on-surface-variant text-sm space-y-2">
                                <span class="material-symbols-outlined animate-spin text-primary text-2xl mb-2">sync</span>
                                <p class="text-white font-medium">Loading your answer sheet...</p>
                            </div>
                        `;
                    }

                    if (fffPrefetchPromise) {
                        fffPrefetchPromise.then(data => {
                            if (data) {
                                renderFffSheetData(data);
                            } else {
                                fetchAnswerSheet();
                            }
                        }).catch(() => {
                            fetchAnswerSheet();
                        });
                    } else {
                        fetchAnswerSheet();
                    }
                    return;
                }

                // 1. If user submitted all answers early before 60s (> 30s remaining): show Processing state
                if (totalRemainingSec > maxCountdown) {
                    if (currentMode !== 'processing') {
                        currentMode = 'processing';
                        if (listEl) {
                            listEl.innerHTML = `
                                <div class="glass-card kbc-card rounded-2xl p-6 text-center space-y-3 border border-amber-400/30 bg-amber-500/5 shadow-[0_0_20px_rgba(245,158,11,0.15)]">
                                    <div class="relative w-14 h-14 mx-auto flex items-center justify-center">
                                        <span class="material-symbols-outlined animate-spin text-amber-400 text-4xl">sync</span>
                                    </div>
                                    <h3 class="font-display text-base font-bold text-amber-200">Processing Submissions</h3>
                                    <p class="text-xs text-on-surface-variant leading-relaxed">
                                        Quiz window in progress. Evaluating answers from all participants...
                                    </p>
                                    <div class="pt-2">
                                        <span class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-amber-500/20 border border-amber-400/40 text-amber-300 font-display text-xs font-bold tracking-wider shadow-inner">
                                            <span class="inline-block w-2 h-2 rounded-full bg-amber-400 animate-ping"></span>
                                            Evaluating answers...
                                        </span>
                                    </div>
                                </div>
                            `;
                        }
                    }
                    return;
                }

                // 2. Final 30 seconds Countdown Window (30s -> 0s)
                const remainingSec = Math.max(1, Math.min(maxCountdown, totalRemainingSec));

                if (currentMode !== 'countdown') {
                    currentMode = 'countdown';
                    playFffAnssheetCountdownSound();
                    if (listEl) {
                        listEl.innerHTML = `
                            <div class="glass-card kbc-card rounded-2xl p-6 text-center space-y-3 border border-amber-400/30 bg-amber-500/5 shadow-[0_0_20px_rgba(245,158,11,0.15)]">
                                <div class="relative w-14 h-14 mx-auto flex items-center justify-center">
                                    <span class="material-symbols-outlined animate-spin text-amber-400 text-4xl">hourglass_top</span>
                                </div>
                                <h3 class="font-display text-base font-bold text-amber-200">Quiz Window In Progress</h3>
                                <p class="text-xs text-on-surface-variant leading-relaxed">
                                    Answer sheet will be released once countdown completes.
                                </p>
                                <div class="pt-2">
                                    <span class="inline-block px-4 py-1.5 rounded-full bg-amber-500/20 border border-amber-400/40 text-amber-300 font-display text-lg font-black tracking-wider shadow-inner">
                                        Releasing in <span id="fff-release-timer-val">${remainingSec}s</span>
                                    </span>
                                </div>
                            </div>
                        `;
                    }
                }

                const timerValEl = document.getElementById('fff-release-timer-val');
                if (timerValEl) {
                    timerValEl.textContent = `${remainingSec}s`;
                }
            }

            updateWaitingUi();
            answerSheetInterval = setInterval(updateWaitingUi, 1000);
            if (window.FffSpa && typeof window.FffSpa.registerTimer === 'function') {
                window.FffSpa.registerTimer(answerSheetInterval);
            }
        }

        function checkAndLoadAnswerSheet() {
            const currentServerTime = Date.now() + (window.clockOffset || 0);
            const sched = typeof window.getFffQuizScheduleTimeMs === 'function' ? window.getFffQuizScheduleTimeMs() : Date.now();
            const calculatedReleaseMs = sched + 125000;
            const anssheetReleaseTimeMs = (window.anssheetReleaseTimeMs && window.anssheetReleaseTimeMs > 1000000000) ? window.anssheetReleaseTimeMs : calculatedReleaseMs;
            const quizCompleteStatus = window.quizCompleteStatus;

            // 1. If countdown has already elapsed or quiz is completed, show immediately
            if (quizCompleteStatus === 'completed' || currentServerTime >= anssheetReleaseTimeMs) {
                if (typeof window.finalizeAndValidateFffAnssheet === 'function') {
                    window.finalizeAndValidateFffAnssheet();
                }

                // Check client-recorded answers count to guard against stale partial sheet
                let clientAnswers = {};
                try {
                    const ls = localStorage.getItem('fff_anssheet_' + window.fffQuizId);
                    if (ls) Object.assign(clientAnswers, JSON.parse(ls) || {});
                } catch (e) {}
                try {
                    const ss = sessionStorage.getItem('fff_anssheet_' + window.fffQuizId);
                    if (ss) Object.assign(clientAnswers, JSON.parse(ss) || {});
                } catch (e) {}
                if (window.initialUserAnswers) Object.assign(clientAnswers, window.initialUserAnswers);
                if (window.__fffClientAnswers) Object.assign(clientAnswers, window.__fffClientAnswers);
                const clientAttempts = Object.keys(clientAnswers).length;

                // 1. Authoritative server sheet data from DB (only if complete and not stale)
                if (window.initialSheetData && (window.initialSheetData.success || window.initialSheetData.data || window.initialSheetData.questions)) {
                    const sheetObj = window.initialSheetData.data || window.initialSheetData;
                    if (sheetObj && (sheetObj.questions || Array.isArray(sheetObj.questions))) {
                        const sheetAttempts = (sheetObj.total_attempted !== undefined) ? sheetObj.total_attempted : (sheetObj.questions.filter(q => q.user_answer).length);
                        if (!clientAttempts || sheetAttempts >= clientAttempts) {
                            renderFffSheetData(sheetObj);
                            return;
                        }
                    }
                }

                if (prefetchedFffSheetData && prefetchedFffSheetData._isServerVerified && prefetchedFffSheetData.questions) {
                    const prefetchedAttempts = (prefetchedFffSheetData.total_attempted !== undefined) ? prefetchedFffSheetData.total_attempted : (prefetchedFffSheetData.questions.filter(q => q.user_answer).length);
                    if (!clientAttempts || prefetchedAttempts >= clientAttempts) {
                        renderFffSheetData(prefetchedFffSheetData);
                        return;
                    }
                }

                const expectedTotal = window.totalQuestionsCount || (window.fffQuestions ? window.fffQuestions.length : 0);
                let localSheet = null;
                try {
                    const raw = sessionStorage.getItem('fff_compiled_sheet_' + window.fffQuizId) ||
                                localStorage.getItem('fff_compiled_sheet_' + window.fffQuizId);
                    if (raw) localSheet = JSON.parse(raw);
                } catch (e) {}

                if (isFffSheetValidAndComplete(localSheet, expectedTotal) && (localSheet.total_attempted > 0 || !window.isFffUserCompleted())) {
                    renderFffSheetData(localSheet);
                    fetchAnswerSheet(true);
                    return;
                }

                fetchAnswerSheet();
                return;
            }

            // 2. Countdown is active: Show countdown waiting card & hold answers until 0s (even if reloaded)
            showWaitingState(anssheetReleaseTimeMs);
        }

        async function fetchAnswerSheet(backgroundSync = false) {
            let clientAnswers = {};
            try {
                const ls = localStorage.getItem('fff_anssheet_' + window.fffQuizId);
                if (ls) Object.assign(clientAnswers, JSON.parse(ls) || {});
            } catch (e) {}
            try {
                const ss = sessionStorage.getItem('fff_anssheet_' + window.fffQuizId);
                if (ss) Object.assign(clientAnswers, JSON.parse(ss) || {});
            } catch (e) {}
            if (window.initialUserAnswers) Object.assign(clientAnswers, window.initialUserAnswers);
            if (window.__fffClientAnswers) Object.assign(clientAnswers, window.__fffClientAnswers);
            const clientAttemptCount = Object.keys(clientAnswers).length;

            if (!backgroundSync && prefetchedFffSheetData && prefetchedFffSheetData._isServerVerified && prefetchedFffSheetData.questions) {
                const pAttempts = (prefetchedFffSheetData.total_attempted !== undefined) ? prefetchedFffSheetData.total_attempted : (prefetchedFffSheetData.questions.filter(q => q.user_answer).length);
                if (!clientAttemptCount || pAttempts >= clientAttemptCount) {
                    renderFffSheetData(prefetchedFffSheetData);
                    return;
                }
            }
            if (isFetchingSheet) return;
            isFetchingSheet = true;

            const listContainer = document.getElementById('fff-sheet-list');

            const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
            const hasAnswers = Object.keys(clientAnswers).length > 0;

            try {
                const res = await fetch('/fsrapp/fff/api/answer-sheet/' + window.fffQuizId, {
                    method: hasAnswers ? 'POST' : 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf || '',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    ...(hasAnswers ? { body: JSON.stringify({ client_answers: clientAnswers }) } : {})
                });
                const json = await res.json();

                if (json && json.quiz_expired) {
                    if (window.checkAndHandleFffQuizExpiry && window.checkAndHandleFffQuizExpiry()) {
                        return;
                    }
                }

                if (json && json.locked) {
                    if (json.data) {
                        prefetchedFffSheetData = json.data;
                    }
                    isFetchingSheet = false;
                    const waitSec = Math.max(1, json.remaining_seconds || 1);
                    const nowServerTime = Date.now() + (window.clockOffset || 0);
                    const newReleaseMs = json.release_time_ms || (nowServerTime + waitSec * 1000);
                    showWaitingState(newReleaseMs);
                    return;
                }

                if (json && (json.success || json.data)) {
                    const freshData = json.data || json;
                    if (freshData && freshData.questions) {
                        freshData._isServerVerified = true;
                        prefetchedFffSheetData = freshData;
                        window.initialSheetData = freshData;
                        try {
                            const str = JSON.stringify(freshData);
                            localStorage.setItem('fff_compiled_sheet_' + window.fffQuizId, str);
                            sessionStorage.setItem('fff_compiled_sheet_' + window.fffQuizId, str);
                        } catch(e) {}
                        renderFffSheetData(freshData);
                    }
                } else if (prefetchedFffSheetData) {
                    renderFffSheetData(prefetchedFffSheetData);
                }
            } catch(e) {
                console.error('Error fetching FFF answer sheet', e);
                if (prefetchedFffSheetData) {
                    renderFffSheetData(prefetchedFffSheetData);
                } else if (!window.initialSheetData && listContainer) {
                    listContainer.innerHTML = '<div class="p-4 text-center text-error text-xs">Loading answer sheet. Please wait...</div>';
                    setTimeout(fetchAnswerSheet, 2000);
                }
            } finally {
                isFetchingSheet = false;
            }
        }

        function resolveMatchingOption(targetVal, options, hindiTargetVal) {
            if ((targetVal === undefined || targetVal === null || targetVal === '') &&
                (hindiTargetVal === undefined || hindiTargetVal === null || hindiTargetVal === '')) {
                return '';
            }

            const primaryVal = (targetVal !== undefined && targetVal !== null && targetVal !== '') ? targetVal : hindiTargetVal;
            if (!options || !Array.isArray(options) || options.length === 0) return String(primaryVal);

            const targetStr = String(primaryVal).replace(/[\u00A0\u1680\u180E\u2000-\u200B\u202F\u205F\u3000]/g, ' ').trim();
            const normTarget = targetStr.replace(/\s+/g, ' ').toLowerCase();

            const hTargetStr = (hindiTargetVal !== undefined && hindiTargetVal !== null) ? String(hindiTargetVal).replace(/[\u00A0\u1680\u180E\u2000-\u200B\u202F\u205F\u3000]/g, ' ').trim() : '';
            const normHTarget = hTargetStr.replace(/\s+/g, ' ').toLowerCase();

            // 1. Direct match on option items
            for (let opt of options) {
                let optText = '';
                let optEng = '';
                let optHindi = '';
                let optKey = '';

                if (typeof opt === 'object' && opt !== null) {
                    optText = String(opt.text ?? opt.option_text ?? '').trim();
                    optEng = String(opt.english ?? '').trim();
                    optHindi = String(opt.hindi ?? opt.hindi_text ?? '').trim();
                    optKey = String(opt.key ?? opt.label ?? '').trim();
                } else {
                    optText = String(opt).trim();
                }

                const cleanOpt = optText.replace(/[\u00A0\u1680\u180E\u2000-\u200B\u202F\u205F\u3000]/g, ' ').trim();
                const normOpt = cleanOpt.replace(/\s+/g, ' ').toLowerCase();
                const normEng = optEng.toLowerCase();
                const normHindi = optHindi.toLowerCase();
                const normKey = optKey.toLowerCase();

                if (normTarget === normOpt || (normEng !== '' && normTarget === normEng) || (normHindi !== '' && normTarget === normHindi) || (normKey !== '' && normTarget === normKey)) {
                    return cleanOpt;
                }
                if (normHTarget !== '' && (normHTarget === normOpt || normHTarget === normHindi || normHTarget === normEng)) {
                    return cleanOpt;
                }
            }

            // 2. Option letter/key match: 'a', 'b', 'c', 'd', 'e', 'f'
            const cleanedLetter = normTarget.replace(/^(?:option|opt)[\s_]*/i, '').trim();
            const letters = { 'a': 0, 'b': 1, 'c': 2, 'd': 3, 'e': 4, 'f': 5 };
            if (letters.hasOwnProperty(cleanedLetter) && options[letters[cleanedLetter]] !== undefined) {
                const opt = options[letters[cleanedLetter]];
                return typeof opt === 'object' && opt !== null ? (opt.text ?? opt.option_text ?? JSON.stringify(opt)) : String(opt);
            }

            // 3. Numeric index
            if (/^\d+$/.test(cleanedLetter)) {
                const idx = parseInt(cleanedLetter, 10);
                if (options[idx] !== undefined) {
                    const opt = options[idx];
                    return typeof opt === 'object' && opt !== null ? (opt.text ?? opt.option_text ?? JSON.stringify(opt)) : String(opt);
                }
                if (idx > 0 && options[idx - 1] !== undefined) {
                    const opt = options[idx - 1];
                    return typeof opt === 'object' && opt !== null ? (opt.text ?? opt.option_text ?? JSON.stringify(opt)) : String(opt);
                }
            }

            // 4. Base match without parenthetical
            const targetBase = targetStr.replace(/\s*\(.*?\)\s*$/g, '').trim().toLowerCase();
            for (let opt of options) {
                const optText = typeof opt === 'object' && opt !== null ? (opt.text ?? opt.option_text ?? JSON.stringify(opt)) : String(opt);
                const cleanOpt = String(optText).replace(/[\u00A0\u1680\u180E\u2000-\u200B\u202F\u205F\u3000]/g, ' ').trim();
                const optBase = cleanOpt.replace(/\s*\(.*?\)\s*$/g, '').trim().toLowerCase();
                if (targetBase !== '' && (targetBase === optBase || targetBase === cleanOpt.toLowerCase())) {
                    return cleanOpt;
                }
            }

            // If target has separate Hindi and English, merge them as "Hindi (English)"
            if (hTargetStr !== '' && targetStr !== '' && hTargetStr.toLowerCase() !== targetStr.toLowerCase()) {
                return `${hTargetStr} (${targetStr})`;
            }

            return targetStr || hTargetStr;
        }

        function renderFffSheetData(data) {
            if (!data) return;

            const questionsList = data.questions || [];
            const totalQ = data.total_questions || questionsList.length || (window.totalQuestionsCount || 15);

            let correctCount = 0;
            let attemptedCount = 0;
            let totalTime = 0.0;

            const listContainer = document.getElementById('fff-sheet-list');
            if (listContainer) listContainer.innerHTML = '';

            if (questionsList.length === 0) {
                if (listContainer) {
                    listContainer.innerHTML = '<div class="p-6 text-center text-on-surface-variant text-sm"><p>No questions found for this quiz.</p></div>';
                }
                return;
            }

            // Stop counter sound and play answer sheet appear sound
            isFffAnssheetRendered = true;
            if (answerSheetInterval) {
                clearInterval(answerSheetInterval);
                answerSheetInterval = null;
            }
            playFffAnssheetAppearSound();

            // Merge client-recorded answers into questionsList to ensure zero missing answers
            let clientAnswers = {};
            try {
                const ls = localStorage.getItem('fff_anssheet_' + window.fffQuizId);
                if (ls) Object.assign(clientAnswers, JSON.parse(ls) || {});
            } catch (e) {}
            try {
                const ss = sessionStorage.getItem('fff_anssheet_' + window.fffQuizId);
                if (ss) Object.assign(clientAnswers, JSON.parse(ss) || {});
            } catch (e) {}
            if (window.initialUserAnswers) Object.assign(clientAnswers, window.initialUserAnswers);
            if (window.__fffClientAnswers) Object.assign(clientAnswers, window.__fffClientAnswers);

            questionsList.forEach(function(q, idx) {
                const qId = q.question_id || q.id;
                const clientAnsObj = clientAnswers[qId] || clientAnswers[String(qId)] || (q.id ? (clientAnswers[q.id] || clientAnswers[String(q.id)]) : null);
                if ((q.user_answer === null || q.user_answer === undefined || String(q.user_answer).trim() === '') && clientAnsObj && clientAnsObj.answer) {
                    q.user_answer = clientAnsObj.answer;
                    q.is_attempted = true;
                    if (!q.total_time && clientAnsObj.total_time) {
                        q.total_time = clientAnsObj.total_time;
                    }
                }

                const isAttempted = q.is_attempted === true || (q.user_answer !== null && q.user_answer !== undefined && String(q.user_answer).trim() !== '');
                const optionsArr = q.options || [];
                const matchedUserAns = isAttempted ? resolveMatchingOption(q.user_answer, optionsArr, null) : null;
                const matchedCorrectAns = resolveMatchingOption(q.correct_answer, optionsArr, q.hindi_correct_answer);

                let isItemCorrect = isAttempted && !!q.is_correct;
                if (!isItemCorrect && isAttempted && (q.correct_answer || q.hindi_correct_answer) && q.user_answer) {
                    const normU = String(matchedUserAns || q.user_answer).replace(/[\u00A0\u1680\u180E\u2000-\u200B\u202F\u205F\u3000]/g, ' ').replace(/\s+/g, ' ').trim().toLowerCase();
                    const normC = String(matchedCorrectAns || q.correct_answer || '').replace(/[\u00A0\u1680\u180E\u2000-\u200B\u202F\u205F\u3000]/g, ' ').replace(/\s+/g, ' ').trim().toLowerCase();
                    const normHC = String(q.hindi_correct_answer || '').replace(/[\u00A0\u1680\u180E\u2000-\u200B\u202F\u205F\u3000]/g, ' ').replace(/\s+/g, ' ').trim().toLowerCase();

                    if (normU === normC || (normHC !== '' && normU === normHC)) {
                        isItemCorrect = true;
                    } else {
                        const uBase = normU.replace(/\s*\(.*?\)\s*$/g, '').trim();
                        const cBase = normC.replace(/\s*\(.*?\)\s*$/g, '').trim();
                        const hcBase = normHC.replace(/\s*\(.*?\)\s*$/g, '').trim();
                        if ((uBase && cBase && uBase === cBase) || (uBase && hcBase && uBase === hcBase)) {
                            isItemCorrect = true;
                        }
                    }
                }

                if (isAttempted) {
                    attemptedCount++;
                    totalTime += parseFloat(q.total_time || q.time_taken || 0);
                }
                if (isItemCorrect) {
                    correctCount++;
                }

                const statusLabel = isItemCorrect ? 'Correct' : (isAttempted ? 'Incorrect' : 'Not Attempted');
                const statusColor = isItemCorrect ? 'text-mint' : (isAttempted ? 'text-error' : 'text-amber-300');
                const statusIcon = isItemCorrect ? 'check_circle' : (isAttempted ? 'cancel' : 'help_outline');

                const card = document.createElement('div');
                card.className = `glass-card kbc-card rounded-2xl p-4 space-y-2 border ${
                    isItemCorrect ? 'border-mint/40 bg-mint/5' : (isAttempted ? 'border-error/40 bg-error/5' : 'border-amber-500/30 bg-amber-500/5')
                }`;

                let questionHtml = '';
                if (q.hindi_question && String(q.hindi_question).trim().length > 0) {
                    questionHtml = `
                        <p class="font-headline-md text-sm font-semibold text-white leading-snug">${escapeHtml(String(q.hindi_question).trim())}</p>
                        ${q.question_text ? `<p class="text-amber-200/90 text-xs font-medium mt-1 pt-1 border-t border-white/10 leading-relaxed font-sans">${escapeHtml(q.question_text)}</p>` : ''}
                    `;
                } else {
                    questionHtml = `
                        <p class="font-headline-md text-sm font-semibold text-white leading-snug">${escapeHtml(q.question_text || ('Question ' + (idx + 1)))}</p>
                    `;
                }

                let audioBtnHtml = '';
                const qAudio = q.question_audio || q.audio_url || null;
                if (qAudio && String(qAudio).trim().length > 0) {
                    audioBtnHtml = `
                        <button type="button" class="q-audio-play-btn inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-primary/20 hover:bg-primary/30 border border-primary/40 text-primary transition-all active:scale-95 text-xs font-bold shrink-0 cursor-pointer shadow-sm" data-audio-url="${escapeHtml(qAudio)}" onclick="window.toggleFffQuestionSheetAudio(this)" title="Play Question Audio">
                            <span class="material-symbols-outlined text-[15px] q-audio-icon">volume_up</span>
                            <span class="q-audio-label text-[10px] uppercase font-bold tracking-wider">Audio</span>
                        </button>
                    `;
                }

                let mediaImgHtml = '';
                const qMedia = q.media_url || q.image_url || null;
                if (qMedia && String(qMedia).trim().length > 0 && !/\.(mp3|wav|ogg|m4a|aac)$/i.test(qMedia)) {
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
                            <span class="font-label-caps text-[10px] text-on-surface-variant font-mono bg-white/5 px-2 py-0.5 rounded border border-white/5">${isAttempted ? (q.total_time ? Number(q.total_time).toFixed(3) + 's' : '-') : 'Skipped'}</span>
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

                listContainer.appendChild(card);
            });

            // Update stats cards and score badge after re-verifying all items
            const finalCorrect = (data.total_correct !== undefined && data.total_correct >= correctCount) ? data.total_correct : correctCount;
            const finalAttempted = (data.total_attempted !== undefined && data.total_attempted >= attemptedCount) ? data.total_attempted : attemptedCount;
            const finalTime = (data.total_time !== undefined && parseFloat(data.total_time) > 0) ? parseFloat(data.total_time).toFixed(3) : totalTime.toFixed(3);

            const statTotal = document.getElementById('fff-stat-total');
            const statCorrect = document.getElementById('fff-stat-correct');
            const statTime = document.getElementById('fff-stat-time');
            const scoreSummary = document.getElementById('fff-score-summary');

            if (statTotal) statTotal.textContent = `${finalAttempted}/${totalQ}`;
            if (statCorrect) statCorrect.textContent = finalCorrect;
            if (statTime) statTime.textContent = `${finalTime}s`;
            if (scoreSummary) scoreSummary.textContent = `Score: ${finalCorrect}/${totalQ}`;

            // Show leaderboard banner now that answer sheet is generated
            const lbWrap = document.getElementById('fff-anssheet-leaderboard-wrap');
            if (lbWrap) {
                lbWrap.classList.remove('hidden');
                lbWrap.style.display = 'block';
            }

            // Trigger Leaderboard status polling only after Answer Sheet has been generated
            if (typeof window.startLastQuizStatusPolling === 'function') {
                window.startLastQuizStatusPolling();
            }
        }

        window.toggleFffQuestionSheetAudio = function(btn) {
            const audioUrl = btn.getAttribute('data-audio-url');
            if (!audioUrl) return;

            if (activeQuestionBtn === btn && activeQuestionAudio && !activeQuestionAudio.paused) {
                window.stopFffQuestionSheetAudio();
                return;
            }

            window.stopFffQuestionSheetAudio();

            try {
                const audio = new Audio(audioUrl);
                audio.volume = 1.0;
                activeQuestionAudio = audio;
                activeQuestionBtn = btn;

                btn.classList.add('playing', 'bg-primary', 'text-on-primary', 'shadow-[0_0_15px_rgba(168,85,247,0.7)]', 'animate-pulse');
                btn.classList.remove('bg-primary/20', 'text-primary');
                const icon = btn.querySelector('.q-audio-icon');
                if (icon) icon.textContent = 'pause';
                const label = btn.querySelector('.q-audio-label');
                if (label) label.textContent = 'Playing';

                audio.play().catch(function() {
                    window.stopFffQuestionSheetAudio();
                });

                audio.onended = window.stopFffQuestionSheetAudio;
                audio.onerror = window.stopFffQuestionSheetAudio;
            } catch(e) {
                window.stopFffQuestionSheetAudio();
            }
        };

        window.stopFffQuestionSheetAudio = function() {
            if (activeQuestionAudio) {
                try {
                    activeQuestionAudio.pause();
                    activeQuestionAudio.currentTime = 0;
                } catch(e) {}
                activeQuestionAudio = null;
            }
            if (activeQuestionBtn) {
                activeQuestionBtn.classList.remove('playing', 'bg-primary', 'text-on-primary', 'shadow-[0_0_15px_rgba(168,85,247,0.7)]', 'animate-pulse');
                activeQuestionBtn.classList.add('bg-primary/20', 'text-primary');
                const icon = activeQuestionBtn.querySelector('.q-audio-icon');
                if (icon) icon.textContent = 'volume_up';
                const label = activeQuestionBtn.querySelector('.q-audio-label');
                if (label) label.textContent = 'Audio';
                activeQuestionBtn = null;
            }
        };

        function handleResumeOnAnswerSheet() {
            if (window.FffSpa && window.FffSpa.currentStage !== 'answer-sheet') return;
            const hasCards = document.querySelectorAll('#fff-sheet-list .kbc-card').length > 0;
            if (!hasCards && !isFetchingSheet) {
                checkAndLoadAnswerSheet();
            }
        }

        document.addEventListener('visibilitychange', function() {
            if (document.visibilityState === 'visible') {
                handleResumeOnAnswerSheet();
            }
        });
        window.addEventListener('pageshow', handleResumeOnAnswerSheet);
        window.addEventListener('focus', handleResumeOnAnswerSheet);

        function escapeHtml(str) {
            return String(str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        window.clearCompletedQuizCacheAndSession = function() {
            // Only clear session when quiz is completed
            if (window.quizCompleteStatus !== 'completed') {
                return;
            }

            try {
                // 1. Remove Temporary FFF Client Storage
                sessionStorage.removeItem('fff_answered_ids_' + window.fffQuizId);
                localStorage.removeItem('fff_answered_ids_' + window.fffQuizId);
                sessionStorage.removeItem('fff_attempted_' + window.fffQuizId);
                localStorage.removeItem('fff_attempted_' + window.fffQuizId);

                const clearKey = 'fff_session_cleared_' + window.fffQuizId;
                if (sessionStorage.getItem(clearKey)) return;
                sessionStorage.setItem(clearKey, '1');

                // 2. Request Backend Cache Cleanup
                const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
                fetch('/fsrapp/fff/api/clear-session/' + window.fffQuizId, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf || '',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                }).catch(() => {});
            } catch (e) {
                console.warn('[FFF Quiz Complete] Cache/Session cleanup error:', e);
            }
        };

        // Immediately start prefetching upon script load if on review or answer-sheet stage
        try {
            const currentStage = window.initialStage || (window.FffSpa ? window.FffSpa.currentStage : null);
            if (currentStage === 'answer-sheet' || currentStage === 'review-counter' || window.__isFffCompleted) {
                window.prefetchFffAnswerSheet();
            }
        } catch(e) {}
    })();
</script>
