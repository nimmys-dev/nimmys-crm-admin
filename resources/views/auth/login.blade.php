@extends('layouts.guest')

@section('title', 'Log in')

@section('content')

    <h4 class="text-center font-medium mb-4">Log in</h4>

    {{-- Field errors render inline below, so only flash messages belong here. --}}
    <x-alerts :show-errors="false" />

    <form id="loginForm" method="POST" action="{{ route('login.store') }}">
        @csrf
<input type="hidden" name="fcm_token" id="fcm_token">
        <div class="grid grid-cols-12 gap-4">
            <x-form.input
                name="email"
                type="email"
                label="Email address"
                col="col-span-12"
                autocomplete="username"
                autofocus
                required
            />

            <x-form.input
                name="password"
                type="password"
                label="Password"
                col="col-span-12"
                autocomplete="current-password"
                required
            />
        </div>


        <div class="flex mt-4 justify-between items-center flex-wrap">

            <div class="form-check">
                <input
                    class="form-check-input input-primary"
                    type="checkbox"
                    name="remember"
                    id="remember"
                    value="1"
                    @checked(old('remember'))
                />

                <label class="form-check-label text-muted" for="remember">
                    Remember me
                </label>
            </div>

            <div>
                <a href="{{ route('password.request') }}"
                class="text-primary text-decoration-none">
                    Forgot Password?
                </a>
            </div>

        </div>



        <div class="mt-4 text-center">
            <x-button type="submit" class="mx-auto">Log in</x-button>
        </div>
    </form>
<script type="module">
import { initializeApp } from "https://www.gstatic.com/firebasejs/11.6.0/firebase-app.js";
import { getMessaging, getToken } from "https://www.gstatic.com/firebasejs/11.6.0/firebase-messaging.js";

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

async function fetchToken() {
    try {
        const registration = await navigator.serviceWorker.register('/firebase-messaging-sw.js');
        const permission = await Notification.requestPermission();

        if (permission === 'granted') {
            const token = await getToken(messaging, {
                vapidKey: 'BNPK9GMRJweFOZ8d6zjoCCcBZUwTBREOXGVowj_xtEUgo1FTaiwkA9nu_fHO1UvAisAPsXWA1VNID-hG0-WjpuQ',
                serviceWorkerRegistration: registration
            });

            if (token) {
                document.getElementById('fcm_token').value = token;
                console.log('FCM Token generated:', token);
                return token;
            }
        }
    } catch (error) {
        console.error('FCM token error:', error);
    }
    return null;
}

// Page load ചെയ്യുമ്പോൾ തനിയെ Token Fetch ചെയ്യുന്നു
fetchToken();

// Form Submit ചെയ്യുമ്പോൾ Token നിർബന്ധമായും ഉറപ്പുവരുത്തുന്നു
document.getElementById('loginForm').addEventListener('submit', async function(e) {
    const tokenInput = document.getElementById('fcm_token');
    
    // ടോക്കൺ വന്നിട്ടില്ലെങ്കിൽ ഒരു തവണ കൂടി എടുക്കാൻ ശ്രമിക്കും
    if (!tokenInput.value) {
        e.preventDefault(); // Submit തടയുന്നു
        await fetchToken();
        this.submit(); // Token ലഭിച്ച ശേഷം submit ചെയ്യുന്നു
    }
});
</script>
@endsection
