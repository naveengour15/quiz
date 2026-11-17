<template id="tpl-quiz-test">
  <main class="min-h-screen px-container-margin pt-8 pb-8 flex flex-col items-center justify-center text-center kbc-stage">
    <div class="relative z-[1] flex flex-col items-center w-full">
      <h2 class="font-headline-lg text-headline-lg mb-4">Webapp Quiz Broadcast Test</h2>
      <p class="text-on-surface-variant mb-6">This SPA component subscribes to PrivateChannel <code>quiz.{id}</code> and logs incoming events.</p>

      <div class="mb-4">
        <button id="web-send-test" class="px-4 py-2 bg-primary rounded text-white">Send test broadcast</button>
      </div>

      <div id="web-log" class="w-full max-w-2xl text-left bg-black/30 p-4 rounded">
        <div id="web-log-entries">No events yet.</div>
      </div>
    </div>
  </main>
</template>

<script>
route('/quiz/test/:id', async (app, params) => {
  const quizId = params.id;
  app.innerHTML = qs('#tpl-quiz-test').innerHTML;

  const logEntries = qs('#web-log-entries');
  function appendLog(text) {
    const el = document.createElement('div');
    el.className = 'py-1 border-b border-white/5 text-sm';
    el.textContent = `[${new Date().toLocaleTimeString()}] ${text}`;
    if (logEntries.innerText === 'No events yet.') logEntries.innerText = '';
    logEntries.prepend(el);
  }

  appendLog('Attempting to subscribe to PrivateChannel quiz.' + quizId);

  try {
    if (window.Echo && typeof window.Echo.private === 'function') {
      window.Echo.private(`quiz.${quizId}`).listen('TestBroadcast', (e) => {
        appendLog('Received TestBroadcast: ' + (e.message || JSON.stringify(e)));
      }).error((err) => {
        appendLog('Echo subscribe error: ' + err);
      });
      appendLog('Subscribed (waiting for events)...');
    } else {
      appendLog('Echo not available on this page. Make sure frontend assets are built and Echo initialized.');
    }
  } catch (e) {
    appendLog('Subscribe exception: ' + e.message);
  }

  qs('#web-send-test').addEventListener('click', async () => {
    appendLog('Sending test broadcast request...');
    try {
      const resp = await fetch(`/fsrapp/quiz/test/broadcast/${quizId}`);
      const data = await resp.json();
      appendLog('Server responded: ' + JSON.stringify(data));
    } catch (err) {
      appendLog('Error sending test broadcast: ' + err.message);
    }
  });
});
</script>
