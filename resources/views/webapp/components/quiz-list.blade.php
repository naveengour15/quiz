<template id="tpl-quiz-list">
  <div id="quiz-list-wrapper">
    <div id="quiz-list-header-placeholder"></div>
    <main class="pt-20 pb-24 px-container-margin min-h-screen">
      <div class="flex gap-2 overflow-x-auto scrollbar-hide mb-stack-lg pb-1" id="quiz-cat-filters">
        <button data-cat="" class="cat-chip whitespace-nowrap px-4 py-2 rounded-full font-action text-action bg-primary text-on-primary">All</button>
      </div>
      <div class="grid grid-cols-2 gap-gutter" id="quiz-list-grid">
        <div class="glass-card rounded-xl h-40 skeleton"></div>
        <div class="glass-card rounded-xl h-40 skeleton"></div>
        <div class="glass-card rounded-xl h-40 skeleton"></div>
        <div class="glass-card rounded-xl h-40 skeleton"></div>
      </div>
    </main>
    <div id="quiz-list-nav-placeholder"></div>
  </div>
</template>

<script>
route('/quiz/list', async (app) => {
  app.innerHTML = qs('#tpl-quiz-list').innerHTML;
  qs('#quiz-list-header-placeholder').outerHTML = topHeader({ title: 'Quizzes', showBack: true });
  qs('#quiz-list-nav-placeholder').outerHTML = bottomNav('rank');

  let allQuizzes = [];
  async function loadQuizzes(categoryId) {
    const grid = qs('#quiz-list-grid');
    grid.innerHTML = skeletonCard('h-40') + skeletonCard('h-40') + skeletonCard('h-40') + skeletonCard('h-40');
    try {
      const res = await api('/quiz/list', { method: 'GET', query: { category_id: categoryId || undefined } });
      const list = asList(res);
      allQuizzes = list;
      grid.innerHTML = list.length ? list.map(quizCardHtml).join('') : `<div class="col-span-2 glass-card rounded-xl p-6 text-center text-on-surface-variant">No quizzes found in this category.</div>`;
      attachQuizCardHandlers(grid, list);
    } catch (e) {
      grid.innerHTML = `<div class="col-span-2 glass-card rounded-xl p-6 text-center text-error">${escapeHtml(e.message)}</div>`;
    }
  }

  api('/categories', { method: 'GET' }).then(res => {
    const cats = asList(res);
    const wrap = qs('#quiz-cat-filters');
    cats.forEach(c => {
      const btn = el(`<button data-cat="${escapeHtml(pick(c,['id','category_id']))}" class="cat-chip whitespace-nowrap px-4 py-2 rounded-full font-action text-action bg-surface-container-high text-on-surface-variant border border-glass">${escapeHtml(pick(c,['name','title'],'Category'))}</button>`);
      wrap.appendChild(btn);
    });
    qsa('.cat-chip', wrap).forEach(btn => {
      btn.addEventListener('click', () => {
        qsa('.cat-chip', wrap).forEach(b => b.className = 'cat-chip whitespace-nowrap px-4 py-2 rounded-full font-action text-action bg-surface-container-high text-on-surface-variant border border-glass');
        btn.className = 'cat-chip whitespace-nowrap px-4 py-2 rounded-full font-action text-action bg-primary text-on-primary';
        loadQuizzes(btn.dataset.cat);
      });
    });
  }).catch(() => {});

  loadQuizzes('');
});
</script>
