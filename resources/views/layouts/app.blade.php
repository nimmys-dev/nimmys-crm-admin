<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    @include('partials.head')
</head>

<body>

    <div class="loader-bg fixed inset-0 bg-white dark:bg-themedark-cardbg z-[1034]">
        <div class="loader-track h-[5px] w-full absolute top-0 overflow-hidden">
            <div class="loader-fill w-[300px] h-[5px] bg-primary-500 absolute top-0 left-0 animate-[hitZak_0.6s_ease-in-out_infinite_alternate]"></div>
        </div>
    </div>

    <x-sidebar />

    <x-navbar />

    <div class="pc-container">
        <div class="pc-content">

            <x-page-header :title="$pageTitle ?? null" :breadcrumbs="$breadcrumbs ?? []">
                @yield('page-actions')
            </x-page-header>

            <x-alerts />

            @yield('content')

        </div>
    </div>

    <x-footer />

    {{-- Modals render here so their forms are never nested inside a page form. --}}
    @stack('modals')

    @include('partials.scripts')
<script type="module">

import { initializeApp } from "https://www.gstatic.com/firebasejs/11.6.0/firebase-app.js";

import {
    getMessaging,
    getToken,
    onMessage
} from "https://www.gstatic.com/firebasejs/11.6.0/firebase-messaging.js";


const firebaseConfig = {
    apiKey: "AIzaSyApc_0E83QYxjn4QiFLMoaOh8DHZIVhWAo",
    authDomain: "nimmyscrm.firebaseapp.com",
    projectId: "nimmyscrm",
    storageBucket: "nimmyscrm.firebasestorage.app",
    messagingSenderId: "44488471486",
    appId: "1:44488471486:web:ec579ba2f62de86499bfef",
    measurementId: "G-26Z7P09YDR"
};


const app = initializeApp(firebaseConfig);
const messaging = getMessaging(app);

async function saveFcmToken() {
    // Service workers and browser notifications require HTTPS (localhost is
    // the only HTTP exception), so an HTTP production deployment cannot use FCM.
    if (!window.isSecureContext || !('serviceWorker' in navigator)) {
        console.warn('FCM requires HTTPS and service worker support.');
        return;
    }

    try {
        const permission = Notification.permission === 'default'
            ? await Notification.requestPermission()
            : Notification.permission;

        if (permission !== 'granted') {
            return;
        }

        const registration = await navigator.serviceWorker.register('/firebase-messaging-sw.js');
        const token = await getToken(messaging, {
            vapidKey: 'BNPK9GMRJweFOZ8d6zjoCCcBZUwTBREOXGVowj_xtEUgo1FTaiwkA9nu_fHO1UvAisAPsXWA1VNID-hG0-WjpuQ',
            serviceWorkerRegistration: registration,
        });

        if (!token) {
            return;
        }

        const response = await fetch('{{ route('firebase.token') }}', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ fcm_token: token }),
        });

        if (!response.ok) {
            throw new Error(`Unable to save FCM token (${response.status}).`);
        }
    } catch (error) {
        console.error('FCM token registration failed:', error);
    }
}

saveFcmToken();


onMessage(messaging, (payload) => {

    console.log('FCM foreground message:', payload);

    const title =
        payload.notification?.title || 'Nimmys CRM';

    const body =
        payload.notification?.body || '';


    if (Notification.permission === 'granted') {

        new Notification(title, {
            body: body,
            icon: '/favicon.ico'
        });

    }

});

</script>
</body>

</html>
