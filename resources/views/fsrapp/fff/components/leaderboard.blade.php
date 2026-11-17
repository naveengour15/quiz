<main class="min-h-screen px-container-margin pt-6 pb-8 flex flex-col justify-between kbc-stage text-white relative" style="touch-action: pan-y !important; -webkit-overflow-scrolling: touch !important; overflow-y: visible !important;">
    <div class="kbc-spotlight"></div>
    <div class="kbc-stage-rings"><span></span><span></span><span></span><span></span></div>

    <!-- Top Header Controls -->
    <div class="relative z-[1] flex items-center justify-between w-full mb-3">
        <a href="{{ route('fsrapp.home') }}" class="kbc-icon-badge flex items-center justify-center" title="Back to Home">
            <span class="material-symbols-outlined text-[18px]">home</span>
        </a>

        <button type="button" id="kbc-mute-toggle" class="kbc-icon-badge" onclick="toggleGlobalMute()">
            <span class="material-symbols-outlined text-[18px]">volume_up</span>
        </button>
    </div>

    <!-- Center Leaderboard Content -->
    <div class="relative z-[1] w-full max-w-md mx-auto my-auto space-y-4">
        <div class="space-y-1 text-center">
            <h1 class="font-display text-2xl font-black text-white kbc-title-glow uppercase tracking-wider">
                {{ __('app.winners_leaderboard') }}
            </h1>
            <p class="font-body-md text-xs text-on-surface-variant">Final rankings and rewards distributed</p>
        </div>

        <!-- Sticky User Rank Position Banner -->
        <div id="fff-user-rank-container" class="hidden">
            <!-- Rendered dynamically -->
        </div>

        <!-- Top 3 Podium Cards -->
        <div id="fff-podium-container" class="grid grid-cols-3 gap-2 items-end pt-2 pb-1">
            <div class="p-4 text-center col-span-3 text-on-surface-variant text-sm">
                <span class="material-symbols-outlined animate-spin text-tertiary text-2xl mb-1">sync</span>
                <p>Loading podium ranks...</p>
            </div>
        </div>

        <!-- Overall Leaderboard List -->
        <div class="space-y-2">
            <h3 class="font-label-caps text-xs text-on-surface-variant font-bold uppercase tracking-wider px-1">ALL PLAYERS</h3>
            <div id="fff-leaderboard-list" class="space-y-2 max-h-[260px] overflow-y-auto pr-1 scrollbar-hide">
                <div class="p-6 text-center text-on-surface-variant text-sm">
                    <span class="material-symbols-outlined animate-spin text-primary text-2xl mb-1">sync</span>
                    <p>{{ __('app.loading_leaderboard') }}</p>
                </div>
            </div>
        </div>

        <!-- Bottom Action Button -->
        <div class="pt-2 text-center">
            <a href="{{ route('fsrapp.home') }}"
                class="w-full py-3.5 px-6 rounded-xl bg-gradient-to-r from-primary via-purple-600 to-tertiary text-black font-display font-black text-sm uppercase tracking-wider shadow-[0_0_20px_rgba(210,187,255,0.4)] flex items-center justify-center gap-2 hover:scale-[1.02] active:scale-95 transition-all">
                <span class="material-symbols-outlined text-lg font-bold">home</span>
                <span>Back to Home</span>
            </a>
        </div>
    </div>
</main>

