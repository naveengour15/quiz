<template id="tpl-quiz-waiting">
  <main class="min-h-screen px-container-margin pt-8 pb-8 flex flex-col items-center justify-center text-center kbc-stage">
    <div class="kbc-spotlight"></div><div class="kbc-stage-rings"><span></span><span></span><span></span><span></span></div>
    <div class="relative z-[1] w-full flex justify-end mb-2" id="waiting-mute-placeholder"></div>
    <div class="relative z-[1] flex flex-col items-center w-full">
      <div class="w-20 h-20 rounded-3xl bg-primary/20 flex items-center justify-center mb-6" id="waiting-icon-box">
        <span class="material-symbols-outlined text-primary text-4xl" id="waiting-icon">hourglass_top</span>
      </div>
      <h2 class="font-headline-lg text-headline-lg mb-2" id="waiting-title">Get Ready!</h2>
      <p class="text-on-surface-variant mb-2 px-4" id="waiting-desc">You're in. This quiz will start automatically when the timer finishes.</p>
      <p id="waiting-start-time-label" class="text-tertiary font-label-caps text-xs mb-6"></p>
      <div id="waiting-countdown" class="font-display text-display text-primary tabular-nums mb-10" style="font-variant-numeric: tabular-nums;">--:--:--</div>
      <button onclick="navigate('/quiz/list')" class="text-on-surface-variant font-action text-action">Back to Quizzes</button>
    </div>
  </main>
</template>

