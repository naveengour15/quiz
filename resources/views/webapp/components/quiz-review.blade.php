<template id="tpl-quiz-review">
  <main class="min-h-screen px-container-margin pt-8 pb-10 kbc-stage">
    <div class="kbc-spotlight"></div><div class="kbc-stage-rings"><span></span><span></span><span></span><span></span></div>
    <div class="relative z-[1] flex justify-end mb-2" id="review-mute-placeholder"></div>
    <div id="review-root" class="relative z-[1] flex flex-col items-center py-16">
      <div class="kbc-drumroll-ring"></div>
      <h3 class="text-on-surface-variant mt-5 kbc-title-glow font-headline-lg">Tallying your answers...</h3>
      <p class="text-tertiary font-label-caps text-sm mt-2">Answers revealing in <span id="review-tally-counter" class="font-display text-xl text-primary font-bold">10</span>s</p>
    </div>
  </main>
</template>

<script>
route('/quiz/review/:id', async (app, params) => {
  const quizId = params.id;
  const winnersDone = localStorage.getItem(`qx_winners_done_${quizId}`) === '1';

  if (winnersDone) {
    navigate(`/quiz/winners/${quizId}`, true);
    return;
  }

  app.innerHTML = qs('#tpl-quiz-review').innerHTML;
  qs('#review-mute-placeholder').outerHTML = kbcMuteBtnHtml();
  wireMuteBtn();

  Sound.ensureCtx();
  Sound.stopBackground();

  let log = [];
  let apiFailed = false;

  // 1. Fetch answer sheet in background without blocking counter startup
  const fetchP = api(`/quiz/${quizId}/answer-sheet`, { method: 'GET' })
    .then(res => {
      const raw = unwrap(res) || res || {};
      const list = Array.isArray(raw) ? raw : (raw.answers || asList(res));
      return list.map(item => {
        const qText = pick(item, ['question', 'question_text', 'title'], 'Question');
        const hindiQText = pick(item, ['hindi_question', 'hindi_text'], null);
        const chosenText = pick(item, ['your_answer', 'user_answer', 'selected_answer', 'chosen_answer', 'user_option', 'selected_option', 'given_answer', 'answer'], null);
        const correctText = pick(item, ['correct_answer', 'correct_option', 'correctAnswer', 'right_answer'], null);
        const rawIsCorrect = pick(item, ['is_correct', 'isCorrect', 'correct', 'answer_correct'], null);
        let isCorrect = null;
        if (rawIsCorrect !== null && rawIsCorrect !== '') {
          isCorrect = (rawIsCorrect === true || rawIsCorrect === 1 || rawIsCorrect === '1' || String(rawIsCorrect).toLowerCase() === 'true');
        } else if (chosenText != null && correctText != null) {
          isCorrect = String(chosenText).trim().toLowerCase() === String(correctText).trim().toLowerCase();
        }
        return { text: qText, hindiText: hindiQText, chosenText, correctText, isCorrect };
      });
    })
    .catch(e => {
      apiFailed = true;
      return [];
    });

  // 2. Start 10-Second Counter Screen IMMEDIATELY (0ms delay)
  let tallySec = 10;
  const tallyTimer = setInterval(() => {
    tallySec = Math.max(tallySec - 1, 0);
    const el = qs('#review-tally-counter');
    if (el) el.textContent = String(tallySec);
    Sound.tick(tallySec <= 3);

    if (tallySec <= 0) {
      clearInterval(tallyTimer);
      renderAnswerSheet();
    }
  }, 1000);

  async function renderAnswerSheet() {
    try {
      log = await fetchP;
    } catch (e) {
      log = [];
    }

    if (!log.length) {
      try { log = JSON.parse(sessionStorage.getItem('qx_review_' + quizId) || '[]'); } catch (e) { log = []; }
    }

    const total = log.length;
    const correctCount = log.filter(r => r.isCorrect === true).length;
    const wrongCount = log.filter(r => r.isCorrect === false).length;

    const summaryHtml = total ? `
    <div class="grid grid-cols-3 gap-3 w-full mb-8">
      <div class="kbc-card rounded-xl p-4 text-center">
        <p class="font-label-caps text-[10px] text-on-surface-variant mb-1">CORRECT</p>
        <p class="font-display text-2xl text-mint">${correctCount}</p>
      </div>
      <div class="kbc-card rounded-xl p-4 text-center">
        <p class="font-label-caps text-[10px] text-on-surface-variant mb-1">WRONG</p>
        <p class="font-display text-2xl" style="color:#ff8a8a">${wrongCount}</p>
      </div>
      <div class="kbc-card rounded-xl p-4 text-center">
        <p class="font-label-caps text-[10px] text-on-surface-variant mb-1">TOTAL</p>
        <p class="font-display text-2xl kbc-title-glow">${total}</p>
      </div>
    </div>
    <div class="w-full flex flex-col gap-3 mb-8">
      ${log.map((r, i) => {
        const isWrong = r.isCorrect === false;
        const qTitleHtml = r.hindiText && r.hindiText.trim().length > 0
          ? `<p class="font-body-sm font-bold text-on-surface">${escapeHtml(r.hindiText.trim())}</p><p class="text-amber-200/90 text-xs font-medium mt-1 pt-1 border-t border-white/10 font-sans">${escapeHtml(r.text)}</p>`
          : `<p class="font-body-sm text-on-surface">${escapeHtml(r.text)}</p>`;
        return `
        <div class="${isWrong ? 'kbc-glow-border-red' : 'kbc-card'} kbc-reveal-row rounded-xl p-4 mb-3" style="animation-delay:${i * 70}ms">
          <div class="kbc-glow-inner-red rounded-xl p-1">
            <div class="mb-2"><span class="font-bold text-xs ${isWrong ? 'text-error' : 'text-mint'}">Question ${i + 1}. ${isWrong ? 'Incorrect' : 'Correct'}</span> ${qTitleHtml}</div>
            <div class="flex flex-col gap-1.5 text-xs pt-2 border-t border-white/10">
              <div class="flex items-center gap-1.5 text-mint">
                <span class="font-semibold">Correct Answer:</span>
                <span class="font-bold">${escapeHtml(r.correctText || '-')}</span>
              </div>
              <div class="flex items-center gap-1.5 ${isWrong ? 'text-error' : 'text-mint'}">
                <span>Your Answer:</span>
                <span class="font-bold">${escapeHtml(r.chosenText || '-')}</span>
              </div>
            </div>
          </div>
        </div>`;
      }).join('')}
    </div>` : `
    <div class="kbc-card rounded-xl p-6 text-center mb-8 w-full">
      <p class="text-on-surface-variant">${apiFailed ? "Couldn't load the answer sheet from the server." : "Detailed answer summary isn't available for this session."}</p>
    </div>`;

    // 3. Answer Sheet Screen for 15 Seconds
    qs('#review-root').outerHTML = `
    <div id="review-root" class="relative z-[1] flex flex-col items-center py-4 fade-in w-full">
      <div class="w-20 h-20 rounded-full vibrant-gradient flex items-center justify-center mb-4 shadow-[0_0_40px_rgba(255,215,106,0.4)]">
        <span class="material-symbols-outlined text-white text-4xl">fact_check</span>
      </div>
      <h2 class="font-display text-headline-lg kbc-title-glow mb-1">Answer Recap</h2>
      <p class="text-on-surface-variant mb-4">Here's how every question went</p>

      <div class="kbc-card rounded-full px-6 py-2 mb-6 border border-primary/30 flex items-center gap-2">
        <span class="material-symbols-outlined text-primary text-sm">schedule</span>
        <span class="text-on-surface text-sm">Moving to results calculation in <span id="review-sec" class="font-bold text-primary">15</span>s</span>
      </div>

      ${summaryHtml}
      <button id="review-skip-btn" class="w-full bg-primary text-on-primary py-4 rounded-xl font-action text-lg shadow-[0_0_20px_rgba(210,187,255,0.3)] active:scale-95 transition-transform">Proceed to Results Calculation</button>
    </div>`;

    Sound.ensureCtx();
    Sound.stopBackground();
    Sound.answerSheet();
    Sound.stopBackground();

    let reviewSec = 15;
    const reviewTimer = setInterval(() => {
      reviewSec = Math.max(reviewSec - 1, 0);
      const secEl = qs('#review-sec');
      if (secEl) secEl.textContent = String(reviewSec);
      Sound.stopBackground();

      if (reviewSec <= 0) {
        clearInterval(reviewTimer);
        goToWaitingRoom();
      }
    }, 1000);

    function goToWaitingRoom() {
      clearInterval(reviewTimer);
      Sound.stopBackground();
      localStorage.setItem(`qx_review_done_${quizId}`, '1');
      localStorage.setItem(`qx_wait_mode_${quizId}`, 'winners');
      navigate(`/quiz/waiting/${quizId}`, true);
    }

    const skipBtn = qs('#review-skip-btn');
    if (skipBtn) {
      skipBtn.onclick = () => goToWaitingRoom();
    }
  }
});
</script>
