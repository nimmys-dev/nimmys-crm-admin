@php
    $user = auth()->user();
    $name = $user?->name ?? 'Guest';
    $email = $user?->email;
@endphp

<header class="pc-header">
    <div class="header-wrapper flex max-sm:px-[15px] px-[25px] grow">

        <div class="me-auto pc-mob-drp">
            <ul class="inline-flex *:min-h-header-height *:inline-flex *:items-center">

                <li class="pc-h-item pc-sidebar-collapse max-lg:hidden lg:inline-flex">
                    <a href="#" class="pc-head-link ltr:!ml-0 rtl:!mr-0" id="sidebar-hide" aria-label="Toggle sidebar">
                        <i class="ti ti-menu-2"></i>
                    </a>
                </li>

                <li class="pc-h-item pc-sidebar-popup lg:hidden">
                    <a href="#" class="pc-head-link ltr:!ml-0 rtl:!mr-0" id="mobile-collapse" aria-label="Open menu">
                        <i class="ti ti-menu-2"></i>
                    </a>
                </li>

                <li class="dropdown pc-h-item">
                    <a class="pc-head-link dropdown-toggle me-0" data-pc-toggle="dropdown" href="#" role="button"
                        aria-haspopup="true" aria-expanded="false" aria-label="Search">
                        <i class="ti ti-search"></i>
                    </a>
                    <div class="dropdown-menu pc-h-dropdown drp-search">
                        <form class="px-2 py-1" action="{{ route('search') }}" method="GET" role="search">
                            <input type="search" name="q" value="{{ request('q') }}"
                                class="form-control !border-0 !shadow-none" placeholder="Search…" aria-label="Search" />
                        </form>
                    </div>
                </li>

            </ul>
        </div>

        <div class="ms-auto">
            <ul class="inline-flex *:min-h-header-height *:inline-flex *:items-center">

                {{-- Theme --}}
                <li class="dropdown pc-h-item">
                    <a class="pc-head-link dropdown-toggle me-0" data-pc-toggle="dropdown" href="#" role="button"
                        aria-haspopup="true" aria-expanded="false" aria-label="Change theme">
                        <i class="ti ti-sun"></i>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end pc-h-dropdown">
                        <a href="#!" class="dropdown-item" onclick="layout_change('light')">
                            <i class="ti ti-sun"></i><span>Light</span>
                        </a>
                        <a href="#!" class="dropdown-item" onclick="layout_change('dark')">
                            <i class="ti ti-moon"></i><span>Dark</span>
                        </a>
                    </div>
                </li>

                {{-- Notifications --}}
                <li class="dropdown pc-h-item">
                    <a class="pc-head-link dropdown-toggle me-0" data-pc-toggle="dropdown" href="#" role="button"
                        aria-haspopup="true" aria-expanded="false" aria-label="Notifications">
                        <i class="ti ti-bell"></i>
                    </a>
                    <div class="dropdown-menu dropdown-notification dropdown-menu-end pc-h-dropdown p-2">
                        <div class="dropdown-header flex items-center justify-between py-4 px-5">
                            <h5 class="m-0">Notifications</h5>
                        </div>
                        <div class="dropdown-body header-notification-scroll relative py-4 px-5"
                            style="max-height: calc(100vh - 215px)">
                            <div class="text-center py-8">
                                <i class="ti ti-bell-off text-[32px] text-muted"></i>
                                <p class="mb-0 mt-3 text-muted">You're all caught up.</p>
                            </div>
                        </div>
                    </div>
                </li>

                {{-- Profile --}}
                <li class="dropdown pc-h-item header-user-profile">
                    <a class="pc-head-link dropdown-toggle arrow-none me-0" data-pc-toggle="dropdown" href="#"
                        role="button" aria-haspopup="true" data-pc-auto-close="outside" aria-expanded="false"
                        aria-label="Account menu">
                        <i class="ti ti-user"></i>
                    </a>
                    <div class="dropdown-menu dropdown-user-profile dropdown-menu-end pc-h-dropdown p-2 overflow-hidden">

                        <div class="dropdown-header flex items-center justify-between py-4 px-5 bg-primary-500">
                            <div class="flex mb-1 items-center">
                                <div class="shrink-0">
                                    <span class="w-10 h-10 rounded-full bg-white/20 text-white flex items-center justify-center font-medium">
                                        {{ Str::of($name)->substr(0, 1)->upper() }}
                                    </span>
                                </div>
                                <div class="grow ms-3">
                                    <h6 class="mb-1 text-white">{{ $name }}</h6>
                                    @if ($email)
                                        <span class="text-white">{{ $email }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="dropdown-body py-4 px-5">
                            <a href="{{ route('profile.index') }}" class="dropdown-item">
                                <span><i class="ti ti-user me-2"></i><span>Profile</span></span>
                            </a>
                            <a href="{{ route('settings.index') }}" class="dropdown-item">
                                <span><i class="ti ti-settings me-2"></i><span>Settings</span></span>
                            </a>

                            <form method="POST" action="{{ route('logout') }}" class="grid my-3">
                                @csrf
                                <button type="submit" class="btn btn-primary flex items-center justify-center">
                                    <i class="ti ti-logout me-2"></i>Log out
                                </button>
                            </form>
                        </div>

                    </div>
                </li>

            </ul>
        </div>

    </div>
</header>
