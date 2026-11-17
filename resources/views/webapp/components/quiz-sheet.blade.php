<template id="tpl-quiz-sheet">
    <div id="quiz-sheet-root">
        <div class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50" id="qsheet-overlay"></div>
        <div class="fixed bottom-0 w-full z-50 bg-[#1a0b3d] rounded-t-3xl border-t border-purple-500/30 shadow-[0_-10px_40px_rgba(168,85,247,0.15)] max-w-md mx-auto left-1/2 -translate-x-1/2 transform transition-transform duration-300"
            id="qsheet">
            <div class="px-5 pb-8 pt-3">
                <div class="w-12 h-1.5 bg-white/20 rounded-full mx-auto mb-5"></div>
                <div id="qsheet-body" class="min-h-[200px] flex items-center justify-center">
                    <div class="w-8 h-8 border-4 border-[#e879f9]/30 border-t-[#e879f9] rounded-full spin"></div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    async function openQuizSheet(quizId) {
        const existing = qs('#quiz-sheet-root');
        if (existing) existing.remove();
        const tpl = qs('#tpl-quiz-sheet');
        const root = el(tpl.innerHTML);
        document.body.appendChild(root);
        qs('#qsheet-overlay').onclick = () => root.remove();

        try {
            const res = await api(`/quiz/${quizId}`, {
                method: 'GET'
            });
            const q = unwrap(res) || {};
            const title = escapeHtml(pick(q, ['title', 'name', 'quiz_title'], 'Quiz'));
            const img = pick(q, ['image', 'banner', 'thumbnail'], '');
            const prize = pick(q, ['prize_pool', 'prize', 'total_prize'], '0');
            const fee = pick(q, ['entry_fee', 'fee'], '0');
            const joined = pick(q, ['joined_count', 'players', 'participants_count'], '0');
            const desc = pick(q, ['description', 'rules'], '');
            const needsPassword = !!pick(q, ['is_private', 'private']);
            const alreadyPlayed = isAlreadyPlayedFlag(q);
            const isJoined = !!pick(q, ['is_joined', 'joined', 'user_joined']) || !!q.is_joined;
            const startTimeStr = pick(q, ['start_time', 'availability_start', 'available_start']);

            let buttonHtml =
                `<button id="qsheet-join-btn" class="w-full mt-2 bg-gradient-to-r from-[#f4bf32] via-[#d946ef] to-[#7c3aed] text-[#1a0b3d] py-3.5 rounded-xl font-display font-bold text-[15px] shadow-[0_8px_22px_rgba(217,70,239,0.3)] active:scale-95 transition-transform flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-[19px]">play_circle</span> Join Quiz Now
                </button>`;
            
            if (alreadyPlayed) {
                buttonHtml =
                    `<button id="qsheet-leaderboard-btn" class="w-full mt-2 bg-gradient-to-r from-[#f4bf32] via-[#d946ef] to-[#7c3aed] text-[#1a0b3d] py-3.5 rounded-xl font-display font-bold text-[15px] shadow-[0_8px_22px_rgba(217,70,239,0.3)] active:scale-95 transition-transform flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined text-[19px]">leaderboard</span> View Leaderboard
                    </button>`;
            } else if (isJoined) {
                 buttonHtml =
                    `<button id="qsheet-open-btn" class="w-full mt-2 bg-gradient-to-r from-[#86efac] to-[#3b82f6] text-[#1a0b3d] py-3.5 rounded-xl font-display font-bold text-[15px] shadow-[0_8px_22px_rgba(59,130,246,0.3)] active:scale-95 transition-transform flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined text-[19px]">play_arrow</span> Open Quiz
                    </button>`;
            }

            qs('#qsheet-body').outerHTML = `
      <div id="qsheet-body">
        <div class="mb-5 text-center">
          ${alreadyPlayed ? `<span class="inline-block mb-3 bg-[#86efac] text-[#1a0b3d] text-[10px] font-extrabold px-3 py-1.5 rounded-full shadow-lg">ALREADY PLAYED</span>` : isJoined ? `<span class="inline-block mb-3 bg-[#e879f9] text-[#1a0b3d] text-[10px] font-extrabold px-3 py-1.5 rounded-full shadow-lg">YOU HAVE JOINED</span>` : ''}
          <h3 class="font-display text-2xl font-extrabold text-[#ffe9b0] mb-2 leading-tight">${title}</h3>
          ${desc ? `<p class="text-[13px] text-[#e8ddff]/70 mb-4 line-clamp-3">${escapeHtml(desc)}</p>` : ''}
          
          <div class="grid grid-cols-3 gap-3 mt-4">
            <div class="bg-white/[0.03] p-3 rounded-xl border border-white/10 text-center flex flex-col items-center justify-center">
                <span class="material-symbols-outlined text-[18px] text-[#ffd76a] mb-1">emoji_events</span>
                <p class="text-[10px] font-bold text-white/50 mb-0.5">PRIZE POOL</p>
                <p class="text-[#ffe9b0] font-mono font-bold text-[13px]">${escapeHtml(prize)}</p>
            </div>
            <div class="bg-white/[0.03] p-3 rounded-xl border border-white/10 text-center flex flex-col items-center justify-center">
                <span class="material-symbols-outlined text-[18px] text-[#86efac] mb-1">confirmation_number</span>
                <p class="text-[10px] font-bold text-white/50 mb-0.5">ENTRY FEE</p>
                <p class="text-[#86efac] font-mono font-bold text-[13px]">${fee == 0 || fee == 'Free' ? 'Free' : escapeHtml(fee)}</p>
            </div>
            <div class="bg-white/[0.03] p-3 rounded-xl border border-white/10 text-center flex flex-col items-center justify-center">
                <span class="material-symbols-outlined text-[18px] text-[#e879f9] mb-1">groups</span>
                <p class="text-[10px] font-bold text-white/50 mb-0.5">JOINED</p>
                <p class="text-[#f0abfc] font-mono font-bold text-[13px]">${escapeHtml(joined)}</p>
            </div>
          </div>
          ${(!alreadyPlayed && !isJoined && needsPassword) ? `<input id="qsheet-password" placeholder="Enter quiz password" class="mt-4 w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-[#e8ddff] placeholder-white/30 focus:outline-none focus:border-[#e879f9]/50 focus:ring-1 focus:ring-[#e879f9]/50 transition-all">` : ''}
        </div>
        ${buttonHtml}
        <button id="qsheet-cancel-btn" class="w-full mt-3 py-2.5 text-[#e8ddff]/60 font-bold text-[14px] hover:text-[#e8ddff] transition-colors rounded-xl hover:bg-white/5">Cancel</button>
      </div>`;
            qs('#qsheet-cancel-btn').onclick = () => root.remove();

            if (alreadyPlayed) {
                qs('#qsheet-leaderboard-btn').onclick = () => {
                    root.remove();
                    navigate(`/quiz/winners/${quizId}`);
                };
            } else if (isJoined) {
                qs('#qsheet-open-btn').onclick = () => {
                    root.remove();
                    goToQuizOrWait(quizId, startTimeStr);
                };
            } else {
                qs('#qsheet-join-btn').onclick = async () => {
                    const password = needsPassword ? (qs('#qsheet-password')?.value || null) : null;
                    await withLoader(async () => {
                        try {
                            const joinRes = await api(`/quiz/${quizId}/join`, {
                                method: 'POST',
                                body: {
                                    password
                                }
                            });
                            const joinData = unwrap(joinRes);
                            const st = pick(joinRes, ['start_time']) || pick(joinData, ['start_time']) || startTimeStr;
                            const pt = pick(joinRes, ['pending_time']) || pick(joinData, ['pending_time']);

                            if (joinRes && joinRes.success === false) {
                                if (isAlreadyJoinedError(joinRes) || (joinRes.message || '')
                                    .includes('already')) {
                                    toast(joinRes.message || 'You have already joined this quiz',
                                        'info');
                                    root.remove();
                                    goToQuizOrWait(quizId, st, pt);
                                    return;
                                }
                                toast(joinRes.message || 'Failed to join quiz', 'error');
                                return;
                            }

                            toast(pick(joinRes, ['message']) || pick(joinData, ['message']) ||
                                'Joined quiz!', 'success');
                            root.remove();
                            goToQuizOrWait(quizId, st, pt);
                        } catch (e) {
                            const payload = e.payload || {};
                            const st = pick(payload, ['start_time']) || pick(e, ['start_time']) ||
                                startTimeStr;
                            const pt = pick(payload, ['pending_time']) || pick(e, ['pending_time']);
                            if (isAlreadyPlayedError(e)) {
                                toast('You have already played this quiz. Showing the leaderboard…',
                                    'info');
                                root.remove();
                                navigate(`/quiz/winners/${quizId}`);
                            } else if (isAlreadyJoinedError(e) || (e.message || '').includes(
                                    'already')) {
                                toast('You have already joined this quiz!', 'info');
                                root.remove();
                                goToQuizOrWait(quizId, st, pt);
                            } else {
                                toast(e.message, 'error');
                            }
                        }
                    });
                };
            }
        } catch (e) {
            qs('#qsheet-body').innerHTML = `<p class="text-red-400 text-center py-8">${escapeHtml(e.message)}</p>`;
        }
    }
</script>
