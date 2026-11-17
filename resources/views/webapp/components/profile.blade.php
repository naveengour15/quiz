<template id="tpl-profile">
  <div id="profile-wrapper">
    <div id="profile-header-placeholder"></div>
    <main class="pt-20 pb-24 px-container-margin min-h-screen">
      <div class="glass-card rounded-2xl p-6 mb-stack-lg flex flex-col items-center text-center relative overflow-hidden" id="prof-card">
        <div class="w-20 h-20 rounded-full border-2 border-primary overflow-hidden bg-primary-container/40 flex items-center justify-center mb-3">
          <span class="material-symbols-outlined text-primary text-4xl">person</span>
        </div>
        <h2 class="font-headline-lg text-headline-lg" id="prof-name">...</h2>
        <p class="text-on-surface-variant font-body-md text-[13px]" id="prof-mobile">+91 ...</p>
      </div>
      <form id="prof-form" class="glass-card rounded-2xl p-6 mb-stack-lg flex flex-col gap-4">
        <h4 class="font-headline-md text-headline-md mb-1">Edit Profile</h4>
        <div>
          <label class="font-label-caps text-[11px] text-on-surface-variant">NAME</label>
          <input id="prof-f-name" type="text" class="mt-1 w-full bg-surface-container border border-glass rounded-xl px-4 py-3 text-on-surface">
        </div>
        <div>
          <label class="font-label-caps text-[11px] text-on-surface-variant">CITY</label>
          <input id="prof-f-city" type="text" class="mt-1 w-full bg-surface-container border border-glass rounded-xl px-4 py-3 text-on-surface">
        </div>
        <button type="submit" class="w-full bg-primary text-on-primary py-3 rounded-xl font-action text-action shadow-[0_0_20px_rgba(210,187,255,0.3)] active:scale-95 transition-transform">Save Changes</button>
      </form>
      <div class="glass-card rounded-2xl p-2 mb-stack-lg divide-y divide-glass">
        <button onclick="navigate('/history')" class="w-full p-4 flex items-center justify-between text-on-surface active:bg-white/5 transition-colors">
          <div class="flex items-center gap-3"><span class="material-symbols-outlined text-primary">history</span><span class="font-headline-md text-[14px]">Quiz History</span></div>
          <span class="material-symbols-outlined text-on-surface-variant text-[18px]">chevron_right</span>
        </button>
        <button onclick="navigate('/wallet')" class="w-full p-4 flex items-center justify-between text-on-surface active:bg-white/5 transition-colors">
          <div class="flex items-center gap-3"><span class="material-symbols-outlined text-tertiary">account_balance_wallet</span><span class="font-headline-md text-[14px]">Wallet & Balance</span></div>
          <span class="material-symbols-outlined text-on-surface-variant text-[18px]">chevron_right</span>
        </button>
        <button onclick="navigate('/referral')" class="w-full p-4 flex items-center justify-between text-on-surface active:bg-white/5 transition-colors">
          <div class="flex items-center gap-3"><span class="material-symbols-outlined text-secondary">share</span><span class="font-headline-md text-[14px]">Refer & Earn</span></div>
          <span class="material-symbols-outlined text-on-surface-variant text-[18px]">chevron_right</span>
        </button>
        <button onclick="openSettings()" class="w-full p-4 flex items-center justify-between text-on-surface active:bg-white/5 transition-colors">
          <div class="flex items-center gap-3"><span class="material-symbols-outlined text-on-surface-variant">settings</span><span class="font-headline-md text-[14px]">API Connection Settings</span></div>
          <span class="material-symbols-outlined text-on-surface-variant text-[18px]">chevron_right</span>
        </button>
      </div>
      <button id="prof-logout-btn" class="w-full py-4 rounded-xl bg-error-container/30 border border-error/30 text-error font-action text-action active:scale-95 transition-transform">Logout</button>
      <button id="prof-delete-btn" class="w-full mt-3 py-2 text-on-surface-variant font-action text-[12px]">Delete Account</button>
    </main>
    <div id="profile-nav-placeholder"></div>
  </div>
</template>

<script>
route('/profile', async (app) => {
  app.innerHTML = qs('#tpl-profile').innerHTML;
  qs('#profile-header-placeholder').outerHTML = topHeader({ title: 'My Profile', showBack: true, backTo: '/home' });
  qs('#profile-nav-placeholder').outerHTML = bottomNav('profile');

  const cachedUser = Store.getUser();
  if (cachedUser) {
    qs('#prof-name').textContent = pick(cachedUser, ['name'], 'User');
    qs('#prof-mobile').textContent = '+91 ' + pick(cachedUser, ['mobile','phone'], Store.getPendingMobile());
    qs('#prof-f-name').value = pick(cachedUser, ['name'], '');
    qs('#prof-f-city').value = pick(cachedUser, ['city','city_name'], '');
  }

  api('/user/profile', { method: 'GET' }).then(res => {
    const u = unwrap(res) || {};
    Store.setUser(u);
    qs('#prof-name').textContent = pick(u, ['name'], 'User');
    qs('#prof-mobile').textContent = '+91 ' + pick(u, ['mobile','phone'], Store.getPendingMobile());
    qs('#prof-f-name').value = pick(u, ['name'], '');
    qs('#prof-f-city').value = pick(u, ['city','city_name'], '');
  }).catch(() => {});

  qs('#prof-form').onsubmit = async (e) => {
    e.preventDefault();
    const name = qs('#prof-f-name').value.trim();
    const city = qs('#prof-f-city').value.trim();
    await withLoader(async () => {
      try {
        const res = await api('/user/profile', { method: 'PUT', body: { name, city_name: city } });
        const updated = unwrap(res) || {};
        if (updated) Store.setUser({ ...cachedUser, ...updated, name, city });
        toast('Profile updated', 'success');
      } catch (err) { toast(err.message, 'error'); }
    });
  };

  qs('#prof-logout-btn').onclick = () => {
    if (!confirm('Are you sure you want to logout?')) return;
    Sound.hardStopAll();
    Store.clearToken(); Store.clearUser();
    toast('Logged out', 'info');
    navigate('/login', true);
  };

  qs('#prof-logout-btn').onclick = () => {
    if (!confirm('Are you sure you want to logout?')) return;
    Sound.hardStopAll();
    Store.clearToken(); Store.clearUser();
    toast('Logged out', 'info');
    navigate('/login', true);
  };

  qs('#prof-delete-btn').onclick = async () => {
    if (!confirm('WARNING: Are you sure you want to delete your account? This action cannot be undone.')) return;
    await withLoader(async () => {
      try {
        await api('/user/account', { method: 'DELETE' });
        Sound.hardStopAll();
        Store.clearToken(); Store.clearUser();
        toast('Account deleted', 'info');
        navigate('/login', true);
      } catch (e) { toast(e.message, 'error'); }
    });
  };
});
</script>
