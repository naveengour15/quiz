<main class="min-h-screen px-container-margin pt-3 pb-12 flex flex-col kbc-stage text-white relative" style="touch-action: pan-y !important; -webkit-overflow-scrolling: touch !important; overflow-y: visible !important;">
    <div class="kbc-spotlight"></div>
    <div class="kbc-stage-rings"><span></span><span></span><span></span><span></span></div>

    <!-- Top Speed Header & Master 75s Timer -->
    <div class="relative z-[2] w-full max-w-md mx-auto space-y-2 mb-2">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span
                    class="font-label-caps bg-primary/20 px-3 py-1 rounded-full border border-primary/40 text-primary text-[11px] font-bold">
                    Q <span id="fff-q-num">1</span> / <span id="fff-q-total">{{ $totalQuestionsCount ?? 15 }}</span>
                </span>
                <span
                    class="font-label-caps bg-tertiary/20 text-tertiary px-3 py-1 rounded-full border border-tertiary/40 text-[11px] font-bold">
                    Attempted: <span id="fff-attempted-count">0</span>
                </span>
            </div>

            <button type="button" id="kbc-mute-toggle" class="kbc-icon-badge" onclick="toggleGlobalMute()">
                <span class="material-symbols-outlined text-[18px]">volume_up</span>
            </button>
        </div>

        <!-- 75-Second Master Timer Bar & Dial -->
        <div
            class="glass-card kbc-card rounded-2xl p-2.5 px-3.5 border border-primary/30 shadow-[0_0_15px_rgba(210,187,255,0.2)] flex items-center gap-3">
            <div class="kbc-dial-wrap relative w-12 h-12 shrink-0 flex items-center justify-center">
                <svg class="w-full h-full transform -rotate-90" viewBox="0 0 100 100">
                    <circle cx="50" cy="50" r="42" stroke="currentColor" stroke-width="7"
                        class="text-surface-container-high" fill="transparent" />
                    <circle id="fff-master-ring" cx="50" cy="50" r="42" stroke="currentColor"
                        stroke-width="7" stroke-dasharray="263.89" stroke-dashoffset="0" stroke-linecap="round"
                        fill="transparent"
                        class="text-tertiary transition-all duration-1000 ease-linear drop-shadow-[0_0_8px_rgba(244,191,50,0.8)]" />
                </svg>
                <div class="absolute inset-0 flex items-center justify-center">
                    <span id="fff-master-sec"
                        class="font-display text-base font-black text-white leading-none">75</span>
                </div>
            </div>

            <div class="flex-1 space-y-1">
                <div
                    class="flex justify-between items-center text-[10px] font-bold text-slate-300 uppercase tracking-wider">
                    <span class="text-tertiary">⚡ 75 SECONDS SPEED ROUND</span>
                    <span id="fff-timer-status" class="text-mint font-bold">5s / Question</span>
                </div>
                <div class="w-full bg-surface-container-high rounded-full h-2 overflow-hidden border border-white/10">
                    <div id="fff-progress-bar"
                        class="bg-gradient-to-r from-primary via-purple-500 to-tertiary h-2 rounded-full transition-all duration-1000 ease-linear"
                        style="width: 100%;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Center KBC Question Area -->
    <div class="relative z-[1] w-full max-w-md mx-auto text-center space-y-2 mt-1 mb-4">
        <!-- Top Banner / Question Media Area -->
        <div id="fff-top-banner-wrapper" class="w-full [&>section]:!mb-0 [&_.glass-card]:!min-h-0">
            @include('fsrapp.home.components.banners', [
                'banners' => $banners,
                'pageType' => 'question',
                'disableClick' => true,
            ])
        </div>

        <div id="fff-media-wrapper" class="w-full hidden mb-1">
            <div
                class="glass-card kbc-card kbc-glow-inner rounded-2xl overflow-hidden shadow-2xl border border-primary/40 p-1 flex items-center justify-center bg-black/40 backdrop-blur-md max-h-[160px] sm:max-h-[200px]">
                <img id="fff-media-img" src="" alt="Question Image"
                    class="w-full h-auto max-h-[150px] sm:max-h-[190px] object-contain rounded-xl transition-all duration-300 shadow-md">
            </div>
        </div>


        <!-- Question Box -->
        <div class="kbc-glow-border">
            <div id="fff-question-card"
                class="glass-card kbc-card kbc-glow-inner rounded-2xl p-4 sm:p-5 shadow-2xl transition-all duration-300">
                <h2 id="fff-question-text"
                    class="font-headline-lg text-sm sm:text-base font-bold text-white leading-relaxed text-center min-h-[48px] flex items-center justify-center kbc-title-glow break-words">
                    Loading Question...
                </h2>
                <p id="fff-sub-question-text"
                    class="hidden text-amber-200/90 text-xs sm:text-sm font-semibold text-center mt-1.5 leading-relaxed font-sans break-words">
                </p>

                <!-- Question Audio Speaker Replay Button -->
                <div id="fff-question-audio-container" class="hidden mt-2.5 flex items-center justify-center">
                    <button type="button" id="btn-fff-question-audio" onclick="replayFffQuestionAudio(event)"
                        class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full bg-gradient-to-r from-amber-500/20 via-yellow-400/25 to-amber-500/20 border border-amber-400/60 hover:border-amber-300 text-amber-300 hover:text-white transition-all duration-200 shadow-[0_0_12px_rgba(245,158,11,0.25)] active:scale-95 cursor-pointer select-none group"
                        title="Question Audio Play Karein">
                        <span id="fff-speaker-icon"
                            class="material-symbols-outlined text-[19px] transition-transform group-hover:scale-110">volume_up</span>
                        <span id="fff-speaker-label" class="text-[11px] font-bold tracking-wide">Play Audio</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Options Grid (Standard KBC 2-column layout) -->
        <div id="fff-options-list" class="grid grid-cols-2 gap-2.5">
            <!-- Dynamically populated options -->
        </div>

        <!-- 5-Second Question Submission Countdown (Directly below options, matching main quiz question counter) -->
        <div class="flex items-center justify-center gap-2.5 w-full max-w-sm mx-auto px-1 pt-1.5 pb-1"
            id="fff-counter-row">
            <div class="flex justify-center items-center shrink-0">
                <div class="kbc-dial-wrap relative !w-14 !h-14 !my-0 flex items-center justify-center"
                    id="fff-qplay-dial-wrap" style="width: 56px; height: 56px; margin: 0 auto;">
                    <svg class="w-full h-full transform -rotate-90" viewBox="0 0 100 100">
                        <circle cx="50" cy="50" r="42" stroke="currentColor" stroke-width="7"
                            class="text-surface-container-high" fill="transparent" />
                        <circle id="fff-q-timer-ring" cx="50" cy="50" r="42" stroke="currentColor"
                            stroke-width="7" class="text-tertiary transition-all duration-1000 ease-linear"
                            fill="transparent" stroke-dasharray="263.89" stroke-dashoffset="0" stroke-linecap="round" />
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                        <span id="fff-q-timer-sec" class="font-display text-base font-black text-white leading-none">5</span>
                        <span id="fff-q-timer-label"
                            class="font-label-caps text-[6.5px] uppercase tracking-wider text-on-surface-variant mt-0.5">Answer</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
    (function initFffEngine() {
        let questions = [];
        let currentIndex = 0;
        let attemptedCount = 0;
        let questionShownTimeMs = 0;
        let totalTimeSec = 75;
        let remainingSec = 75;
        let masterInterval = null;
        let isHandlingAnswer = false;
        let activeQuestionAudio = null;

        window.initFffQuestionScreen = async function() {
            if (window.checkAndHandleFffQuizExpiry && window.checkAndHandleFffQuizExpiry()) return;

            // Stop previous audios
            if (window.stopAllQuizAudios) window.stopAllQuizAudios();

            let answeredIds = new Set();
            let isAllAnswered = false;

            // Load questions from embedded initial payload or fallback API
            if (window.initialFffQuestions && Array.isArray(window.initialFffQuestions) && window
                .initialFffQuestions.length > 0) {
                questions = window.initialFffQuestions;
                const totalEl = document.getElementById('fff-q-total');
                if (totalEl) totalEl.textContent = questions.length;
                preloadAllFffQuestionMedia(questions);
            } else {
                try {
                    const res = await fetch('/fsrapp/fff/api/questions/' + window.fffQuizId);
                    const json = await res.json();
                    if (json && json.data && json.data.length > 0) {
                        questions = json.data;
                        const totalEl = document.getElementById('fff-q-total');
                        if (totalEl) totalEl.textContent = questions.length;

                        preloadAllFffQuestionMedia(questions);

                        if (Array.isArray(json.answered_question_ids)) {
                            json.answered_question_ids.forEach(id => answeredIds.add(Number(id)));
                        }
                        if (json.is_all_answered) {
                            isAllAnswered = true;
                        }
                    }
                } catch (e) {
                    console.error('Failed to load FFF questions', e);
                }
            }

            if (!questions || questions.length === 0) {
                window.FffSpa.showStage('review-counter');
                return;
            }

            // Restore answered questions from server (initialUserAnswers) and device storage
            window.__fffClientAnswers = window.__fffClientAnswers || {};
            if (window.initialUserAnswers && typeof window.initialUserAnswers === 'object') {
                Object.keys(window.initialUserAnswers).forEach(qId => {
                    window.__fffClientAnswers[qId] = window.initialUserAnswers[qId];
                    answeredIds.add(Number(qId));
                });
            }
            const mergeStoredAnswers = (storageStr) => {
                if (!storageStr) return;
                try {
                    const parsed = JSON.parse(storageStr);
                    if (parsed && typeof parsed === 'object') {
                        Object.keys(parsed).forEach(qId => {
                            if (!window.__fffClientAnswers[qId]) {
                                window.__fffClientAnswers[qId] = parsed[qId];
                            }
                            answeredIds.add(Number(qId));
                        });
                    }
                } catch (e) {}
            };
            try {
                mergeStoredAnswers(localStorage.getItem('fff_anssheet_' + window.fffQuizId));
                mergeStoredAnswers(sessionStorage.getItem('fff_anssheet_' + window.fffQuizId));
            } catch (e) {}

            try {
                const localAnsweredStr = sessionStorage.getItem('fff_answered_ids_' + window.fffQuizId) ||
                    localStorage.getItem('fff_answered_ids_' + window.fffQuizId);
                if (localAnsweredStr) {
                    const parsed = JSON.parse(localAnsweredStr);
                    if (Array.isArray(parsed)) {
                        parsed.forEach(id => answeredIds.add(Number(id)));
                    }
                }
            } catch (e) {}

            // Persist the combined set back to storage so neither refresh nor submission can wipe it
            try {
                const combinedSheetJson = JSON.stringify(window.__fffClientAnswers);
                sessionStorage.setItem('fff_anssheet_' + window.fffQuizId, combinedSheetJson);
                localStorage.setItem('fff_anssheet_' + window.fffQuizId, combinedSheetJson);
                sessionStorage.setItem('fff_answered_ids_' + window.fffQuizId, JSON.stringify(Array.from(answeredIds)));
                localStorage.setItem('fff_answered_ids_' + window.fffQuizId, JSON.stringify(Array.from(answeredIds)));
            } catch (e) {}

            const now = Date.now() + (window.clockOffset || 0);
            const sched = typeof window.getFffQuizScheduleTimeMs === 'function' ? window.getFffQuizScheduleTimeMs() : Date.now();
            const qStartTime = typeof window.getFffQuestionStartTimeMs === 'function' ? window
                .getFffQuestionStartTimeMs() : (sched + 10000);
            const qEndTime = qStartTime + 75000;

            if (now < sched) {
                if (window.FffSpa) window.FffSpa.showStage('waiting');
                return;
            }
            if (now < qStartTime) {
                if (window.FffSpa) window.FffSpa.showStage('start-counter');
                return;
            }

            const elapsed = (now - qStartTime) / 1000;

            if (now >= qEndTime || elapsed >= 75) {
                finishFffQuestions('time_up');
                return;
            }

            // Time-based question positioning: each question occupies 5 seconds
            const timeBasedIndex = Math.min(questions.length - 1, Math.max(0, Math.floor(elapsed / 5)));
            currentIndex = timeBasedIndex;
            attemptedCount = answeredIds.size;

            renderCurrentQuestion();
            startMaster75sTimer();
        };

        function startMaster75sTimer() {
            if (masterInterval) clearInterval(masterInterval);
            const ring = document.getElementById('fff-master-ring');
            const secEl = document.getElementById('fff-master-sec');
            const bar = document.getElementById('fff-progress-bar');
            const timerStatusEl = document.getElementById('fff-timer-status');
            const circumference = 263.89;
            const qStartTime = typeof window.getFffQuestionStartTimeMs === 'function' ? window
                .getFffQuestionStartTimeMs() : ((window.getFffQuizScheduleTimeMs ? window
                    .getFffQuizScheduleTimeMs() : Date.now()) + 10000);
            const qEndTime = qStartTime + 75000;

            let lastFlushedBatchIndex = 0;

            function updateMasterTimer() {
                if (window.checkAndHandleFffQuizExpiry && window.checkAndHandleFffQuizExpiry()) return;

                if (window.FffSpa && window.FffSpa.currentStage !== 'question') {
                    if (masterInterval) clearInterval(masterInterval);
                    return;
                }

                const now = Date.now() + (window.clockOffset || 0);
                const sched = typeof window.getFffQuizScheduleTimeMs === 'function' ? window.getFffQuizScheduleTimeMs() : Date.now();
                const qStartTime = typeof window.getFffQuestionStartTimeMs === 'function' ? window.getFffQuestionStartTimeMs() : (sched + 10000);
                const qEndTime = qStartTime + 75000;

                if (now < sched) {
                    if (masterInterval) clearInterval(masterInterval);
                    if (window.FffSpa) window.FffSpa.showStage('waiting');
                    return;
                }
                if (now < qStartTime) {
                    if (masterInterval) clearInterval(masterInterval);
                    if (window.FffSpa) window.FffSpa.showStage('start-counter');
                    return;
                }

                const elapsedSec = (now - qStartTime) / 1000;
                const rem = Math.max(0, Math.ceil(75 - elapsedSec));
                remainingSec = rem;

                // 5-SECOND BATCH SYSTEM: 15 batches in 75 seconds (every 5 seconds)
                const currentBatchIndex = Math.min(15, Math.floor(elapsedSec / 5));
                if (currentBatchIndex > lastFlushedBatchIndex && currentBatchIndex >= 1) {
                    lastFlushedBatchIndex = currentBatchIndex;
                    if (typeof window.flushFff5sBatch === 'function') {
                        window.flushFff5sBatch(currentBatchIndex);
                    }
                }

                // Auto-advance question at 5-second boundaries
                const targetQIndex = Math.min(questions.length - 1, Math.max(0, Math.floor(elapsedSec / 5)));
                if (targetQIndex !== currentIndex && elapsedSec < 75) {
                    currentIndex = targetQIndex;
                    renderCurrentQuestion();
                }

                if (secEl) secEl.textContent = rem;

                // 5-Second Question Countdown Dial (below options)
                const qElapsed = elapsedSec >= 0 ? (elapsedSec % 5) : 0;
                const qRemSec = Math.max(1, 5 - Math.floor(qElapsed));
                if (timerStatusEl) {
                    timerStatusEl.textContent = `Q: ${qRemSec}s remaining`;
                }

                const qRing = document.getElementById('fff-q-timer-ring');
                const qTimerSec = document.getElementById('fff-q-timer-sec');
                const qTimerLabel = document.getElementById('fff-q-timer-label');

                if (qTimerSec) {
                    qTimerSec.textContent = qRemSec;
                }
                if (qTimerLabel) {
                    qTimerLabel.textContent = isHandlingAnswer ? 'Submitted' : 'Answer';
                }
                if (qRing) {
                    const qOffset = circumference - (qRemSec / 5) * circumference;
                    qRing.style.strokeDashoffset = qOffset;

                    if (qRemSec <= 2) {
                        qRing.classList.remove('text-tertiary', 'text-primary');
                        qRing.classList.add('text-error');
                        if (qTimerSec) qTimerSec.classList.add('text-error');
                    } else {
                        qRing.classList.remove('text-error');
                        qRing.classList.add(isHandlingAnswer ? 'text-primary' : 'text-tertiary');
                        if (qTimerSec) qTimerSec.classList.remove('text-error');
                    }
                }

                if (ring) {
                    const fraction = Math.max(0, Math.min(1, rem / totalTimeSec));
                    const offset = circumference - fraction * circumference;
                    ring.style.strokeDashoffset = offset;
                }
                if (bar) {
                    const fraction = Math.max(0, Math.min(1, rem / totalTimeSec));
                    bar.style.width = (fraction * 100) + '%';
                }

                if (rem <= 10 && rem > 0) {
                    if (secEl) secEl.classList.add('text-error', 'animate-pulse');
                    if (ring) {
                        ring.classList.remove('text-tertiary');
                        ring.classList.add('text-error');
                    }
                } else {
                    if (secEl) secEl.classList.remove('text-error', 'animate-pulse');
                    if (ring) {
                        ring.classList.add('text-tertiary');
                        ring.classList.remove('text-error');
                    }
                }

                if (now >= qEndTime || rem <= 0) {
                    if (masterInterval) clearInterval(masterInterval);
                    finishFffQuestions('time_up');
                }
            }

            updateMasterTimer();
            masterInterval = setInterval(updateMasterTimer, 50);
            if (window.FffSpa && typeof window.FffSpa.registerTimer === 'function') {
                window.FffSpa.registerTimer(masterInterval);
            }
        }

        function extractBilingualQuestion(q) {
            let eng = q.question_text || q.question || 'Question ' + (currentIndex + 1);
            if (typeof eng === 'object' && eng !== null) {
                eng = eng.question_text || eng.question || 'Question ' + (currentIndex + 1);
            }
            let hindi = q.hindi_question || q.hindi_text || (typeof q.question === 'object' ? q.question
                ?.hindi_question : null) || null;

            if (hindi && typeof hindi === 'string' && hindi.trim().length > 0 && hindi.trim().toLowerCase() !==
                String(eng).trim().toLowerCase()) {
                return {
                    primary: hindi.trim(),
                    secondary: String(eng).trim()
                };
            }
            return {
                primary: String(eng).trim(),
                secondary: null
            };
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

        function getFffQuestionMediaUrl(qObj) {
            if (!qObj) return null;

            let rawMedia = qObj ? (
                qObj.media_url ||
                qObj.media ||
                qObj.image_file ||
                qObj.image_url ||
                (typeof qObj.question === 'object' && qObj.question ? (qObj.question.media_url || qObj.question.media || qObj.question.image_file || qObj.question.image_url) : null)
            ) : null;

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

            const awsBaseUrl = "{{ rtrim(env('AWS_URL', 'https://chaloonline.s3.ap-south-1.amazonaws.com'), '/') }}";
            const cleanPath = trimmed.replace(/^\/+/, '');
            return awsBaseUrl + '/' + cleanPath;
        }

        function preloadAllFffQuestionMedia(qList) {
            if (!Array.isArray(qList)) return;
            qList.forEach(q => {
                const url = getFffQuestionMediaUrl(q);
                if (url) {
                    const img = new Image();
                    img.src = url;
                }
            });
        }

        function renderCurrentQuestion() {
            if (currentIndex >= questions.length) {
                finishFffQuestions('all_answered');
                return;
            }

            // Stop previous question audios cleanly
            if (window.stopAllQuizAudios) window.stopAllQuizAudios();

            const q = questions[currentIndex];
            const qStartTime = typeof window.getFffQuestionStartTimeMs === 'function' ? window.getFffQuestionStartTimeMs() : Date.now();
            questionShownTimeMs = qStartTime + (currentIndex * 5000);

            // Check if user already answered this question (e.g. before reload)
            let savedAnsPayload = null;
            const qTargetId = q ? (q.id || q.question_id) : null;
            if (qTargetId) {
                if (window.__fffClientAnswers) {
                    savedAnsPayload = window.__fffClientAnswers[q.id] || window.__fffClientAnswers[String(q.id)] ||
                        (q.question_id && (window.__fffClientAnswers[q.question_id] || window.__fffClientAnswers[String(q.question_id)])) || null;
                }
                if (!savedAnsPayload && window.initialUserAnswers) {
                    savedAnsPayload = window.initialUserAnswers[q.id] || window.initialUserAnswers[String(q.id)] ||
                        (q.question_id && (window.initialUserAnswers[q.question_id] || window.initialUserAnswers[String(q.question_id)])) || null;
                }
                if (!savedAnsPayload) {
                    try {
                        const ls = localStorage.getItem('fff_anssheet_' + window.fffQuizId);
                        if (ls) {
                            const p = JSON.parse(ls);
                            if (p && (p[q.id] || p[String(q.id)])) savedAnsPayload = p[q.id] || p[String(q.id)];
                        }
                    } catch (e) {}
                }
                if (!savedAnsPayload) {
                    try {
                        const ss = sessionStorage.getItem('fff_anssheet_' + window.fffQuizId);
                        if (ss) {
                            const p = JSON.parse(ss);
                            if (p && (p[q.id] || p[String(q.id)])) savedAnsPayload = p[q.id] || p[String(q.id)];
                        }
                    } catch (e) {}
                }
                if (!savedAnsPayload) {
                    const singleSaved = localStorage.getItem('fff_selected_ans_' + window.fffQuizId + '_' + q.id) ||
                                       sessionStorage.getItem('fff_selected_ans_' + window.fffQuizId + '_' + q.id) ||
                                       (q.question_id ? (localStorage.getItem('fff_selected_ans_' + window.fffQuizId + '_' + q.question_id) ||
                                                         sessionStorage.getItem('fff_selected_ans_' + window.fffQuizId + '_' + q.question_id)) : null);
                    if (singleSaved) {
                        savedAnsPayload = { question_id: q.id, answer: singleSaved };
                    }
                }
            }
            isHandlingAnswer = !!savedAnsPayload;

            // Reset 5-Second countdown dial for current question
            const qTimerSec = document.getElementById('fff-q-timer-sec');
            const qTimerLabel = document.getElementById('fff-q-timer-label');
            const qRing = document.getElementById('fff-q-timer-ring');
            if (qTimerSec) {
                qTimerSec.textContent = '5';
                qTimerSec.classList.remove('text-error');
            }
            if (qTimerLabel) {
                qTimerLabel.textContent = isHandlingAnswer ? 'Submitted' : 'Answer';
            }
            if (qRing) {
                qRing.style.strokeDashoffset = '0';
                qRing.classList.remove('text-error');
                qRing.classList.add(isHandlingAnswer ? 'text-primary' : 'text-tertiary');
            }

            // Header numbers
            const qNumEl = document.getElementById('fff-q-num');
            const attEl = document.getElementById('fff-attempted-count');
            if (qNumEl) qNumEl.textContent = currentIndex + 1;
            if (attEl) attEl.textContent = attemptedCount;

            // Question Text & Subtext
            const textEl = document.getElementById('fff-question-text');
            const subTextEl = document.getElementById('fff-sub-question-text');
            const bilingual = extractBilingualQuestion(q);

            if (textEl) textEl.textContent = bilingual.primary;
            if (subTextEl) {
                if (bilingual.secondary) {
                    subTextEl.textContent = bilingual.secondary;
                    subTextEl.classList.remove('hidden');
                } else {
                    subTextEl.textContent = '';
                    subTextEl.classList.add('hidden');
                }
            }

            // Question Audio
            const audioContainer = document.getElementById('fff-question-audio-container');
            const audioUrl = q.question_audio || q.audio_url || q.audio_file || null;
            if (audioContainer) {
                if (audioUrl) {
                    audioContainer.classList.remove('hidden');
                } else {
                    audioContainer.classList.add('hidden');
                }
            }

            // Play question appear sound or audio (only if not already answered)
            if (!savedAnsPayload) {
                if (audioUrl) {
                    if (window.isGlobalMuted && window.isGlobalMuted()) {
                        try {
                            sessionStorage.setItem('qx_sound_muted_manual', 'false');
                            localStorage.setItem('qx_sound_muted', 'false');
                            localStorage.setItem('qx_mute', '0');
                            if (typeof window.updateSpeakerIcon === 'function') window.updateSpeakerIcon();
                        } catch (e) {}
                    }
                    if (window.playQuizAudio) {
                        activeQuestionAudio = window.playQuizAudio(audioUrl, 0.95, false, 'question');
                    }
                } else if (window.playNamedSound) {
                    window.playNamedSound('question_appear_sound', 0.85, false, 'question');
                }
            }

            // Media Image vs Top Banner
            const topBannerWrapper = document.getElementById('fff-top-banner-wrapper');
            const mediaWrap = document.getElementById('fff-media-wrapper');
            const mediaImg = document.getElementById('fff-media-img');
            const mediaUrl = getFffQuestionMediaUrl(q);

            if (mediaUrl) {
                if (topBannerWrapper) topBannerWrapper.classList.add('hidden');
                if (mediaWrap) mediaWrap.classList.remove('hidden');
                if (mediaImg) {
                    mediaImg.src = mediaUrl;
                    mediaImg.onerror = function() {
                        if (mediaWrap) mediaWrap.classList.add('hidden');
                        if (topBannerWrapper) topBannerWrapper.classList.remove('hidden');
                    };
                }
            } else {
                if (mediaWrap) mediaWrap.classList.add('hidden');
                if (topBannerWrapper) topBannerWrapper.classList.remove('hidden');
                if (mediaImg) mediaImg.src = '';
            }

            // Options List (KBC Option buttons with shuffle)
            const optionsContainer = document.getElementById('fff-options-list');
            if (optionsContainer) {
                optionsContainer.innerHTML = '';
                let rawOptions = q ? (q.options || (typeof q.question === 'object' && q.question ? q.question.options : null)) : null;
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

                let rawHindiOpts = q ? (q.hindi_options || (typeof q.question === 'object' && q.question ? q.question.hindi_options : null)) : null;
                if (typeof rawHindiOpts === 'string') {
                    try {
                        rawHindiOpts = JSON.parse(rawHindiOpts);
                    } catch (e) {
                        rawHindiOpts = [];
                    }
                }

                // Shuffle options for this question if not already shuffled (matching main quiz)
                if (rawOptions.length > 1 && !q._shuffled) {
                    q._shuffled = true;
                    for (let i = rawOptions.length - 1; i > 0; i--) {
                        const j = Math.floor(Math.random() * (i + 1));
                        [rawOptions[i], rawOptions[j]] = [rawOptions[j], rawOptions[i]];
                        if (Array.isArray(rawHindiOpts) && rawHindiOpts.length === rawOptions.length) {
                            [rawHindiOpts[i], rawHindiOpts[j]] = [rawHindiOpts[j], rawHindiOpts[i]];
                        }
                    }
                }

                const opts = rawOptions;
                opts.forEach(function(optItem, idx) {
                    let optText = '';
                    if (typeof optItem === 'object' && optItem !== null) {
                        optText = optItem.text ?? optItem.option_text ?? optItem.label ?? JSON.stringify(optItem);
                    } else {
                        optText = String(optItem);
                    }

                    const optBilingual = parseOptionBilingual(optText, optItem, idx, rawHindiOpts);

                    const wrap = document.createElement('div');
                    wrap.className = 'kbc-opt-glow w-full h-full';

                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className =
                        'option-btn kbc-option kbc-option-flat active:scale-[0.98] w-full h-full min-h-[50px] text-center flex items-center justify-center p-2.5 sm:p-3 rounded-xl border border-border-glass bg-surface-container/80 hover:bg-surface-container-high transition-all cursor-pointer relative';
                    btn.dataset.answer = optText;
                    btn.dataset.index = idx;

                    let isThisSelected = false;
                    if (savedAnsPayload && savedAnsPayload.answer !== null && savedAnsPayload.answer !== undefined) {
                        const normOpt = String(optText).replace(/[\u00A0\u1680\u180E\u2000-\u200B\u202F\u205F\u3000]/g, ' ').replace(/\s+/g, ' ').trim().toLowerCase();
                        const normAns = String(savedAnsPayload.answer).replace(/[\u00A0\u1680\u180E\u2000-\u200B\u202F\u205F\u3000]/g, ' ').replace(/\s+/g, ' ').trim().toLowerCase();
                        if (normOpt === normAns) {
                            isThisSelected = true;
                        } else {
                            const optBase = normOpt.replace(/\s*\(.*?\)\s*$/g, '').trim();
                            const ansBase = normAns.replace(/\s*\(.*?\)\s*$/g, '').trim();
                            if ((optBase && ansBase && optBase === ansBase) || normOpt.includes(normAns) || normAns.includes(normOpt) || (optBase && normAns.includes(optBase)) || (ansBase && normOpt.includes(ansBase))) {
                                isThisSelected = true;
                            }
                        }
                    }

                    if (savedAnsPayload) {
                        btn.disabled = true;
                        btn.classList.add('pointer-events-none');
                        if (isThisSelected) {
                            btn.classList.add(
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
                        } else {
                            btn.classList.add('opacity-60');
                        }
                    }

                    const contentHtml = optBilingual.isBilingual ? `
                        <div class="flex flex-col items-center justify-center leading-tight w-full">
                            <span class="font-bold text-white text-xs sm:text-sm leading-snug break-words font-sans text-center">
                                ${escapeHtml(optBilingual.hindi)}
                            </span>
                            <span class="text-amber-200/90 font-medium text-[11px] sm:text-xs mt-0.5 leading-snug break-words font-sans text-center">
                                ${escapeHtml(optBilingual.english)}
                            </span>
                        </div>
                    ` : `
                        <span class="font-body-lg text-on-surface text-xs sm:text-sm font-semibold leading-snug break-words font-sans text-center">${escapeHtml(optBilingual.single)}</span>
                    `;

                    btn.innerHTML = `
                        <div class="flex-1 flex items-center justify-center min-w-0 text-center">
                            ${contentHtml}
                        </div>
                        <span class="material-symbols-outlined check-icon text-tertiary text-base ${isThisSelected ? '' : 'opacity-0'} transition-opacity font-bold shrink-0 self-center absolute right-2">check_circle</span>
                    `;

                    btn.onclick = function() {
                        handleOptionClick(btn, q, optText);
                    };

                    wrap.appendChild(btn);
                    optionsContainer.appendChild(wrap);
                });
            }
        }

        async function handleOptionClick(btnElement, question, selectedAnswerValue) {
            if (isHandlingAnswer) return;

            const nowTime = Date.now() + (window.clockOffset || 0);
            const schedTime = typeof window.getFffQuizScheduleTimeMs === 'function' ? window.getFffQuizScheduleTimeMs() : Date.now();
            const minSubmitTime = schedTime + 11000; // sched + 10s start counter + 1s = 11s
            if (nowTime < minSubmitTime) {
                console.warn('[FFF Submission Guard] Cannot submit answer before schedule time + 11s');
                return;
            }

            isHandlingAnswer = true;

            const qTimerLabel = document.getElementById('fff-q-timer-label');
            const qRing = document.getElementById('fff-q-timer-ring');
            if (qTimerLabel) {
                qTimerLabel.textContent = 'Submitted';
            }
            if (qRing && !qRing.classList.contains('text-error')) {
                qRing.classList.remove('text-tertiary');
                qRing.classList.add('text-primary');
            }

            const clickTimeMs = Date.now();
            const timeTakenSec = Math.max(0.05, Math.min(5.0, (clickTimeMs - questionShownTimeMs) / 1000));

            // Visual feedback matching standard quiz
            const allBtns = document.querySelectorAll('#fff-options-list .option-btn');
            allBtns.forEach(b => {
                b.disabled = true;
                b.classList.add('opacity-60', 'pointer-events-none');
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
            }

            attemptedCount++;
            const attEl = document.getElementById('fff-attempted-count');
            if (attEl) attEl.textContent = attemptedCount;

            // Track locally answered IDs and full answer payload in storage for 100% data preservation
            window.__fffClientAnswers = window.__fffClientAnswers || {};
            try {
                const storedPrior = sessionStorage.getItem('fff_anssheet_' + window.fffQuizId) ||
                    localStorage.getItem('fff_anssheet_' + window.fffQuizId);
                if (storedPrior) {
                    const parsedPrior = JSON.parse(storedPrior);
                    if (parsedPrior && typeof parsedPrior === 'object') {
                        window.__fffClientAnswers = Object.assign({}, parsedPrior, window.__fffClientAnswers);
                    }
                }
            } catch (e) {}

            const answerPayload = {
                question_id: question.id,
                answer: selectedAnswerValue,
                total_time: timeTakenSec,
                show_time: new Date(questionShownTimeMs).toISOString(),
                ans_time: new Date(clickTimeMs).toISOString()
            };
            window.__fffClientAnswers[question.id] = answerPayload;

            try {
                let answeredArr = [];
                const localAnsweredStr = sessionStorage.getItem('fff_answered_ids_' + window.fffQuizId) ||
                    localStorage.getItem('fff_answered_ids_' + window.fffQuizId);
                if (localAnsweredStr) {
                    try {
                        answeredArr = JSON.parse(localAnsweredStr);
                    } catch (e) {
                        answeredArr = [];
                    }
                }
                if (!Array.isArray(answeredArr)) answeredArr = [];
                const qIdNum = Number(question.id);
                if (!answeredArr.includes(qIdNum)) {
                    answeredArr.push(qIdNum);
                }
                const updatedStr = JSON.stringify(answeredArr);
                sessionStorage.setItem('fff_answered_ids_' + window.fffQuizId, updatedStr);
                localStorage.setItem('fff_answered_ids_' + window.fffQuizId, updatedStr);
                sessionStorage.setItem('fff_attempted_' + window.fffQuizId, attemptedCount);
                localStorage.setItem('fff_attempted_' + window.fffQuizId, attemptedCount);

                // Save single answer for instant fast restore per question using PrivateStorage (no correct answer leakage)
                PrivateStorage.set('fff_selected_ans_' + window.fffQuizId + '_' + question.id, selectedAnswerValue);
                if (question.question_id) {
                    PrivateStorage.set('fff_selected_ans_' + window.fffQuizId + '_' + question.question_id, selectedAnswerValue);
                }

                // Save full answer dictionary for robust reconciliation (strip any correct answers if present)
                const sheetJson = JSON.stringify(window.__fffClientAnswers);
                PrivateStorage.set('fff_anssheet_' + window.fffQuizId, sheetJson);
            } catch (e) {}

            // Background Async submission with exponential retry
            submitFffAnswerWithRetry(answerPayload);

            // If this was the last question, flush batch immediately
            if (currentIndex + 1 >= questions.length) {
                if (typeof window.flushFff5sBatch === 'function') {
                    window.flushFff5sBatch(15);
                }
            }
        }

        window.__fffSubmissionConfirmed = window.__fffSubmissionConfirmed || {};

        function submitFffAnswerWithRetry(payload, attempt = 1) {
            const delays = [250, 500, 1000, 1500];
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
            fetch('/fsrapp/fff/api/submit-answer/' + window.fffQuizId, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf || '',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload),
                keepalive: true
            }).then(function(res) {
                if (res.ok) {
                    window.__fffSubmissionConfirmed = window.__fffSubmissionConfirmed || {};
                    window.__fffSubmissionConfirmed[payload.question_id] = true;
                } else if (attempt <= delays.length) {
                    setTimeout(function() {
                        submitFffAnswerWithRetry(payload, attempt + 1);
                    }, delays[attempt - 1]);
                }
            }).catch(function(err) {
                if (attempt <= delays.length) {
                    setTimeout(function() {
                        submitFffAnswerWithRetry(payload, attempt + 1);
                    }, delays[attempt - 1]);
                }
            });
        }

        // 5-Second Batch System: Collects and inserts pending answers in 5-second batches (15 batches in 75s)
        window.flushFff5sBatch = function(batchIndex) {
            if (!window.__fffClientAnswers) return;

            const pendingAnswers = {};
            let pendingCount = 0;
            Object.keys(window.__fffClientAnswers).forEach(function(qId) {
                if (!window.__fffSubmissionConfirmed[qId]) {
                    pendingAnswers[qId] = window.__fffClientAnswers[qId];
                    pendingCount++;
                }
            });

            if (pendingCount === 0) return;

            const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
            fetch('/fsrapp/fff/api/submit-answer/' + window.fffQuizId, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf || '',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    answers: pendingAnswers,
                    batch_index: batchIndex
                }),
                keepalive: true
            }).then(function(res) {
                return res.json();
            }).then(function(data) {
                if (data && (data.success || data.reconciled_count !== undefined || data.processed_count !== undefined)) {
                    Object.keys(pendingAnswers).forEach(function(qId) {
                        window.__fffSubmissionConfirmed[qId] = true;
                    });
                }
            }).catch(function(err) {
                console.warn('FFF 5s Batch ' + batchIndex + ' error:', err);
            });
        };

        // Backwards compatibility alias
        window.flushFff10sBatch = window.flushFff5sBatch;

        // Post-Quiz Reconciliation: Checks if any answer was missed across all batches.
        // If any answer was missed, creates a single batch of all missed answers and updates on server (no duplicates!).
        let isReconcilingFff = false;
        window.reconcileMissedFffAnswers = function() {
            if (isReconcilingFff) return;

            try {
                const storedSheet = sessionStorage.getItem('fff_anssheet_' + window.fffQuizId) ||
                    localStorage.getItem('fff_anssheet_' + window.fffQuizId);
                let answersObj = window.__fffClientAnswers || {};
                if (storedSheet) {
                    try {
                        const parsed = JSON.parse(storedSheet);
                        if (parsed && typeof parsed === 'object') {
                            answersObj = Object.assign({}, parsed, answersObj);
                        }
                    } catch (e) {}
                }
                if (!answersObj || typeof answersObj !== 'object' || Object.keys(answersObj).length === 0) {
                    if (typeof window.triggerFffProcessResultOnce === 'function') {
                        window.triggerFffProcessResultOnce(window.fffQuizId);
                    }
                    return;
                }

                window.__fffSubmissionConfirmed = window.__fffSubmissionConfirmed || {};

                // Filter only MISSED / unconfirmed answers
                const missedAnswers = {};
                let missedCount = 0;
                Object.keys(answersObj).forEach(function(qId) {
                    if (!window.__fffSubmissionConfirmed[qId]) {
                        missedAnswers[qId] = answersObj[qId];
                        missedCount++;
                    }
                });

                // If all answers have already been confirmed by the 6 batches, skip reconcile API completely
                if (missedCount === 0) {
                    if (typeof window.triggerFffProcessResultOnce === 'function') {
                        window.triggerFffProcessResultOnce(window.fffQuizId);
                    }
                    return;
                }

                isReconcilingFff = true;

                const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
                fetch('/fsrapp/fff/api/reconcile-answers/' + window.fffQuizId, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf || '',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ 
                        answers: missedAnswers,
                        is_reconcile: true 
                    }),
                    keepalive: true
                }).then(function(res) {
                    return res.json();
                }).then(function(data) {
                    if (data && (data.success || data.reconciled_count !== undefined)) {
                        Object.keys(missedAnswers).forEach(function(qId) {
                            window.__fffSubmissionConfirmed[qId] = true;
                        });
                    }
                    if (data && data.trigger_process_result && typeof window.triggerFffProcessResultOnce === 'function') {
                        window.triggerFffProcessResultOnce(window.fffQuizId);
                    }
                }).catch(function(err) {
                    console.warn('reconcileMissedFffAnswers error:', err);
                }).finally(function() {
                    isReconcilingFff = false;
                });
            } catch (e) {
                isReconcilingFff = false;
            }
        };

        // Alias for backwards compatibility
        window.flushFffClientAnswersToDatabase = window.reconcileMissedFffAnswers;

        window.triggerFffProcessResultOnce = async function(qId) {
            try {
                const targetQuizId = qId || window.fffQuizId;
                const triggerKey = 'fff_result_proc_sent_' + targetQuizId;
                if (sessionStorage.getItem(triggerKey)) return;
                sessionStorage.setItem(triggerKey, 'true');

                const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
                await fetch('/fsrapp/fff/api/process-result/' + targetQuizId, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf || '',
                        'Accept': 'application/json'
                    },
                    keepalive: true
                });
            } catch (e) {
                console.warn('triggerFffProcessResultOnce error:', e);
            }
        };

        window.replayFffQuestionAudio = function(e) {
            if (e && e.stopPropagation) e.stopPropagation();
            if (currentIndex >= questions.length) return;
            const q = questions[currentIndex];
            const audioUrl = q ? (q.question_audio || q.audio_url || q.audio_file || null) : null;
            if (audioUrl && window.playQuizAudio) {
                window.playQuizAudio(audioUrl, 0.95, false, 'question');
            }
        };
        window.playCurrentQuestionAudio = window.replayFffQuestionAudio;

        // Compile and validate complete local FFF answer sheet so server request is completely bypassed
        window.finalizeAndValidateFffAnssheet = function() {
            try {
                const targetQuizId = window.fffQuizId;
                if (!targetQuizId) return null;

                let fullQuestions = [];
                if (window.encryptedFffPayload && typeof window.encryptedFffPayload === 'string' && window.encryptedFffPayload.trim() !== '') {
                    try {
                        const jsonStr = atob(window.encryptedFffPayload);
                        fullQuestions = JSON.parse(jsonStr) || [];
                    } catch(e) {}
                }

                if (!Array.isArray(fullQuestions) || fullQuestions.length === 0) {
                    if (Array.isArray(questions) && questions.length > 0) {
                        fullQuestions = questions;
                    } else if (Array.isArray(window.initialFffQuestions) && window.initialFffQuestions.length > 0) {
                        fullQuestions = window.initialFffQuestions;
                    }
                }

                if (!Array.isArray(fullQuestions) || fullQuestions.length === 0) {
                    return null;
                }

                let userAnswersObj = {};
                if (window.initialUserAnswers && typeof window.initialUserAnswers === 'object') {
                    Object.assign(userAnswersObj, window.initialUserAnswers);
                }
                try {
                    const ls = localStorage.getItem('fff_anssheet_' + targetQuizId);
                    if (ls) {
                        const parsed = JSON.parse(ls);
                        if (parsed && typeof parsed === 'object') Object.assign(userAnswersObj, parsed);
                    }
                } catch(e) {}
                try {
                    const ss = sessionStorage.getItem('fff_anssheet_' + targetQuizId);
                    if (ss) {
                        const parsed = JSON.parse(ss);
                        if (parsed && typeof parsed === 'object') Object.assign(userAnswersObj, parsed);
                    }
                } catch(e) {}
                if (window.__fffClientAnswers && typeof window.__fffClientAnswers === 'object') {
                    Object.assign(userAnswersObj, window.__fffClientAnswers);
                }

                let totalAttempted = 0;
                let totalCorrect = 0;
                let totalTime = 0.0;
                const sheetQuestions = [];

                fullQuestions.forEach((q, idx) => {
                    const qId = q.id;
                    const ansPayload = userAnswersObj[qId] || userAnswersObj[String(qId)] || (q.question_id ? (userAnswersObj[q.question_id] || userAnswersObj[String(q.question_id)]) : null);
                    const isAttempted = ansPayload && ansPayload.answer !== null && ansPayload.answer !== undefined && String(ansPayload.answer).trim() !== '';

                    let userAns = isAttempted ? String(ansPayload.answer) : null;
                    let qTime = isAttempted && ansPayload.total_time ? parseFloat(ansPayload.total_time) : 0.0;
                    let isCorrect = false;

                    const correctAns = q.correct_answer || '';
                    const hindiCorrectAns = q.hindi_correct_answer || '';

                    if (isAttempted) {
                        totalAttempted++;
                        totalTime += qTime;

                        if (correctAns || hindiCorrectAns) {
                            const normU = userAns.replace(/[\u00A0\u1680\u180E\u2000-\u200B\u202F\u205F\u3000]/g, ' ').replace(/\s+/g, ' ').trim().toLowerCase();
                            const normC = String(correctAns).replace(/[\u00A0\u1680\u180E\u2000-\u200B\u202F\u205F\u3000]/g, ' ').replace(/\s+/g, ' ').trim().toLowerCase();
                            const normHC = String(hindiCorrectAns).replace(/[\u00A0\u1680\u180E\u2000-\u200B\u202F\u205F\u3000]/g, ' ').replace(/\s+/g, ' ').trim().toLowerCase();

                            if (normU === normC || (normHC !== '' && normU === normHC)) {
                                isCorrect = true;
                            } else {
                                const uBase = normU.replace(/\s*\(.*?\)\s*$/g, '').trim();
                                const cBase = normC.replace(/\s*\(.*?\)\s*$/g, '').trim();
                                const hcBase = normHC.replace(/\s*\(.*?\)\s*$/g, '').trim();
                                if ((uBase && cBase && uBase === cBase) || (uBase && hcBase && uBase === hcBase)) {
                                    isCorrect = true;
                                }
                            }
                        }
                        if (isCorrect) totalCorrect++;
                    }

                    sheetQuestions.push({
                        id: q.id,
                        question_id: q.question_id || q.id,
                        order: q.order || (idx + 1),
                        question_text: q.question_text || q.question || ('Question ' + (idx + 1)),
                        hindi_question: q.hindi_question || null,
                        question_audio: q.question_audio || q.audio_file || null,
                        media_url: q.media_url || null,
                        options: q.options || [],
                        hindi_options: q.hindi_options || [],
                        user_answer: userAns,
                        correct_answer: correctAns,
                        hindi_correct_answer: hindiCorrectAns,
                        is_correct: isCorrect,
                        is_attempted: isAttempted,
                        time_taken: Math.round(qTime * 100) / 100,
                        total_time: Math.round(qTime * 100) / 100
                    });
                });

                const compiled = {
                    quiz_id: targetQuizId,
                    total_questions: fullQuestions.length,
                    total_attempted: totalAttempted,
                    total_correct: totalCorrect,
                    total_time: Math.round(totalTime * 1000) / 1000,
                    questions: sheetQuestions
                };

                const compiledStr = JSON.stringify(compiled);
                sessionStorage.setItem('fff_compiled_sheet_' + targetQuizId, compiledStr);
                localStorage.setItem('fff_compiled_sheet_' + targetQuizId, compiledStr);

                return compiled;
            } catch(e) {
                console.warn('finalizeAndValidateFffAnssheet error:', e);
                return null;
            }
        };

        function finishFffQuestions(reason) {
            if (masterInterval) clearInterval(masterInterval);

            // If time is up and user has not attempted or submitted answers, exit expired quiz
            if (reason === 'time_up' && (attemptedCount === 0 || !window.isFffUserCompleted())) {
                if (window.checkAndHandleFffQuizExpiry && window.checkAndHandleFffQuizExpiry()) {
                    return;
                }
            }

            window.__isFffCompleted = true;
            window.__fffFinishReason = reason || 'time_up';
            const now = Date.now() + (window.clockOffset || 0);

            // 1. Ensure any pending answers are dispatched in batch 15 (or latest)
            if (typeof window.flushFff5sBatch === 'function') {
                window.flushFff5sBatch(15);
            } else if (typeof window.flushFff10sBatch === 'function') {
                window.flushFff10sBatch(15);
            }

            // 2. Post-Quiz Reconciliation: Check if ANY answer was missed across all batches
            if (typeof window.reconcileMissedFffAnswers === 'function') {
                window.reconcileMissedFffAnswers();
            }

            // 3. Finalize complete local answer sheet in local storage with 0 network calls
            if (typeof window.finalizeAndValidateFffAnssheet === 'function') {
                window.finalizeAndValidateFffAnssheet();
            }

            // Backup single-requester fallback trigger when FFF questions finish
            if (!sessionStorage.getItem('fff_result_proc_sent_' + window.fffQuizId)) {
                setTimeout(function() {
                    if (!sessionStorage.getItem('fff_result_proc_sent_' + window.fffQuizId) && typeof window.triggerFffProcessResultOnce === 'function') {
                        window.triggerFffProcessResultOnce(window.fffQuizId);
                    }
                }, 5000 + Math.floor(Math.random() * 3000));
            }

            let finishTime = parseInt(sessionStorage.getItem('fff_finish_time_' + window.fffQuizId) || localStorage
                .getItem('fff_finish_time_' + window.fffQuizId) || '0', 10);
            if (!finishTime || finishTime <= 0) {
                finishTime = now;
            }

            try {
                sessionStorage.setItem('fff_completed_' + window.fffQuizId, 'true');
                sessionStorage.setItem('fff_finish_reason_' + window.fffQuizId, reason || 'time_up');
                sessionStorage.setItem('fff_finish_time_' + window.fffQuizId, finishTime);
                localStorage.setItem('fff_completed_' + window.fffQuizId, 'true');
                localStorage.setItem('fff_finish_reason_' + window.fffQuizId, reason || 'time_up');
                localStorage.setItem('fff_finish_time_' + window.fffQuizId, finishTime);
            } catch (e) {}

            if (window.playNamedSound) {
                window.playNamedSound('answer_sheet_appear_sound', 0.7, false, 'sfx');
            }

            // Always show review-counter stage with 10s countdown first before answer-sheet
            window.FffSpa.showStage('review-counter');
        }

        const onQuestionWakeUp = () => {
            if (!document.hidden && window.FffSpa && window.FffSpa.currentStage === 'question') {
                if (window.checkAndHandleFffQuizExpiry && window.checkAndHandleFffQuizExpiry()) {
                    return;
                }
                const now = Date.now() + (window.clockOffset || 0);
                const qStartTime = typeof window.getFffQuestionStartTimeMs === 'function' ? window
                    .getFffQuestionStartTimeMs() : ((window.getFffQuizScheduleTimeMs ? window
                        .getFffQuizScheduleTimeMs() : Date.now()) + 10000);
                if (now >= qStartTime + 75000) {
                    finishFffQuestions('time_up');
                }
            }
        };
        document.addEventListener('visibilitychange', onQuestionWakeUp);
        window.addEventListener('focus', onQuestionWakeUp);
        window.addEventListener('pageshow', onQuestionWakeUp);

        function escapeHtml(str) {
            return String(str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(
                /"/g, '&quot;');
        }
    })();
</script>
