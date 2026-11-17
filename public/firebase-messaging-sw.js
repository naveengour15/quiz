importScripts('https://www.gstatic.com/firebasejs/9.23.0/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/9.23.0/firebase-messaging-compat.js');

const firebaseConfig = {
    apiKey: "AIzaSyCuPnEty4CdFAA6ueQSqsTKpn8GYfmGr0c",
    authDomain: "fsrquiz.firebaseapp.com",
    projectId: "fsrquiz",
    storageBucket: "fsrquiz.firebasestorage.app",
    messagingSenderId: "145585273968",
    appId: "1:145585273968:android:a71813c8159d0c8f2508b5"
};

firebase.initializeApp(firebaseConfig);
const messaging = firebase.messaging();

messaging.onBackgroundMessage((payload) => {
    console.log('[firebase-messaging-sw.js] Received background message:', payload);
    const title = payload.notification?.title || payload.data?.title || '⚡ FSR Quiz Alert';
    const body = payload.notification?.body || payload.data?.body || 'Quiz सिर्फ 5 मिनट में शुरू हो रहा है!';
    const actionUrl = payload.data?.action_url || payload.fcmOptions?.link || payload.data?.click_action || '/fsrapp/home';

    const options = {
        body: body,
        icon: '/assets/images/logo.png',
        badge: '/assets/images/logo.png',
        data: {
            ...payload.data,
            action_url: actionUrl
        },
        actions: [
            {
                action: 'join_now',
                title: '🚀 Join Now'
            }
        ],
        vibrate: [200, 100, 200],
        requireInteraction: true
    };
    self.registration.showNotification(title, options);
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const actionUrl = event.notification.data?.action_url || event.notification.data?.click_action || '/fsrapp/home';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
            for (let client of clientList) {
                if (client.url.includes(actionUrl) && 'focus' in client) {
                    return client.focus();
                }
            }
            if (clients.openWindow) {
                return clients.openWindow(actionUrl);
            }
        })
    );
});
