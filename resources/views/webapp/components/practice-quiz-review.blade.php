<template id="tpl-practice-quiz-review">
  <div id="practice-review-header-placeholder"></div>
  <main class="min-h-screen px-container-margin pt-20 pb-10 kbc-stage">
    <div class="kbc-spotlight"></div><div class="kbc-stage-rings"><span></span><span></span><span></span><span></span></div>
    <div id="practice-review-root" class="relative z-[1] flex flex-col items-center py-16">
      <div class="kbc-drumroll-ring"></div>
      <p class="text-on-surface-variant mt-5 kbc-title-glow font-headline-md">Tallying your practice answers...</p>
      <p class="text-tertiary font-label-caps text-sm mt-2">Answers revealing in <span id="practice-review-tally-counter" class="font-display text-xl text-primary font-bold">10</span>s</p>
    </div>
  </main>
</template>

<script>
route('/practice-quiz/review/:id', async (app, params) => {
  const quizId = params.id;
  app.innerHTML = qs('#tpl-practice-quiz-review').innerHTML;

  const hdr = qs('#practice-review-header-placeholder');
  if (hdr) hdr.outerHTML = topHeader({ title: 'Answer Sheet', showBack: true, backTo: '/home' });

  Sound.ensureCtx();
  Sound.stopBackground();

  let log = [];
  let summaryStats = {
    total_time: 0,
    correct_answers: 0,
    wrong_answers: 0,
    total_questions: 10
  };
  let winnerComp = null;

  const fetchP = api(`/practice-quiz/${quizId}/answer-sheet`, { method: 'GET' })
    .then(res => {
      const raw = unwrap(res) || res || {};
      let list = [];
      if (raw.result && Array.isArray(raw.result.answers)) {
        list = raw.result.answers;
        summaryStats.total_time = parseFloat(raw.result.total_time) || 0;
        summaryStats.correct_answers = raw.result.correct_answers ?? 0;
        summaryStats.wrong_answers = raw.result.wrong_answers ?? 0;
        summaryStats.total_questions = raw.result.total_questions ?? list.length;
        winnerComp = raw.result.winner_comparison || null;
      } else if (Array.isArray(raw.answers)) {
        list = raw.answers;
        summaryStats.total_time = parseFloat(raw.total_time) || 0;
        winnerComp = raw.winner_comparison || null;
      } else if (Array.isArray(raw)) {
        list = raw;
      }

      return list.map(item => {
        const qText = item.question || item.question_text || 'Question';
        const chosenText = item.selected_answer ?? item.your_answer ?? null;
        const correctText = item.correct_answer ?? null;
        const totalTime = item.total_time ?? null;
        let isCorrect = item.is_correct;
        if (isCorrect === undefined && chosenText != null && correctText != null) {
          isCorrect = String(chosenText).trim().toLowerCase() === String(correctText).trim().toLowerCase();
        }
        return { text: qText, chosenText, correctText, isCorrect, totalTime };
      });
    })
    .catch(e => {
      apiFailed = true;
      return [];
    });

  let tallySec = 10;
  // Play 10-second counter sound before answer sheet
  Sound.tick(true);

  const tallyTimer = setInterval(() => {
    tallySec = Math.max(tallySec - 1, 0);
    const el = qs('#practice-review-tally-counter');
    if (el) el.textContent = String(tallySec);

    if (tallySec <= 0) {
      clearInterval(tallyTimer);
      renderAnswerSheet();
    }
  }, 1000);

  async function renderAnswerSheet() {
    log = await fetchP;
    const root = qs('#practice-review-root');
    if (!root) return;

    Sound.ensureCtx();
    Sound.stopBackground();
    // 6. Answer Sheet Appear sound
    Sound.answerSheet();

    // Clear sound caches and practice session
    try {
      sessionStorage.removeItem('qx_sound_settings');
      sessionStorage.removeItem('qx_cached_sound_urls');
      localStorage.removeItem('qx_sound_settings');
      localStorage.removeItem('qx_cached_sound_urls');
      sessionStorage.removeItem('pq_session_' + quizId);
      localStorage.removeItem('pq_session_' + quizId);
    } catch(e) {}

    const total = log.length || summaryStats.total_questions || 10;
    const correctCount = log.filter(x => x.isCorrect === true).length;
    const wrongCount = total - correctCount;
    const sumTime = log.reduce((acc, x) => acc + (parseFloat(x.totalTime) || 0), 0);
    const totalTimeFormatted = (summaryStats.total_time > 0 ? summaryStats.total_time : sumTime).toFixed(1);

    root.className = 'relative z-[1] max-w-xl mx-auto w-full';
    root.innerHTML = `
      <div class="kbc-card rounded-3xl p-6 mb-6 text-center border border-white/10 shadow-2xl">
        <span class="inline-block px-3 py-1 rounded-full text-xs font-bold bg-mint/20 text-mint uppercase tracking-wider mb-2">Practice Completed</span>
        <h2 class="font-headline-lg text-2xl font-bold text-on-surface mb-2">Practice Answer Recap</h2>
        <div class="grid grid-cols-4 gap-3 mt-4 text-center">
          <div>
            <span class="block text-xl font-bold text-mint">${correctCount}</span>
            <span class="text-[10px] text-on-surface-variant uppercase">Correct</span>
          </div>
          <div>
            <span class="block text-xl font-bold text-error">${wrongCount}</span>
            <span class="text-[10px] text-on-surface-variant uppercase">Wrong</span>
          </div>
          <div>
            <span class="block text-xl font-bold text-primary">${total}</span>
            <span class="text-[10px] text-on-surface-variant uppercase">Total</span>
          </div>
          <div>
            <span class="block text-xl font-bold text-amber-300">${totalTimeFormatted}s</span>
            <span class="text-[10px] text-on-surface-variant uppercase">Time</span>
          </div>
        </div>
      </div>

      ${winnerComp && winnerComp.has_winner ? `
        <div class="kbc-card rounded-2xl p-5 mb-6 border ${winnerComp.is_equal_or_better ? 'border-amber-400/40 bg-amber-400/5' : 'border-white/10'} shadow-lg text-center">
          <div class="flex items-center justify-between mb-3">
            <div class="flex items-center gap-2">
              <span class="text-xl">🏆</span>
              <div class="text-left">
                <h3 class="font-bold text-sm text-amber-300">Rank #1 Champion Benchmark</h3>
                <p class="text-[11px] text-on-surface-variant">Winner: <span class="text-white font-semibold">${escapeHtml(winnerComp.winner_name)}</span></p>
              </div>
            </div>
            <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase ${winnerComp.is_better ? 'bg-mint/20 text-mint' : (winnerComp.is_equal ? 'bg-amber-400/20 text-amber-300' : 'bg-white/10 text-white/70')}">
              ${winnerComp.is_better ? '🔥 Champion Beater' : (winnerComp.is_equal ? '⭐ Equal to Winner' : '⚡ Keep Trying')}
            </span>
          </div>

          <div class="grid grid-cols-2 gap-3 mb-3">
            <div class="bg-black/30 rounded-xl p-3 border border-white/5">
              <p class="text-[10px] uppercase font-bold text-white/50">Winner Record</p>
              <p class="text-sm font-bold text-mint mt-1">${winnerComp.winner_correct_ans} Correct · <span class="text-amber-300">${winnerComp.winner_total_time}s</span></p>
            </div>
            <div class="bg-black/30 rounded-xl p-3 border ${winnerComp.is_equal_or_better ? 'border-amber-400/30' : 'border-white/5'}">
              <p class="text-[10px] uppercase font-bold text-amber-300/80">Your Record</p>
              <p class="text-sm font-bold ${winnerComp.user_correct_ans >= winnerComp.winner_correct_ans ? 'text-mint' : 'text-white'} mt-1">${winnerComp.user_correct_ans} Correct · <span class="text-amber-300">${winnerComp.user_total_time}s</span></p>
            </div>
          </div>

          <p class="text-xs font-semibold ${winnerComp.is_equal_or_better ? 'text-amber-200' : 'text-on-surface-variant'}">
            ${escapeHtml(winnerComp.message)}
          </p>
        </div>
      ` : ''}

      <div class="space-y-4 mb-8">
        ${log.map((item, idx) => {
          const isCorrect = item.isCorrect === true;
          const cardBg = isCorrect ? 'bg-mint/10 border-mint/30' : 'bg-error/10 border-error/30';
          const badgeColor = isCorrect ? 'bg-mint text-black' : 'bg-error text-white';
          const statusText = isCorrect ? 'Correct' : 'Wrong';

          return `
            <div class="kbc-card rounded-2xl p-5 border ${cardBg}">
              <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold text-on-surface-variant">Q${idx + 1} of ${total}</span>
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold ${badgeColor}">${statusText}</span>
              </div>
              <p class="font-bold text-on-surface text-sm mb-3">${escapeHtml(item.text)}</p>
              <div class="text-xs space-y-1">
                <p class="text-on-surface-variant">Your Choice: <span class="font-semibold text-on-surface">${escapeHtml(item.chosenText || 'Not Answered')}</span></p>
                <p class="text-on-surface-variant">Correct Answer: <span class="font-semibold text-mint">${escapeHtml(item.correctText || '-')}</span></p>
              </div>
            </div>
          `;
        }).join('')}
      </div>

      <div class="flex flex-col gap-3">
        <button onclick="navigate('/practice-quiz/play/${quizId}', true)" class="w-full py-3.5 rounded-xl font-bold bg-primary text-on-primary hover:brightness-110 transition-all text-center">
          Play Again
        </button>
        <button onclick="navigate('/home', true)" class="w-full py-3.5 rounded-xl font-bold bg-surface-container-high text-on-surface hover:bg-surface-container-highest transition-all text-center">
          Back to Home
        </button>
      </div>
    `;
  }
});
</script>
