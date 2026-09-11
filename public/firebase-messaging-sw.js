importScripts(
    'https://www.gstatic.com/firebasejs/11.6.0/firebase-app-compat.js'
);

importScripts(
    'https://www.gstatic.com/firebasejs/11.6.0/firebase-messaging-compat.js'
);

firebase.initializeApp({
    apiKey: "AIzaSyApc_0E83QYxjn4QiFLMoaOh8DHZIVhWAo",
    authDomain: "nimmyscrm.firebaseapp.com",
    projectId: "nimmyscrm",
    storageBucket: "nimmyscrm.firebasestorage.app",
    messagingSenderId: "44488471486",
    appId: "1:44488471486:web:ec579ba2f62de86499bfef"
});

const messaging = firebase.messaging();

messaging.onBackgroundMessage(function(payload) {

    console.log('Background FCM:', payload);

    self.registration.showNotification(
        payload.notification?.title || 'Nimmys CRM',
        {
            body: payload.notification?.body || '',
            icon: '/favicon.ico',
            data: payload.data || {}
        }
    );

});