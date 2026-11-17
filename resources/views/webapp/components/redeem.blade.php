<template id="tpl-redeem">
  <div id="redeem-wrapper">
    <div id="redeem-header-placeholder"></div>
    <main class="pt-20 pb-24 px-container-margin min-h-screen">
      <div class="flex justify-between items-center mb-stack-md">
        <div>
          <h2 class="font-headline-lg text-headline-lg">Rewards Store</h2>
          <p class="text-on-surface-variant font-body-md">Redeem your hard-earned coins</p>
        </div>
        <button onclick="navigate('/redeem/history')" class="font-action text-tertiary text-action">My Redemptions</button>
      </div>
      <div class="grid grid-cols-2 gap-gutter" id="redeem-grid">
        <div class="glass-card rounded-xl h-44 skeleton"></div>
        <div class="glass-card rounded-xl h-44 skeleton"></div>
      </div>
    </main>
    <div id="redeem-nav-placeholder"></div>
  </div>
</template>

<script>
route('/redeem', async (app) => {
  app.innerHTML = qs('#tpl-redeem').innerHTML;
  qs('#redeem-header-placeholder').outerHTML = topHeader({ title: 'Redeem', showBack: true, backTo: '/wallet' });
  qs('#redeem-nav-placeholder').outerHTML = bottomNav('redeem');

  try {
    const res = await api('/user/rewards', { method: 'GET' });
    const list = asList(res);
    const grid = qs('#redeem-grid');
    if (!list.length) { grid.innerHTML = `<div class="col-span-2 glass-card rounded-xl p-6 text-center text-on-surface-variant">No rewards currently available. Check back soon!</div>`; return; }
    grid.innerHTML = list.map(r => {
      const id = pick(r, ['id','reward_id']);
      const title = escapeHtml(pick(r, ['title','name'], 'Voucher'));
      const cost = pick(r, ['cost','coins_required','coins'], '0');
      const img = pick(r, ['image','icon'], '');
      return `
      <div class="glass-card rounded-xl p-4 flex flex-col justify-between items-center text-center">
        <div class="w-16 h-16 rounded-xl bg-surface-container flex items-center justify-center mb-2 overflow-hidden">
          ${img ? `<img src="${escapeHtml(img)}" class="w-full h-full object-cover">` : `<span class="material-symbols-outlined text-3xl text-tertiary">card_giftcard</span>`}
        </div>
        <h4 class="font-headline-md text-on-surface text-[14px] line-clamp-1 mb-1">${title}</h4>
        <div class="flex items-center gap-1 text-tertiary mb-3"><span class="material-symbols-outlined text-[14px]" style="font-variation-settings:'FILL' 1;">monetization_on</span><span class="font-bold text-[14px]">${escapeHtml(cost)}</span></div>
        <button data-id="${escapeHtml(id)}" class="redeem-btn w-full bg-tertiary text-surface-container-lowest py-2 rounded-lg font-action text-[12px] active:scale-95 transition-transform">Redeem</button>
      </div>`;}).join('');

    qsa('.redeem-btn', grid).forEach(b => {
      b.onclick = async () => {
        const id = b.dataset.id;
        if (!confirm('Are you sure you want to redeem this reward?')) return;
        await withLoader(async () => {
          try {
            await api('/user/redeem', { method: 'POST', body: { reward_id: id } });
            toast('Reward redeemed successfully!', 'success');
            navigate('/redeem/history');
          } catch (e) { toast(e.message, 'error'); }
        });
      };
    });
  } catch (e) {
    qs('#redeem-grid').innerHTML = `<div class="col-span-2 glass-card rounded-xl p-6 text-center text-error">${escapeHtml(e.message)}</div>`;
  }
});
</script>
