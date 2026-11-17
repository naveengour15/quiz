<template id="tpl-history">
  <div id="history-wrapper">
    <div id="history-header-placeholder"></div>
    <main class="pt-20 pb-24 px-container-margin min-h-screen" id="history-main">
      <div class="space-y-gutter">
        <div class="glass-card rounded-xl h-20 skeleton"></div>
        <div class="glass-card rounded-xl h-20 skeleton"></div>
        <div class="glass-card rounded-xl h-20 skeleton"></div>
      </div>
    </main>
    <div id="history-nav-placeholder"></div>
  </div>
</template>

<script>
route('/history', async (app) => {
  app.innerHTML = qs('#tpl-history').innerHTML;
  qs('#history-header-placeholder').outerHTML = topHeader({ title: 'My History', showBack: true });
  qs('#history-nav-placeholder').outerHTML = bottomNav('profile');

  try {
    const res = await api('/user/quiz-history', { method: 'GET' });
    const list = asList(res);
    qs('#history-main').innerHTML = list.length ? `
      <div class="space-y-gutter">${list.map(h => {
        const id = pick(h, ['quiz_id','id']);
        const title = escapeHtml(pick(h, ['quiz_title','title','name'], 'Quiz'));
        const score = pick(h, ['score','total_score'], '0');
        const rank = pick(h, ['rank'], '-');
        const coins = pick(h, ['coins_earned','reward','coins'], '0');
        const date = pick(h, ['created_at','date'], '');
        return `
      <div onclick="navigate('/quiz/result/${id}')" class="glass-card rounded-xl p-4 flex items-center justify-between cursor-pointer hover:border-primary/50 transition-colors">
        <div>
          <h4 class="font-headline-md text-on-surface mb-1">${title}</h4>
          <p class="font-body-md text-on-surface-variant text-[12px]">Rank #${escapeHtml(rank)} • Score ${escapeHtml(score)}${date ? ' • ' + escapeHtml(date) : ''}</p>
        </div>
        <div class="text-right">
          <p class="font-headline-md text-tertiary flex items-center justify-end gap-0.5"><span class="material-symbols-outlined text-[16px]" style="font-variation-settings:'FILL' 1;">monetization_on</span>+${escapeHtml(coins)}</p>
          <span class="font-action text-[11px] text-primary">View →</span>
        </div>
      </div>`;}).join('')}</div>` : `<p class="text-on-surface-variant text-center py-10">No quiz history yet. Play a quiz!</p>`;
  } catch (e) {
    qs('#history-main').innerHTML = `<p class="text-error text-center py-10">${escapeHtml(e.message)}</p>`;
  }
});
</script>
