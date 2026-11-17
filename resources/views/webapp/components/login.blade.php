<template id="tpl-login">
    <div class="min-h-screen flex flex-col justify-center px-container-margin py-10 fade-in">
        <button onclick="openSettings()"
            class="absolute top-5 right-5 w-10 h-10 rounded-full flex items-center justify-center bg-surface-glass border border-glass active:scale-90">
            <span class="material-symbols-outlined text-on-surface-variant">settings</span>
        </button>
        <div class="flex flex-col items-center mb-10">
            <div
                class="w-20 h-20 rounded-3xl vibrant-gradient flex items-center justify-center mb-4 shadow-[0_0_40px_rgba(124,58,237,0.4)]">
                <span class="material-symbols-outlined text-white text-4xl">bolt</span>
            </div>
            <h1 class="font-display text-display text-primary">FSRQuiz</h1>
            <p class="text-on-surface-variant mt-1">Play. Compete. Win real rewards.</p>
        </div>
        <div class="glass-card rounded-2xl p-6">
            <h2 class="font-headline-md text-headline-md mb-1">Login / Sign up</h2>
            <p class="text-on-surface-variant text-body-md mb-5">Enter your mobile number to get an OTP.</p>
            <label class="font-label-caps text-[11px] text-on-surface-variant">MOBILE NUMBER</label>
            <div class="flex items-center gap-2 mt-1 bg-surface-container border border-glass rounded-xl px-4 py-3">
                <span class="text-on-surface-variant">+91</span>
                <input id="login-mobile" type="tel" inputmode="numeric" maxlength="10" placeholder="98765 43210"
                    class="bg-transparent flex-1 min-w-0 text-on-surface placeholder:text-on-surface-variant/60">
            </div>
            <button id="login-send-otp"
                class="w-full mt-5 bg-primary text-on-primary py-4 rounded-xl font-action text-lg shadow-[0_0_20px_rgba(210,187,255,0.3)] active:scale-95 transition-transform">Send
                OTP</button>
        </div>
        <p class="text-center text-on-surface-variant text-[12px] mt-6">By continuing you agree to our Terms & Privacy
            Policy.</p>
    </div>
</template>

<script>
    route('/login', async (app) => {
        app.innerHTML = qs('#tpl-login').innerHTML;

        qs('#login-send-otp').onclick = async () => {
            const mobileInput = qs('#login-mobile').value.trim();
            if (!/^\d{10}$/.test(mobileInput)) {
                toast('Enter a valid 10-digit mobile number', 'error');
                return;
            }
            await withLoader(async () => {
                try {
                    const res = await api('/auth/send-otp', {
                        method: 'POST',
                        auth: false,
                        body: {
                            mobile: mobileInput
                        }
                    });
                    const data = unwrap(res);
                    const otpToken = pick(res, ['token']) || pick(data, ['token']);
                    Store.setPendingMobile(mobileInput);
                    if (otpToken) Store.setOtpToken(otpToken);
                    const isNew = pick(res, ['is_new_user']) ?? pick(data, [
                        'is_new_user'
                    ]);
                    if (isNew !== undefined && isNew !== null) Store.setIsNewUser(
                    isNew);
                    toast('OTP sent successfully', 'success');
                    navigate('/otp');
                } catch (e) {
                    toast(e.message, 'error');
                }
            });
        };
    });
</script>