<script>
    (function() {
        window.loadFffLeaderboard = async function() {
            if (window.stopAllQuizAudios) window.stopAllQuizAudios();
            if (window.playNamedSound) {
                window.playNamedSound('leaderboard_appear_sound', 0.6, true, 'bg');
            }

            try {
                history.pushState({ stage: 'leaderboard', quizId: window.fffQuizId }, document.title, location.href);
            } catch (e) {}

            window.addEventListener('popstate', function handleFffLeaderboardPopstate(e) {
                window.location.replace('/fsrapp/home');
            });

            try {
                const res = await fetch('/fsrapp/fff/api/leaderboard/' + window.fffQuizId);
                const json = await res.json();

                if (json && json.success && json.data) {
                    window.quizCompleteStatus = 'completed';
                    renderFffLeaderboard(json.data, json.currentUserRank);
                    if (typeof window.clearCompletedQuizCacheAndSession === 'function') {
                        window.clearCompletedQuizCacheAndSession();
                    } else {
                        const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
                        fetch('/fsrapp/fff/api/clear-session/' + window.fffQuizId, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrf || '',
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            }
                        }).catch(() => {});
                    }
                } else {
                    showFffLeaderboardError(json?.message || 'No rankings available yet.');
                }
            } catch(e) {
                console.error('Fetch leaderboard failed:', e);
                showFffLeaderboardError('Could not load leaderboard.');
            }
        };

        function renderFffLeaderboard(winners, currentUserRank) {
            const podiumContainer = document.getElementById('fff-podium-container');
            const listContainer = document.getElementById('fff-leaderboard-list');
            const userRankContainer = document.getElementById('fff-user-rank-container');

            if (currentUserRank && userRankContainer) {
                userRankContainer.innerHTML = `
                    <div class="glass-card rounded-2xl p-3 border-2 border-tertiary/70 bg-gradient-to-r from-tertiary/20 via-purple-600/30 to-primary/20 shadow-[0_0_20px_rgba(244,191,50,0.3)] flex items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-tertiary text-black flex items-center justify-center font-display font-black text-sm shadow-md shrink-0">
                                #${currentUserRank.rank || '-'}
                            </div>
                            <div>
                                <div class="flex items-center gap-1.5">
                                    <span class="font-headline-sm text-xs font-bold text-white">${escapeHtml(currentUserRank.user_name || 'You')}</span>
                                    <span class="font-label-caps text-[9px] bg-tertiary text-black px-1.5 py-0.2 rounded font-extrabold">YOU</span>
                                </div>
                                <div class="flex items-center gap-2 text-[10px] text-on-surface-variant font-medium mt-0.5">
                                    <span class="text-mint font-bold">${currentUserRank.score || currentUserRank.total_correct_answer || 0} Correct</span>
                                    <span>•</span>
                                    <span class="text-tertiary font-bold">${parseFloat(currentUserRank.total_time || 0).toFixed(3)}s</span>
                                </div>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="font-label-caps text-[10px] text-tertiary uppercase font-bold block">REWARD</span>
                            <span class="font-display font-black text-sm text-white">+${currentUserRank.reward || 0} Marks</span>
                        </div>
                    </div>
                `;
                userRankContainer.classList.remove('hidden');
            } else if (userRankContainer) {
                userRankContainer.classList.add('hidden');
            }

            const top1 = winners.find(w => w.rank === 1) || winners[0] || null;
            const top2 = winners.find(w => w.rank === 2) || winners[1] || null;
            const top3 = winners.find(w => w.rank === 3) || winners[2] || null;

            if (podiumContainer) {
                podiumContainer.innerHTML = `
                    <!-- 2nd Place -->
                    <div class="glass-card rounded-2xl p-2.5 border border-secondary/30 bg-secondary/5 text-center flex flex-col items-center justify-end h-28 shadow-lg">
                        <span class="material-symbols-outlined text-secondary text-2xl">military_tech</span>
                        <span class="font-display text-xs font-bold text-white truncate max-w-full px-1">${escapeHtml(top2?.user_name || '2nd Place')}</span>
                        <span class="font-label-caps text-[10px] text-mint font-bold">${top2 ? (top2.score ?? top2.total_correct_answer ?? 0) + ' Marks' : '-'}</span>
                        <span class="font-label-caps text-[9px] text-tertiary/90 font-bold">${top2 ? parseFloat(top2.total_time || 0).toFixed(3) + 's' : ''}</span>
                        <span class="font-label-caps text-[9px] text-secondary font-black bg-secondary/20 px-2 py-0.5 rounded-full mt-1">2ND</span>
                    </div>

                    <!-- 1st Place -->
                    <div class="glass-card rounded-2xl p-3 border-2 border-tertiary bg-tertiary/10 text-center flex flex-col items-center justify-end h-36 shadow-[0_0_20px_rgba(244,191,50,0.3)] relative -top-1">
                        <span class="material-symbols-outlined text-tertiary text-4xl animate-bounce">workspace_premium</span>
                        <span class="font-display text-sm font-black text-white truncate max-w-full px-1">${escapeHtml(top1?.user_name || '1st Winner')}</span>
                        <span class="font-label-caps text-xs text-mint font-black">${top1 ? (top1.score ?? top1.total_correct_answer ?? 0) + ' Marks' : '-'}</span>
                        <span class="font-label-caps text-[10px] text-tertiary font-bold">${top1 ? parseFloat(top1.total_time || 0).toFixed(3) + 's' : ''}</span>
                        <span class="font-label-caps text-[10px] text-black bg-tertiary px-2.5 py-0.5 rounded-full font-black mt-1">1ST WINNER</span>
                    </div>

                    <!-- 3rd Place -->
                    <div class="glass-card rounded-2xl p-2.5 border border-amber-600/30 bg-amber-600/5 text-center flex flex-col items-center justify-end shadow-lg">
                        <span class="material-symbols-outlined text-amber-500 text-xl">military_tech</span>
                        <span class="font-display text-xs font-bold text-white truncate max-w-full px-1">${escapeHtml(top3?.user_name || '3rd Place')}</span>
                        <span class="font-label-caps text-[10px] text-mint font-bold">${top3 ? (top3.score ?? top3.total_correct_answer ?? 0) + ' Marks' : '-'}</span>
                        <span class="font-label-caps text-[9px] text-tertiary/90 font-bold">${top3 ? parseFloat(top3.total_time || 0).toFixed(3) + 's' : ''}</span>
                        <span class="font-label-caps text-[9px] text-amber-500 font-black bg-amber-500/20 px-2 py-0.5 rounded-full mt-1">3RD</span>
                    </div>
                `;
            }

            if (listContainer) {
                listContainer.innerHTML = '';
                if (!winners || winners.length === 0) {
                    listContainer.innerHTML = '<div class="p-4 text-center text-on-surface-variant text-xs">No participants found yet.</div>';
                    return;
                }

                winners.forEach((winner, idx) => {
                    const rank = winner.rank || (idx + 1);
                    const isUser = winner.is_current_user || false;
                    const row = document.createElement('div');
                    row.className = `glass-card rounded-xl p-3 border ${isUser ? 'border-tertiary/60 bg-tertiary/10' : 'border-white/10'} flex items-center justify-between gap-2`;

                    row.innerHTML = `
                        <div class="flex items-center gap-3 min-w-0">
                            <span class="font-display text-xs font-black ${rank <= 3 ? 'text-tertiary' : 'text-on-surface-variant'} w-6 text-center shrink-0">#${rank}</span>
                            <div class="min-w-0">
                                <p class="font-headline-sm text-xs font-bold text-white truncate">${escapeHtml(winner.user_name || 'Player')}</p>
                                <p class="text-[10px] text-on-surface-variant">${winner.score ?? winner.total_correct_answer ?? 0} Correct • <span class="text-tertiary font-semibold">${parseFloat(winner.total_time || 0).toFixed(3)}s</span></p>
                            </div>
                        </div>
                        <div class="text-right shrink-0">
                            <span class="font-label-caps text-[10px] ${rank <= 3 ? 'text-mint font-bold' : 'text-on-surface-variant'}">+${winner.reward || 0} Marks</span>
                        </div>
                    `;
                    listContainer.appendChild(row);
                });
            }
        }

        function showFffLeaderboardError(msg) {
            const podium = document.getElementById('fff-podium-container');
            const list = document.getElementById('fff-leaderboard-list');
            if (podium) podium.innerHTML = `<div class="p-4 text-center text-error text-xs col-span-3">${escapeHtml(msg)}</div>`;
            if (list) list.innerHTML = '';
        }

        function escapeHtml(str) {
            return String(str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }
    })();
</script>
