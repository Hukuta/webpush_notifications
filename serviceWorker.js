self.addEventListener('push', function (event) {
    if (!(self.Notification && self.Notification.permission === 'granted')) {
        return;
    }

    self.addEventListener('notificationclick', function(e) {
        var notification = e.notification;
        clients.openWindow(notification.data.click_url);
        notification.close();
    });

    const sendNotification = body => {
        // you could refresh a notification badge here with postMessage API
        // const title = "Web Push example";

        body = JSON.parse(body);

        return self.registration.showNotification(body.title, body);
    };

    if (event.data) {
        const message = event.data.text();
        event.waitUntil(sendNotification(message));
    }
});
