<template id="tpl-quiz-result">
  <div id="quiz-result-wrapper">
    <div id="quiz-result-header-placeholder"></div>
    <main class="pt-20 pb-24 px-container-margin min-h-screen" id="result-main">
      <div class="flex flex-col items-center py-12">
        <div class="w-10 h-10 border-4 border-primary/30 border-t-primary rounded-full spin"></div>
      </div>
    </main>
  </div>
</template>

<script>
route('/quiz/result/:id', async (app, params) => {
  const quizId = params.id;
  app.innerHTML = qs('#tpl-quiz-result').innerHTML;
  qs('#quiz-result-header-placeholder').outerHTML = topHeader({ title: 'Result', showBack: true, backTo: '/home' });

  try {
    const res = await api(`/quiz/${quizId}/my-result`, { method: 'GET' });
    const d = unwrap(res) || {};
    const score = pick(d, ['score','total_score'], '0');
    const rank = pick(d, ['rank','my_rank'], '-');
    const coins = pick(d, ['coins_earned','reward','coins'], '0');
    const correct = pick(d, ['correct_answers','correct'], '-');
    const total = pick(d, ['total_questions','total'], '-');
    Sound.ensureCtx();
    Sound.coin();
    qs('#result-main').innerHTML = `
    <div class="flex flex-col items-center text-center py-6 kbc-stage p-4">
      <div class="kbc-spotlight"></div><div class="kbc-stage-rings"><span></span><span></span><span></span><span></span></div>
      <div class="relative z-[1] w-full flex justify-end mb-2">${kbcMuteBtnHtml()}</div>
      <div class="relative z-[1] w-24 h-24 rounded-full vibrant-gradient flex items-center justify-center mb-4 shadow-[0_0_40px_rgba(255,215,106,0.45)]">
        <span class="material-symbols-outlined text-white text-5xl">emoji_events</span>
      </div>
      <h2 class="relative z-[1] font-display text-headline-lg kbc-title-glow mb-1">Quiz Completed!</h2>
      <p class="relative z-[1] text-on-surface-variant mb-8">Here's how you did</p>
      <div class="relative z-[1] grid grid-cols-2 gap-3 w-full mb-6">
        <div class="kbc-card rounded-xl p-4 text-center"><p class="font-label-caps text-[10px] text-on-surface-variant mb-1">SCORE</p><p class="font-display text-2xl text-primary">${escapeHtml(score)}</p></div>
        <div class="kbc-card rounded-xl p-4 text-center"><p class="font-label-caps text-[10px] text-on-surface-variant mb-1">RANK</p><p class="font-display text-2xl text-on-surface">#${escapeHtml(rank)}</p></div>
        <div class="kbc-card rounded-xl p-4 text-center"><p class="font-label-caps text-[10px] text-on-surface-variant mb-1">COINS EARNED</p><p class="font-display text-2xl kbc-title-glow">+${escapeHtml(coins)}</p></div>
        <div class="kbc-card rounded-xl p-4 text-center"><p class="font-label-caps text-[10px] text-on-surface-variant mb-1">CORRECT</p><p class="font-display text-2xl text-mint">${escapeHtml(correct)}/${escapeHtml(total)}</p></div>
      </div>
      <button onclick="navigate('/quiz/winners/${quizId}')" class="relative z-[1] w-full bg-primary text-on-primary py-4 rounded-xl font-action text-lg shadow-[0_0_20px_rgba(210,187,255,0.3)] active:scale-95 transition-transform mb-3">View Leaderboard</button>
      <button onclick="navigate('/home', true)" class="relative z-[1] w-full py-3 text-on-surface-variant font-action text-action">Back to Home</button>
    </div>`;
    wireMuteBtn();
  } catch (e) {
    qs('#result-main').innerHTML = `<p class="text-error text-center py-10">${escapeHtml(e.message)}</p><button onclick="navigate('/home',true)" class="w-full text-primary font-action text-center mt-4">Back to Home</button>`;
  }
});
</script>
