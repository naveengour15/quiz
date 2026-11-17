<template id="tpl-home">
    <div id="home-wrapper">
        <!-- Top header dynamically injected -->
        <div id="home-header-placeholder"></div>
        <main class="pt-20 pb-24 px-container-margin min-h-screen" id="home-main">
            <section class="mt-stack-md mb-stack-lg">
                <h2 class="font-headline-lg text-headline-lg text-on-surface" id="home-welcome">Welcome back!</h2>
                <p class="text-on-surface-variant font-body-md">Ready to dominate today's challenges?</p>
            </section>
            <section class="mb-stack-lg">
                <div class="flex justify-between items-center mb-stack-sm">
                    <h4 class="font-headline-md text-headline-md">Active Quizzes</h4>
                    <button onclick="navigate('/quiz/list')" class="font-action text-primary text-action">View
                        All</button>
                </div>
                <div class="grid grid-cols-2 gap-gutter" id="home-quiz-grid">
                    <div class="glass-card rounded-xl h-40 skeleton"></div>
                    <div class="glass-card rounded-xl h-40 skeleton"></div>
                </div>
            </section>
            <section class="mb-stack-lg">
                <div class="flex justify-between items-center mb-stack-sm">
                    <h4 class="font-headline-md text-headline-md">Active Surveys</h4>
                    <button onclick="navigate('/survey/list')" class="font-action text-tertiary text-action">View
                        All</button>
                </div>
                <div class="grid grid-cols-2 gap-gutter" id="home-survey-grid">
                    <div class="glass-card rounded-xl h-40 skeleton"></div>
                    <div class="glass-card rounded-xl h-40 skeleton"></div>
                </div>
            </section>
            <section class="mb-stack-lg">
                <div class="grid grid-cols-2 gap-gutter">
                    <div onclick="navigate('/quiz/list')"
                        class="glass-card rounded-xl p-5 flex flex-col items-center justify-center text-center group hover:border-primary/50 transition-all cursor-pointer active:scale-95">
                        <div class="w-14 h-14 rounded-2xl bg-primary/20 flex items-center justify-center mb-stack-sm">
                            <span class="material-symbols-outlined text-primary text-3xl">public</span></div>
                        <h4 class="font-headline-md text-[18px] text-on-surface">Public Quiz</h4>
                        <p class="font-label-caps text-on-surface-variant text-[10px] mt-1">GLOBAL BATTLES</p>
                    </div>
                    <div onclick="navigate('/survey/list')"
                        class="glass-card rounded-xl p-5 flex flex-col items-center justify-center text-center group hover:border-tertiary/50 transition-all cursor-pointer active:scale-95">
                        <div class="w-14 h-14 rounded-2xl bg-tertiary/20 flex items-center justify-center mb-stack-sm">
                            <span class="material-symbols-outlined text-tertiary text-3xl">poll</span></div>
                        <h4 class="font-headline-md text-[18px] text-on-surface">Surveys</h4>
                        <p class="font-label-caps text-on-surface-variant text-[10px] mt-1">EARN POINTS</p>
                    </div>
                </div>
            </section>
            <section class="mb-stack-lg">
                <div class="grid grid-cols-6 grid-rows-2 gap-3 h-48" id="home-stats">
                    <div class="glass-card rounded-xl h-full col-span-3 row-span-2 skeleton"></div>
                    <div class="col-span-3 row-span-1 grid gap-3">
                        <div class="glass-card rounded-xl h-full skeleton"></div>
                    </div>
                    <div class="col-span-3 row-span-1 grid gap-3">
                        <div class="glass-card rounded-xl h-full skeleton"></div>
                    </div>
                </div>
            </section>
            <section>
                <div class="flex justify-between items-center mb-stack-sm">
                    <h4 class="font-headline-md text-headline-md">Recent Activity</h4>
                    <button onclick="navigate('/history')" class="font-action text-primary text-action">View
                        All</button>
                </div>
                <div class="space-y-gutter" id="home-recent">
                    <div class="glass-card rounded-xl h-16 skeleton"></div>
                    <div class="glass-card rounded-xl h-16 skeleton"></div>
                </div>
            </section>
        </main>
        <!-- Bottom nav dynamically injected -->
        <div id="home-nav-placeholder"></div>
    </div>
</template>

