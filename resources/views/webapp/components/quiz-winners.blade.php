<template id="tpl-quiz-winners">
    <div id="quiz-winners-wrapper">
        <div id="quiz-winners-header-placeholder"></div>
        <main class="pt-20 pb-24 px-container-margin min-h-screen" id="winners-main">
            <div class="space-y-gutter">
                <div class="glass-card rounded-xl h-16 skeleton"></div>
                <div class="glass-card rounded-xl h-16 skeleton"></div>
                <div class="glass-card rounded-xl h-16 skeleton"></div>
            </div>
        </main>
    </div>
</template>

<script>
    route('/quiz/winners/:id', async (app, params) => {
        const quizId = params.id;
        app.innerHTML = qs('#tpl-quiz-winners').innerHTML;
        qs('#quiz-winners-header-placeholder').outerHTML = topHeader({
            title: 'Leaderboard',
            showBack: true
        });

        try {
            let res = await api(`/fsrapp/quizes/api/leaderboard/${quizId}`, { method: 'GET' }).catch(() => null);
            if (!res || !res.success) {
                res = await api(`/fsrapp/quiz/get-winners/${quizId}`, { method: 'GET' });
            }
            const list = asList(res);
            const userRank = res?.user_rank || (list ? list.find(u => u.is_current_user) : null);

            if (list.length) {
                Sound.ensureCtx();
                Sound.fanfare();
            }

            let userRankHtml = '';
            if (userRank) {
                const uRank = userRank.rank || 1;
                const uName = pick(userRank, ['name', 'user_name'], 'Player');
                const uCorrect = pick(userRank, ['correct', 'total_correct_answer'], 0);
                const uTotal = pick(userRank, ['total', 'total_question'], 10);
                const uScore = pick(userRank, ['reward', 'points', 'score'], uCorrect * 10);
                const uTime = pick(userRank, ['total_time'], null);
                userRankHtml = `
                    <div class="relative overflow-hidden rounded-xl p-3 mb-4 bg-gradient-to-r from-tertiary-container/90 via-purple-900/90 to-primary-container/90 border-2 border-tertiary shadow-xl text-white">
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <div class="w-10 h-10 rounded-lg bg-tertiary text-on-tertiary font-display font-black text-sm flex flex-col items-center justify-center shrink-0">
                                    <span class="text-[8px] uppercase font-bold tracking-tighter opacity-80 leading-none">RANK</span>
                                    <span class="leading-none mt-0.5">#${uRank}</span>
                                </div>
                                <div class="min-w-0">
                                    <div class="flex items-center gap-1.5 truncate">
                                        <span class="font-headline-md text-xs font-bold text-white truncate">${escapeHtml(uName)}</span>
                                        <span class="px-1.5 py-0.5 rounded-full bg-tertiary text-on-tertiary text-[9px] font-black tracking-wider uppercase shrink-0">YOUR POSITION</span>
                                    </div>
                                    <div class="flex items-center gap-2 text-[10px] text-tertiary-fixed mt-0.5 font-medium">
                                        <span>${uCorrect} Correct</span>
                                        ${uTime ? `<span>•</span><span class="flex items-center gap-0.5 text-white font-mono font-bold"><span class="material-symbols-outlined text-[11px] text-tertiary">schedule</span>${Number(uTime).toFixed(3)}s</span>` : ''}
                                    </div>
                                </div>
                            </div>
                            <div class="text-right shrink-0">
                                <div class="text-[9px] uppercase font-bold text-tertiary-fixed">Reward</div>
                                <div class="font-display text-xs font-black text-tertiary px-2 py-0.5 rounded-lg bg-black/40 border border-tertiary/40">
                                    ${uScore} pts
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }

            qs('#winners-main').innerHTML = list.length ? `
      <div class="relative z-[1] flex justify-end mb-3">${kbcMuteBtnHtml()}</div>
      ${userRankHtml}
      <div class="space-y-gutter">${list.map((w,i) => {
        const name = pick(w, ['user_name','name'], 'Player');
        const correct = pick(w, ['total_correct_answer','correct_answers','correct'], '0');
        const total = pick(w, ['total_question','total_questions','total'], '10');
        const score = pick(w, ['score','total_score'], (correct !== '-' ? correct * 10 : '0'));
        const totalTime = pick(w, ['total_time'], null);
        const reward = pick(w, ['reward'], null);
        const rewardType = pick(w, ['reward_type'], null);
        const hasReward = reward !== null && reward !== '' && reward !== undefined;
        const isMe = w.is_current_user;
        return `
      <div class="kbc-card kbc-winner-row ${i<3 ? 'top' : ''} ${isMe ? 'border-2 border-tertiary bg-tertiary/20' : ''} kbc-reveal-row rounded-lg p-3 flex items-center gap-3 sm:gap-4 flex-wrap sm:flex-nowrap" style="animation-delay:${i*60}ms">
        <div class="w-10 h-10 shrink-0 rounded-full ${i===0?'kbc-coin-badge':i===1?'bg-outline text-surface':i===2?'bg-secondary text-on-secondary':'bg-surface-container text-on-surface-variant'} flex items-center justify-center font-bold">${i===0?'<span class="material-symbols-outlined text-[18px]">emoji_events</span>':(w.rank || i+1)}</div>
        <div class="flex-1 min-w-0">
          <div class="flex items-center gap-1.5">
            <p class="font-headline-md text-body-lg text-on-surface truncate">${escapeHtml(name)}</p>
            ${isMe ? `<span class="px-1.5 py-0.2 rounded-full bg-tertiary text-on-tertiary text-[8px] font-black">YOU</span>` : ''}
          </div>
          <div class="flex flex-wrap items-center gap-x-3 gap-y-0.5 mt-0.5">
            <p class="font-body-md text-on-surface-variant text-[12px]">Score: <span class="text-primary font-bold">${escapeHtml(score)} Pts</span></p>
            <p class="font-body-md text-on-surface-variant text-[12px]">Correct: <span class="text-mint font-bold">${escapeHtml(correct)}/${escapeHtml(total)}</span></p>
            ${totalTime ? `<p class="font-body-md text-on-surface-variant text-[12px] flex items-center gap-0.5"><span class="material-symbols-outlined text-[13px] text-tertiary">schedule</span><span class="text-tertiary font-bold">${Number(totalTime).toFixed(3)}s</span></p>` : ''}
          </div>
        </div>
        <div class="flex flex-col items-end shrink-0">
          <span class="text-tertiary font-bold flex items-center gap-1"><span class="material-symbols-outlined text-[16px]" style="font-variation-settings:'FILL' 1;">monetization_on</span>${hasReward ? escapeHtml(reward) : '0'}</span>
          ${hasReward && rewardType ? `<span class="text-on-surface-variant text-[10px] uppercase tracking-wide mt-0.5">${escapeHtml(rewardType)}</span>` : ''}
        </div>
      </div>`;
        }).join('')
    }</div>` : `<p class="text-on-surface-variant text-center py-10">No results yet.</p>`;
    wireMuteBtn();
  } catch (e) {
    qs('#winners-main').innerHTML = `<p class="text-error text-center py-10">${escapeHtml(e.message)}</p>`;
  }
});
</script>
