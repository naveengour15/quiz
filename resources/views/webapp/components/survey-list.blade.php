<template id="tpl-survey-list">
  <div id="survey-list-wrapper">
    <div id="survey-list-header-placeholder"></div>
    <main class="pt-20 pb-24 px-container-margin min-h-screen">
      <div class="mb-stack-lg">
        <h2 class="font-headline-lg text-headline-lg">Active Surveys</h2>
        <p class="text-on-surface-variant mt-1 font-body-md">Share your opinion, answer simple Yes/No questions and earn rewards!</p>
      </div>
      <div class="grid grid-cols-2 gap-gutter" id="survey-list-grid">
        <div class="glass-card rounded-xl h-40 skeleton"></div>
        <div class="glass-card rounded-xl h-40 skeleton"></div>
      </div>
    </main>
    <div id="survey-list-nav-placeholder"></div>
  </div>
</template>

<script>
route('/survey/list', async (app) => {
  app.innerHTML = qs('#tpl-survey-list').innerHTML;
  qs('#survey-list-header-placeholder').outerHTML = topHeader({ title: 'Surveys', showBack: true, backTo: '/home' });
  qs('#survey-list-nav-placeholder').outerHTML = bottomNav('home');

  try {
    const res = await api('/survey/list', { method: 'GET' });
    const list = pick(res, ['survey', 'surveys', 'data'], []) || asList(res);
    const grid = qs('#survey-list-grid');
    if (!list.length) {
      grid.innerHTML = `<div class="col-span-2 glass-card rounded-xl p-6 text-center text-on-surface-variant">No active surveys available. Check back soon!</div>`;
      return;
    }
    grid.innerHTML = list.map(surveyCardHtml).join('');
  } catch (e) {
    qs('#survey-list-grid').innerHTML = `<div class="col-span-2 glass-card rounded-xl p-6 text-center text-error">${escapeHtml(e.message)}</div>`;
  }
});
</script>
