<template id="tpl-referral">
    <div id="referral-wrapper">
        <div id="referral-header-placeholder"></div>
        <main class="pt-20 pb-24 px-container-margin min-h-screen">
            <div class="flex flex-col items-center text-center mb-stack-lg">
                <div class="w-16 h-16 rounded-2xl bg-secondary/20 flex items-center justify-center mb-3"><span
                        class="material-symbols-outlined text-secondary text-3xl">share</span></div>
                <h2 class="font-headline-lg text-headline-lg">Refer & Earn</h2>
                <p class="text-on-surface-variant font-body-md mt-1">Invite friends to FSRQuiz and earn 50 bonus coins
                    for every signup!</p>
            </div>
            <div class="glass-card rounded-2xl p-5 mb-stack-lg">
                <p class="font-label-caps text-[11px] text-on-surface-variant mb-2 text-center">YOUR REFERRAL CODE</p>
                <div class="flex items-center gap-2 bg-surface-container border border-glass rounded-xl p-2 pl-4">
                    <span
                        class="font-display text-xl text-primary font-bold tracking-widest flex-1 truncate text-center"
                        id="ref-code">...</span>
                    <button id="ref-copy-btn"
                        class="bg-primary text-on-primary px-4 py-2 rounded-lg font-action text-action active:scale-95 transition-transform">Copy</button>
                </div>
            </div>
            <div class="glass-card rounded-2xl p-5 mb-stack-lg">
                <p class="font-label-caps text-[11px] text-on-surface-variant mb-2">HAVE A REFERRAL CODE?</p>
                <div class="flex items-center gap-2">
                    <input id="ref-input" type="text" placeholder="Enter code"
                        class="flex-1 bg-surface-container border border-glass rounded-xl px-4 py-2.5 text-on-surface uppercase">
                    <button id="ref-apply-btn"
                        class="bg-surface-container-high border border-glass text-on-surface px-4 py-2.5 rounded-xl font-action text-action active:scale-95 transition-transform">Apply</button>
                </div>
            </div>
            <div class="flex justify-between items-center mb-stack-sm">
                <h4 class="font-headline-md text-headline-md">My Referrals</h4>
            </div>
            <div class="space-y-gutter" id="ref-list">
                <div class="glass-card rounded-xl h-16 skeleton"></div>
            </div>
        </main>
        <div id="referral-nav-placeholder"></div>
    </div>
</template>

<script>
    route('/referral', async (app) => {
        app.innerHTML = qs('#tpl-referral').innerHTML;
        qs('#referral-header-placeholder').outerHTML = topHeader({
            title: 'Referral',
            showBack: true,
            backTo: '/home'
        });
        qs('#referral-nav-placeholder').outerHTML = bottomNav('profile');

        api('/user/referral-code', {
            method: 'GET'
        }).then(res => {
            const d = unwrap(res);
            const code = pick(d, ['referral_code', 'code'], 'QX' + Math.random().toString(36)
                .substring(2, 8).toUpperCase());
            qs('#ref-code').textContent = code;
            qs('#ref-copy-btn').onclick = () => {
                navigator.clipboard.writeText(code).then(() => toast('Referral code copied!',
                    'success')).catch(() => toast('Code: ' + code, 'info'));
            };
        }).catch(() => {
            qs('#ref-code').textContent = 'UNAVAILABLE';
        });

        qs('#ref-apply-btn').onclick = async () => {
            const code = qs('#ref-input').value.trim();
            if (!code) {
                toast('Enter a referral code', 'error');
                return;
            }
            await withLoader(async () => {
                try {
                    await api('/user/apply-referral', {
                        method: 'POST',
                        body: {
                            referral_code: code
                        }
                    });
                    toast('Referral code applied!', 'success');
                    qs('#ref-input').value = '';
                } catch (e) {
                    toast(e.message, 'error');
                }
            });
        };

        api('/user/referrals', {
            method: 'GET'
        }).then(res => {
            const list = asList(res);
            qs('#ref-list').innerHTML = list.length ? list.map(r => {
                    const name = escapeHtml(pick(r, ['name', 'user_name'], 'User'));
                    const date = pick(r, ['created_at', 'date'], '');
                    const bonus = pick(r, ['bonus', 'coins'], '50');
                    return `
      <div class="glass-card rounded-xl p-4 flex justify-between items-center">
        <div>
          <h4 class="font-headline-md text-on-surface text-[14px]">${name}</h4>
          ${date ? `<p class="font-body-md text-on-surface-variant text-[11px]">${escapeHtml(date)}</p>` : ''}
        </div>
        <span class="font-headline-md text-mint">+${escapeHtml(bonus)} Coins</span>
      </div>`;
                }).join('') :
                `<p class="text-on-surface-variant text-center py-8">No referrals yet. Share your code to earn coins!</p>`;
        }).catch(() => {
            qs('#ref-list').innerHTML =
                `<p class="text-on-surface-variant text-center py-8">Could not load referrals.</p>`;
        });
    });
</script>
