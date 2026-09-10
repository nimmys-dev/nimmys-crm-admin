importScripts('https://www.gstatic.com/firebasejs/9.23.0/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/9.23.0/firebase-messaging-compat.js');

// നിങ്ങളുടെ Firebase Configuration നൽകുക
const firebaseConfig = {
    apiKey: "AIzaSyApc_0E83QYxjn4QiFLMoaOh8DHZIVhWAo",
    authDomain: "nimmyscrm.firebaseapp.com",
    projectId: "nimmyscrm",
    storageBucket: "nimmyscrm.firebasestorage.app",
    messagingSenderId: "44488471486",
    appId: "1:44488471486:web:ec579ba2f62de86499bfef",
    measurementId: "G-26Z7P0YDR"
};

firebase.initializeApp(firebaseConfig);

const messaging = firebase.messaging();

// Background-ൽ ഉള്ളപ്പോൾ Notification വരാൻ
messaging.onBackgroundMessage(function(payload) {
    console.log('[firebase-messaging-sw.js] Received background message ', payload);

    const notificationTitle = payload.notification.title;
    const notificationOptions = {
        body: payload.notification.body,
        icon: '/favicon.ico' // നിങ്ങളുടെ App Icon നൽകാം
    };

    self.registration.showNotification(notificationTitle, notificationOptions);
});