<main class="min-h-screen px-container-margin pt-4 pb-20 flex flex-col kbc-stage text-white relative">
    <div class="kbc-spotlight"></div>
    <div class="kbc-stage-rings"><span></span><span></span><span></span><span></span></div>

    <!-- Top Header Controls -->
    <div class="relative z-[1] flex items-center justify-between w-full mb-2">
        <div></div>
        <div class="flex items-center gap-2">
            <span
                class="font-label-caps bg-primary/20 px-3 py-1 rounded-full border border-primary/40 text-primary text-[11px] font-bold">
                Q <span id="q-num">1</span> / <span id="q-total">{{ $quiz->question_limit ?? 10 }}</span>
            </span>
            <span id="phase-badge"
                class="font-label-caps bg-tertiary/20 text-tertiary px-3 py-1 rounded-full border border-tertiary/40 text-[11px] font-bold">
                Question Appearing
            </span>
        </div>
        <button type="button" id="kbc-mute-toggle" class="kbc-icon-badge" onclick="toggleGlobalMute()">
            <span class="material-symbols-outlined text-[18px]">volume_up</span>
        </button>
    </div>

    <!-- Top Refresh & Rules Warning Note -->
    {{-- <div class="relative z-[2] w-full max-w-md mx-auto mb-1">
        <div
            class="px-3 py-1.5 rounded-xl bg-gradient-to-r from-red-500/20 via-amber-500/25 to-red-500/20 border border-amber-400/80 text-center shadow-[0_0_12px_rgba(245,158,11,0.25)] space-y-0.5">

            <p class="text-[10px] text-[#ffe9b0] font-bold leading-tight">
                <span class="text-sm">⚠️</span> Refresh, Back Button, Tab Switch ya 5 second se jyada Internet band rehne par <strong>Quiz se OUT</strong> kar diya jayega!
            </p>
        </div>
    </div> --}}

    <!-- Center KBC Question Area -->
    <div class="relative z-[1] w-full max-w-md mx-auto text-center mt-1 mb-2 space-y-2">
        <style>
            @keyframes movingGradient {
                0% {
                    background-position: 0% 50%;
                    box-shadow: 0 0 15px rgba(52, 211, 153, 0.4);
                    transform: scale(1);
                }

                50% {
                    background-position: 100% 50%;
                    box-shadow: 0 0 25px rgba(52, 211, 153, 0.8);
                    transform: scale(1.02);
                }

                100% {
                    background-position: 0% 50%;
                    box-shadow: 0 0 15px rgba(52, 211, 153, 0.4);
                    transform: scale(1);
                }
            }

            .animated-lifeline-bg {
                background-size: 200% 200%;
                animation: movingGradient 2s ease-in-out infinite;
            }
        </style>

        <!-- Top Banner / Question Media Area -->
        <div id="top-banner-wrapper" class="w-full [&>section]:!mb-0 [&_.glass-card]:!min-h-0">
            @include('fsrapp.home.components.banners', [
                'banners' => $banners,
                'pageType' => 'question',
                'disableClick' => true,
            ])
        </div>

        <div id="question-media-wrapper" class="w-full hidden mb-1">
            <div
                class="glass-card kbc-card kbc-glow-inner rounded-2xl overflow-hidden shadow-2xl border border-primary/40 p-1 flex items-center justify-center bg-black/40 backdrop-blur-md max-h-[160px] sm:max-h-[200px]">
                <img id="question-media-img" src="" alt="Question Image"
                    class="w-full h-auto max-h-[150px] sm:max-h-[190px] object-contain rounded-xl transition-all duration-300 shadow-md">
            </div>
        </div>

        <!-- Question Box -->
        <div class="kbc-glow-border">
            <div id="question-card"
                class="glass-card kbc-card kbc-glow-inner rounded-2xl p-4 sm:p-5 shadow-2xl transition-all duration-300">
                <div id="switch-question-badge" class="hidden mb-3 text-center">
                    <div
                        class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-gradient-to-r from-cyan-500/30 via-cyan-400/40 to-teal-500/30 border-2 border-cyan-300 text-cyan-100 font-display font-black text-xs uppercase tracking-widest shadow-[0_0_20px_rgba(34,211,238,0.6)] animate-pulse">
                        <span class="material-symbols-outlined text-sm font-bold">swap_calls</span>
                        <span>LIFELINE ACTIVATED: SWITCHED QUESTION</span>
                    </div>
                </div>
                <div id="skipped-question-banner" class="hidden mb-3 text-center">
                    <div
                        class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-gradient-to-r from-amber-500/30 via-orange-400/30 to-amber-500/30 border-2 border-amber-400/80 text-amber-200 font-display font-black text-xs uppercase tracking-widest shadow-[0_0_20px_rgba(245,158,11,0.5)] animate-pulse">
                        <span class="material-symbols-outlined text-sm font-bold">skip_next</span>
                        <span>QUESTION SWITCHED & SKIPPED (ADDED TO END)</span>
                    </div>
                </div>
                <h2 id="question-text"
                    class="font-headline-lg text-sm sm:text-base font-bold text-white leading-relaxed text-center min-h-[45px] flex items-center justify-center kbc-title-glow break-words">
                    Loading Question...
                </h2>
                <p id="sub-question-text"
                    class="hidden text-amber-200/90 text-xs sm:text-sm font-semibold text-center mt-1.5 leading-relaxed font-sans break-words">
                </p>

                <!-- Question Audio Speaker Replay Button -->
                <div id="question-audio-container" class="hidden mt-2.5 flex items-center justify-center">
                    <button type="button" id="btn-question-audio" onclick="replayCurrentQuestionAudio(event)"
                        class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full bg-gradient-to-r from-amber-500/20 via-yellow-400/25 to-amber-500/20 border border-amber-400/60 hover:border-amber-300 text-amber-300 hover:text-white transition-all duration-200 shadow-[0_0_12px_rgba(245,158,11,0.25)] active:scale-95 cursor-pointer select-none group"
                        title="Question Audio Play Karein">
                        <span id="question-speaker-icon"
                            class="material-symbols-outlined text-[19px] transition-transform group-hover:scale-110">volume_up</span>
                        <span id="question-speaker-label" class="text-[11px] font-bold tracking-wide">Play Audio</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Options List (Appears after 5 seconds) -->
        <div id="options-container" class="space-y-2.5 hidden transition-all duration-500 transform translate-y-3">

            <div id="options-list" class="grid grid-cols-2 gap-2.5">
                <!-- Options populated dynamically (2 per row) -->
            </div>
        </div>

        <!-- Counter & Lifelines Row: 50-50 | Counter | Switch Q (Positioned at Bottom) -->
        <div class="flex items-center justify-center gap-2.5 w-full max-w-sm mx-auto px-1 pt-1 pb-1"
            id="counter-lifelines-row">
            <!-- Left: 50-50 Lifeline Button -->
            <div class="flex justify-end items-center">
                @if ($quiz->fifty_fifty ?? true)
                    <button type="button" id="btn-5050" onclick="useFiftyFifty(event)" disabled
                        class="flex items-center gap-1.5 px-3 py-1.5 rounded-full font-bold text-xs bg-surface-container/70 border border-emerald-400/30 text-emerald-300/40 opacity-40 pointer-events-none grayscale transition-all shadow-sm">
                        <span class="material-symbols-outlined text-base">flaky</span>
                        <span>50 : 50</span>
                    </button>
                @endif
            </div>

            <!-- Center: Circular Dial Countdown (Smaller Size) -->
            <div class="flex justify-center items-center shrink-0">
                <div class="kbc-dial-wrap relative w-14 h-14 flex items-center justify-center" id="qplay-dial-wrap">
                    <svg class="w-full h-full transform -rotate-90" viewBox="0 0 100 100">
                        <circle cx="50" cy="50" r="42" stroke="currentColor" stroke-width="7"
                            class="text-surface-container-high" fill="transparent" />
                        <circle id="timer-ring" cx="50" cy="50" r="42" stroke="currentColor"
                            stroke-width="7" class="text-tertiary transition-all duration-1000 ease-linear"
                            fill="transparent" stroke-dasharray="263.89" stroke-dashoffset="0" stroke-linecap="round" />
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                        <span id="timer-sec" class="font-display text-base font-black text-white leading-none">5</span>
                        <span id="timer-label"
                            class="font-label-caps text-[6.5px] uppercase tracking-wider text-on-surface-variant mt-0.5">Question</span>
                    </div>
                </div>
            </div>

            <!-- Right: Switch Question Lifeline Button -->
            <div class="flex justify-start items-center">
                @if ($quiz->switch_option ?? true)
                    <button type="button" id="btn-switch" onclick="useSwitchQuestion(event)" disabled
                        class="flex items-center gap-1.5 px-3 py-1.5 rounded-full font-bold text-xs bg-surface-container/70 border border-emerald-400/30 text-emerald-300/40 opacity-40 pointer-events-none grayscale transition-all shadow-sm">
                        <span class="material-symbols-outlined text-base">swap_calls</span>
                        <span>Switch Q</span>
                    </button>
                @endif
            </div>
        </div>
    </div>

    <!-- Switch Lifeline Alert Modal -->
    <div id="switch-alert-modal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/75 backdrop-blur-sm hidden transition-opacity duration-300">
        <div class="glass-card bg-surface-container-high/95 border-2 border-cyan-400 rounded-3xl p-6 max-w-xs w-[90%] mx-auto text-center shadow-[0_0_35px_rgba(34,211,238,0.5)] transform transition-transform duration-300 scale-95"
            id="switch-alert-content">
            <div
                class="w-16 h-16 mx-auto mb-3 rounded-full bg-cyan-500/20 border-2 border-cyan-400/60 flex items-center justify-center text-cyan-300 shadow-[0_0_20px_rgba(34,211,238,0.4)] animate-pulse">
                <span class="material-symbols-outlined text-3xl">swap_calls</span>
            </div>
            <h3 class="font-display font-black text-lg text-white mb-1.5 tracking-wide">Lifeline Activated!</h3>
            <p class="text-xs text-cyan-100/90 leading-relaxed mb-5">
                Current question skip ho gaya hai. Replacement question quiz ke <span
                    class="font-bold text-emerald-300">last</span> me aayega.
            </p>
            <button type="button" onclick="window.closeSwitchAlertModal()"
                class="w-full py-2.5 px-4 rounded-xl bg-gradient-to-r from-cyan-500 to-teal-500 text-slate-950 font-black text-xs uppercase tracking-wider shadow-lg hover:brightness-110 active:scale-95 transition-all">
                OK, CONTINUE
            </button>
        </div>
    </div>

</main>

