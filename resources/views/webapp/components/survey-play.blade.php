<template id="tpl-survey-play">
  <div id="survey-play-wrapper">
    <div id="survey-play-header-placeholder"></div>
    <main class="pt-20 pb-24 px-container-margin min-h-screen flex items-center justify-center">
      <div id="survey-play-root" class="w-full kbc-card rounded-2xl p-4 fade-in">
        <div class="flex flex-col items-center py-12 text-on-surface-variant">
          <div class="w-10 h-10 border-4 border-tertiary/30 border-t-tertiary rounded-full spin mb-4"></div>
          <p class="font-body-md">Loading survey questions...</p>
        </div>
      </div>
    </main>
  </div>
</template>

<script>
route('/survey/play/:id', async (app, params) => {
  const surveyId = params.id;
  app.innerHTML = qs('#tpl-survey-play').innerHTML;
  qs('#survey-play-header-placeholder').outerHTML = topHeader({ title: 'Survey', showBack: true, backTo: '/survey/list' });

  let surveyData = null;
  let questions = [];
  try {
    const res = await api(`/survey/${surveyId}`, { method: 'GET' });
    surveyData = unwrap(res) || {};
    const rawQ = pick(surveyData, ['questions', 'question_list', 'data'], []) || asList(res);
    questions = Array.isArray(rawQ) ? rawQ : [];
  } catch (e) {
    qs('#survey-play-root').innerHTML = `<p class="text-error text-center py-8">${escapeHtml(e.message)}</p><button onclick="navigate('/survey/list')" class="w-full text-primary font-action text-center mt-4">Back to Surveys</button>`;
    return;
  }

  if (!questions.length) {
    qs('#survey-play-root').innerHTML = `<p class="text-on-surface-variant text-center py-8">No questions found for this survey.</p><button onclick="navigate('/survey/list')" class="w-full text-primary font-action text-center mt-4">Back to Surveys</button>`;
    return;
  }

  let currentIndex = 0;
  const userAnswers = [];

  function renderCurrentQuestion() {
    const q = questions[currentIndex];
    const qId = pick(q, ['id', 'question_id']);
    const qTitle = escapeHtml(pick(q, ['title', 'question', 'question_text'], `Question ${currentIndex + 1}`));
    const progressPct = Math.round(((currentIndex + 1) / questions.length) * 100);

    qs('#survey-play-root').innerHTML = `
      <div class="flex items-center justify-between mb-4">
        <span class="font-label-caps bg-tertiary/20 text-tertiary rounded-full px-3 py-1 text-[11px]">QUESTION ${currentIndex + 1} / ${questions.length}</span>
        <span class="font-label-caps text-on-surface-variant text-[11px]">${progressPct}%</span>
      </div>
      <div class="w-full h-2 bg-surface-container rounded-full overflow-hidden mb-6">
        <div class="h-full bg-tertiary transition-all duration-300" style="width:${progressPct}%"></div>
      </div>
      <div class="glass-card rounded-2xl p-6 mb-6">
        <h3 class="font-headline-lg text-headline-lg text-on-surface">${qTitle}</h3>
      </div>
      <div class="grid grid-cols-2 gap-4">
        <button id="survey-opt-yes" class="py-4 px-6 rounded-xl bg-mint/20 border border-mint/40 text-mint font-display text-xl active:scale-95 transition-transform flex items-center justify-center gap-2">
          <span class="material-symbols-outlined text-2xl">thumb_up</span> YES
        </button>
        <button id="survey-opt-no" class="py-4 px-6 rounded-xl bg-secondary/20 border border-secondary/40 text-secondary font-display text-xl active:scale-95 transition-transform flex items-center justify-center gap-2">
          <span class="material-symbols-outlined text-2xl">thumb_down</span> NO
        </button>
      </div>`;

    qs('#survey-opt-yes').onclick = () => handleChoice(qId, 'yes');
    qs('#survey-opt-no').onclick = () => handleChoice(qId, 'no');
  }

  async function handleChoice(qId, answerVal) {
    userAnswers.push({ question_id: qId, answer: answerVal });
    currentIndex++;
    if (currentIndex < questions.length) {
      renderCurrentQuestion();
    } else {
      await submitAllAnswers();
    }
  }

  async function submitAllAnswers() {
    qs('#survey-play-root').innerHTML = `
      <div class="flex flex-col items-center py-12 text-on-surface-variant">
        <div class="w-10 h-10 border-4 border-tertiary/30 border-t-tertiary rounded-full spin mb-4"></div>
        <p class="font-body-md">Submitting survey responses...</p>
      </div>`;
    try {
      const res = await api(`/survey/${surveyId}/submit`, {
        method: 'POST',
        body: { answers: userAnswers }
      });
      const data = unwrap(res) || {};
      const reward = pick(data, ['reward_points', 'coins', 'points', 'reward'], pick(surveyData, ['reward_points', 'reward'], '10'));
      Sound.ensureCtx();
      Sound.coin();
      qs('#survey-play-root').innerHTML = `
        <div class="flex flex-col items-center text-center py-6">
          <div class="w-20 h-20 rounded-full bg-tertiary/20 flex items-center justify-center mb-4 text-tertiary">
            <span class="material-symbols-outlined text-5xl">task_alt</span>
          </div>
          <h2 class="font-display text-headline-lg text-on-surface mb-1">Survey Completed!</h2>
          <p class="text-on-surface-variant mb-6">Thank you for sharing your feedback.</p>
          <div class="glass-card rounded-xl p-4 w-full text-center mb-6">
            <p class="font-label-caps text-[10px] text-on-surface-variant mb-1">REWARD EARNED</p>
            <p class="font-display text-3xl text-tertiary">+${escapeHtml(reward)} Coins</p>
          </div>
          <button onclick="navigate('/survey/list', true)" class="w-full bg-tertiary text-surface-container-lowest py-4 rounded-xl font-action text-lg active:scale-95 transition-transform mb-3">Back to Surveys</button>
          <button onclick="navigate('/home', true)" class="w-full py-3 text-on-surface-variant font-action text-action">Go to Home</button>
        </div>`;
    } catch (e) {
      qs('#survey-play-root').innerHTML = `
        <span class="material-symbols-outlined text-error text-5xl mb-3">error</span>
        <p class="text-error text-center mb-4">${escapeHtml(e.message)}</p>
        <button onclick="navigate('/survey/list', true)" class="w-full text-primary font-action text-center">Back to Surveys</button>`;
    }
  }

  renderCurrentQuestion();
});
</script>
