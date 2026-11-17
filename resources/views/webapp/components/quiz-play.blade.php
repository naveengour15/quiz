<template id="tpl-quiz-play">
    <main class="min-h-screen px-container-margin pt-8 pb-8 flex flex-col kbc-stage">
        <div class="kbc-spotlight"></div>
        <div class="kbc-stage-rings"><span></span><span></span><span></span><span></span></div>
        <div id="quiz-play-root" class="flex-1 flex flex-col items-center justify-center relative z-[1]">
            <p class="text-center text-on-surface-variant" role="status">Loading question...</p>
        </div>
    </main>
</template>

<script>
    route('/quiz/play/:id', async (app, params) => {
        const quizId = params.id;

        app.innerHTML = qs('#tpl-quiz-play').innerHTML;

        Sound.ensureCtx();
        Sound.stopBackground();
        let bgStopped = false;

        function stopBg() {
            if (!bgStopped) {
                bgStopped = true;
                Sound.stopBackground();
            }
        }

        let tickInterval = null;

        let routeActive = true;

        function onLeaveQuizRoute() {
            if (!currentPath().startsWith('/quiz/play/')) {
                routeActive = false;
                if (tickInterval) clearInterval(tickInterval);
                stopBg();
                window.removeEventListener('hashchange', onLeaveQuizRoute);
            }
        }
        window.addEventListener('hashchange', onLeaveQuizRoute);

        const reviewLog = [];

        await QuizMasterClock.syncStatus(quizId);

        try {
            if (window.Echo && typeof window.Echo.private === 'function') {
                window.Echo.private(`quiz.${quizId}`).listen('QuizStarted', (e) => {
                    QuizMasterClock.updateServerTime(e.server_now_ms);
                    QuizMasterClock.setQuizConfig(quizId, e);
                });
            }
        } catch (e) {}

        let currentQIndex = -1;
        let currentQuestionObj = null;
        let currentOptions = [];
        let isLoadingQuestion = false;
        let isLoadingOptions = false;
        let nextQuestionRetryAt = 0;
        let nextOptionsRetryAt = 0;
        let answeredQuestions = {};
        let lastPlayedSecond = -1;
        let lastSayAnsQIndex = -1;
        let lastOptionsRevealQIndex = -1;
        let lifelineState = null;

        function renderLifelinesBarHtml(enabled = false) {
            if (!lifelineState || lifelineState.has_used_any) return '';
            const show5050 = lifelineState.can_use_fifty_fifty;
            const showSwitch = lifelineState.can_use_switch_option;
            if (!show5050 && !showSwitch) return '';

            const disAttr = enabled ? '' : 'disabled';
            const disClass = enabled ? '' : 'opacity-50 pointer-events-none cursor-not-allowed';

            return `
    <div id="qplay-lifelines-bar" class="flex items-center justify-center gap-3 mb-4">
      ${show5050 ? `
        <button id="btn-lifeline-5050" ${disAttr} class="px-4 py-2 rounded-xl text-xs font-bold transition-all bg-gradient-to-r from-amber-500 to-yellow-400 text-black shadow-lg hover:scale-105 active:scale-95 flex items-center gap-1.5 cursor-pointer ${disClass}" onclick="triggerLifeline('fifty_fifty')">
          <span>⚡</span>
          <span>50:50</span>
        </button>
      ` : ''}
      ${showSwitch ? `
        <button id="btn-lifeline-switch" ${disAttr} class="px-4 py-2 rounded-xl text-xs font-bold transition-all bg-gradient-to-r from-purple-600 to-indigo-500 text-white shadow-lg hover:scale-105 active:scale-95 flex items-center gap-1.5 cursor-pointer ${disClass}" onclick="triggerLifeline('switch_option')">
          <span class="material-symbols-outlined text-[16px]">sync</span>
          <span>Switch Q</span>
        </button>
      ` : ''}
    </div>`;
        }

        window.triggerLifeline = async (type) => {
            if (!currentQuestionObj || !currentQuestionObj.id || (lifelineState && lifelineState
                    .has_used_any)) return;
            if (answeredQuestions[currentQIndex] && answeredQuestions[currentQIndex].answered) return;
            if (qs('#qplay-hold-spinner')) return;

            const btn = qs(`#btn-lifeline-${type === 'fifty_fifty' ? '5050' : 'switch'}`);
            if (btn) btn.disabled = true;

            try {
                const res = await api(`/quiz/${quizId}/use-lifeline`, {
                    method: 'POST',
                    body: {
                        lifeline: type,
                        question_id: currentQuestionObj.id
                    }
                });

                const data = unwrap(res) || res || {};

                if (!data.success) {
                    if (typeof toast === 'function') toast(data.message || 'Could not use lifeline',
                        'error');
                    else alert(data.message || 'Could not use lifeline');
                    if (btn) btn.disabled = false;
                    return;
                }

                if (data.lifelines) lifelineState = data.lifelines;

                const bar = qs('#qplay-lifelines-bar');
                if (bar) bar.style.display = 'none';

                if (type === 'fifty_fifty') {
                    Sound.select();
                    if (typeof toast === 'function') toast('50:50 Lifeline applied!', 'success');

                    const optWrap = qs('#qplay-options');
                    if (optWrap) {
                        const OPT_LETTERS = ['A', 'B', 'C', 'D', 'E', 'F'];
                        const removed = (data.removed_options || []).map(x => String(x).trim().toLowerCase());
                        const removedIdx = (data.removed_indices || []).map(Number);
                        const optBtns = qsa('.option-btn', optWrap);

                        const remainingWrappers = [];
                        const removedWrappers = [];

                        optBtns.forEach((b, i) => {
                            const val = String(b.dataset.val || '').trim().toLowerCase();
                            const text = String(b.dataset.text || '').trim().toLowerCase();
                            const isValMatch = removed.length > 0 && (removed.includes(val) || removed.includes(text));
                            const isIdxMatch = removedIdx.includes(i);

                            const wrapper = b.closest('.kbc-opt-glow') || b.parentElement;
                            if (isValMatch || isIdxMatch) {
                                removedWrappers.push({ btn: b, wrapper });
                            } else {
                                remainingWrappers.push({ btn: b, wrapper });
                            }
                        });

                        remainingWrappers.forEach((item, idx) => {
                            optWrap.appendChild(item.wrapper);
                            const letterSpan = item.btn.querySelector('.kbc-opt-letter-flat');
                            if (letterSpan) {
                                letterSpan.textContent = (OPT_LETTERS[idx] || (idx + 1)) + ':';
                            }
                        });

                        removedWrappers.forEach((item) => {
                            optWrap.appendChild(item.wrapper);
                            item.btn.disabled = true;
                            item.btn.classList.add('pointer-events-none');
                            if (item.wrapper) {
                                item.wrapper.style.transition = 'opacity 0.5s ease, transform 0.5s ease, filter 0.5s ease';
                                item.wrapper.style.opacity = '0';
                                item.wrapper.style.transform = 'scale(0.95)';
                                item.wrapper.style.filter = 'blur(4px)';
                                item.wrapper.style.pointerEvents = 'none';
                                setTimeout(() => {
                                    item.wrapper.style.visibility = 'hidden';
                                    item.wrapper.style.display = 'none';
                                }, 500);
                            }
                        });
                    }
                } else if (type === 'switch_option') {
                    Sound.select();
                    if (typeof toast === 'function') toast('Switch Question Lifeline applied!',
                        'success');
                    if (data.new_question) {
                        const nq = data.new_question;
                        currentQuestionObj = {
                            id: nq.id || nq.question_id,
                            text: nq.question_text || nq.text || 'Question'
                        };

                        const qTitleEl = qs('#quiz-play-root h3');
                        if (qTitleEl) qTitleEl.textContent = currentQuestionObj.text;

                        currentOptions = nq.options || [];
                        renderOptions(currentOptions, currentQIndex);
                    }
                }
            } catch (err) {
                if (typeof toast === 'function') toast(err.message || 'Lifeline error', 'error');
                else alert(err.message || 'Lifeline error');
                if (btn) btn.disabled = false;
            }
        };

        function extractQuestion(raw) {
            const d = unwrap(raw);
            if (!d) return null;
            if (d.remaining_quiz_question === 'completed' || d.complete === 'completed' || d.completed ===
                true) {
                return {
                    isCompleted: true
                };
            }
            const q = d.question && typeof d.question === 'object' ? d.question : d;
            const qId = pick(q, ['id', 'question_id']);
            if (!qId) {
                if (d.success && !d.question) {
                    return {
                        isCompleted: true
                    };
                }
                return null;
            }
            return {
                id: qId,
                text: pick(q, ['question', 'question_text', 'title'], 'Question'),
                hindi_text: pick(q, ['hindi_question', 'hindi_text'], null)
            };
        }

        function extractOptions(raw) {
            const d = unwrap(raw);
            if (Array.isArray(d)) return d;
            if (d && Array.isArray(d.options)) return d.options;
            if (d && d.question && Array.isArray(d.question.options)) return d.question.options;
            return asList(raw);
        }

        function showQuestionLoadError() {
            const root = qs('#quiz-play-root');
            if (root) {
                root.innerHTML = '<p class="text-center text-on-surface-variant" role="status">Connection issue. Retrying question...</p>';
            }
        }

        function showOptionsLoadError() {
            const message = qs('#qplay-hold-spinner p');
            if (message) message.textContent = 'Connection issue. Retrying options...';
        }

        async function loadQuestionForIndex(index) {
            let res;
            if (index === 0) {
                res = await api(`/quiz/${quizId}/start`, {
                    method: 'GET'
                });
            } else {
                res = await api(`/quiz/${quizId}/get-question`, {
                    method: 'POST',
                    body: {
                        last_question_id: String(currentQuestionObj ? currentQuestionObj.id :
                            '')
                    }
                });
            }
            const unwrapped = unwrap(res) || res || {};
            if (unwrapped.lifelines) lifelineState = unwrapped.lifelines;
            return extractQuestion(res);
        }

        async function loadOptions(questionId) {
            const res = await api(`/quiz/${quizId}/get-question-options`, {
                method: 'POST',
                body: {
                    question_id: questionId
                }
            });
            const unwrapped = unwrap(res) || res || {};
            if (unwrapped.lifelines) lifelineState = unwrapped.lifelines;
            return extractOptions(res);
        }

        async function submitAnswer(questionId, answer) {
            const ansTime = new Date();
            const res = await api(`/quiz/${quizId}/submit-answer`, {
                method: 'POST',
                body: {
                    question_id: questionId,
                    answer,
                    ans_time: ansTime.toTimeString().slice(0, 8),
                },
            });
            return res;
        }

        const DIAL_CIRCUMFERENCE = 276.5;        async function renderBaseQuestionCard(qNumber, totalQs, qText, hindiText = null) {
            let sessionPrize = '0';
            try {
                sessionPrize = sessionStorage.getItem('qx_prize_' + quizId) || '0';
            } catch (e) {}

            const root = qs('#quiz-play-root');
            if (!root) return;

            const primaryText = (hindiText && String(hindiText).trim().length > 0) ? hindiText.trim() : qText;
            const secondaryText = (hindiText && String(hindiText).trim().length > 0) ? qText : null;

            root.outerHTML = `
    <div id="quiz-play-root" class="w-full kbc-card rounded-2xl p-4 fade-in">
      <div class="flex items-center justify-between mb-4">
        <button onclick="if(confirm('Leave this quiz? Your progress may be lost.')) { window.__quizLeave && window.__quizLeave(); }" class="kbc-icon-badge"><span class="material-symbols-outlined text-[18px]">close</span></button>
        <span class="font-label-caps kbc-title-glow text-[12px] tracking-wide">FSRQuiz</span>
        <div id="qplay-mute-slot" class="kbc-icon-badge"></div>
      </div>
      ${Number(String(sessionPrize).replace(/[^0-9.]/g,'')) > 0 ? `
        <div class="flex justify-center mb-5">
          <div class="kbc-prize-pill"><span>💎</span><span>₹ ${escapeHtml(sessionPrize)}</span><span>💎</span></div>
        </div>` : ''}
      <div class="kbc-dial-wrap" id="qplay-dial-wrap">
        <svg viewBox="0 0 100 100">
          <defs>
            <linearGradient id="kbcDialGradient" x1="0%" y1="0%" x2="100%" y2="100%">
              <stop offset="0%" stop-color="var(--kbc-teal)"/>
              <stop offset="100%" stop-color="var(--kbc-mint)"/>
            </linearGradient>
          </defs>
          <circle class="kbc-dial-track" cx="50" cy="50" r="44" fill="none" stroke-width="7"/>
          <circle class="kbc-dial-fill" id="qplay-dial-fill" cx="50" cy="50" r="44" fill="none" stroke-width="7" stroke-dasharray="276.5" stroke-dashoffset="0"/>
        </svg>
        <div class="kbc-dial-number" id="qplay-dial-number">5</div>
      </div>
      <div class="kbc-q-caption">Question ${qNumber}${totalQs ? ' / ' + totalQs : ''}</div>
      ${renderLifelinesBarHtml()}
      <div class="kbc-glow-border mb-6 ${Number(qNumber) % 2 === 1 ? 'kbc-q-slide-left' : 'kbc-q-slide-right'}">
        <div class="glass-card kbc-card kbc-glow-inner rounded-2xl p-6">
          <h3 class="font-headline-lg text-headline-lg kbc-title-glow">${escapeHtml(primaryText)}</h3>
          ${secondaryText ? `<p class="text-amber-200/90 text-sm font-semibold text-center mt-2.5 pt-2 border-t border-white/10 leading-relaxed font-sans">${escapeHtml(secondaryText)}</p>` : ''}
        </div>
      </div>    </div>
      <div class="flex flex-col gap-3" id="qplay-options">
        <div class="flex flex-col items-center py-4 text-on-surface-variant" id="qplay-hold-spinner">
          <p class="font-body-sm text-center">Options appearing in <span id="qplay-hold-sec">5</span>s...</p>
        </div>
      </div>
    </div>`;

            qs('#qplay-mute-slot').outerHTML = kbcMuteBtnHtml().replace('kbc-mute-btn',
                'kbc-icon-badge');
            wireMuteBtn();
        }

        function renderOptions(options, qIndex) {
            const optWrap = qs('#qplay-options');
            if (!optWrap) return;

            const OPT_LETTERS = ['A', 'B', 'C', 'D', 'E', 'F'];
            optWrap.innerHTML = options.map((opt, i) => {
                const optText = typeof opt === 'string' ? opt : pick(opt, ['text', 'option',
                    'option_text', 'label'
                ], `Option ${i+1}`);
                const optVal = typeof opt === 'string' ? opt : pick(opt, ['id', 'option_id',
                    'value'], optText);
                const slideClass = (i % 2 === 0) ? 'kbc-opt-slide-left' : 'kbc-opt-slide-right';
                const animDelay = (i * 0.18).toFixed(2);
                return `<div class="kbc-opt-glow ${slideClass}" style="animation-delay: ${animDelay}s;">
        <button data-val="${escapeHtml(optVal)}" data-text="${escapeHtml(optText)}" class="option-btn kbc-option kbc-option-flat active:scale-[0.98]">
          <span class="kbc-opt-letter-flat">${OPT_LETTERS[i] || i+1}:</span>
          <span class="font-body-lg text-on-surface">${escapeHtml(optText)}</span>
        </button>
      </div>`;
            }).join('');

            const userAns = answeredQuestions[qIndex];
            const lifelineBar = qs('#qplay-lifelines-bar');
            if (lifelineBar) {
                qsa('button', lifelineBar).forEach((btn) => {
                    const isAns = userAns && userAns.answered;
                    btn.disabled = isAns;
                    btn.classList.toggle('opacity-50', isAns);
                    btn.classList.toggle('pointer-events-none', isAns);
                    btn.classList.toggle('cursor-not-allowed', isAns);
                });
            }

            qsa('.option-btn', optWrap).forEach((btn) => {
                if (userAns && userAns.answered) {
                    btn.disabled = true;
                    btn.classList.add('opacity-60', 'pointer-events-none');
                    if (btn.dataset.val === String(userAns.val) || btn.dataset.text === String(
                            userAns.val)) {
                        btn.classList.add('selected');
                    }
                } else {
                    btn.addEventListener('click', () => handleOptionClick(btn.dataset.val, btn,
                        qIndex));
                }
            });
        }

        async function handleOptionClick(val, btnEl, qIndex) {
            if (!routeActive || (answeredQuestions[qIndex] && answeredQuestions[qIndex].answered))
                return;
            answeredQuestions[qIndex] = {
                answered: true,
                val,
                isCorrect: null,
                correctVal: null
            };
            Sound.select();

            const lifelineBar = qs('#qplay-lifelines-bar');
            if (lifelineBar) {
                qsa('button', lifelineBar).forEach((b) => {
                    b.disabled = true;
                    b.classList.add('opacity-50', 'pointer-events-none', 'cursor-not-allowed');
                });
            }

            const optWrap = qs('#qplay-options');
            if (optWrap) {
                qsa('.option-btn', optWrap).forEach((b) => {
                    b.disabled = true;
                    b.classList.add('opacity-60', 'pointer-events-none');
                });
                if (btnEl) btnEl.classList.add('selected');
            }

            try {
                const submitRes = await submitAnswer(currentQuestionObj.id, val);
                const submitData = unwrap(submitRes) || submitRes || {};
                const rawIsCorrect = pick(submitData, ['is_correct', 'isCorrect', 'correct',
                    'answer_correct'
                ], null);
                const correctVal = pick(submitData, ['correct_answer', 'correct_option',
                    'correct_option_id', 'correctAnswer', 'answer'
                ], null);
                let isCorrect = null;
                if (rawIsCorrect !== null) isCorrect = (rawIsCorrect === true || rawIsCorrect === 1 ||
                    rawIsCorrect === '1' || String(rawIsCorrect).toLowerCase() === 'true');

                answeredQuestions[qIndex].isCorrect = isCorrect;
                answeredQuestions[qIndex].correctVal = correctVal;

                if (isCorrect !== null && optWrap) {
                    if (btnEl) btnEl.classList.add('selected');
                }

                reviewLog.push({
                    text: currentQuestionObj ? currentQuestionObj.text : 'Question',
                    chosenText: btnEl ? btnEl.dataset.text : val,
                    isCorrect,
                    correctText: correctVal ? String(correctVal) : null,
                });
                try {
                    sessionStorage.setItem('qx_review_' + quizId, JSON.stringify(reviewLog));
                } catch (e) {}
            } catch (e) {
                toast(e.message, 'error');
            }
        }

        async function masterTick() {
            if (!routeActive) return;

            const state = QuizMasterClock.calculateState(quizId);
            const reviewDone = localStorage.getItem(`qx_review_done_${quizId}`);
            const winnersDone = localStorage.getItem(`qx_winners_done_${quizId}`);

            if (winnersDone) {

                routeActive = false;
                if (tickInterval) clearInterval(tickInterval);
                stopBg();
                navigate(`/quiz/winners/${quizId}`, true);
                return;
            }

            if (state.phase === 'PENDING_WAIT' || state.phase === 'WARMUP_WAIT') {
                if (!reviewDone) {

                    routeActive = false;
                    if (tickInterval) clearInterval(tickInterval);
                    stopBg();
                    navigate(`/quiz/waiting/${quizId}`, true);
                    return;
                }
            }

            if (state.phase === 'ANSWERSHEET_REVIEW' || state.phase === 'LEADERBOARD_WAIT' || state
                .phase === 'FINAL_WINNERS' || (currentQuestionObj && currentQuestionObj.isCompleted)) {

                routeActive = false;
                if (tickInterval) clearInterval(tickInterval);
                stopBg();
                try {
                    sessionStorage.setItem('qx_review_' + quizId, JSON.stringify(reviewLog));
                } catch (e) {}

                if (!reviewDone) {
                    navigate(`/quiz/review/${quizId}`, true);
                    return;
                }

                localStorage.setItem(`qx_wait_mode_${quizId}`, 'winners');
                navigate(`/quiz/waiting/${quizId}`, true);
                return;
            }

            if (state.phase === 'QUESTION_HOLD' || state.phase === 'OPTIONS_ANSWER') {
                const qIndex = state.questionIndex;

                if (currentQIndex !== qIndex && !isLoadingQuestion) {
                    if (Date.now() < nextQuestionRetryAt) return;

                    isLoadingQuestion = true;
                    lastSayAnsQIndex = -1;
                    lastOptionsRevealQIndex = -1;
                    currentQuestionObj = null;
                    currentOptions = [];
                    isLoadingOptions = false;
                    Sound.questionLoad();

                    try {
                        currentQuestionObj = await loadQuestionForIndex(qIndex);
                        if (!currentQuestionObj) {
                            throw new Error('Question API returned no question');
                        }
                        currentQIndex = qIndex;
                        nextQuestionRetryAt = 0;
                    } catch (e) {
                        console.error('Failed to load question:', e);
                        nextQuestionRetryAt = Date.now() + 1000;
                        showQuestionLoadError();
                    } finally {
                        isLoadingQuestion = false;
                    }

                    if (currentQuestionObj && currentQuestionObj.isCompleted) {
                        masterTick();
                        return;
                    }

                    if (currentQuestionObj && routeActive) {
                        await renderBaseQuestionCard(state.questionNumber, state.totalQuestions,
                            currentQuestionObj.text, currentQuestionObj.hindi_text);

                        isLoadingOptions = true;
                        try {
                            currentOptions = await loadOptions(currentQuestionObj.id);
                            if (!currentOptions || currentOptions.length === 0) {
                                throw new Error('Options API returned no options');
                            }
                            nextOptionsRetryAt = 0;
                        } catch (e) {
                            console.error('Failed to load question options:', e);
                            currentOptions = [];
                            nextOptionsRetryAt = Date.now() + 1000;
                            showOptionsLoadError();
                        } finally {
                            isLoadingOptions = false;
                        }

                        if (routeActive && currentOptions && currentOptions.length > 0) {
                            const checkState = QuizMasterClock.calculateState(quizId);
                            if (checkState.phase === 'OPTIONS_ANSWER') {
                                if (lastOptionsRevealQIndex !== qIndex) {
                                    lastOptionsRevealQIndex = qIndex;
                                    Sound.optionsReveal();
                                }
                                renderOptions(currentOptions, qIndex);
                            }
                        }
                    }
                }

                if (!currentQuestionObj || currentQuestionObj.isCompleted) return;

                if (state.phase === 'OPTIONS_ANSWER' && !isLoadingOptions &&
                    (!currentOptions || currentOptions.length === 0) && Date.now() >= nextOptionsRetryAt) {
                    isLoadingOptions = true;
                    try {
                        currentOptions = await loadOptions(currentQuestionObj.id);
                        if (!currentOptions || currentOptions.length === 0) {
                            throw new Error('Options API returned no options');
                        }
                        nextOptionsRetryAt = 0;
                    } catch (e) {
                        console.error('Failed to retry question options:', e);
                        currentOptions = [];
                        nextOptionsRetryAt = Date.now() + 1000;
                        showOptionsLoadError();
                    } finally {
                        isLoadingOptions = false;
                    }

                    if (routeActive && currentOptions.length > 0 &&
                        QuizMasterClock.calculateState(quizId).phase === 'OPTIONS_ANSWER') {
                        if (lastOptionsRevealQIndex !== qIndex) {
                            lastOptionsRevealQIndex = qIndex;
                            Sound.optionsReveal();
                        }
                        renderOptions(currentOptions, qIndex);
                    }
                }

                const fill = qs('#qplay-dial-fill');
                const num = qs('#qplay-dial-number');
                const wrap = qs('#qplay-dial-wrap');

                if (state.phase === 'QUESTION_HOLD') {
                    const s = state.remainingHoldSec;
                    if (fill) fill.style.strokeDashoffset = String(DIAL_CIRCUMFERENCE * (1 - Math.max(
                        state.remainingHoldMs, 0) / 5000));
                    if (num) num.textContent = String(Math.max(s, 1));
                    const holdSecEl = qs('#qplay-hold-sec');
                    if (holdSecEl) holdSecEl.textContent = String(Math.max(s, 1));

                    if (lastPlayedSecond !== s && s > 0) {
                        lastPlayedSecond = s;
                    }
                } else if (state.phase === 'OPTIONS_ANSWER') {
                    const s = state.remainingAnswerSec;

                    if (!isLoadingOptions && currentOptions && currentOptions.length > 0 && (qs('#qplay-hold-spinner') || !qs('.option-btn'))) {
                        if (lastOptionsRevealQIndex !== qIndex) {
                            lastOptionsRevealQIndex = qIndex;
                            Sound.optionsReveal();
                        }
                        renderOptions(currentOptions, qIndex);
                    }

                    if (fill) fill.style.strokeDashoffset = String(DIAL_CIRCUMFERENCE * (1 - Math.max(
                        state.remainingAnswerMs, 0) / 10000));
                    if (num) num.textContent = String(Math.max(s, 0));
                    if (wrap) wrap.classList.toggle('urgent', s <= 3 && s > 0);

                    if (lastPlayedSecond !== s && s > 0) {
                        lastPlayedSecond = s;
                        if (s === 3) Sound.tension();
                    }

                }
            }
        }

        masterTick();
        tickInterval = setInterval(masterTick, 250);

        window.__quizLeave = async () => {
            if (tickInterval) clearInterval(tickInterval);
            stopBg();
            try {
                await api(`/quiz/${quizId}/leave`, {
                    method: 'POST'
                });
            } catch (e) {}
            navigate('/home', true);
        };
    });
</script>
