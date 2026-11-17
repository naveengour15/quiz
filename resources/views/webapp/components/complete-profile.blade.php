<template id="tpl-complete-profile">
  <div class="min-h-screen px-container-margin py-8 sm:py-10 fade-in">
    <div class="mb-6">
      <h2 class="font-headline-lg text-headline-lg">Complete your profile</h2>
      <p class="text-on-surface-variant mt-1">Just a few details to get you started.</p>
    </div>
    <form id="cp-form" class="glass-card rounded-2xl p-4 sm:p-6 flex flex-col gap-4">
      <div>
        <label class="font-label-caps text-[11px] text-on-surface-variant">FULL NAME (OPTIONAL)</label>
        <input id="f-name" type="text" placeholder="Your name" class="mt-1 w-full bg-surface-container border border-glass rounded-xl px-4 py-3 text-on-surface placeholder:text-on-surface-variant/60">
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="font-label-caps text-[11px] text-on-surface-variant">STATE (OPTIONAL)</label>
          <select id="f-state" class="mt-1 w-full bg-surface-container border border-glass rounded-xl px-4 py-3 text-on-surface">
            <option value="">Select state</option>
          </select>
        </div>
        <div>
          <label class="font-label-caps text-[11px] text-on-surface-variant">CITY (OPTIONAL)</label>
          <input id="f-city" type="text" placeholder="e.g. Indore" class="mt-1 w-full bg-surface-container border border-glass rounded-xl px-4 py-3 text-on-surface placeholder:text-on-surface-variant/60">
        </div>
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="font-label-caps text-[11px] text-on-surface-variant">DATE OF BIRTH (OPTIONAL)</label>
          <input id="f-dob" type="date" class="mt-1 w-full bg-surface-container border border-glass rounded-xl px-4 py-3 text-on-surface">
        </div>
        <div>
          <label class="font-label-caps text-[11px] text-on-surface-variant">GENDER (OPTIONAL)</label>
          <select id="f-gender" class="mt-1 w-full bg-surface-container border border-glass rounded-xl px-4 py-3 text-on-surface">
            <option value="">Select</option>
            <option value="male">Male</option>
            <option value="female">Female</option>
            <option value="other">Other</option>
          </select>
        </div>
      </div>
      <div>
        <label class="font-label-caps text-[11px] text-on-surface-variant">REFERRAL CODE (OPTIONAL)</label>
        <input id="f-referral" type="text" placeholder="e.g. NAV1A8K2QX" class="mt-1 w-full bg-surface-container border border-glass rounded-xl px-4 py-3 text-on-surface placeholder:text-on-surface-variant/60">
      </div>
      <button type="submit" class="w-full mt-2 bg-primary text-on-primary py-4 rounded-xl font-action text-lg shadow-[0_0_20px_rgba(210,187,255,0.3)] active:scale-95 transition-transform">Save & Continue</button>
    </form>
  </div>
</template>

<script>
route('/complete-profile', async (app) => {
  const mobile = Store.getPendingMobile();
  if (!mobile) { navigate('/login', true); return; }

  app.innerHTML = qs('#tpl-complete-profile').innerHTML;

  const stateSelect = qs('#f-state');
  if (stateSelect) {
    stateSelect.innerHTML = `<option value="">Select state</option>` +
      INDIAN_STATES.map(s => `<option value="${s.id}">${escapeHtml(s.name)}</option>`).join('');
  }

  qs('#cp-form').onsubmit = async (e) => {
    e.preventDefault();
    const name = qs('#f-name').value.trim();
    const stateId = qs('#f-state').value;
    const city = qs('#f-city').value.trim();
    const dob = qs('#f-dob').value;
    const gender = qs('#f-gender').value;
    const referral = qs('#f-referral').value.trim();

    const body = { mobile: Number(mobile) };
    if (name) body.name = name;
    if (stateId) body.state_id = Number(stateId);
    if (city) body.city_name = city;
    if (dob) body.dob = dob;
    if (gender) body.gender = gender;
    if (referral) body.referral_code = referral;

    await withLoader(async () => {
      try {
        let res;
        try {
          res = await api('/auth/signup', { method: 'POST', body });
        } catch (err) {
          if (err.status === 404) {
            res = await api('/user/complete-profile', { method: 'POST', body });
          } else {
            throw err;
          }
        }
        const data = unwrap(res);
        const accessToken = pick(res, ['access_token']) || pick(data, ['access_token']) || pick(res, ['token']) || pick(data, ['token']);
        if (accessToken) Store.setToken(accessToken);
        const userObj = data && typeof data === 'object' ? (data.user || data) : null;
        if (userObj) Store.setUser(userObj);
        Store.setIsNewUser(false);
        toast('Profile completed', 'success');
        navigate('/home', true);
      } catch (e) { toast(e.message, 'error'); }
    });
  };
});
</script>
