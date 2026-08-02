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

    @include('partials.scripts')

</body>

</html>
