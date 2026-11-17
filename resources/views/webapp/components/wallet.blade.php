<template id="tpl-wallet">
  <div id="wallet-wrapper">
    <div id="wallet-header-placeholder"></div>
    <main class="pt-20 pb-24 px-container-margin min-h-screen" id="wallet-main">
      <div class="glass-card rounded-2xl p-6 mb-stack-lg flex flex-col items-center justify-center text-center relative overflow-hidden">
        <div class="absolute -right-8 -top-8 w-32 h-32 bg-tertiary/10 rounded-full blur-2xl"></div>
        <p class="font-label-caps text-on-surface-variant text-[11px] mb-1">TOTAL BALANCE</p>
        <div class="flex items-center gap-2 mb-4">
          <span class="material-symbols-outlined text-tertiary text-4xl" style="font-variation-settings: 'FILL' 1;">monetization_on</span>
          <span class="font-display text-4xl text-on-surface font-bold" id="wallet-bal">0</span>
          <span class="text-on-surface-variant font-headline-md">Coins</span>
        </div>
        <div class="flex gap-3 w-full max-w-xs">
          <button onclick="navigate('/redeem')" class="flex-1 py-3 rounded-xl bg-tertiary text-surface-container-lowest font-action text-action shadow-[0_0_20px_rgba(255,215,106,0.3)] active:scale-95 transition-transform">Redeem</button>
          <button onclick="navigate('/referral')" class="flex-1 py-3 rounded-xl bg-surface-container-high border border-glass text-on-surface font-action text-action active:scale-95 transition-transform">Earn More</button>
        </div>
      </div>
      <div class="flex justify-between items-center mb-stack-sm">
        <h4 class="font-headline-md text-headline-md">Transaction History</h4>
      </div>
      <div class="space-y-gutter" id="wallet-tx-list">
        <div class="glass-card rounded-xl h-16 skeleton"></div>
        <div class="glass-card rounded-xl h-16 skeleton"></div>
      </div>
    </main>
    <div id="wallet-nav-placeholder"></div>
  </div>
</template>

<script>
route('/wallet', async (app) => {
  app.innerHTML = qs('#tpl-wallet').innerHTML;
  qs('#wallet-header-placeholder').outerHTML = topHeader({ title: 'My Wallet', showBack: true, backTo: '/home' });
  qs('#wallet-nav-placeholder').outerHTML = bottomNav('wallet');

  api('/user/wallet', { method: 'GET' }).then(res => {
    const d = unwrap(res);
    const bal = pick(d, ['balance','coins','wallet_balance'], '0');
    qs('#wallet-bal').textContent = bal;
  }).catch(() => {});

  api('/user/wallet-history', { method: 'GET' }).then(res => {
    const list = asList(res);
    qs('#wallet-tx-list').innerHTML = list.length ? list.map(tx => {
      const type = pick(tx, ['type','transaction_type'], 'credit');
      const isCredit = type === 'credit' || type === 'earn' || type === 'reward';
      const amount = pick(tx, ['amount','coins','points'], '0');
      const desc = escapeHtml(pick(tx, ['description','title','remark'], isCredit ? 'Reward Earned' : 'Redemption'));
      const date = pick(tx, ['created_at','date'], '');
      return `
      <div class="glass-card rounded-xl p-4 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-full ${isCredit ? 'bg-mint/20 text-mint' : 'bg-secondary/20 text-secondary'} flex items-center justify-center">
            <span class="material-symbols-outlined">${isCredit ? 'arrow_downward' : 'arrow_upward'}</span>
          </div>
          <div>
            <p class="font-headline-md text-on-surface text-[14px]">${desc}</p>
            ${date ? `<p class="font-body-md text-on-surface-variant text-[11px]">${escapeHtml(date)}</p>` : ''}
          </div>
        </div>
        <span class="font-headline-md ${isCredit ? 'text-mint' : 'text-secondary'}">${isCredit ? '+' : '-'}${escapeHtml(amount)}</span>
      </div>`;}).join('') : `<p class="text-on-surface-variant text-center py-8">No transaction history found.</p>`;
  }).catch(() => {
    qs('#wallet-tx-list').innerHTML = `<p class="text-on-surface-variant text-center py-8">Could not load transactions.</p>`;
  });
});
</script>
