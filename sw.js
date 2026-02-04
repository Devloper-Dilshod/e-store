self.addEventListener('push', function (event) {
    if (event.data) {
        const data = event.data.json();
        const options = {
            body: data.body,
            icon: data.icon || 'assets/images/logo.png',
            badge: 'assets/images/badge.png',
            data: {
                url: data.url
            },
            vibrate: [100, 50, 100],
            actions: [
                { action: 'view', title: 'Ko\'rish' }
            ]
        };
        event.waitUntil(
            self.registration.showNotification(data.title, options)
        );
    }
});

self.addEventListener('notificationclick', function (event) {
    event.notification.close();
    if (event.action === 'view' || !event.action) {
        event.waitUntil(
            clients.openWindow(event.notification.data.url)
        );
    }
});
