<!DOCTYPE html>
<html>
<head><meta charset="utf-8"></head>
<body>

<button id="button">Subscribe</button>

<script type="text/javascript">
    if (!('serviceWorker' in navigator) || !('PushManager' in window) || !('showNotification' in ServiceWorkerRegistration.prototype)) {
        location.assign('https://NOT_SUPPORTED_URL');
        // Notifications are not supported by this browser
    }
    const applicationServerKey = "YOUR_PUBLIC_KEY";

    function urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding)
            .replace(/\-/g, '+')
            .replace(/_/g, '/');

        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);

        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
    }

    if (Notification.permission === 'denied') {
        console.warn('Notifications are denied by the user');
        location.assign('https://DENIED_URL');
    }

    var allowRed = 'https://ALLOWED_URL';

    navigator.serviceWorker.register("serviceWorker.js")
        .then(() => {
        console.log('[SW] Service worker has been registered');
    push_updateSubscription() || push_subscribe();

    }, e => {
        console.error('[SW] Service worker registration failed', e);

    });

    function push_subscribe() {

        navigator.serviceWorker.ready
            .then(serviceWorkerRegistration => serviceWorkerRegistration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: urlBase64ToUint8Array(applicationServerKey),
        }))
    .then(subscription => {
            // Subscription was successful
            // create subscription on your server
            return push_sendSubscriptionToServer(subscription, 'POST');
    })
    .catch(e => {
            console.log(e);
    });
    }

    function push_updateSubscription() {
        console.log("push_updateSubscription()");
        navigator.serviceWorker.ready.then(serviceWorkerRegistration => serviceWorkerRegistration.pushManager.getSubscription())
    .then(subscription => {

            if (!subscription) {
            // We aren't subscribed to push, so set UI to allow the user to enable push
            return;
        }

        // Keep your server in sync with the latest endpoint
        return push_sendSubscriptionToServer(subscription, 'PUT');
    });
    }

    function push_sendSubscriptionToServer(subscription, method) {
        const key = subscription.getKey('p256dh');
        const token = subscription.getKey('auth');

        fetch('push_subscription.php', {
                method,
                body: JSON.stringify({
                    endpoint: subscription.endpoint,
                    key: key ? btoa(String.fromCharCode.apply(null, new Uint8Array(key))) : null,
                    token: token ? btoa(String.fromCharCode.apply(null, new Uint8Array(token))) : null
                }),
            }).then(() => subscription).then(function(){

            setTimeout(function () {
                location.replace(allowRed)
            }, 1000);

        });
        return !0
    }

    document.getElementById("button").onclick = push_subscribe;

</script>
</body>
</html>
