<template id="tpl-redeem-history">
  <div id="redeem-history-wrapper">
    <div id="redeem-history-header-placeholder"></div>
    <main class="pt-20 pb-24 px-container-margin min-h-screen" id="redeem-history-main">
      <div class="space-y-gutter">
        <div class="glass-card rounded-xl h-20 skeleton"></div>
        <div class="glass-card rounded-xl h-20 skeleton"></div>
      </div>
    </main>
    <div id="redeem-history-nav-placeholder"></div>
  </div>
</template>

<script>
route('/redeem/history', async (app) => {
  app.innerHTML = qs('#tpl-redeem-history').innerHTML;
  qs('#redeem-history-header-placeholder').outerHTML = topHeader({ title: 'My Redemptions', showBack: true, backTo: '/redeem' });
  qs('#redeem-history-nav-placeholder').outerHTML = bottomNav('redeem');

  try {
    const res = await api('/user/redemptions', { method: 'GET' });
    const list = asList(res);
    qs('#redeem-history-main').innerHTML = list.length ? `
      <div class="space-y-gutter">${list.map(r => {
        const title = escapeHtml(pick(r, ['reward_title','title','name'], 'Reward'));
        const code = pick(r, ['voucher_code','code','pin'], '');
        const status = pick(r, ['status'], 'Success');
        const date = pick(r, ['created_at','date'], '');
        return `
      <div class="glass-card rounded-xl p-4 flex justify-between items-center">
        <div>
          <h4 class="font-headline-md text-on-surface text-[14px]">${title}</h4>
          ${code ? `<p class="font-mono text-tertiary text-[13px] mt-0.5 select-all">Code: ${escapeHtml(code)}</p>` : ''}
          ${date ? `<p class="font-body-md text-on-surface-variant text-[11px] mt-0.5">${escapeHtml(date)}</p>` : ''}
        </div>
        <span class="text-[11px] font-bold px-2 py-1 rounded-full ${status==='Success'||status==='completed'?'bg-mint/20 text-mint':'bg-secondary/20 text-secondary'}">${escapeHtml(status)}</span>
      </div>`;}).join('')}</div>` : `<p class="text-on-surface-variant text-center py-10">No redemptions yet.</p>`;
  } catch (e) {
    qs('#redeem-history-main').innerHTML = `<p class="text-error text-center py-10">${escapeHtml(e.message)}</p>`;
  }
});
</script>
