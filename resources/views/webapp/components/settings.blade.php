<template id="tpl-settings">
  <div id="settings-modal" class="fixed inset-0 z-[95] bg-black/70 backdrop-blur-sm flex items-end sm:items-center justify-center">
    <div class="w-full max-w-md glass-card bg-surface-container-highest rounded-t-2xl sm:rounded-2xl border border-glass p-6 fade-in">
      <div class="w-12 h-1.5 bg-white/20 rounded-full mx-auto mb-5 sm:hidden"></div>
      <h3 class="font-display text-headline-md text-white mb-1">API Connection</h3>
      <p class="text-on-surface-variant text-body-md mb-4">Point the app at your FSRQuiz API. Works the same for a local server (e.g. <span class="font-label-caps text-[11px]">http://localhost:8000/api</span>) or a live domain (e.g. <span class="font-label-caps text-[11px]">https://your-domain.com/api</span>).</p>
      <label class="font-label-caps text-[11px] text-on-surface-variant">API BASE URL</label>
      <input id="settings-api-base" type="text" placeholder="https://your-domain.com/api" class="mt-1 w-full bg-surface-container border border-glass rounded-xl px-4 py-3 text-on-surface placeholder:text-on-surface-variant/60">
      <div id="settings-test-result" class="mt-2 text-body-md"></div>
      <div class="flex gap-3 mt-5">
        <button id="settings-test-btn" class="flex-1 py-3 rounded-xl border border-glass text-on-surface font-action active:scale-95 transition-transform">Test Connection</button>
        <button id="settings-save-btn" class="flex-1 py-3 rounded-xl bg-primary text-on-primary font-action active:scale-95 transition-transform">Save</button>
      </div>
      <button id="settings-close-btn" class="w-full mt-3 py-2 text-on-surface-variant font-action text-action hidden">Close</button>
    </div>
  </div>
</template>

<script>
function openSettings(forced = false) {
  const existing = qs('#settings-modal'); if (existing) existing.remove();
  const tpl = qs('#tpl-settings');
  const modal = el(tpl.innerHTML);

  const input = modal.querySelector('#settings-api-base');
  if (input) input.value = Store.getApiBase();

  const closeBtn = modal.querySelector('#settings-close-btn');
  if (!forced && closeBtn) closeBtn.classList.remove('hidden');

  document.body.appendChild(modal);

  modal.querySelector('#settings-test-btn').onclick = async () => {
    const val = input.value.trim().replace(/\/+$/, '');
    const resultBox = modal.querySelector('#settings-test-result');
    resultBox.textContent = 'Testing...'; resultBox.className = 'mt-2 text-body-md text-on-surface-variant';
    try {
      const res = await fetch(val + '/categories', { headers: { 'Accept': 'application/json' } });
      if (res.status < 500) { resultBox.textContent = `Reachable (HTTP ${res.status}).`; resultBox.className = 'mt-2 text-body-md text-mint'; }
      else { resultBox.textContent = `Server responded with error ${res.status}.`; resultBox.className = 'mt-2 text-body-md text-secondary'; }
    } catch (e) {
      resultBox.textContent = 'Could not reach this URL. Check the address, CORS settings on the server, and your network.';
      resultBox.className = 'mt-2 text-body-md text-error';
    }
  };

  modal.querySelector('#settings-save-btn').onclick = () => {
    const val = input.value.trim();
    if (!val) { toast('Enter an API base URL', 'error'); return; }
    Store.setApiBase(val);
    toast('API base URL saved', 'success');
    modal.remove();
    router();
  };

  if (closeBtn) closeBtn.onclick = () => modal.remove();
}
</script>