<script>
route('/quiz/waiting/:id', async (app, params) => {
  const quizId = params.id;
  const modeKey = `qx_wait_mode_${quizId}`;
  const reviewDone = localStorage.getItem(`qx_review_done_${quizId}`) === '1';
  const winnersDone = localStorage.getItem(`qx_winners_done_${quizId}`) === '1';
  const isWinnersMode = localStorage.getItem(modeKey) === 'winners' || reviewDone;

  await QuizMasterClock.syncStatus(quizId);

  try {
    if (window.Echo && typeof window.Echo.private === 'function') {
      window.Echo.private(`quiz.${quizId}`).listen('QuizStarted', (e) => {
        QuizMasterClock.updateServerTime(e.server_now_ms);
        QuizMasterClock.setQuizConfig(quizId, e);
        render();
      });
    }
  } catch (e) {}

  app.innerHTML = qs('#tpl-quiz-waiting').innerHTML;
  if (qs('#waiting-mute-placeholder')) qs('#waiting-mute-placeholder').outerHTML = kbcMuteBtnHtml();

  function fmt2(n) {
    return String(Math.max(n, 0)).padStart(2, '0');
  }

  let winnerApiFired = false;
  let winnersWaitSec = 30; // 30 second leaderboard wait timer
  let lastPendingQIndex = -1;
  let lastWarmupQIndex = -1;
  let lastWinnersAudioQuizId = -1;

  function render() {
    if (winnersDone) {
      clearInterval(timerHandle);
      clearInterval(secondDecrementTimer);
      navigate(`/quiz/winners/${quizId}`, true);
      return;
    }

    if (isWinnersMode) {
      if (lastWinnersAudioQuizId !== quizId) {
        lastWinnersAudioQuizId = quizId;
        Sound.stopBackground();
      }

      let lastPlayedWaitSec = -1;
      if (!winnerApiFired) {
        winnerApiFired = true;
        // Fire async hits to background result calculation & winner fetching
        api(`/fsrapp/quiz/get-winners/${quizId}`, { method: 'GET' }).catch(() => {});
        api(`/quiz/${quizId}/process-result`, { method: 'POST' }).catch(() => {});
      }

      const titleEl = qs('#waiting-title');
      const subEl = qs('#waiting-desc');
      const iconEl = qs('#waiting-icon');
      if (titleEl) titleEl.textContent = 'Calculating Results...';
      if (subEl) subEl.textContent = 'Tallying final scores & calculating winners in';
      if (iconEl) iconEl.textContent = 'emoji_events';

      const remainingSec = winnersWaitSec;
      const m = Math.floor(remainingSec / 60);
      const s = remainingSec % 60;
      const box = qs('#waiting-countdown');
      if (box) box.textContent = `${fmt2(m)}:${fmt2(s)}`;

      if (winnersWaitSec <= 2) {
        Sound.stopBackground();
      }

      if (winnersWaitSec <= 0) {
        clearInterval(timerHandle);
        clearInterval(secondDecrementTimer);
        Sound.stopBackground();
        localStorage.removeItem(modeKey);
        localStorage.setItem(`qx_winners_done_${quizId}`, '1');
        navigate(`/quiz/winners/${quizId}`, true);
      }
      return;
    }

    const state = QuizMasterClock.calculateState(quizId);

    if (state.phase === 'LEADERBOARD_WAIT') {
      if (lastWinnersAudioQuizId !== quizId) {
        lastWinnersAudioQuizId = quizId;
        Sound.stopBackground();
      }

      if (!winnerApiFired) {
        winnerApiFired = true;
        api(`/fsrapp/quiz/get-winners/${quizId}`, { method: 'GET' }).catch(() => {});
        api(`/quiz/${quizId}/process-result`, { method: 'POST' }).catch(() => {});
      }

      const titleEl = qs('#waiting-title');
      const subEl = qs('#waiting-desc');
      const iconEl = qs('#waiting-icon');
      if (titleEl) titleEl.textContent = 'Calculating Results...';
      if (subEl) subEl.textContent = 'Tallying final scores & calculating winners in';
      if (iconEl) iconEl.textContent = 'emoji_events';

      const remainingSec = Math.max(state.remainingSec || 0, winnersWaitSec);
      const m = Math.floor(remainingSec / 60);
      const s = remainingSec % 60;
      const box = qs('#waiting-countdown');
      if (box) box.textContent = `${fmt2(m)}:${fmt2(s)}`;

      if (remainingSec <= 0) {
        clearInterval(timerHandle);
        clearInterval(secondDecrementTimer);
        localStorage.removeItem(modeKey);
        localStorage.setItem(`qx_winners_done_${quizId}`, '1');
        navigate(`/quiz/winners/${quizId}`, true);
      }
      return;
    }

    if (state.phase === 'PENDING_WAIT') {
      if (lastPendingQIndex !== quizId) {
        lastPendingQIndex = quizId;
      }
      const titleEl = qs('#waiting-title');
      const subEl = qs('#waiting-desc');
      const iconEl = qs('#waiting-icon');
      if (titleEl) titleEl.textContent = 'Get Ready!';
      if (subEl) subEl.textContent = "You're in. This quiz will start automatically in";
      if (iconEl) iconEl.textContent = 'hourglass_top';

      const totalSec = state.remainingSec;
      const h = Math.floor(totalSec / 3600);
      const m = Math.floor((totalSec % 3600) / 60);
      const s = totalSec % 60;
      const box = qs('#waiting-countdown');
      if (box) box.textContent = h > 0 ? `${fmt2(h)}:${fmt2(m)}:${fmt2(s)}` : `${fmt2(m)}:${fmt2(s)}`;
      return;
    }

    if (state.phase === 'WARMUP_WAIT') {
      if (typeof lastWarmupQIndex !== 'undefined' && lastWarmupQIndex !== quizId) {
        lastWarmupQIndex = quizId;
        if (typeof Sound !== 'undefined' && Sound.startQuizSound) {
          Sound.startQuizSound();
        }
      }
      const titleEl = qs('#waiting-title');
      const subEl = qs('#waiting-desc');
      const iconEl = qs('#waiting-icon');
      if (titleEl) titleEl.textContent = 'Quiz Starting Soon!';
      if (subEl) subEl.textContent = 'Get ready! The first question will appear in';
      if (iconEl) iconEl.textContent = 'bolt';

      const totalSec = state.remainingSec;
      const m = Math.floor(totalSec / 60);
      const s = totalSec % 60;
      const box = qs('#waiting-countdown');
      if (box) box.textContent = `${fmt2(m)}:${fmt2(s)}`;
      return;
    }

    if (state.phase === 'QUESTION_HOLD' || state.phase === 'OPTIONS_ANSWER') {
      if (!reviewDone && !winnersDone && !isWinnersMode) {
        clearInterval(timerHandle);
        clearInterval(secondDecrementTimer);
        navigate(`/quiz/play/${quizId}`, true);
        return;
      }
    }

    if (state.phase === 'ANSWERSHEET_REVIEW') {
      if (!reviewDone && !winnersDone) {
        clearInterval(timerHandle);
        clearInterval(secondDecrementTimer);
        navigate(`/quiz/review/${quizId}`, true);
        return;
      }
    }

    if (state.phase === 'FINAL_WINNERS') {
      clearInterval(timerHandle);
      clearInterval(secondDecrementTimer);
      localStorage.removeItem(modeKey);
      localStorage.setItem(`qx_winners_done_${quizId}`, '1');
      navigate(`/quiz/winners/${quizId}`, true);
      return;
    }
  }

  render();
  const timerHandle = setInterval(render, 500);
  let counterTickPlayed = false;
  const secondDecrementTimer = setInterval(() => {
    winnersWaitSec = Math.max(winnersWaitSec - 1, 0);
    if (winnersWaitSec > 0 && !counterTickPlayed) {
      Sound.tick(winnersWaitSec <= 5);
      counterTickPlayed = true;
    }
  }, 1000);

  function stopOnNav() {
    if (currentPath() !== `/quiz/waiting/${quizId}`) {
      clearInterval(timerHandle);
      clearInterval(secondDecrementTimer);
      window.removeEventListener('hashchange', stopOnNav);
    }
  }
  window.addEventListener('hashchange', stopOnNav);
});
</script>
