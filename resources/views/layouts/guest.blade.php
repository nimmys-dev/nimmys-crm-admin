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

    <div class="auth-main relative">
        <div class="auth-wrapper v1 flex items-center w-full h-full min-h-screen">
            <div class="auth-form flex items-center justify-center grow flex-col min-h-screen relative p-6">
                <div class="w-full max-w-[350px] relative">

                    <div class="auth-bg">
                        <span class="absolute top-[-100px] right-[-100px] w-[300px] h-[300px] block rounded-full bg-theme-bg-1 animate-[floating_7s_infinite]"></span>
                        <span class="absolute top-[150px] right-[-150px] w-5 h-5 block rounded-full bg-primary-500 animate-[floating_9s_infinite]"></span>
                        <span class="absolute left-[-150px] bottom-[150px] w-5 h-5 block rounded-full bg-theme-bg-1 animate-[floating_7s_infinite]"></span>
                        <span class="absolute left-[-100px] bottom-[-100px] w-[300px] h-[300px] block rounded-full bg-theme-bg-2 animate-[floating_9s_infinite]"></span>
                    </div>

                    <div class="card sm:my-12 w-full shadow-none">
                        <div class="card-body !p-10">
                            <div class="text-center mb-8">
                                <a href="{{ route('login') }}">
                                    <img src="{{ asset('assets/images/logo-dark.svg') }}" alt="{{ config('app.name') }}" class="mx-auto auth-logo" />
                                </a>
                            </div>

                            @yield('content')
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    @include('partials.scripts')

</body>

</html>