<script>
    route('/home', async (app) => {
        app.innerHTML = qs('#tpl-home').innerHTML;
        qs('#home-header-placeholder').outerHTML = topHeader({
            title: 'FSRQuiz',
            coins: '…'
        });
        qs('#home-nav-placeholder').outerHTML = bottomNav('home');

        const user = Store.getUser();
        if (user) qs('#home-welcome').textContent = `Welcome back, ${pick(user,['name'],'Champ')}!`;

        api('/user/wallet', {
            method: 'GET'
        }).then(res => {
            const d = unwrap(res);
            const bal = pick(d, ['balance', 'coins', 'wallet_balance'], '0');
            const c = qs('#header-coins');
            if (c) c.textContent = bal;
        }).catch(() => {
            const c = qs('#header-coins');
            if (c) c.textContent = '0';
        });

        api('/user/dashboard', {
            method: 'GET'
        }).then(res => {
            const d = unwrap(res) || {};
            const rank = pick(d, ['rank', 'current_rank'], '-');
            const streak = pick(d, ['streak', 'streak_days'], '0');
            const wins = pick(d, ['total_wins', 'wins'], '0');
            qs('#home-stats').innerHTML = `
      <div class="col-span-3 row-span-2 glass-card rounded-xl p-4 flex flex-col justify-between">
        <span class="material-symbols-outlined text-mint text-2xl">trending_up</span>
        <div><p class="font-label-caps text-on-surface-variant">RANK</p><p class="font-display text-2xl text-on-surface font-bold">#${escapeHtml(rank)}</p></div>
      </div>
      <div class="col-span-3 row-span-1 glass-card rounded-xl px-4 py-3 flex items-center justify-between">
        <div><p class="font-label-caps text-on-surface-variant">STREAK</p><p class="font-headline-md text-on-surface">${escapeHtml(streak)} Days</p></div>
        <span class="material-symbols-outlined text-secondary">local_fire_department</span>
      </div>
      <div class="col-span-3 row-span-1 glass-card rounded-xl px-4 py-3 flex items-center justify-between">
        <div><p class="font-label-caps text-on-surface-variant">TOTAL</p><p class="font-headline-md text-on-surface">${escapeHtml(wins)} Wins</p></div>
        <span class="material-symbols-outlined text-primary">emoji_events</span>
      </div>`;
            const recent = d.recent_activity || d.recent || d.history || [];
            const recentList = Array.isArray(recent) ? recent : [];
            qs('#home-recent').innerHTML = recentList.length ? recentList.slice(0, 5).map(r => `
      <div class="glass-card rounded-lg p-3 flex items-center gap-4">
        <div class="w-12 h-12 rounded-lg bg-surface-container flex items-center justify-center"><span class="material-symbols-outlined text-on-surface-variant">quiz</span></div>
        <div class="flex-1">
          <p class="font-headline-md text-body-lg text-on-surface">${escapeHtml(pick(r,['quiz_title','title','name'],'Quiz'))}</p>
          <p class="font-body-md text-on-surface-variant text-[12px]">${escapeHtml(pick(r,['status','result'],'Completed'))} • +${escapeHtml(pick(r,['coins','reward'],'0'))} Coins</p>
        </div>
        <span class="material-symbols-outlined text-on-surface-variant">chevron_right</span>
      </div>`).join('') : `<p class="text-on-surface-variant text-center py-6">No recent activity yet. Join a quiz to get started!</p>`;

            const surveys = d.survey || d.surveys || [];
            if (Array.isArray(surveys) && surveys.length) {
                renderDashboardSurveys(surveys);
            } else {
                api('/survey/list', {
                        method: 'GET'
                    })
                    .then(sRes => renderDashboardSurveys(pick(sRes, ['survey', 'surveys', 'data'],
                    []) || asList(sRes)))
                    .catch(() => renderDashboardSurveys([]));
            }
        }).catch(() => {
            qs('#home-stats').innerHTML =
                `<div class="col-span-6 row-span-2 glass-card rounded-xl p-4 flex items-center justify-center text-on-surface-variant">Stats unavailable</div>`;
            qs('#home-recent').innerHTML =
                `<p class="text-on-surface-variant text-center py-6">Could not load recent activity.</p>`;
            api('/survey/list', {
                    method: 'GET'
                })
                .then(sRes => renderDashboardSurveys(pick(sRes, ['survey', 'surveys', 'data'],
                []) || asList(sRes)))
                .catch(() => renderDashboardSurveys([]));
        });

        function renderDashboardSurveys(list) {
            const grid = qs('#home-survey-grid');
            if (!grid) return;
            const surveyArr = Array.isArray(list) ? list : [];
            if (!surveyArr.length) {
                grid.innerHTML =
                    `<div class="col-span-2 glass-card rounded-xl p-6 text-center text-on-surface-variant">No active surveys right now. Check back soon!</div>`;
                return;
            }
            grid.innerHTML = surveyArr.slice(0, 4).map(surveyCardHtml).join('');
        }

        api('/quiz/list', {
            method: 'GET'
        }).then(res => {
            const list = asList(res);
            const grid = qs('#home-quiz-grid');
            if (!list.length) {
                grid.innerHTML =
                    `<div class="col-span-2 glass-card rounded-xl p-6 text-center text-on-surface-variant">No active quizzes right now. Check back soon!</div>`;
                return;
            }
            grid.innerHTML = list.slice(0, 4).map(quizCardHtml).join('');
            attachQuizCardHandlers(grid, list);
        }).catch(() => {
            qs('#home-quiz-grid').innerHTML =
                `<div class="col-span-2 glass-card rounded-xl p-6 text-center text-on-surface-variant">Could not load quizzes.</div>`;
        });
    });
</script>
