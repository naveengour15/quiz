<template id="tpl-practice-quiz-play">
    <main class="min-h-screen px-container-margin pt-8 pb-8 flex flex-col kbc-stage">
        <div class="kbc-spotlight"></div>
        <div class="kbc-stage-rings"><span></span><span></span><span></span><span></span></div>
        <div id="practice-play-root" class="flex-1 flex flex-col items-center justify-center relative z-[1]">
            <!-- Render initial landing card via JS -->
        </div>
    </main>
</template>

<script>
    route('/practice-quiz/play/:id', async (app, params) => {
        const quizId = params.id;
        app.innerHTML = qs('#tpl-practice-quiz-play').innerHTML;

        Sound.ensureCtx();
        Sound.stopBackground();
        let bgStopped = false;

        function stopBg() {
            if (!bgStopped) {
                bgStopped = true;
                Sound.stopBackground();
            }
        }

        const isObj = (val) => val !== null && typeof val === 'object';
        const DIAL_CIRCUMFERENCE = 276.5;

        let currentQIndex = 0;
        let totalQuestions = 10;
        let currentQuestionObj = null;
        let currentOptions = [];
        let questionTimer = null;
        let warmupTimer = null;
        let secondsLeft = 5;
        let phase = 'reveal'; // 'reveal' (5s question text only) or 'active' (10s options interactive)
        let sayAnsPlayed = false;
        let answered = false;
        let lifelines = {
            fifty_fifty: true,
            switch_option: true,
            used_fifty: false,
            used_switch: false,
            used_lifeline: false
        };
        let questionShowTime = null;

        renderLandingUI();

        function renderLandingUI() {
            const root = qs('#practice-play-root');
            if (!root) return;

            root.innerHTML = `
      <div class="w-full max-w-lg mx-auto text-center py-8 px-4 flex flex-col items-center">
        <div class="w-20 h-20 mx-auto rounded-3xl bg-mint/10 border border-mint/30 flex items-center justify-center mb-6 shadow-[0_0_30px_rgba(74,222,128,0.2)]">
          <span class="material-symbols-outlined text-mint text-4xl">school</span>
        </div>
        <span class="px-3 py-1 rounded-full text-xs font-bold bg-mint/20 text-mint uppercase tracking-wider mb-3 inline-block">Practice Session</span>
        <h2 class="font-headline-lg text-2xl md:text-3xl font-extrabold text-on-surface mb-3">Ready to Practice?</h2>
        <p class="text-on-surface-variant text-sm mb-6 max-w-sm mx-auto">Sharpen your knowledge with 10 practice questions. No points or wallet deduction!</p>
        
        <div class="grid grid-cols-2 gap-3 mb-8 w-full text-left">
          <div class="kbc-card rounded-2xl p-4 border border-white/10 flex items-center gap-3">
            <span class="material-symbols-outlined text-secondary text-2xl">visibility</span>
            <div>
              <p class="text-[11px] text-on-surface-variant uppercase font-bold">Q Reveal</p>
              <p class="font-bold text-on-surface text-sm">5 Seconds</p>
            </div>
          </div>
          <div class="kbc-card rounded-2xl p-4 border border-white/10 flex items-center gap-3">
            <span class="material-symbols-outlined text-primary text-2xl">timer</span>
            <div>
              <p class="text-[11px] text-on-surface-variant uppercase font-bold">Answer Time</p>
              <p class="font-bold text-on-surface text-sm">10 Seconds</p>
            </div>
          </div>
          <div class="kbc-card rounded-2xl p-4 border border-white/10 flex items-center gap-3">
            <span class="material-symbols-outlined text-tertiary text-2xl">bolt</span>
            <div>
              <p class="text-[11px] text-on-surface-variant uppercase font-bold">Lifelines</p>
              <p class="font-bold text-on-surface text-sm">50:50 & Switch Q</p>
            </div>
          </div>
          <div class="kbc-card rounded-2xl p-4 border border-white/10 flex items-center gap-3">
            <span class="material-symbols-outlined text-mint text-2xl">format_list_numbered</span>
            <div>
              <p class="text-[11px] text-on-surface-variant uppercase font-bold">Total Qs</p>
              <p class="font-bold text-on-surface text-sm">10 Questions</p>
            </div>
          </div>
        </div>

        <button id="btn-start-practice-sp" class="w-full py-4 rounded-2xl font-display font-bold text-base bg-gradient-to-r from-primary via-secondary to-primary bg-[length:200%_auto] text-on-primary shadow-[0_0_25px_rgba(168,85,247,0.4)] hover:brightness-110 active:scale-95 transition-all flex items-center justify-center gap-2 cursor-pointer">
          <span class="material-symbols-outlined text-2xl">play_circle</span>
          <span>Start Practice Quiz</span>
        </button>
      </div>
    `;

            const btn = qs('#btn-start-practice-sp');
            if (btn) {
                btn.addEventListener('click', () => {
                    startPracticeQuizFlow();
                });
            }
        }

        async function startPracticeQuizFlow() {
            try {
                const initRes = await api(`/practice-quiz/${quizId}/start`, {
                    method: 'GET'
                });
                const rawInit = unwrap(initRes) || initRes || {};
                totalQuestions = rawInit.total_questions || 10;
                if (rawInit.lifelines) {
                    lifelines = rawInit.lifelines;
                }
            } catch (e) {
                console.warn('[Practice Start Error]', e);
            }

            run20SecondWarmup();
        }

        function run20SecondWarmup() {
            const root = qs('#practice-play-root');
            if (!root) return;

            if (typeof Sound !== 'undefined' && Sound.startQuizSound) {
                Sound.startQuizSound();
            }

            let warmupSec = 10;
            root.innerHTML = `
      <div class="w-full max-w-lg mx-auto text-center py-12 px-4 flex flex-col items-center">
        <span class="px-3 py-1 rounded-full text-xs font-bold bg-secondary/20 text-secondary uppercase tracking-wider mb-4 inline-block">Get Ready</span>
        <h2 class="font-headline-lg text-2xl font-bold text-on-surface mb-2">Practice Quiz Starting</h2>
        <p class="text-on-surface-variant text-sm mb-8">First question appears when timer reaches zero</p>
        
        <div class="relative w-36 h-36 rounded-full border-4 border-secondary/30 flex items-center justify-center mb-8 shadow-[0_0_30px_rgba(244,191,50,0.2)]">
          <div class="absolute inset-0 rounded-full border-4 border-secondary border-t-transparent animate-spin"></div>
          <span id="warmup-timer" class="font-display text-5xl font-extrabold text-secondary">10</span>
        </div>

        <button id="btn-skip-warmup-sp" class="px-5 py-2.5 rounded-xl text-xs font-bold bg-white/10 text-on-surface hover:bg-white/20 transition-all flex items-center gap-1.5 cursor-pointer">
          <span>Skip Warmup</span>
          <span class="material-symbols-outlined text-sm">fast_forward</span>
        </button>
      </div>
    `;

            if (warmupTimer) clearInterval(warmupTimer);
            warmupTimer = setInterval(() => {
                warmupSec--;
                const timerEl = qs('#warmup-timer');
                if (timerEl) {
                    timerEl.textContent = Math.max(warmupSec, 0);
                }

                if (warmupSec <= 0) {
                    clearInterval(warmupTimer);
                    loadQuestion(0);
                }
            }, 1000);

            const skipBtn = qs('#btn-skip-warmup-sp');
            if (skipBtn) {
                skipBtn.addEventListener('click', () => {
                    if (warmupTimer) clearInterval(warmupTimer);
                    loadQuestion(0);
                });
            }
        }

        async function loadQuestion(index) {
            if (index >= totalQuestions) {
                finishQuiz();
                return;
            }

            currentQIndex = index;
            answered = false;
            phase = 'reveal';
            secondsLeft = 5;
            if (questionTimer) clearInterval(questionTimer);

            const root = qs('#practice-play-root');

            try {
                const res = await api(`/practice-quiz/${quizId}/get-question`, {
                    method: 'POST',
                    body: JSON.stringify({
                        index
                    })
                });
                const data = unwrap(res) || res || {};

                if (!data.success || !data.question) {
                    finishQuiz();
                    return;
                }

                currentQuestionObj = data.question;
                currentOptions = data.question.options || [];
                if (data.lifelines) {
                    lifelines = data.lifelines;
                }

                questionShowTime = new Date().toISOString();
                Sound.ensureCtx();
                Sound.questionLoad();
                renderQuestionUI();
                start5SecRevealTimer();
            } catch (e) {
                toast('Error loading practice question', 'error');
            }
        }

        function updateDialUI(totalSec) {
            const fill = qs('#qplay-dial-fill');
            const num = qs('#qplay-dial-number');
            const wrap = qs('#qplay-dial-wrap');
            const holdEl = qs('#options-countdown');

            const clampedSec = Math.max(secondsLeft, 0);
            const offset = DIAL_CIRCUMFERENCE * (1 - (clampedSec / totalSec));

            if (fill) fill.style.strokeDashoffset = String(offset);
            if (num) num.textContent = String(clampedSec);
            if (holdEl) holdEl.textContent = String(clampedSec);
            if (wrap) wrap.classList.toggle('urgent', clampedSec <= 3 && clampedSec > 0);
        }

        function start5SecRevealTimer() {
            if (questionTimer) clearInterval(questionTimer);
            questionTimer = setInterval(() => {
                secondsLeft--;
                updateDialUI(5);

                if (secondsLeft <= 0) {
                    clearInterval(questionTimer);
                    phase = 'active';
                    secondsLeft = 10;
                    // 4. answer_appear sound
                    Sound.optionsReveal();
                    renderQuestionUI();
                    start10SecAnswerTimer();
                }
            }, 1000);
        }

        function start10SecAnswerTimer() {
            if (questionTimer) clearInterval(questionTimer);
            // 5. counter 10 second sound for answering
            Sound.tick(true);

            questionTimer = setInterval(() => {
                secondsLeft--;
                updateDialUI(10);
                if (secondsLeft === 3) Sound.tension();

                if (secondsLeft <= 0) {
                    clearInterval(questionTimer);
                    if (!answered) {
                        loadQuestion(currentQIndex + 1);
                    }
                }
            }, 1000);
        }

        function renderLifelinesHtml(enabled = false) {
            const hasUsed = lifelines.used_lifeline || lifelines.used_fifty || lifelines.used_switch;
            if (hasUsed) return '';

            const disAttr = enabled ? '' : 'disabled';
            const disClass = enabled ? '' : 'opacity-50 pointer-events-none cursor-not-allowed';

            return `
      <div id="qplay-lifelines-bar" class="flex items-center justify-center gap-3 mb-4">
        ${lifelines.fifty_fifty ? `
          <button id="btn-lifeline-5050" ${disAttr} class="px-4 py-2 rounded-xl text-xs font-bold transition-all bg-gradient-to-r from-amber-500 to-yellow-400 text-black shadow-lg hover:scale-105 active:scale-95 flex items-center gap-1.5 cursor-pointer ${disClass}" onclick="usePracticeLifeline('fifty_fifty')">
            <span>⚡</span>
            <span>50:50</span>
          </button>
        ` : ''}
        ${lifelines.switch_option ? `
          <button id="btn-lifeline-switch" ${disAttr} class="px-4 py-2 rounded-xl text-xs font-bold transition-all bg-gradient-to-r from-purple-600 to-indigo-500 text-white shadow-lg hover:scale-105 active:scale-95 flex items-center gap-1.5 cursor-pointer ${disClass}" onclick="usePracticeLifeline('switch_option')">
            <span class="material-symbols-outlined text-[16px]">sync</span>
            <span>Switch Q</span>
          </button>
        ` : ''}
      </div>
    `;
        }

        function renderQuestionUI() {
            const root = qs('#practice-play-root');
            if (!root) return;

            const q = currentQuestionObj;
            const totalSec = phase === 'reveal' ? 5 : 10;
            const initialOffset = DIAL_CIRCUMFERENCE * (1 - (secondsLeft / totalSec));

            let optionsHtml = '';
            if (phase === 'reveal') {
                optionsHtml = `
        <div class="w-full flex flex-col items-center justify-center py-8 text-on-surface-variant kbc-card rounded-2xl p-6 border border-white/10 mb-6">
          <div class="w-8 h-8 border-4 border-secondary/30 border-t-secondary rounded-full spin mb-3"></div>
          <p class="font-body-sm font-bold text-sm">Options appearing in <span id="options-countdown" class="text-secondary font-extrabold text-base">${secondsLeft}</span>s...</p>
        </div>
      `;
            } else {
                optionsHtml = `
        <div id="options-container" class="flex flex-col gap-3 w-full mb-6">
          ${currentOptions.map((opt, i) => {
            const val = isObj(opt) ? (opt.value ?? opt.text ?? '') : opt;
            const label = isObj(opt) ? (opt.text ?? opt.value ?? '') : opt;
            const slideClass = (i % 2 === 0) ? 'kbc-opt-slide-left' : 'kbc-opt-slide-right';
            const animDelay = (i * 0.18).toFixed(2);
            return `
              <div class="kbc-opt-glow ${slideClass} w-full" style="animation-delay: ${animDelay}s;">
                <button class="opt-btn option-btn kbc-option kbc-option-flat active:scale-[0.98] cursor-pointer" data-val="${escapeHtml(val)}" onclick="submitPracticeAns('${escapeHtml(val)}', this)">
                  <span class="kbc-opt-letter-flat">${String.fromCharCode(65 + i)}:</span>
                  <span class="font-body-lg text-on-surface flex-1">${escapeHtml(label)}</span>
                </button>
              </div>
            `;
          }).join('')}
        </div>
      `;
            }

            root.innerHTML = `
      <div class="w-full max-w-xl mx-auto flex flex-col items-center">
        <div class="w-full flex items-center justify-between mb-4">
          <span class="text-xs font-bold uppercase tracking-wider text-mint px-3 py-1 rounded-full bg-mint/10 border border-mint/20">Practice Mode</span>
          <span class="font-label-caps kbc-title-glow text-[12px] tracking-wide">FSRQuiz PRACTICE</span>
          ${kbcMuteBtnHtml()}
        </div>

        <!-- KBC Dial Timer (Same as Main Quiz) -->
        <div class="kbc-dial-wrap ${secondsLeft <= 3 ? 'urgent' : ''}" id="qplay-dial-wrap">
          <svg viewBox="0 0 100 100">
            <defs>
              <linearGradient id="kbcDialGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" stop-color="var(--kbc-teal)"/>
                <stop offset="100%" stop-color="var(--kbc-mint)"/>
              </linearGradient>
            </defs>
            <circle class="kbc-dial-track" cx="50" cy="50" r="44" fill="none" stroke-width="7"/>
            <circle class="kbc-dial-fill" id="qplay-dial-fill" cx="50" cy="50" r="44" fill="none" stroke-width="7" stroke-dasharray="276.5" stroke-dashoffset="${initialOffset}"/>
          </svg>
          <div class="kbc-dial-number" id="qplay-dial-number">${secondsLeft}</div>
        </div>
        <div class="kbc-q-caption">Question ${currentQIndex + 1} / ${totalQuestions}</div>

        ${renderLifelinesHtml(phase === 'active' && !answered)}

        <div class="kbc-glow-border mb-6 w-full ${currentQIndex % 2 === 0 ? 'kbc-q-slide-left' : 'kbc-q-slide-right'}">
          <div class="glass-card kbc-card kbc-glow-inner rounded-2xl p-6 text-center">
            <h3 class="font-headline-lg text-headline-lg kbc-title-glow">
              ${escapeHtml((q.hindi_question && String(q.hindi_question).trim().length > 0) ? q.hindi_question.trim() : q.question_text)}
            </h3>
            ${(q.hindi_question && String(q.hindi_question).trim().length > 0) ? `<p class="text-amber-200/90 text-sm font-semibold text-center mt-2.5 pt-2 border-t border-white/10 leading-relaxed font-sans">${escapeHtml(q.question_text)}</p>` : ''}
          </div>
        </div>

        ${optionsHtml}
      </div>
    `;
        }

        window.submitPracticeAns = async function(val, btnEl) {
            if (answered || phase !== 'active') return;
            answered = true;
            if (questionTimer) clearInterval(questionTimer);

            const timeSpent = 10 - secondsLeft;

            Sound.select();

            const lifelineBar = qs('#qplay-lifelines-bar');
            if (lifelineBar) {
                qsa('button', lifelineBar).forEach(b => {
                    b.disabled = true;
                    b.classList.add('opacity-50', 'pointer-events-none', 'cursor-not-allowed');
                });
            }

            const allBtns = qsa('.opt-btn');
            allBtns.forEach(b => {
                b.disabled = true;
                b.classList.add('opacity-60', 'pointer-events-none');
            });

            if (btnEl) {
                btnEl.classList.remove('opacity-60');
                btnEl.classList.add('selected');
                const glowParent = btnEl.closest('.kbc-opt-glow');
                if (glowParent) glowParent.classList.add('selected');
            }

            try {
                const answerSubmitTime = new Date().toISOString();
                const res = await api(`/practice-quiz/${quizId}/submit-answer`, {
                    method: 'POST',
                    body: JSON.stringify({
                        question_id: currentQuestionObj.id,
                        answer: val,
                        show_time: questionShowTime || new Date().toISOString(),
                        ans_time: answerSubmitTime,
                        total_time: timeSpent
                    })
                });
                const data = unwrap(res) || res || {};

                if (btnEl) {
                    btnEl.classList.add('selected');
                    const glowParent = btnEl.closest('.kbc-opt-glow');
                    if (glowParent) glowParent.classList.add('selected');
                }

                setTimeout(() => {
                    if (currentQIndex + 1 >= totalQuestions) {
                        finishQuiz();
                    } else {
                        loadQuestion(currentQIndex + 1);
                    }
                }, 1200);
            } catch (e) {
                toast('Error submitting answer', 'error');
                setTimeout(() => {
                    if (currentQIndex + 1 >= totalQuestions) {
                        finishQuiz();
                    } else {
                        loadQuestion(currentQIndex + 1);
                    }
                }, 1200);
            }
        };

        window.usePracticeLifeline = async function(lifeline) {
            if (answered || phase !== 'active') return;
            try {
                const res = await api(`/practice-quiz/${quizId}/use-lifeline`, {
                    method: 'POST',
                    body: JSON.stringify({
                        lifeline: lifeline,
                        question_id: currentQuestionObj.id
                    })
                });
                const data = unwrap(res) || res || {};

                if (!data.success) {
                    toast(data.message || 'Cannot use lifeline', 'warning');
                    return;
                }

                lifelines.used_lifeline = true;
                if (lifeline === 'fifty_fifty') {
                    lifelines.used_fifty = true;
                    Sound.select();
                    if (typeof toast === 'function') toast('50:50 Lifeline applied!', 'success');

                    const bar = qs('#qplay-lifelines-bar');
                    if (bar) bar.style.display = 'none';

                    const optWrap = qs('#options-container');
                    if (optWrap) {
                        const OPT_LETTERS = ['A', 'B', 'C', 'D', 'E', 'F'];
                        const remainingVals = (data.remaining_options || []).map(opt => {
                            const v = isObj(opt) ? (opt.value ?? opt.text ?? '') : opt;
                            return String(v).trim().toLowerCase();
                        });
                        const removedVals = (data.removed_options || []).map(opt => {
                            const v = isObj(opt) ? (opt.value ?? opt.text ?? '') : opt;
                            return String(v).trim().toLowerCase();
                        });

                        const optBtns = qsa('.opt-btn', optWrap);
                        const remainingWrappers = [];
                        const removedWrappers = [];

                        optBtns.forEach((b) => {
                            const val = String(b.dataset.val || '').trim().toLowerCase();
                            const text = String(b.textContent || '').trim().toLowerCase();

                            const isRemoved = (removedVals.length > 0 && (removedVals.includes(val) || removedVals.includes(text))) ||
                                              (remainingVals.length > 0 && !remainingVals.includes(val));

                            const wrapper = b.closest('.kbc-opt-glow') || b.parentElement;
                            if (isRemoved) {
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

                    currentOptions = data.remaining_options || currentOptions;
                } else if (lifeline === 'switch_option') {
                    lifelines.used_switch = true;
                    if (data.question) {
                        currentQuestionObj = data.question;
                        currentOptions = data.question.options || [];
                        renderQuestionUI();
                    }
                }
            } catch (e) {
                toast('Lifeline error', 'error');
            }
        };

        function finishQuiz() {
            if (questionTimer) clearInterval(questionTimer);
            if (warmupTimer) clearInterval(warmupTimer);
            stopBg();
            navigate(`/practice-quiz/review/${quizId}`, true);
        }
    });
</script>
