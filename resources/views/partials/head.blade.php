<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui" />
<meta name="csrf-token" content="{{ csrf_token() }}" />

<title>@yield('title', 'Dashboard') &middot; {{ config('app.name') }}</title>

<link rel="icon" href="{{ asset('assets/images/favicon.svg') }}" type="image/svg+xml" />

{{-- Apply the saved theme before paint so dark mode never flashes white. --}}
<script>
    (function () {
        try {
            var saved = localStorage.getItem('crm-theme') || 'light';
            document.documentElement.setAttribute('data-pc-theme', saved);
        } catch (e) {}
    })();
</script>

<link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;500;600&display=swap" rel="stylesheet" />
<link rel="stylesheet" href="{{ asset('assets/fonts/tabler-icons.min.css') }}" />
<link rel="stylesheet" href="{{ asset('assets/css/style.css') }}" id="main-style-link" />
{{-- Supplies alerts, modals and validation states, which the theme's purged Tailwind build omits. --}}
<link rel="stylesheet" href="{{ asset('assets/css/crm.css') }}" />

@stack('styles')
