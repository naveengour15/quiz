// Optional Echo initializer. This file attempts to initialize Laravel Echo
// only if pusher-js and laravel-echo are available in the runtime.
// To enable real-time broadcasts, install packages and import this file in your entry script.

try {
  // Only run if dependencies are present
  if (typeof window !== 'undefined' && !window.Echo) {
    // These require calls will fail if packages are not installed; keep in try/catch.
    // To enable: npm install laravel-echo pusher-js
    const Echo = require('laravel-echo').default || require('laravel-echo');
    window.Pusher = require('pusher-js');

    // Configure using environment variables provided to Vite (MIX/VITE vars)
    const key = (import.meta && import.meta.env && (import.meta.env.VITE_PUSHER_APP_KEY || import.meta.env.MIX_PUSHER_APP_KEY)) || process.env.MIX_PUSHER_APP_KEY || process.env.VITE_PUSHER_APP_KEY;
    const cluster = (import.meta && import.meta.env && (import.meta.env.VITE_PUSHER_APP_CLUSTER || import.meta.env.MIX_PUSHER_APP_CLUSTER)) || process.env.MIX_PUSHER_APP_CLUSTER || process.env.VITE_PUSHER_APP_CLUSTER;

    if (!key) {
      console.warn('Echo init: Pusher key not found in env (VITE_PUSHER_APP_KEY / MIX_PUSHER_APP_KEY). Skipping Echo initialization.');
    } else {
      window.Echo = new Echo({
        broadcaster: 'pusher',
        key: key,
        cluster: cluster || 'mt1',
        forceTLS: true,
        // disable stats to reduce noise
        enabledTransports: ['ws', 'wss', 'xhr_polling']
      });
      console.info('Laravel Echo initialized');
    }
  }
} catch (e) {
  // no-op: dependencies may not be installed in this environment
  console.warn('Echo init failed (dependencies missing?):', e && e.message ? e.message : e);
}
