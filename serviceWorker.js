self.addEventListener('push', function (event) {
    if (!(self.Notification && self.Notification.permission === 'granted')) {
        return;
    }

    const sendNotification = function (body) {
        body = JSON.parse(body);

        if (!body.title) {
            return (new Promise(function (resolve, reject) {
                // you can do some staff here
                resolve();
            }));
        }
        return self.registration.showNotification(body.title, body);
    };

    if (event.data) {
        const message = event.data.text();
        event.waitUntil(sendNotification(message));
    }
});

self.addEventListener('notificationclick', function(e) {
    var notification = e.notification;
    if (notification.data && notification.data.click_url)
        clients.openWindow(notification.data.click_url);
    notification.close();
});