<script>
    (function() {
        let currentQIndex = {{ $qIndex ?? 0 }};
        const quizId = {{ $id }};
        const questionLimit = {{ !empty($quiz->question_limit) ? (int) $quiz->question_limit : 10 }};

        let questionsData = [];
        let currentQuestion = null;
        let selectedAnswer = null;
        let isAnswerSubmitted = false;
        let hasSelectedOption = false;

        let soundUrls = {};
        let activeTimer = null;

        let switchQuestionsData = [];
        let is5050Used = false;
        let isSwitchUsed = false;
        let isLifelinesEnabled = false;
        let switchedQuestionIds = new Set();

        let submissionPromises = {};
        let submissionConfirmed = {};
        const submissionSent = Object.create(null);
        const currentUserId = {{ Auth::id() ?: (session('fsr_user_id') ?: 'null') }};
        window.quizUserId = currentUserId;
        let isTransitioning = false;
        let quizFlowStartTime = 0;
        let activePhase = 1;
        let isSyncListenerAttached = false;
        let activeQuestionAudio = null;
        let questionBackgroundAudio = null;
        let questionAudioTimer = null;

        window.showSwitchAlertModal = function() {
            const modal = document.getElementById('switch-alert-modal');
            const content = document.getElementById('switch-alert-content');
            if (!modal) return;
            modal.classList.remove('hidden');
            setTimeout(() => {
                if (content) {
                    content.classList.remove('scale-95');
                    content.classList.add('scale-100');
                }
            }, 10);

            if (window.__switchModalTimer) clearTimeout(window.__switchModalTimer);
            window.__switchModalTimer = setTimeout(() => {
                window.closeSwitchAlertModal();
            }, 3000);
        };

        window.closeSwitchAlertModal = function() {
            if (window.__switchModalTimer) {
                clearTimeout(window.__switchModalTimer);
                window.__switchModalTimer = null;
            }
            const modal = document.getElementById('switch-alert-modal');
            const content = document.getElementById('switch-alert-content');
            if (!modal) return;
            if (content) {
                content.classList.remove('scale-100');
                content.classList.add('scale-95');
            }
            setTimeout(() => {
                modal.classList.add('hidden');
            }, 200);
        };

        window.initQuestionStage = function() {
            if (window.__isExitingQuiz) return;
            if (window.checkAndHandleQuizExpiry && window.checkAndHandleQuizExpiry()) return;

            const now = Date.now() + (window.clockOffset || 0);
            const sched = window.getQuizScheduleTimeMs ? window.getQuizScheduleTimeMs() : Date.now();
            const flowStart = window.getQuestionStartTimeMs ? window.getQuestionStartTimeMs() : (sched + 10000);

            if (now < sched) {
                if (window.QuizSpa) window.QuizSpa.switchStage('waiting');
                return;
            }
            if (now < flowStart) {
                if (window.QuizSpa) window.QuizSpa.switchStage('start-counter');
                return;
            }

            if (activeTimer) clearInterval(activeTimer);
            if (window.stopAllQuizAudios) window.stopAllQuizAudios();
            if (window.enforceQuizProtection) window.enforceQuizProtection();

            currentQIndex = 0;
            isTransitioning = false;

            // Synchronously initialize lifelines before loading questions to prevent race conditions on reload
            initLifelines();
            loadCachedSounds();
            loadQuestionsFromSession();
        };

        function isQuestionSwitched(qObj) {
            if (!qObj) return false;
            if (qObj._isSwitchedOut) return true;
            const qid = qObj.id !== undefined && qObj.id !== null ? qObj.id : qObj.question_id;
            if (qid !== undefined && qid !== null) {
                if (switchedQuestionIds.has(qid) || switchedQuestionIds.has(String(qid)) || switchedQuestionIds.has(Number(qid))) {
                    return true;
                }
            }
            if (qObj.question_id !== undefined && qObj.question_id !== null) {
                if (switchedQuestionIds.has(qObj.question_id) || switchedQuestionIds.has(String(qObj.question_id)) || switchedQuestionIds.has(Number(qObj.question_id))) {
                    return true;
                }
            }
            return false;
        }

        let fiftyFiftyQId = null;

        function initLifelines() {
            const safeGet = window.safeStorageGet || function(k) {
                try { return sessionStorage.getItem(k) || localStorage.getItem(k); } catch (e) { return null; }
            };
            is5050Used = safeGet('qx_used_5050_' + quizId) === 'true' ||
                         Boolean(window.userLifeline && window.userLifeline.fifty_fifty);

            isSwitchUsed = safeGet('qx_used_switch_' + quizId) === 'true' ||
                          Boolean(window.userLifeline && window.userLifeline.switch_option);

            fiftyFiftyQId = safeGet('qx_5050_qid_' + quizId) ||
                            (window.userLifeline?.fifty_fifty ? window.userLifeline?.question_id : null);

            switchedQuestionIds = new Set();
            try {
                const savedSwitched = sessionStorage.getItem('qx_switched_q_ids_' + quizId) ||
                                      localStorage.getItem('qx_switched_q_ids_' + quizId);
                if (savedSwitched) {
                    const parsed = JSON.parse(savedSwitched);
                    if (Array.isArray(parsed)) {
                        parsed.forEach(id => {
                            if (id !== undefined && id !== null) {
                                switchedQuestionIds.add(id);
                                switchedQuestionIds.add(Number(id));
                                switchedQuestionIds.add(String(id));
                            }
                        });
                    }
                }
            } catch (e) {}

            if (window.userLifeline && window.userLifeline.switch_option && window.userLifeline.question_id) {
                const sId = window.userLifeline.question_id;
                switchedQuestionIds.add(sId);
                switchedQuestionIds.add(Number(sId));
                switchedQuestionIds.add(String(sId));
            }

            try {
                const cachedSwitchKey = 'qx_cached_switch_questions_' + quizId;
                const cachedSwitch = sessionStorage.getItem(cachedSwitchKey) || localStorage.getItem(
                    cachedSwitchKey);
                if (cachedSwitch) {
                    switchQuestionsData = JSON.parse(cachedSwitch);
                }
            } catch (e) {}

            setLifelinesEnabled(false);
            updateLifelinesUI();
        }

        function setLifelinesEnabled(enabled) {
            isLifelinesEnabled = !!enabled;
            const btn5050 = document.getElementById('btn-5050');
            const btnSwitch = document.getElementById('btn-switch');
            const isAnyUsed = is5050Used || isSwitchUsed ||
                sessionStorage.getItem('qx_any_lifeline_used_' + quizId) === 'true' ||
                localStorage.getItem('qx_any_lifeline_used_' + quizId) === 'true';

            if (btn5050) {
                btn5050.disabled = !enabled || isAnyUsed;
                if (enabled && !isAnyUsed) {
                    btn5050.className =
                        'flex items-center gap-1.5 px-3 py-1.5 rounded-full font-extrabold text-xs tracking-wider bg-gradient-to-r from-green-900 via-emerald-800 to-green-900 border-2 border-emerald-500 text-emerald-100 cursor-pointer animated-lifeline-bg transition-colors duration-300 shadow-md';
                    btn5050.style.pointerEvents = 'auto';
                } else {
                    btn5050.className =
                        'flex items-center gap-1.5 px-3 py-1.5 rounded-full font-bold text-xs bg-surface-container/70 border border-emerald-400/30 text-emerald-300/40 opacity-40 pointer-events-none grayscale transition-all shadow-sm';
                    btn5050.style.pointerEvents = 'none';
                }
            }

            if (btnSwitch) {
                btnSwitch.disabled = !enabled || isAnyUsed;
                if (enabled && !isAnyUsed) {
                    btnSwitch.className =
                        'flex items-center gap-1.5 px-3 py-1.5 rounded-full font-extrabold text-xs tracking-wider bg-gradient-to-r from-green-900 via-emerald-800 to-green-900 border-2 border-emerald-500 text-emerald-100 cursor-pointer animated-lifeline-bg transition-colors duration-300 shadow-md';
                    btnSwitch.style.pointerEvents = 'auto';
                } else {
                    btnSwitch.className =
                        'flex items-center gap-1.5 px-3 py-1.5 rounded-full font-bold text-xs bg-surface-container/70 border border-emerald-400/30 text-emerald-300/40 opacity-40 pointer-events-none grayscale transition-all shadow-sm';
                    btnSwitch.style.pointerEvents = 'none';
                }
            }

            updateLifelinesUI();
        }

        function updateLifelinesUI() {
            const isAnyUsed = is5050Used || isSwitchUsed ||
                sessionStorage.getItem('qx_any_lifeline_used_' + quizId) === 'true' ||
                localStorage.getItem('qx_any_lifeline_used_' + quizId) === 'true';

            const btn5050 = document.getElementById('btn-5050');
            const btnSwitch = document.getElementById('btn-switch');

            if (btn5050) {
                if (is5050Used || isAnyUsed) {
                    btn5050.classList.add('invisible');
                    btn5050.disabled = true;
                    btn5050.style.pointerEvents = 'none';
                } else {
                    btn5050.classList.remove('invisible');
                    if (isLifelinesEnabled) {
                        btn5050.style.pointerEvents = 'auto';
                        btn5050.disabled = false;
                    } else {
                        btn5050.style.pointerEvents = 'none';
                        btn5050.disabled = true;
                    }
                }
            }

            if (btnSwitch) {
                if (isSwitchUsed || isAnyUsed) {
                    btnSwitch.classList.add('invisible');
                    btnSwitch.disabled = true;
                    btnSwitch.style.pointerEvents = 'none';
                } else {
                    btnSwitch.classList.remove('invisible');
                    if (isLifelinesEnabled) {
                        btnSwitch.style.pointerEvents = 'auto';
                        btnSwitch.disabled = false;
                    } else {
                        btnSwitch.style.pointerEvents = 'none';
                        btnSwitch.disabled = true;
                    }
                }
            }
        }

        async function recordLifelineUseToServer(lifelineType, qId) {
            try {
                const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
                await fetch(`/fsrapp/quizes/api/use-lifeline/${quizId}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf || '',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        lifeline: lifelineType,
                        question_id: qId
                    })
                });
            } catch (e) {
                console.error('Error recording lifeline usage:', e);
            }
        }

        function findCorrectWrap(optionWraps, currentQuestion) {
            if (!currentQuestion || !optionWraps || optionWraps.length === 0) return null;

            const rawCorrect = (currentQuestion.correct_answer ?? currentQuestion.correct_ans ?? currentQuestion
                .answer ?? currentQuestion.hindi_correct_answer ?? '').toString().trim();
            if (rawCorrect === '') return optionWraps[0] || null;

            const normCorrect = rawCorrect.toLowerCase();

            // 1. Check exact match first
            for (let idx = 0; idx < optionWraps.length; idx++) {
                const wrap = optionWraps[idx];
                const btn = wrap.querySelector('.option-btn');
                if (!btn) continue;

                const ansText = (btn.dataset.answer ?? btn.textContent ?? '').toString().trim();
                const normAns = ansText.toLowerCase();
                if (normAns === normCorrect) return wrap;
            }

            // 2. Letter map (A, B, C, D)
            const letterMap = {
                'a': 0,
                'b': 1,
                'c': 2,
                'd': 3,
                'e': 4,
                'f': 5
            };
            let letterMatchKey = normCorrect.replace(/^option\s*/i, '').trim();
            if (letterMap.hasOwnProperty(letterMatchKey) && optionWraps[letterMap[letterMatchKey]]) {
                return optionWraps[letterMap[letterMatchKey]];
            }

            // 3. Normalized / parenthetical substring match
            const correctBase = normCorrect.replace(/\s*\(.*?\)\s*$/g, '').trim();
            const correctInnerMatch = normCorrect.match(/\((.*?)\)/);
            const correctInner = correctInnerMatch ? correctInnerMatch[1].trim().toLowerCase() : '';

            for (let idx = 0; idx < optionWraps.length; idx++) {
                const wrap = optionWraps[idx];
                const btn = wrap.querySelector('.option-btn');
                if (!btn) continue;

                const ansText = (btn.dataset.answer ?? btn.textContent ?? '').toString().trim();
                const normAns = ansText.toLowerCase();

                const ansBase = normAns.replace(/\s*\(.*?\)\s*$/g, '').trim();
                const ansInnerMatch = normAns.match(/\((.*?)\)/);
                const ansInner = ansInnerMatch ? ansInnerMatch[1].trim().toLowerCase() : '';

                if (
                    (correctBase !== '' && ansBase !== '' && correctBase === ansBase) ||
                    (correctInner !== '' && ansBase !== '' && correctInner === ansBase) ||
                    (correctBase !== '' && ansInner !== '' && correctBase === ansInner) ||
                    (correctInner !== '' && ansInner !== '' && correctInner === ansInner)
                ) {
                    return wrap;
                }

                if (
                    (correctBase.length >= 3 && normAns.includes(correctBase)) ||
                    (ansBase.length >= 3 && normCorrect.includes(ansBase))
                ) {
                    return wrap;
                }
            }

            return optionWraps[0] || null;
        }

        function applyFiftyFiftyToOptions(qObj) {
            const optList = document.getElementById('options-list');
            if (!optList) return;

            const optionWraps = Array.from(optList.querySelectorAll('.kbc-opt-glow'));
            if (optionWraps.length <= 2) return;

            try {
                let raw5050 = sessionStorage.getItem('qx_5050_data_' + quizId) ||
                              localStorage.getItem('qx_5050_data_' + quizId);

                if (!raw5050 && window.userLifeline && window.userLifeline.fifty_fifty_data) {
                    raw5050 = JSON.stringify(window.userLifeline.fifty_fifty_data);
                }

                if (raw5050) {
                    const parsed = JSON.parse(raw5050);
                    const hiddenList = parsed.hiddenAnswers || parsed.hidden_options || parsed.removed_options || [];

                    if (Array.isArray(hiddenList) && hiddenList.length > 0) {
                        const normStr = (s) => String(s || '').trim().toLowerCase().replace(/\s*\(.*?\)\s*$/g, '');
                        const normHidden = hiddenList.map(normStr);

                        optionWraps.forEach(wrap => {
                            const btn = wrap.querySelector('.option-btn');
                            const bAns = normStr(btn?.dataset.answer || btn?.textContent || '');
                            const isMatch = normHidden.some(h => h === bAns || bAns.includes(h) || h.includes(bAns));

                            if (isMatch) {
                                wrap.classList.add('hidden');
                                wrap.style.display = 'none';
                                if (btn) btn.disabled = true;
                            } else {
                                wrap.classList.remove('hidden', 'invisible', 'pointer-events-none');
                                wrap.style.display = '';
                            }
                        });

                        const remainingWraps = optionWraps.filter(w => !w.classList.contains('hidden'));
                        const OPT_LETTERS = ['A', 'B', 'C', 'D'];
                        remainingWraps.forEach((wrap, idx) => {
                            const letterSpan = wrap.querySelector('.kbc-opt-letter-flat');
                            if (letterSpan) {
                                letterSpan.textContent = (OPT_LETTERS[idx] || (idx + 1)) + ':';
                            }
                        });
                    }
                }
            } catch (e) {
                console.warn('applyFiftyFiftyToOptions error:', e);
            }

            setLifelinesEnabled(false);
            updateLifelinesUI();
        }

        window.useFiftyFifty = async function(e) {
            if (e) {
                if (typeof e.preventDefault === 'function') e.preventDefault();
                if (typeof e.stopPropagation === 'function') e.stopPropagation();
            }

            const isAnyUsed = is5050Used || isSwitchUsed ||
                sessionStorage.getItem('qx_any_lifeline_used_' + quizId) === 'true' ||
                localStorage.getItem('qx_any_lifeline_used_' + quizId) === 'true';

            if (!isLifelinesEnabled || isAnyUsed || isAnswerSubmitted || hasSelectedOption) {
                return;
            }

            const currentQId = currentQuestion ? (currentQuestion.id || currentQuestion.question_id) : null;
            if (!currentQId) return;

            const btn5050 = document.getElementById('btn-5050');
            if (btn5050) {
                btn5050.disabled = true;
                btn5050.style.pointerEvents = 'none';
                btn5050.classList.add('opacity-50');
            }

            const optList = document.getElementById('options-list');
            if (!optList) return;
            const optionWraps = Array.from(optList.querySelectorAll('.kbc-opt-glow'));
            if (optionWraps.length <= 2) return;

            try {
                const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
                const res = await fetch(`/fsrapp/quizes/api/use-lifeline/${quizId}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf || '',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        lifeline: 'fifty_fifty',
                        question_id: currentQId
                    })
                });

                const data = await res.json();
                if (!data || !data.success) {
                    console.warn('50:50 API rejected:', data?.message);
                    return;
                }

                const hiddenOptions = (data.hidden_options || data.removed_options || []).map(o => String(o).trim().toLowerCase());
                const keptOptions = (data.kept_options || []).map(o => String(o).trim().toLowerCase());

                const normStr = (s) => String(s || '').trim().toLowerCase().replace(/\s*\(.*?\)\s*$/g, '');

                let toHide = [];
                let toKeep = [];

                optionWraps.forEach(wrap => {
                    const btn = wrap.querySelector('.option-btn');
                    const bAns = btn?.dataset.answer || btn?.textContent || '';
                    const normB = normStr(bAns);

                    const matchHidden = hiddenOptions.some(h => normStr(h) === normB || normB.includes(normStr(h)) || normStr(h).includes(normB));
                    const matchKept = keptOptions.some(k => normStr(k) === normB || normB.includes(normStr(k)) || normStr(k).includes(normB));

                    if (matchHidden && !matchKept) {
                        toHide.push(wrap);
                    } else {
                        toKeep.push(wrap);
                    }
                });

                // Safety: Ensure exactly 2 options remain visible (1 correct + 1 wrong)
                if (toKeep.length > 2 && toHide.length > 0) {
                    while (toKeep.length > 2) {
                        toHide.push(toKeep.pop());
                    }
                } else if (toKeep.length < 2 && toHide.length > 0) {
                    while (toKeep.length < 2 && toHide.length > 0) {
                        toKeep.push(toHide.pop());
                    }
                }

                const OPT_LETTERS = ['A', 'B', 'C', 'D'];
                toKeep.forEach((wrap, idx) => {
                    optList.appendChild(wrap);
                    wrap.classList.remove('invisible', 'pointer-events-none', 'hidden');
                    wrap.style.display = '';
                    const letterSpan = wrap.querySelector('.kbc-opt-letter-flat');
                    if (letterSpan) {
                        letterSpan.textContent = (OPT_LETTERS[idx] || (idx + 1)) + ':';
                    }
                });

                const finalHiddenAnswers = [];
                toHide.forEach(wrap => {
                    optList.appendChild(wrap);
                    const btn = wrap.querySelector('.option-btn');
                    if (btn) {
                        btn.disabled = true;
                        if (btn.dataset.answer) finalHiddenAnswers.push(btn.dataset.answer);
                    }
                    wrap.classList.add('hidden');
                    wrap.style.display = 'none';
                });

                const finalKeptAnswers = toKeep.map(w => w.querySelector('.option-btn')?.dataset.answer || '').filter(Boolean);

                is5050Used = true;
                fiftyFiftyQId = currentQId;
                sessionStorage.setItem('qx_used_5050_' + quizId, 'true');
                localStorage.setItem('qx_used_5050_' + quizId, 'true');
                sessionStorage.setItem('qx_any_lifeline_used_' + quizId, 'true');
                localStorage.setItem('qx_any_lifeline_used_' + quizId, 'true');
                sessionStorage.setItem('qx_5050_qid_' + quizId, String(currentQId));
                localStorage.setItem('qx_5050_qid_' + quizId, String(currentQId));

                const data5050 = {
                    questionId: currentQId,
                    hiddenAnswers: finalHiddenAnswers,
                    keptAnswers: finalKeptAnswers
                };
                sessionStorage.setItem('qx_5050_data_' + quizId, JSON.stringify(data5050));
                localStorage.setItem('qx_5050_data_' + quizId, JSON.stringify(data5050));

                setLifelinesEnabled(false);
                updateLifelinesUI();
                playSoundByKey('answer_appear_sound');
            } catch (err) {
                console.error('50:50 lifeline error:', err);
            }
        };

        function ensureSwitchQuestionIncluded() {
            if (!isSwitchUsed) return;
            // Mark any question that was switched out
            questionsData.forEach(q => {
                if (isQuestionSwitched(q)) {
                    q._isSwitchedOut = true;
                }
            });

            // Check if replacement question is already in questionsData
            const hasSwitchQ = questionsData.some(q => q.is_switch || q.is_switch_question);
            if (!hasSwitchQ) {
                let switchQ = null;
                if (switchQuestionsData && switchQuestionsData.length > 0) {
                    switchQ = switchQuestionsData[0];
                } else if (window.initialSwitchQuestions && window.initialSwitchQuestions.length > 0) {
                    switchQ = window.initialSwitchQuestions[0];
                }
                if (!switchQ) {
                    switchQ = {
                        id: 99999,
                        question: 'Replacement Question: Which gas is essential for human respiration?',
                        hindi_question: 'श्वसन के लिए कौन सी गैस आवश्यक है? (Which gas is essential for human respiration?)',
                        options: ['Oxygen (ऑक्सीजन)', 'Carbon Dioxide (कार्बन डाइऑक्साइड)', 'Nitrogen (नाइट्रोजन)', 'Helium (हीलियम)'],
                        correct_answer: 'Oxygen (ऑक्सीजन)',
                        is_switch: true
                    };
                }
                switchQ.is_switch = true;
                switchQ._isSwitchedOut = false;
                questionsData.push(switchQ);
            }
        }

        window.useSwitchQuestion = function(e) {
            if (e) {
                if (typeof e.preventDefault === 'function') e.preventDefault();
                if (typeof e.stopPropagation === 'function') e.stopPropagation();
            }

            const isAnyUsed = is5050Used || isSwitchUsed ||
                sessionStorage.getItem('qx_any_lifeline_used_' + quizId) === 'true' ||
                localStorage.getItem('qx_any_lifeline_used_' + quizId) === 'true';

            if (!isLifelinesEnabled || isAnyUsed || isAnswerSubmitted || hasSelectedOption ||
                (currentQuestion && (currentQuestion._isSwitchedOut || isQuestionSwitched(currentQuestion)))) {
                console.warn('Switch Question blocked:', {
                    isLifelinesEnabled,
                    isAnyUsed,
                    isAnswerSubmitted,
                    hasSelectedOption
                });
                return;
            }

            let switchQ = null;
            if (switchQuestionsData && switchQuestionsData.length > 0) {
                switchQ = switchQuestionsData.shift();
                try {
                    sessionStorage.setItem('qx_cached_switch_questions_' + quizId, JSON.stringify(
                        switchQuestionsData));
                    localStorage.setItem('qx_cached_switch_questions_' + quizId, JSON.stringify(
                        switchQuestionsData));
                } catch (e) {}
            } else if (window.initialSwitchQuestions && window.initialSwitchQuestions.length > 0) {
                switchQ = window.initialSwitchQuestions[0];
            } else if (questionsData && questionsData.length > 1) {
                switchQ = questionsData.find(q => q.id !== currentQuestion?.id && !q._isSwitchedOut && !
                    switchedQuestionIds.has(q.id));
            }

            if (!switchQ) {
                switchQ = {
                    id: (currentQuestion ? (currentQuestion.id || 1) : 1) + 9999,
                    question: 'Replacement Question: Which gas is essential for human respiration?',
                    hindi_question: 'श्वसन के लिए कौन सी गैस आवश्यक है? (Which gas is essential for human respiration?)',
                    options: ['Oxygen (ऑक्सीजन)', 'Carbon Dioxide (कार्बन डाइऑक्साइड)',
                        'Nitrogen (नाइट्रोजन)', 'Helium (हीलियम)'
                    ],
                    correct_answer: 'Oxygen (ऑक्सीजन)',
                    is_switch: true
                };
            }

            const originalQId = currentQuestion ? (currentQuestion.id || currentQuestion.question_id) : null;

            // 1. Mark lifeline as used permanently for this session
            isSwitchUsed = true;
            sessionStorage.setItem('qx_used_switch_' + quizId, 'true');
            localStorage.setItem('qx_used_switch_' + quizId, 'true');
            sessionStorage.setItem('qx_any_lifeline_used_' + quizId, 'true');
            localStorage.setItem('qx_any_lifeline_used_' + quizId, 'true');
            setLifelinesEnabled(false);
            updateLifelinesUI();

            // 2. Mark current question as switched out and track ID
            if (currentQuestion) {
                currentQuestion._isSwitchedOut = true;
            }
            if (originalQId) {
                switchedQuestionIds.add(originalQId);
                switchedQuestionIds.add(Number(originalQId));
                switchedQuestionIds.add(String(originalQId));
                try {
                    const idsArr = Array.from(switchedQuestionIds);
                    sessionStorage.setItem('qx_switched_q_ids_' + quizId, JSON.stringify(idsArr));
                    localStorage.setItem('qx_switched_q_ids_' + quizId, JSON.stringify(idsArr));
                } catch (e) {}
            }

            // Immediately mark selection as complete on switched question
            hasSelectedOption = true;
            isAnswerSubmitted = true;

            // 3. Mark replacement question and push it to the end of questionsData
            switchQ.is_switch = true;
            switchQ._isSwitchedOut = false;
            if (!questionsData.some(q => q.id === switchQ.id && (q.is_switch || q.is_switch_question))) {
                questionsData.push(switchQ);
            }

            // Update total questions count in DOM
            const effCount = typeof window.getEffectiveTotalQuestionsCount === 'function' ? window.getEffectiveTotalQuestionsCount() : (questionsData.length || 11);
            const qTotalEl = document.getElementById('q-total');
            if (qTotalEl) qTotalEl.textContent = effCount;

            // Update cached questions (sanitized) so state persists
            const sanitizedQuestions = questionsData.map(q => {
                const { correct_answer, hindi_correct_answer, ...rest } = q;
                return rest;
            });
            sessionStorage.setItem('qx_cached_questions_' + quizId, JSON.stringify(sanitizedQuestions));
            localStorage.setItem('qx_cached_questions_' + quizId, JSON.stringify(sanitizedQuestions));

            // Preload audio for the pushed question
            if (window.preloadAllQuestionAudios) {
                window.preloadAllQuestionAudios([switchQ]);
            }

            // 4. Disable all options on current question so user cannot click
            const optionBtns = document.querySelectorAll('#options-list .option-btn');
            optionBtns.forEach(btn => {
                btn.disabled = true;
                btn.classList.add('opacity-40', 'pointer-events-none');
                btn.classList.remove('cursor-pointer', 'hover:bg-surface-container-high',
                    'active:scale-[0.98]');
            });

            // 5. Show skipped banner on current question card
            const skippedBadge = document.getElementById('skipped-question-banner');
            if (skippedBadge) skippedBadge.classList.remove('hidden');

            // 6. Show the Alert Modal to the user
            if (window.showSwitchAlertModal) {
                window.showSwitchAlertModal();
            }

            // 7. Record lifeline usage on server
            recordLifelineUseToServer('switch_option', originalQId);
            playSoundByKey('question_appear_sound');
        };

        function loadCachedSounds() {
            try {
                const cached = sessionStorage.getItem('qx_sound_settings') ||
                    sessionStorage.getItem('qx_cached_sound_urls') ||
                    localStorage.getItem('qx_sound_settings') ||
                    localStorage.getItem('qx_cached_sound_urls');
                if (cached) soundUrls = JSON.parse(cached);
            } catch (e) {}
        }

        function playSoundByKey(key) {
            if (window.isGlobalMuted && window.isGlobalMuted()) return;
            let url = (soundUrls && soundUrls[key]) ? soundUrls[key] : (typeof window.getSoundUrl === 'function' ? window.getSoundUrl(key) : null);
            if (!url && key === 'question_appear_sound') {
                url = soundUrls?.question || soundUrls?.question_appear_sound || (typeof window.getSoundUrl === 'function' ? window.getSoundUrl('question_appear_sound') : null);
            }
            if (!url && (key === 'answer_appear_sound' || key === 'option_appear_sound')) {
                url = soundUrls?.option_appear_sound || soundUrls?.answer_appear_sound || soundUrls?.options || (typeof window.getSoundUrl === 'function' ? window.getSoundUrl('answer_appear_sound') : null);
            }
            if (!url && typeof window.getSoundUrl === 'function') {
                url = window.getSoundUrl(key);
            }
            if (!url) return;
            if (window.playQuizAudio) {
                window.playQuizAudio(url, 0.7);
            }
        }

        function startQuestionBackgroundSound() {
            if (window.isGlobalMuted && window.isGlobalMuted()) return;

            const soundUrl = typeof window.getSoundUrl === 'function'
                ? window.getSoundUrl('background_sound')
                : null;
            if (!soundUrl || !window.playQuizAudio) return;

            if (questionBackgroundAudio && !questionBackgroundAudio.paused && !questionBackgroundAudio.ended) {
                return;
            }

            questionBackgroundAudio = window.playQuizAudio(soundUrl, 0.5, true, 'bg');
        }

        function stopQuestionBackgroundSound() {
            if (!questionBackgroundAudio) return;

            try {
                questionBackgroundAudio.pause();
                questionBackgroundAudio.currentTime = 0;
            } catch (e) {}

            questionBackgroundAudio = null;
        }

        function prepareQuizQuestions(qList, switchList) {
            if (window.QuizQuestionManager && typeof window.QuizQuestionManager.prepareQuestionsForDevice ===
                'function') {
                return window.QuizQuestionManager.prepareQuestionsForDevice(quizId, qList, switchList);
            }
            return {
                questions: Array.isArray(qList) ? qList : [],
                switch_questions: Array.isArray(switchList) ? switchList : []
            };
        }

        async function loadQuestionsFromSession() {
            const safeGet = window.safeStorageGet || function(k) {
                try { return sessionStorage.getItem(k) || localStorage.getItem(k); } catch (e) { return null; }
            };
            const safeSet = window.safeStorageSet || function(k, v) {
                if (v === null || v === undefined) return;
                try { sessionStorage.setItem(k, String(v)); } catch (e) {}
                try { localStorage.setItem(k, String(v)); } catch (e) {}
            };

            try {
                const encKey = 'qx_enc_questions_' + quizId;
                const cachedKey = 'qx_cached_questions_' + quizId;
                const cachedSwitchKey = 'qx_cached_switch_questions_' + quizId;
                const secretKey = window.QuizCipher ? window.QuizCipher.getKey(quizId) : ('FSRQ_' + quizId);
                const encryptedPayload = window.__encryptedQuizPayload || safeGet(encKey);

                const cached = safeGet(cachedKey);
                const cachedSwitch = safeGet(cachedSwitchKey);
                let cachedSwitchList = [];
                if (cachedSwitch) {
                    try {
                        cachedSwitchList = JSON.parse(cachedSwitch);
                    } catch (e) {}
                }

                // If Switch Question was already used, prefer cached questions so the 11th replacement question is never wiped out
                if (isSwitchUsed && cached) {
                    try {
                        const parsed = JSON.parse(cached);
                        if (parsed && parsed.length > 0) {
                            const processed = prepareQuizQuestions(parsed, cachedSwitchList);
                            questionsData = processed.questions;
                            switchQuestionsData = processed.switch_questions;
                            ensureSwitchQuestionIncluded();

                            applyQuestionsAndStart();
                            return;
                        }
                    } catch (e) {}
                }

                if (encryptedPayload && encryptedPayload !== 'null') {
                    try {
                        const decrypted = window.QuizCipher ? window.QuizCipher.decrypt(encryptedPayload,
                            secretKey) : JSON.parse(encryptedPayload);
                        if (decrypted && decrypted.questions && decrypted.questions.length > 0) {
                            const processed = prepareQuizQuestions(decrypted.questions, decrypted
                                .switch_questions || []);
                            questionsData = processed.questions;
                            switchQuestionsData = processed.switch_questions;

                            ensureSwitchQuestionIncluded();

                            safeSet(encKey, encryptedPayload, quizId);
                            safeSet(cachedKey, JSON.stringify(questionsData), quizId);
                            safeSet(cachedSwitchKey, JSON.stringify(switchQuestionsData), quizId);

                            applyQuestionsAndStart();
                            return;
                        }
                    } catch (decErr) {
                        console.warn('Encrypted payload decrypt skipped:', decErr);
                    }
                }

                if (cached) {
                    try {
                        const parsed = JSON.parse(cached);
                        if (parsed && parsed.length > 0) {
                            const processed = prepareQuizQuestions(parsed, cachedSwitchList);
                            questionsData = processed.questions;
                            switchQuestionsData = processed.switch_questions;

                            ensureSwitchQuestionIncluded();

                            applyQuestionsAndStart();
                            return;
                        }
                    } catch (cacheErr) {
                        console.warn('Cached questions parse skipped:', cacheErr);
                    }
                }

                const sessionQuestions = (window.initialQuestions && window.initialQuestions.length > 0)
                    ? window.initialQuestions
                    : @json($initialQuestions ?? session("quiz_{$id}_questions", []));
                const sessionSwitchQuestions = (window.initialSwitchQuestions && window.initialSwitchQuestions.length > 0)
                    ? window.initialSwitchQuestions
                    : @json($initialSwitchQuestions ?? session("quiz_{$id}_switch_questions", []));

                if (sessionQuestions && sessionQuestions.length > 0) {
                    const processed = prepareQuizQuestions(sessionQuestions, sessionSwitchQuestions || []);
                    questionsData = processed.questions;
                    switchQuestionsData = processed.switch_questions;

                    ensureSwitchQuestionIncluded();

                    const json = {
                        success: true,
                        questions: questionsData,
                        switch_questions: switchQuestionsData
                    };
                    const encryptedStr = window.QuizCipher ? window.QuizCipher.encrypt(json, secretKey) : JSON
                        .stringify(json);
                    if (encryptedStr) {
                        window.__encryptedQuizPayload = encryptedStr;
                        safeSet(encKey, encryptedStr, quizId);
                    }
                    safeSet(cachedKey, JSON.stringify(questionsData), quizId);
                    safeSet(cachedSwitchKey, JSON.stringify(switchQuestionsData), quizId);

                    applyQuestionsAndStart();
                    return;
                }

                // Fallback: Fetch questions from backend API endpoint (with retry for iOS Safari / WebView)
                for (let attempt = 0; attempt < 3; attempt++) {
                    try {
                        const res = await fetch(`/fsrapp/quizes/api/questions/${quizId}?t=${Date.now()}`, {
                            credentials: 'same-origin',
                            cache: 'no-store'
                        });
                        const apiData = await res.json().catch(() => null);
                        if (res.status === 403 && apiData && apiData.quiz_expired) {
                            if (window.checkAndHandleQuizExpiry) window.checkAndHandleQuizExpiry();
                            return;
                        }
                        if (!res.ok) throw new Error('HTTP ' + res.status);
                        if (apiData && apiData.questions && apiData.questions.length > 0) {
                            const processed = prepareQuizQuestions(apiData.questions, apiData.switch_questions ||
                            []);
                            questionsData = processed.questions;
                            switchQuestionsData = processed.switch_questions;

                            ensureSwitchQuestionIncluded();

                            const json = {
                                success: true,
                                questions: questionsData,
                                switch_questions: switchQuestionsData
                            };
                            const encryptedStr = window.QuizCipher ? window.QuizCipher.encrypt(json, secretKey) :
                                JSON.stringify(json);
                            if (encryptedStr) {
                                window.__encryptedQuizPayload = encryptedStr;
                                safeSet(encKey, encryptedStr, quizId);
                            }
                            safeSet(cachedKey, JSON.stringify(questionsData), quizId);
                            safeSet(cachedSwitchKey, JSON.stringify(switchQuestionsData), quizId);

                            applyQuestionsAndStart();
                            return;
                        }
                    } catch (apiErr) {
                        console.error('API questions load fallback failed (attempt ' + (attempt + 1) + ')', apiErr);
                        if (attempt < 2) {
                            await new Promise(r => setTimeout(r, 500));
                        }
                    }
                }
            } catch (e) {
                console.error('Session questions load failed', e);
            }

            if (Array.isArray(questionsData) && questionsData.length > 0) {
                applyQuestionsAndStart();
                return;
            }

            const qTextEl = document.getElementById('q-text');
            if (qTextEl) {
                qTextEl.innerHTML = 'Loading Question... <span class="text-xs text-amber-300 block mt-1">Syncing with server, please wait...</span>';
            }
            if (!window.__qLoadRetryCount) window.__qLoadRetryCount = 0;
            if (window.__qLoadRetryCount < 5 && !window.__isExitingQuiz) {
                window.__qLoadRetryCount++;
                setTimeout(() => {
                    loadQuestionsFromSession();
                }, 2000);
            }
        }

        function applyQuestionsAndStart() {
            const totalQ = typeof window.getEffectiveTotalQuestionsCount === 'function' ? window.getEffectiveTotalQuestionsCount() : (questionLimit || 10);
            const qTotalEl = document.getElementById('q-total');
            if (qTotalEl) qTotalEl.textContent = totalQ;

            if (window.preloadAllQuestionAudios) window.preloadAllQuestionAudios(questionsData);
            if (typeof preloadAllQuestionMedia === 'function') preloadAllQuestionMedia(questionsData);

            const now = Date.now() + (window.clockOffset || 0);
            const sched = window.getQuizScheduleTimeMs ? window.getQuizScheduleTimeMs() : Date.now();
            const flowStart = window.getQuestionStartTimeMs ? window.getQuestionStartTimeMs() : (sched + 10000);

            if (now < sched) {
                if (window.QuizSpa) window.QuizSpa.switchStage('waiting');
                return;
            }
            if (now < flowStart) {
                if (window.QuizSpa) window.QuizSpa.switchStage('start-counter');
                return;
            }

            const totalElapsedSec = (now - flowStart) / 1000;
            const targetIdx = Math.floor(totalElapsedSec / 17);

            if (targetIdx >= totalQ) {
                window.__isQuizCompleted = true;
                if (typeof window.finalizeAndValidateClientAnssheet === 'function') {
                    window.finalizeAndValidateClientAnssheet();
                }
                // Backup single-requester fallback trigger when question answering finishes
                if (!sessionStorage.getItem('qx_result_proc_sent_' + quizId)) {
                    setTimeout(() => {
                        if (!sessionStorage.getItem('qx_result_proc_sent_' + quizId) && typeof window.triggerQuizProcessResultOnce === 'function') {
                            window.triggerQuizProcessResultOnce(quizId);
                        }
                    }, 5000 + Math.floor(Math.random() * 3000));
                }
                const isSwitchUsed = sessionStorage.getItem('qx_used_switch_' + quizId) === 'true' ||
                                     localStorage.getItem('qx_used_switch_' + quizId) === 'true';
                const baseQ = window.totalQuestionsCount || questionLimit || 10;
                const userQCount = isSwitchUsed ? (baseQ + 1) : baseQ;
                const reviewEndMs = flowStart + (userQCount * 17000) + 10000;
                window.QuizSpa.switchStage(now >= reviewEndMs ? 'answer-sheet' : 'review-counter');
                return;
            }

            currentQIndex = Math.max(0, Math.min(targetIdx, questionsData.length - 1));
            renderQuestion(questionsData[currentQIndex]);
            startQuestionFlow();
        }

        function extractQuestionText(qObj) {
            if (!qObj) return 'Question';
            let text = qObj.question || qObj.question_text || qObj.title;
            if (typeof text === 'object' && text !== null) {
                text = text.question_text || text.question || text.title || text.text || text.en || '';
            }
            if (typeof text === 'string' && text.trim().length > 0) return text;
            return 'Question ' + (currentQIndex + 1);
        }

        function playFallbackQuestionSound() {
            if (typeof window.isGlobalMuted === 'function' && window.isGlobalMuted()) return;
            try {
                const cached = sessionStorage.getItem('qx_sound_settings') ||
                    sessionStorage.getItem('qx_cached_sound_urls') ||
                    localStorage.getItem('qx_sound_settings') ||
                    localStorage.getItem('qx_cached_sound_urls');
                let fallbackUrl = null;
                if (cached) {
                    const sounds = JSON.parse(cached);
                    fallbackUrl = sounds.question_appear_sound || sounds.question || sounds.start_quiz_sound ||
                        sounds.start;
                }
                if (!fallbackUrl) {
                    fallbackUrl =
                        "https://chaloonline.s3.ap-south-1.amazonaws.com/sounds/question_appear_sound_1787231022.mp3";
                }
                if (fallbackUrl && window.playQuizAudio) {
                    const audio = window.playQuizAudio(fallbackUrl, 0.85, false, 'question');
                    activeQuestionAudio = audio;
                    if (audio) {
                        if (questionAudioTimer) clearTimeout(questionAudioTimer);
                        questionAudioTimer = setTimeout(() => {
                            try {
                                if (activeQuestionAudio === audio) {
                                    audio.pause();
                                    audio.currentTime = 0;
                                }
                            } catch (e) {}
                        }, 5000);
                    }
                } else {
                    playSoundByKey('question_appear_sound');
                }
            } catch (e) {
                console.warn('Fallback sound error:', e);
            }
        }

        window.playCurrentQuestionAudio = function() {
            if (window.isGlobalMuted && window.isGlobalMuted()) return;
            if (activePhase === 1) {
                if (currentQuestion) {
                    playQuestionAudioIfAvailable(currentQuestion);
                } else {
                    playFallbackQuestionSound();
                }
            } else if (activePhase === 2) {
                playSoundByKey('answer_appear_sound');
            } else {
                if (currentQuestion) {
                    playQuestionAudioIfAvailable(currentQuestion);
                } else {
                    playFallbackQuestionSound();
                }
            }
        };

        function getQuestionAudioUrl(qObj) {
            if (!qObj) return null;

            const sessionQuestions = @json(session("quiz_{$id}_questions", []));
            const sessionQObj = (sessionQuestions && qObj) ? sessionQuestions.find(sq => sq.id === (qObj.id || qObj
                .question_id)) : null;

            let rawAudio = qObj ? (
                qObj.question_audio ||
                qObj.audio_file ||
                (typeof qObj.question === 'object' && qObj.question ? (qObj.question.question_audio || qObj
                    .question.audio_file) : null)
            ) : null;

            if (!rawAudio && sessionQObj) {
                rawAudio = sessionQObj.question_audio || sessionQObj.audio_file;
            }

            if (!rawAudio) {
                const rawMedia = qObj ? (qObj.media_url || qObj.media || (sessionQObj ? (sessionQObj.media_url ||
                    sessionQObj.media) : null)) : null;
                if (rawMedia && typeof rawMedia === 'string' && /\.(mp3|wav|ogg|m4a|aac|flac)$/i.test(rawMedia
                    .trim())) {
                    rawAudio = rawMedia.trim();
                }
            }

            if (!rawAudio || typeof rawAudio !== 'string' || rawAudio.trim().length === 0) {
                return null;
            }

            if (/\.(jpe?g|png|webp|svg|gif)$/i.test(rawAudio.trim())) {
                return null;
            }

            let audioUrl = window.resolveAudioUrl ? window.resolveAudioUrl(rawAudio) : rawAudio.trim();
            return audioUrl || null;
        }

        function setQuestionAudioButtonState(isPlaying) {
            const btn = document.getElementById('btn-question-audio');
            const icon = document.getElementById('question-speaker-icon');
            const label = document.getElementById('question-speaker-label');
            if (!btn) return;

            if (isPlaying) {
                btn.classList.add('ring-2', 'ring-amber-400', 'shadow-[0_0_20px_rgba(245,158,11,0.6)]',
                    'bg-amber-500/40');
                if (icon) icon.classList.add('animate-pulse');
                if (label) label.textContent = 'Playing...';
            } else {
                btn.classList.remove('ring-2', 'ring-amber-400', 'shadow-[0_0_20px_rgba(245,158,11,0.6)]',
                    'bg-amber-500/40');
                if (icon) icon.classList.remove('animate-pulse');
                if (label) label.textContent = 'Play Audio';
            }
        }

        function playQuestionAudioIfAvailable(qObj) {
            const audioUrl = getQuestionAudioUrl(qObj);

            if (!audioUrl) {
                setQuestionAudioButtonState(false);
                if (!window.isGlobalMuted || !window.isGlobalMuted()) {
                    playFallbackQuestionSound();
                }
                return;
            }

            // Audio question auto-on: ensure sound is unmuted so user clearly hears question audio
            if (window.isGlobalMuted && window.isGlobalMuted()) {
                try {
                    sessionStorage.setItem('qx_sound_muted_manual', 'false');
                    localStorage.setItem('qx_sound_muted', 'false');
                    localStorage.setItem('qx_mute', '0');
                    if (typeof Sound !== 'undefined' && typeof Sound.setMuted === 'function') {
                        Sound.setMuted(false);
                    }
                    if (typeof window.updateSpeakerIcon === 'function') {
                        window.updateSpeakerIcon();
                    }
                } catch (e) {}
            }

            if (activeQuestionAudio) {
                try {
                    activeQuestionAudio.pause();
                    activeQuestionAudio.currentTime = 0;
                } catch (e) {}
                activeQuestionAudio = null;
            }
            if (questionAudioTimer) {
                clearTimeout(questionAudioTimer);
                questionAudioTimer = null;
            }

            try {
                if (window.stopAllQuizAudios) {
                    window.stopAllQuizAudios();
                }

                setQuestionAudioButtonState(true);

                const audio = window.playQuizAudio ? window.playQuizAudio(audioUrl, 0.95, false, 'question') :
                    new Audio(audioUrl);
                activeQuestionAudio = audio;

                if (audio) {
                    audio.onended = () => {
                        setQuestionAudioButtonState(false);
                        activeQuestionAudio = null;
                    };
                    audio.onpause = () => {
                        setQuestionAudioButtonState(false);
                    };
                    audio.onerror = () => {
                        setQuestionAudioButtonState(false);
                        activeQuestionAudio = null;
                        playFallbackQuestionSound();
                    };
                } else {
                    setQuestionAudioButtonState(false);
                }
            } catch (e) {
                setQuestionAudioButtonState(false);
                playFallbackQuestionSound();
            }
        }

        window.replayCurrentQuestionAudio = function(e) {
            if (e && typeof e.stopPropagation === 'function') {
                e.stopPropagation();
            }

            if (!currentQuestion) return;
            const audioUrl = getQuestionAudioUrl(currentQuestion);
            if (!audioUrl) return;

            // Unmute if currently muted so user can hear the requested replay audio
            if (window.isGlobalMuted && window.isGlobalMuted()) {
                if (typeof window.toggleGlobalMute === 'function') {
                    window.toggleGlobalMute();
                }
            }

            if (activeQuestionAudio) {
                try {
                    activeQuestionAudio.pause();
                    activeQuestionAudio.currentTime = 0;
                } catch (err) {}
                activeQuestionAudio = null;
            }
            if (questionAudioTimer) {
                clearTimeout(questionAudioTimer);
                questionAudioTimer = null;
            }

            setQuestionAudioButtonState(true);

            try {
                if (window.stopAllQuizAudios) {
                    window.stopAllQuizAudios();
                }

                const audio = window.playQuizAudio ? window.playQuizAudio(audioUrl, 0.95, false, 'question') :
                    new Audio(audioUrl);
                activeQuestionAudio = audio;

                if (audio) {
                    audio.onended = () => {
                        setQuestionAudioButtonState(false);
                        activeQuestionAudio = null;
                    };
                    audio.onpause = () => {
                        setQuestionAudioButtonState(false);
                    };
                    audio.onerror = () => {
                        setQuestionAudioButtonState(false);
                        activeQuestionAudio = null;
                    };
                } else {
                    setQuestionAudioButtonState(false);
                }
            } catch (err) {
                console.error('Error replaying question audio:', err);
                setQuestionAudioButtonState(false);
            }
        };

        function getQuestionMediaUrl(qObj) {
            if (!qObj) return null;

            const sessionQuestions = @json(session("quiz_{$id}_questions", []));
            const sessionQObj = (sessionQuestions && qObj) ? sessionQuestions.find(sq => sq.id === (qObj.id || qObj
                .question_id)) : null;

            let rawMedia = qObj ? (
                qObj.media_url ||
                qObj.media ||
                qObj.image_file ||
                (typeof qObj.question === 'object' && qObj.question ? (qObj.question.media_url || qObj.question
                    .media || qObj.question.image_file) : null)
            ) : null;

            if (!rawMedia && sessionQObj) {
                rawMedia = sessionQObj.media_url || sessionQObj.media || sessionQObj.image_file;
            }

            if (!rawMedia || typeof rawMedia !== 'string' || rawMedia.trim().length === 0) {
                return null;
            }

            const trimmed = rawMedia.trim();
            // Don't treat audio files as images
            if (/\.(mp3|wav|ogg|m4a|aac|flac)$/i.test(trimmed)) {
                return null;
            }

            if (trimmed.startsWith('http://') || trimmed.startsWith('https://')) {
                return trimmed;
            }

            if (typeof window.resolveMediaUrl === 'function') {
                return window.resolveMediaUrl(trimmed);
            }
            if (typeof window.resolveAudioUrl === 'function') {
                return window.resolveAudioUrl(trimmed);
            }

            const awsBaseUrl =
            "{{ rtrim(env('AWS_URL', 'https://chaloonline.s3.ap-south-1.amazonaws.com'), '/') }}";
            const cleanPath = trimmed.replace(/^\/+/, '');
            return awsBaseUrl + '/' + cleanPath;
        }

        function preloadAllQuestionMedia(questions) {
            if (!Array.isArray(questions)) return;
            questions.forEach(q => {
                const url = getQuestionMediaUrl(q);
                if (url) {
                    const img = new Image();
                    img.src = url;
                }
            });
        }

        function parseOptionBilingual(optText, optItem, idx, rawHindiOpts) {
            let hindiText = '';
            let englishText = '';

            // 1. Check if optItem has direct bilingual properties
            if (typeof optItem === 'object' && optItem !== null) {
                const hVal = optItem.hindi ?? optItem.hindi_text ?? optItem.hindi_option;
                if (hVal !== undefined && hVal !== null) {
                    hindiText = String(hVal).trim();
                }
                const eVal = optItem.english ?? optItem.english_text ?? optItem.option_text ?? optItem.text;
                if (eVal !== undefined && eVal !== null) {
                    englishText = String(eVal).trim();
                }
            }

            // 2. Check if rawHindiOpts array has a value at idx
            if (hindiText === '' && Array.isArray(rawHindiOpts) && rawHindiOpts[idx] !== undefined && rawHindiOpts[
                    idx] !== null) {
                let hVal = rawHindiOpts[idx];
                let hStr = typeof hVal === 'string' ? hVal : (hVal?.option_text ?? hVal?.text ?? hVal?.label ??
                    String(hVal));
                if (hStr !== null && hStr !== undefined && String(hStr).trim().length > 0) {
                    hindiText = String(hStr).trim();
                }
            }

            // 3. Merged parenthetical string pattern: "Part1 (Part2)"
            const textStr = (optText !== null && optText !== undefined ? String(optText) : '').trim();
            const parenMatch = textStr.match(/^(.*?)\s*\((.*?)\)$/);
            if (parenMatch) {
                const p1 = parenMatch[1].trim();
                const p2 = parenMatch[2].trim();
                const hasDevanagari1 = /[\u0900-\u097F]/.test(p1);
                const hasDevanagari2 = /[\u0900-\u097F]/.test(p2);

                if (hasDevanagari1 && !hasDevanagari2) {
                    hindiText = p1;
                    englishText = p2;
                } else if (!hasDevanagari1 && hasDevanagari2) {
                    hindiText = p2;
                    englishText = p1;
                } else {
                    // Both or neither in Devanagari script, keep p1 on top, p2 below
                    hindiText = p1;
                    englishText = p2;
                }
            }

            if (hindiText !== '' && englishText !== '' && hindiText.toLowerCase() !== englishText.toLowerCase()) {
                return {
                    isBilingual: true,
                    hindi: hindiText,
                    english: englishText
                };
            } else if (hindiText !== '' && englishText === '') {
                return {
                    isBilingual: false,
                    single: hindiText
                };
            } else if (englishText !== '' && hindiText === '') {
                return {
                    isBilingual: false,
                    single: englishText
                };
            } else {
                return {
                    isBilingual: false,
                    single: textStr
                };
            }
        }

        function renderQuestion(qObj) {
            if (!qObj) return;
            currentQuestion = qObj;
            hasSelectedOption = false;
            selectedAnswer = null;
            isAnswerSubmitted = false;
            activePhase = 1;

            const isSwitched = isQuestionSwitched(qObj);
            if (isSwitched) {
                hasSelectedOption = true;
                isAnswerSubmitted = true;
                setLifelinesEnabled(false);
            }

            const optionsContainer = document.getElementById('options-container');
            if (optionsContainer) {
                optionsContainer.classList.add('hidden', 'translate-y-3');
            }

            updateLifelinesUI();

            // 1. Synchronously update question UI in the DOM so text appears on page
            document.getElementById('q-num').textContent = currentQIndex + 1;
            document.getElementById('q-total').textContent = typeof window.getEffectiveTotalQuestionsCount === 'function' ? window.getEffectiveTotalQuestionsCount() : (questionLimit || questionsData.length || 10);
            const englishText = extractQuestionText(qObj);
            const hindiText = qObj ? (qObj.hindi_question || qObj.hindi_text || (typeof qObj.question === 'object' ?
                qObj.question?.hindi_question : null)) : null;
            const subEl = document.getElementById('sub-question-text');

            if (hindiText && typeof hindiText === 'string' && hindiText.trim().length > 0) {
                document.getElementById('question-text').textContent = hindiText.trim();
                if (subEl) {
                    subEl.textContent = englishText;
                    subEl.classList.remove('hidden');
                }
            } else {
                document.getElementById('question-text').textContent = englishText;
                if (subEl) {
                    subEl.textContent = '';
                    subEl.classList.add('hidden');
                }
            }

            // Question Audio Speaker Icon visibility
            const audioUrl = getQuestionAudioUrl(qObj);
            const audioContainer = document.getElementById('question-audio-container');
            if (audioContainer) {
                if (audioUrl) {
                    audioContainer.classList.remove('hidden');
                } else {
                    audioContainer.classList.add('hidden');
                }
            }
            setQuestionAudioButtonState(false);

            // 2. Instantly play preloaded question audio if in Phase 1
            const nowTime = Date.now() + (window.clockOffset || 0);
            const qFlowStartTime = window.getQuestionStartTimeMs ? window.getQuestionStartTimeMs() : Date.now();
            const totalElapsed = (nowTime - qFlowStartTime) / 1000;
            const qElapsed = totalElapsed >= 0 ? (totalElapsed % 17) : 0;
            if (qElapsed < 5) {
                playQuestionAudioIfAvailable(qObj);
            }

            // 3. Handle Question Image vs Top Banner
            const topBannerWrapper = document.getElementById('top-banner-wrapper');
            const questionMediaWrapper = document.getElementById('question-media-wrapper');
            const questionMediaImg = document.getElementById('question-media-img');
            const mediaUrl = getQuestionMediaUrl(qObj);

            if (mediaUrl) {
                if (topBannerWrapper) topBannerWrapper.classList.add('hidden');
                if (questionMediaWrapper) questionMediaWrapper.classList.remove('hidden');
                if (questionMediaImg) {
                    questionMediaImg.src = mediaUrl;
                    questionMediaImg.onerror = function() {
                        if (questionMediaWrapper) questionMediaWrapper.classList.add('hidden');
                        if (topBannerWrapper) topBannerWrapper.classList.remove('hidden');
                    };
                }
            } else {
                if (questionMediaWrapper) questionMediaWrapper.classList.add('hidden');
                if (topBannerWrapper) topBannerWrapper.classList.remove('hidden');
                if (questionMediaImg) questionMediaImg.src = '';
            }

            const switchBadge = document.getElementById('switch-question-badge');
            const skippedBadge = document.getElementById('skipped-question-banner');
            const qCard = document.getElementById('question-card');

            if (skippedBadge) {
                if (isSwitched) skippedBadge.classList.remove('hidden');
                else skippedBadge.classList.add('hidden');
            }

            if (qObj && (qObj.is_switch || qObj.is_switch_question)) {
                if (switchBadge) switchBadge.classList.remove('hidden');
                if (qCard) {
                    qCard.classList.add('border-2', 'border-cyan-400', 'shadow-[0_0_25px_rgba(34,211,238,0.5)]',
                        'bg-cyan-950/40');
                }
            } else {
                if (switchBadge) switchBadge.classList.add('hidden');
                if (qCard) {
                    qCard.classList.remove('border-2', 'border-cyan-400', 'shadow-[0_0_25px_rgba(34,211,238,0.5)]',
                        'bg-cyan-950/40');
                }
            }

            const optList = document.getElementById('options-list');
            optList.innerHTML = '';

            let rawOptions = qObj ? (qObj.options || (typeof qObj.question === 'object' && qObj.question ? qObj
                .question.options : null)) : null;
            if (typeof rawOptions === 'string') {
                try {
                    rawOptions = JSON.parse(rawOptions);
                } catch (e) {
                    rawOptions = [];
                }
            }
            if (!Array.isArray(rawOptions)) {
                rawOptions = [];
            }

            let rawHindiOpts = qObj ? (qObj.hindi_options || (typeof qObj.question === 'object' && qObj.question ?
                qObj.question.hindi_options : null)) : null;
            if (typeof rawHindiOpts === 'string') {
                try {
                    rawHindiOpts = JSON.parse(rawHindiOpts);
                } catch (e) {
                    rawHindiOpts = [];
                }
            }

            if (rawOptions.length > 1 && !qObj._shuffled) {
                qObj._shuffled = true;
                for (let i = rawOptions.length - 1; i > 0; i--) {
                    const j = Math.floor(Math.random() * (i + 1));
                    [rawOptions[i], rawOptions[j]] = [rawOptions[j], rawOptions[i]];
                    if (Array.isArray(rawHindiOpts) && rawHindiOpts.length === rawOptions.length) {
                        [rawHindiOpts[i], rawHindiOpts[j]] = [rawHindiOpts[j], rawHindiOpts[i]];
                    }
                }
            }

            const options = rawOptions;
            const OPT_LETTERS = ['A', 'B', 'C', 'D', 'E', 'F'];

            options.forEach((optItem, idx) => {
                let optText = '';
                if (optItem !== null && optItem !== undefined) {
                    if (typeof optItem === 'object') {
                        optText = optItem.option_text ?? optItem.text ?? optItem.label ?? JSON.stringify(
                            optItem);
                    } else {
                        optText = String(optItem);
                    }
                }

                const bilingual = parseOptionBilingual(optText, optItem, idx, rawHindiOpts);

                const wrap = document.createElement('div');
                wrap.className = 'kbc-opt-glow w-full h-full';

                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className =
                    'option-btn kbc-option kbc-option-flat active:scale-[0.98] w-full h-full min-h-[50px] text-center flex items-center justify-center p-2.5 sm:p-3 rounded-xl border border-border-glass bg-surface-container/80 hover:bg-surface-container-high transition-all cursor-pointer relative';
                btn.dataset.answer = optText;
                btn.dataset.index = idx;
                if (isSwitched) {
                    btn.disabled = true;
                    btn.classList.add('opacity-40', 'pointer-events-none');
                    btn.classList.remove('cursor-pointer', 'hover:bg-surface-container-high');
                } else {
                    btn.onclick = () => selectOption(optText, btn);
                }

                const contentHtml = bilingual.isBilingual ? `
                    <div class="flex flex-col items-center justify-center leading-tight w-full">
                        <span class="font-bold text-white text-xs sm:text-sm leading-snug break-words font-sans text-center">
                            ${escapeHtml(bilingual.hindi)}
                        </span>
                        <span class="text-amber-200/90 font-medium text-[11px] sm:text-xs mt-0.5 leading-snug break-words font-sans text-center">
                            ${escapeHtml(bilingual.english)}
                        </span>
                    </div>
                ` : `
                    <span class="font-body-lg text-on-surface text-xs sm:text-sm font-semibold leading-snug break-words font-sans text-center">${escapeHtml(bilingual.single)}</span>
                `;

                btn.innerHTML = `
                    <div class="flex-1 flex items-center justify-center min-w-0 text-center">
                        ${contentHtml}
                    </div>
                    <span class="material-symbols-outlined check-icon text-tertiary text-base opacity-0 transition-opacity font-bold shrink-0 self-center absolute right-2">check_circle</span>
                `;
                wrap.appendChild(btn);
                optList.appendChild(wrap);
            });

            if (isSwitched) {
                const optionBtns = optList.querySelectorAll('.option-btn');
                optionBtns.forEach(btn => {
                    btn.disabled = true;
                    btn.classList.add('opacity-40', 'pointer-events-none');
                    btn.classList.remove('cursor-pointer', 'hover:bg-surface-container-high', 'active:scale-[0.98]');
                });
            }

            // Apply 50:50 if used on this specific question (guarantees same 2 options on reload)
            const qId = qObj ? (qObj.id || qObj.question_id) : null;
            const saved5050QId = sessionStorage.getItem('qx_5050_qid_' + quizId) ||
                                 localStorage.getItem('qx_5050_qid_' + quizId) ||
                                 (window.userLifeline?.fifty_fifty ? window.userLifeline?.question_id : null);
            const is5050OnThisQ = is5050Used && qId && (String(saved5050QId) === String(qId));

            if (is5050OnThisQ) {
                applyFiftyFiftyToOptions(qObj);
            }

            // 4. Restore previously selected answer if page was reloaded or answer was already given
            if (qId) {
                let savedAns = sessionStorage.getItem('qx_selected_ans_' + quizId + '_' + qId) ||
                               localStorage.getItem('qx_selected_ans_' + quizId + '_' + qId);

                if (!savedAns) {
                    savedAns = sessionStorage.getItem('qx_selected_ans_' + quizId + '_' + String(qId)) ||
                               localStorage.getItem('qx_selected_ans_' + quizId + '_' + String(qId));
                }

                if (!savedAns && window.initialUserAnswers) {
                    const uAns = window.initialUserAnswers[qId] || window.initialUserAnswers[String(qId)] ||
                        (qObj.question_id && (window.initialUserAnswers[qObj.question_id] || window.initialUserAnswers[String(qObj.question_id)]));
                    if (uAns && uAns.answer) {
                        savedAns = uAns.answer;
                    }
                }

                if (!savedAns) {
                    try {
                        const rawSheet = sessionStorage.getItem('qx_anssheet_' + quizId) || localStorage.getItem('qx_anssheet_' + quizId);
                        if (rawSheet) {
                            const sheet = JSON.parse(rawSheet);
                            if (Array.isArray(sheet)) {
                                const found = sheet.find(item => item && (item.question_id === qId || String(item.question_id) === String(qId) || (qObj.question_id && (item.question_id === qObj.question_id || String(item.question_id) === String(qObj.question_id)))));
                                if (found && found.user_answer) {
                                    savedAns = found.user_answer;
                                }
                            }
                        }
                    } catch (e) {}
                }

                if (savedAns !== null && savedAns !== undefined && String(savedAns).trim() !== '') {
                    selectedAnswer = savedAns;
                    hasSelectedOption = true;
                    isAnswerSubmitted = true;
                    setLifelinesEnabled(false);

                    const allBtns = optList.querySelectorAll('.option-btn');
                    const normSaved = String(savedAns).replace(/[\u00A0\u1680\u180E\u2000-\u200B\u202F\u205F\u3000]/g, ' ').replace(/\s+/g, ' ').trim().toLowerCase();
                    const baseSaved = normSaved.replace(/\s*\(.*?\)\s*$/g, '').trim();

                    allBtns.forEach(b => {
                        b.disabled = true;
                        b.classList.add('opacity-60', 'pointer-events-none');
                        b.classList.remove('cursor-pointer', 'hover:bg-surface-container-high', 'active:scale-[0.98]');

                        const bAns = b.dataset.answer;
                        let isMatch = false;
                        if (bAns) {
                            const normB = String(bAns).replace(/[\u00A0\u1680\u180E\u2000-\u200B\u202F\u205F\u3000]/g, ' ').replace(/\s+/g, ' ').trim().toLowerCase();
                            const baseB = normB.replace(/\s*\(.*?\)\s*$/g, '').trim();
                            if (normB === normSaved || (baseB && baseSaved && baseB === baseSaved) || normB.includes(normSaved) || normSaved.includes(normB)) {
                                isMatch = true;
                            }
                        }

                        if (isMatch) {
                            b.classList.remove('opacity-60');
                            b.classList.add(
                                'selected',
                                'border-2',
                                'border-tertiary',
                                'bg-gradient-to-r',
                                'from-tertiary/20',
                                'via-purple-600/30',
                                'to-primary/20',
                                'shadow-[0_0_20px_rgba(244,191,50,0.5)]',
                                'scale-[1.02]'
                            );
                            const checkIcon = b.querySelector('.check-icon');
                            if (checkIcon) checkIcon.classList.remove('opacity-0');
                        }
                    });
                }
            }
        }

        let phase2StartTime = 0;

        function selectOption(optText, btnElement) {
            if (isAnswerSubmitted || hasSelectedOption || (currentQuestion && isQuestionSwitched(currentQuestion))) return;
            hasSelectedOption = true;
            selectedAnswer = (optText !== null && optText !== undefined) ? String(optText) : '';

            const qId = currentQuestion ? (currentQuestion.id || currentQuestion.question_id) : null;
            if (qId) {
                try {
                    sessionStorage.setItem('qx_selected_ans_' + quizId + '_' + qId, selectedAnswer);
                    localStorage.setItem('qx_selected_ans_' + quizId + '_' + qId, selectedAnswer);
                    if (currentQuestion && currentQuestion.question_id) {
                        sessionStorage.setItem('qx_selected_ans_' + quizId + '_' + currentQuestion.question_id, selectedAnswer);
                        localStorage.setItem('qx_selected_ans_' + quizId + '_' + currentQuestion.question_id, selectedAnswer);
                    }
                    if (btnElement && btnElement.dataset.index !== undefined) {
                        sessionStorage.setItem('qx_selected_idx_' + quizId + '_' + qId, btnElement.dataset.index);
                        localStorage.setItem('qx_selected_idx_' + quizId + '_' + qId, btnElement.dataset.index);
                    }
                } catch (e) {}
            }

            const timeTakenSec = phase2StartTime > 0 ? ((Date.now() - phase2StartTime) / 1000).toFixed(3) : '0.000';

            setLifelinesEnabled(false);

            document.querySelectorAll('#options-list .option-btn').forEach(b => {
                b.disabled = true;
                b.classList.add('opacity-60', 'pointer-events-none');
                b.classList.remove('cursor-pointer', 'hover:bg-surface-container-high',
                    'active:scale-[0.98]');
            });

            if (btnElement) {
                btnElement.classList.remove('opacity-60');
                btnElement.classList.add(
                    'selected',
                    'border-2',
                    'border-tertiary',
                    'bg-gradient-to-r',
                    'from-tertiary/20',
                    'via-purple-600/30',
                    'to-primary/20',
                    'shadow-[0_0_20px_rgba(244,191,50,0.5)]',
                    'scale-[1.02]'
                );

                const checkIcon = btnElement.querySelector('.check-icon');
                if (checkIcon) checkIcon.classList.remove('opacity-0');

                const letter = btnElement.querySelector('.kbc-opt-letter-flat');
                if (letter) {
                    letter.className =
                        'kbc-opt-letter-flat font-black text-black bg-tertiary text-xs px-2 py-0.5 rounded-md transition-all';
                }
            }

            if (typeof Sound !== 'undefined' && Sound.select) Sound.select();
            handleAnswerSubmit(timeTakenSec);
        }

        function getOrInitQuizFlowStartTime() {
            return window.getQuestionStartTimeMs ? window.getQuestionStartTimeMs() : (window
            .getQuizScheduleTimeMs() + 10000);
        }

        function startQuestionFlow() {
            quizFlowStartTime = getOrInitQuizFlowStartTime();
            if (activeTimer) clearInterval(activeTimer);

            syncQuestionFlowTick();
            activeTimer = setInterval(syncQuestionFlowTick, 200);
            window.QuizSpa.registerTimer(activeTimer);

            if (!isSyncListenerAttached) {
                isSyncListenerAttached = true;
                document.addEventListener('visibilitychange', () => {
                    if (!window.__isExitingQuiz && !document.hidden) syncQuestionFlowTick();
                });
                window.addEventListener('focus', () => {
                    if (!window.__isExitingQuiz) syncQuestionFlowTick();
                });
                window.addEventListener('pageshow', () => {
                    if (!window.__isExitingQuiz) syncQuestionFlowTick();
                });
            }
        }

        function syncQuestionFlowTick() {
            if (window.__isExitingQuiz) return;
            if (!questionsData || questionsData.length === 0 || isTransitioning) return;

            const now = Date.now() + (window.clockOffset || 0);
            const sched = window.getQuizScheduleTimeMs ? window.getQuizScheduleTimeMs() : Date.now();
            const flowStartTime = window.getQuestionStartTimeMs ? window.getQuestionStartTimeMs() : (sched + 10000);

            if (now < sched) {
                if (window.QuizSpa) window.QuizSpa.switchStage('waiting');
                return;
            }
            if (now < flowStartTime) {
                if (window.QuizSpa) window.QuizSpa.switchStage('start-counter');
                return;
            }

            const totalElapsedSec = (now - flowStartTime) / 1000;
            const totalQ = typeof window.getEffectiveTotalQuestionsCount === 'function' ? window.getEffectiveTotalQuestionsCount() : (questionLimit || questionsData.length || 10);
            const timedTargetIndex = Math.floor(totalElapsedSec / 17);
            const targetIndex = timedTargetIndex;
            const qElapsedSec = totalElapsedSec - (timedTargetIndex * 17);

            if (targetIndex >= totalQ) {
                stopQuestionBackgroundSound();
                window.__isQuizCompleted = true;
                isTransitioning = true;
                if (activeTimer) clearInterval(activeTimer);

                try {
                    sessionStorage.setItem('qx_completed_' + quizId, 'true');
                    localStorage.setItem('qx_completed_' + quizId, 'true');
                } catch (e) {}

                // Backup single-requester fallback trigger when question answering finishes
                if (!sessionStorage.getItem('qx_result_proc_sent_' + quizId)) {
                    setTimeout(() => {
                        if (!sessionStorage.getItem('qx_result_proc_sent_' + quizId) && typeof window.triggerQuizProcessResultOnce === 'function') {
                            window.triggerQuizProcessResultOnce(quizId);
                        }
                    }, 5000 + Math.floor(Math.random() * 3000));
                }
                const isSwitchUsed = sessionStorage.getItem('qx_used_switch_' + quizId) === 'true' ||
                                     localStorage.getItem('qx_used_switch_' + quizId) === 'true';
                const baseQ = window.totalQuestionsCount || questionLimit || 10;
                const userQCount = isSwitchUsed ? (baseQ + 1) : baseQ;
                const reviewEndMs = flowStartTime + (userQCount * 17000) + 10000;
                window.QuizSpa.switchStage(now >= reviewEndMs ? 'answer-sheet' : 'review-counter');
                return;
            }

            if (targetIndex !== currentQIndex) {
                if (targetIndex >= totalQ) return;
                isTransitioning = true;
                stopQuestionBackgroundSound();
                setLifelinesEnabled(false);

                currentQIndex = targetIndex;
                selectedAnswer = null;
                hasSelectedOption = false;
                isAnswerSubmitted = false;

                if (questionsData && questionsData[currentQIndex]) {
                    renderQuestion(questionsData[currentQIndex]);
                }
                isTransitioning = false;
                return;
            }

            if (qElapsedSec < 5) {
                renderPhase1Sync(5 - qElapsedSec);
            } else {
                renderPhase2Sync(17 - qElapsedSec, qElapsedSec - 5);
            }
        }

        function renderPhase1Sync(remainingSec) {
            activePhase = 1;
            const optionsContainer = document.getElementById('options-container');
            if (optionsContainer && !optionsContainer.classList.contains('hidden')) {
                optionsContainer.classList.add('hidden', 'translate-y-3');
            }
            setLifelinesEnabled(false);

            const secondsLeft = Math.max(0, Math.ceil(remainingSec));
            const ring = document.getElementById('timer-ring');
            const timerSec = document.getElementById('timer-sec');
            const timerLabel = document.getElementById('timer-label');
            const phaseBadge = document.getElementById('phase-badge');

            if (timerSec) timerSec.textContent = secondsLeft;
            if (timerLabel) timerLabel.textContent = 'Question';
            if (phaseBadge) {
                phaseBadge.textContent = 'Question Appearing';
                phaseBadge.className =
                    'font-label-caps bg-secondary/20 text-secondary px-3 py-1 rounded-full border border-secondary/40 text-[11px] font-bold';
            }

            if (ring) {
                ring.classList.remove('text-primary');
                ring.classList.add('text-tertiary');
                const circumference = 263.89;
                const offset = circumference - (secondsLeft / 5) * circumference;
                ring.style.strokeDashoffset = offset;
            }
        }

        function renderPhase2Sync(remainingSec, elapsedSec) {
            const isEnteringAnswerPhase = activePhase !== 2;
            if (activePhase !== 2) {
                activePhase = 2;
                const isCustomAudioPlaying = activeQuestionAudio && !activeQuestionAudio.paused && !
                    activeQuestionAudio.ended && getQuestionAudioUrl(currentQuestion);
                if (!isCustomAudioPlaying) {
                    if (activeQuestionAudio) {
                        try {
                            activeQuestionAudio.pause();
                            activeQuestionAudio.currentTime = 0;
                        } catch (e) {}
                        activeQuestionAudio = null;
                    }
                    if (questionAudioTimer) {
                        clearTimeout(questionAudioTimer);
                        questionAudioTimer = null;
                    }
                    setQuestionAudioButtonState(false);
                    playSoundByKey('answer_appear_sound');
                }
            }

            const optionsContainer = document.getElementById('options-container');
            if (optionsContainer && optionsContainer.classList.contains('hidden')) {
                optionsContainer.classList.remove('hidden', 'translate-y-3');
            }
            if (isEnteringAnswerPhase) {
                startQuestionBackgroundSound();
            }

            const isCurrentSwitched = isQuestionSwitched(currentQuestion);

            if (isCurrentSwitched) {
                const optBtns = document.querySelectorAll('#options-list .option-btn');
                optBtns.forEach(btn => {
                    btn.disabled = true;
                    btn.classList.add('opacity-40', 'pointer-events-none');
                    btn.classList.remove('cursor-pointer', 'hover:bg-surface-container-high', 'active:scale-[0.98]');
                });
                setLifelinesEnabled(false);
            } else if (!hasSelectedOption && !isAnswerSubmitted) {
                setLifelinesEnabled(true);
            } else {
                setLifelinesEnabled(false);
            }

            phase2StartTime = Date.now() - Math.floor(elapsedSec * 1000);

            const secondsLeft = Math.max(0, Math.ceil(remainingSec));
            const ring = document.getElementById('timer-ring');
            const timerSec = document.getElementById('timer-sec');
            const timerLabel = document.getElementById('timer-label');
            const phaseBadge = document.getElementById('phase-badge');

            if (timerSec) timerSec.textContent = secondsLeft;
            if (timerLabel) timerLabel.textContent = 'Answer';
            if (phaseBadge) {
                if (isCurrentSwitched) {
                    phaseBadge.textContent = 'Question Skipped';
                    phaseBadge.className =
                        'font-label-caps bg-amber-500/20 text-amber-300 px-3 py-1 rounded-full border border-amber-400/40 text-[11px] font-bold';
                } else {
                    phaseBadge.textContent = 'Select Answer';
                    phaseBadge.className =
                        'font-label-caps bg-primary/20 text-primary px-3 py-1 rounded-full border border-primary/40 text-[11px] font-bold';
                }
            }

            if (ring) {
                ring.classList.remove('text-tertiary');
                ring.classList.add('text-primary');
                const circumference = 263.89;
                const offset = circumference - (secondsLeft / 12) * circumference;
                ring.style.strokeDashoffset = offset;
            }

        }

        // Compile and validate complete local answer sheet so server request is completely bypassed
        window.finalizeAndValidateClientAnssheet = function() {
            try {
                const targetQuizId = window.quizId;
                if (!targetQuizId) return null;
                const sheetKey = 'qx_anssheet_' + targetQuizId;
                let clientSheet = [];
                const rawSheet = sessionStorage.getItem(sheetKey) || localStorage.getItem(sheetKey);
                if (rawSheet) {
                    try { clientSheet = JSON.parse(rawSheet); } catch (e) {}
                }
                if (!Array.isArray(clientSheet)) clientSheet = [];

                let qPool = (typeof questionsData !== 'undefined' && Array.isArray(questionsData) && questionsData.length > 0)
                    ? questionsData
                    : (window.initialQuestions || []);

                if (!qPool || qPool.length === 0) {
                    try {
                        const cachedQStr = sessionStorage.getItem('qx_cached_questions_' + targetQuizId) ||
                                           localStorage.getItem('qx_cached_questions_' + targetQuizId);
                        if (cachedQStr) qPool = JSON.parse(cachedQStr);
                    } catch (e) {}
                }

                if (!Array.isArray(qPool) || qPool.length === 0) {
                    return clientSheet;
                }

                const isSwitchUsed = sessionStorage.getItem('qx_used_switch_' + targetQuizId) === 'true' ||
                                     localStorage.getItem('qx_used_switch_' + targetQuizId) === 'true';
                let switchedIds = [];
                try {
                    const rawSwitched = sessionStorage.getItem('qx_switched_q_ids_' + targetQuizId) ||
                                         localStorage.getItem('qx_switched_q_ids_' + targetQuizId);
                    if (rawSwitched) switchedIds = JSON.parse(rawSwitched);
                } catch (e) {}

                const finalSheet = [];
                for (let idx = 0; idx < qPool.length; idx++) {
                    const q = qPool[idx];
                    if (!q || !q.id) continue;
                    if (switchedIds.includes(q.id) || switchedIds.includes(String(q.id)) || switchedIds.includes(Number(q.id))) {
                        continue;
                    }

                    const existing = clientSheet.find(item => item && (item.question_id === q.id || Number(item.question_id) === Number(q.id)));
                    const correctAnsStr = q.correct_answer || (existing ? existing.correct_answer : '') || '';
                    const userAnsStr = existing && existing.user_answer !== undefined && existing.user_answer !== null ? String(existing.user_answer) : '';
                    const isAttempted = userAnsStr.trim() !== '';

                    let isCorrect = existing ? !!existing.is_correct : false;
                    if (isAttempted && correctAnsStr) {
                        const normUser = userAnsStr.replace(/[\u00A0\u1680\u180E\u2000-\u200B\u202F\u205F\u3000]/g, ' ').replace(/\s+/g, ' ').trim().toLowerCase();
                        const normCorrect = correctAnsStr.replace(/[\u00A0\u1680\u180E\u2000-\u200B\u202F\u205F\u3000]/g, ' ').replace(/\s+/g, ' ').trim().toLowerCase();
                        if (normUser === normCorrect) {
                            isCorrect = true;
                        } else {
                            const uBase = normUser.replace(/\s*\(.*?\)\s*$/g, '').trim();
                            const cBase = normCorrect.replace(/\s*\(.*?\)\s*$/g, '').trim();
                            if (uBase && cBase && uBase === cBase) isCorrect = true;
                        }
                    }

                    finalSheet.push({
                        question_id: q.id,
                        question: q.question || q.question_text || ('Question ' + (idx + 1)),
                        hindi_question: q.hindi_question || null,
                        question_audio: q.question_audio || q.audio_file || null,
                        media_url: q.media_url || null,
                        options: Array.isArray(q.options) ? q.options : (existing?.options || []),
                        correct_answer: correctAnsStr,
                        user_answer: userAnsStr,
                        is_correct: isCorrect,
                        total_time: existing && existing.total_time ? parseFloat(existing.total_time) : (isAttempted ? 12.000 : 0)
                    });
                }

                if (isSwitchUsed) {
                    let switchPool = (typeof switchQuestionsData !== 'undefined' && Array.isArray(switchQuestionsData) && switchQuestionsData.length > 0)
                        ? switchQuestionsData
                        : (window.initialSwitchQuestions || []);
                    if (!switchPool || switchPool.length === 0) {
                        try {
                            const cachedSwStr = sessionStorage.getItem('qx_cached_switch_questions_' + targetQuizId) ||
                                                 localStorage.getItem('qx_cached_switch_questions_' + targetQuizId);
                            if (cachedSwStr) switchPool = JSON.parse(cachedSwStr);
                        } catch (e) {}
                    }
                    if (Array.isArray(switchPool) && switchPool.length > 0) {
                        const sq = switchPool[0];
                        const existingSw = clientSheet.find(item => item && (item.question_id === sq.id || Number(item.question_id) === Number(sq.id)));
                        if (existingSw || !finalSheet.some(f => f.question_id === sq.id)) {
                            const correctAnsStr = sq.correct_answer || (existingSw ? existingSw.correct_answer : '') || '';
                            const userAnsStr = existingSw && existingSw.user_answer !== undefined && existingSw.user_answer !== null ? String(existingSw.user_answer) : '';
                            const isAttempted = userAnsStr.trim() !== '';

                            let isCorrect = existingSw ? !!existingSw.is_correct : false;
                            if (isAttempted && correctAnsStr) {
                                const normUser = userAnsStr.replace(/[\u00A0\u1680\u180E\u2000-\u200B\u202F\u205F\u3000]/g, ' ').replace(/\s+/g, ' ').trim().toLowerCase();
                                const normCorrect = correctAnsStr.replace(/[\u00A0\u1680\u180E\u2000-\u200B\u202F\u205F\u3000]/g, ' ').replace(/\s+/g, ' ').trim().toLowerCase();
                                if (normUser === normCorrect) {
                                    isCorrect = true;
                                } else {
                                    const uBase = normUser.replace(/\s*\(.*?\)\s*$/g, '').trim();
                                    const cBase = normCorrect.replace(/\s*\(.*?\)\s*$/g, '').trim();
                                    if (uBase && cBase && uBase === cBase) isCorrect = true;
                                }
                            }

                            finalSheet.push({
                                question_id: sq.id,
                                question: sq.question || sq.question_text || 'Switch Question',
                                hindi_question: sq.hindi_question || null,
                                question_audio: sq.question_audio || sq.audio_file || null,
                                media_url: sq.media_url || null,
                                options: Array.isArray(sq.options) ? sq.options : (existingSw?.options || []),
                                correct_answer: correctAnsStr,
                                user_answer: userAnsStr,
                                is_correct: isCorrect,
                                total_time: existingSw && existingSw.total_time ? parseFloat(existingSw.total_time) : (isAttempted ? 12.000 : 0)
                            });
                        }
                    }
                }

                if (finalSheet.length > 0) {
                    sessionStorage.setItem(sheetKey, JSON.stringify(finalSheet));
                    localStorage.setItem(sheetKey, JSON.stringify(finalSheet));
                    sessionStorage.setItem('qx_anssheet_finalized_' + targetQuizId, 'true');
                    localStorage.setItem('qx_anssheet_finalized_' + targetQuizId, 'true');
                    return finalSheet;
                }

                return clientSheet;
            } catch (e) {
                console.warn('finalizeAndValidateClientAnssheet error:', e);
                return null;
            }
        };

        window.triggerQuizProcessResultOnce = async function(qId) {
            try {
                const targetQuizId = qId || quizId;
                const triggerKey = 'qx_result_proc_sent_' + targetQuizId;
                if (sessionStorage.getItem(triggerKey)) return;
                sessionStorage.setItem(triggerKey, 'true');

                const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
                await fetch(`/fsrapp/quizes/api/process-result/${targetQuizId}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf || '',
                        'Accept': 'application/json'
                    },
                    keepalive: true
                });
            } catch (e) {
                console.warn('triggerQuizProcessResultOnce error:', e);
            }
        };

        function handleAnswerSubmit(timeTakenSec) {
            if (window.__isExitingQuiz) return Promise.resolve(false);

            const nowTime = Date.now() + (window.clockOffset || 0);
            const schedTime = window.getQuizScheduleTimeMs ? window.getQuizScheduleTimeMs() : Date.now();
            const minSubmitTime = schedTime + 15000; // sched + 10s start counter + 5s question appear = 15s
            if (nowTime < minSubmitTime) {
                console.warn('[Quiz Submission Guard] Attempted submission before schedule time + 15s.');
                return Promise.resolve(false);
            }

            const qObj = currentQuestion;
            if (!qObj) return Promise.resolve(false);
            const qId = qObj.id;
            const isSwitched = isQuestionSwitched(qObj);
            if (isSwitched) return Promise.resolve(false);
            const qIndexToUse = currentQIndex;

            if (submissionConfirmed[qId]) return Promise.resolve(true);
            if (submissionSent[qId]) return submissionPromises[qId] || Promise.resolve(false);
            if (submissionPromises[qId]) return submissionPromises[qId];

            if (selectedAnswer === null || selectedAnswer === undefined || selectedAnswer === '') {
                return Promise.resolve(false);
            }

            isAnswerSubmitted = true;

            const answerToSubmit = String(selectedAnswer);
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

            // Immediately save answer locally in clientSheet so it is NEVER lost on client
            try {
                const sheetKey = 'qx_anssheet_' + quizId;
                let clientSheet = [];
                const rawSheet = sessionStorage.getItem(sheetKey) || localStorage.getItem(sheetKey);
                if (rawSheet) {
                    try { clientSheet = JSON.parse(rawSheet); } catch (e) {}
                }
                if (!Array.isArray(clientSheet)) clientSheet = [];

                const correctAnsStr = qObj.correct_answer || '';
                const userAnsStr = answerToSubmit;
                let isCorrect = false;

                if (correctAnsStr && userAnsStr) {
                    const normUser = String(userAnsStr).replace(/[\u00A0\u1680\u180E\u2000-\u200B\u202F\u205F\u3000]/g, ' ').replace(/\s+/g, ' ').trim().toLowerCase();
                    const normCorrect = String(correctAnsStr).replace(/[\u00A0\u1680\u180E\u2000-\u200B\u202F\u205F\u3000]/g, ' ').replace(/\s+/g, ' ').trim().toLowerCase();
                    if (normUser === normCorrect) {
                        isCorrect = true;
                    } else {
                        const uBase = normUser.replace(/\s*\(.*?\)\s*$/g, '').trim();
                        const cBase = normCorrect.replace(/\s*\(.*?\)\s*$/g, '').trim();
                        if (uBase && cBase && uBase === cBase) isCorrect = true;
                    }
                }

                const itemDetail = {
                    question_id: qId,
                    question: qObj.question || ('Question ' + (qIndexToUse + 1)),
                    hindi_question: qObj.hindi_question || null,
                    options: qObj.options || [],
                    correct_answer: correctAnsStr,
                    user_answer: userAnsStr,
                    is_correct: isCorrect,
                    total_time: parseFloat(timeTakenSec)
                };

                const existingIdx = clientSheet.findIndex(item => item.question_id === qId);
                if (existingIdx >= 0) {
                    clientSheet[existingIdx] = itemDetail;
                } else {
                    clientSheet.push(itemDetail);
                }

                sessionStorage.setItem(sheetKey, JSON.stringify(clientSheet));
                localStorage.setItem(sheetKey, JSON.stringify(clientSheet));
            } catch (e) {
                console.error('Error saving local answer:', e);
            }

            const sentKey = `qx_answer_sent_${quizId}_${qId}`;
            try {
                if (sessionStorage.getItem(sentKey)) {
                    submissionSent[qId] = true;
                    return Promise.resolve(false);
                }
                sessionStorage.setItem(sentKey, 'true');
            } catch (e) {
                console.warn('Could not persist answer submission guard:', e);
            }
            submissionSent[qId] = true;

            const promise = (async () => {
                if (window.__isExitingQuiz) return false;

                try {
                    const res = await fetch(`/fsrapp/quizes/api/submit-answer/${quizId}`, {
                        method: 'POST',
                        keepalive: true,
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrf || '',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            question_id: qId,
                            answer: answerToSubmit,
                            total_time: timeTakenSec,
                            user_id: window.quizUserId || null
                        })
                    });
                    const json = await res.json().catch(() => null);

                    if (!res.ok || !json || !(json.success || json.question_id || json.correct_answer)) {
                        console.warn(
                            `Answer submission failed for question ${qId} (${res.status}):`,
                            json?.message || res.statusText
                        );
                        return false;
                    }

                    submissionConfirmed[qId] = true;
                    if (qIndexToUse >= (questionLimit - 1) || qIndexToUse >= 9) {
                        window.__isQuizCompleted = true;
                        try {
                            sessionStorage.setItem('qx_completed_' + quizId, 'true');
                            localStorage.setItem('qx_completed_' + quizId, 'true');
                        } catch (e) {}
                    }

                    if (typeof json.is_correct === 'boolean') {
                        try {
                            const sheetKey = 'qx_anssheet_' + quizId;
                            const rawSheet = sessionStorage.getItem(sheetKey) || localStorage.getItem(sheetKey);
                            if (rawSheet) {
                                const clientSheet = JSON.parse(rawSheet);
                                const target = clientSheet.find(item => String(item.question_id) === String(qId));
                                if (target) {
                                    target.is_correct = json.is_correct;
                                    if (json.correct_answer) target.correct_answer = json.correct_answer;
                                    sessionStorage.setItem(sheetKey, JSON.stringify(clientSheet));
                                    localStorage.setItem(sheetKey, JSON.stringify(clientSheet));
                                }
                            }
                        } catch (e) {}
                    }

                    return true;
                } catch (e) {
                    console.error(`Answer submission failed for question ${qId}`, e);
                    return false;
                }
            })();

            submissionPromises[qId] = promise;
            return promise;
        }

        function escapeHtml(str) {
            return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g,
                '&quot;');
        }
    })();
</script>
