<template id="tpl-otp">
  <div class="min-h-screen flex flex-col justify-center px-container-margin py-10 fade-in">
    <div class="flex flex-col items-center mb-8">
      <div class="w-16 h-16 rounded-2xl bg-primary/20 flex items-center justify-center mb-4">
        <span class="material-symbols-outlined text-primary text-3xl">sms</span>
      </div>
      <h2 class="font-headline-lg text-headline-lg">Verify OTP</h2>
      <p class="text-on-surface-variant mt-1 text-center">We've sent a 4-digit code to <span class="text-on-surface font-bold" id="otp-mobile-label">+91 </span></p>
    </div>
    <div class="glass-card rounded-2xl p-6">
      <div class="flex justify-center gap-3" id="otp-boxes">
        <input maxlength="1" inputmode="numeric" data-idx="0" class="otp-box bg-surface-container border border-glass rounded-xl font-headline-md text-2xl">
        <input maxlength="1" inputmode="numeric" data-idx="1" class="otp-box bg-surface-container border border-glass rounded-xl font-headline-md text-2xl">
        <input maxlength="1" inputmode="numeric" data-idx="2" class="otp-box bg-surface-container border border-glass rounded-xl font-headline-md text-2xl">
        <input maxlength="1" inputmode="numeric" data-idx="3" class="otp-box bg-surface-container border border-glass rounded-xl font-headline-md text-2xl">
      </div>
      <button id="verify-otp-btn" class="w-full mt-6 bg-primary text-on-primary py-4 rounded-xl font-action text-lg shadow-[0_0_20px_rgba(210,187,255,0.3)] active:scale-95 transition-transform">Verify & Continue</button>
      <div class="flex justify-between items-center mt-4">
        <button onclick="navigate('/login')" class="text-on-surface-variant font-action text-action">Change number</button>
        <button id="resend-otp-btn" class="text-primary font-action text-action">Resend OTP</button>
      </div>
    </div>
  </div>
</template>

<script>
route('/otp', async (app) => {
  const mobile = Store.getPendingMobile();
  if (!mobile) { navigate('/login', true); return; }

  app.innerHTML = qs('#tpl-otp').innerHTML;
  qs('#otp-mobile-label').textContent = `+91 ${mobile}`;

  const boxes = qsa('.otp-box');
  boxes[0].focus();
  boxes.forEach((b, i) => {
    b.addEventListener('input', () => {
      b.value = b.value.replace(/\D/g, '');
      if (b.value && boxes[i+1]) boxes[i+1].focus();
    });
    b.addEventListener('keydown', (e) => {
      if (e.key === 'Backspace' && !b.value && boxes[i-1]) boxes[i-1].focus();
    });
  });

  qs('#verify-otp-btn').onclick = async () => {
    const otp = boxes.map(b => b.value).join('');
    if (otp.length !== 4) { toast('Enter the 4-digit OTP', 'error'); return; }
    await withLoader(async () => {
      try {
        const res = await api('/auth/verify-otp', { method: 'POST', auth: false, body: { mobile, otp, token: Store.getOtpToken() } });
        const data = unwrap(res);
        const accessToken = pick(res, ['access_token']) || pick(data, ['access_token']) || pick(res, ['token']);
        if (!accessToken) throw new Error('Login succeeded but no access token was returned by the API.');
        Store.setToken(accessToken);
        const userObj = data && typeof data === 'object' ? (data.user || data) : null;
        if (userObj) Store.setUser(userObj);
        toast('Logged in successfully', 'success');
        const isNewFromVerify = pick(res, ['is_new_user']) ?? pick(data, ['is_new_user']);
        const isNew = isNewFromVerify !== undefined && isNewFromVerify !== null ? isNewFromVerify : Store.getIsNewUser();
        navigate(isNew ? '/complete-profile' : '/home', true);
      } catch (e) {
        toast(e.message, 'error');
      }
    });
  };

  qs('#resend-otp-btn').onclick = async () => {
    await withLoader(async () => {
      try {
        await api('/auth/resend-otp', { method: 'POST', auth: false, body: { mobile, token: Store.getOtpToken() } });
        toast('OTP resent', 'success');
      } catch (e) { toast(e.message, 'error'); }
    });
  };
});
</script>
