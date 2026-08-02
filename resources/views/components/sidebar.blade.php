@php
    /**
     * Renders config/menu.php. Active state is resolved server-side with
     * request()->routeIs(), so nested routes (staff.create, staff.edit)
     * keep their parent item highlighted — the theme's own JS only does
     * exact URL matching and cannot do this.
     */
    $menu = config('menu', []);

    /**
     * A missing permission means "always visible". An array means "any of
     * these", which is what lets a section caption disappear when the role
     * holds none of the items beneath it.
     */
    $allowed = static function (array $item): bool {
        $permission = $item['permission'] ?? null;

        if (blank($permission)) {
            return true;
        }

        $user = auth()->user();

        if (! $user) {
            return false;
        }

        return is_array($permission)
            ? $user->canAny($permission)
            : $user->can($permission);
    };

    $isActive = static function (array $item): bool {
        $pattern = $item['active'] ?? null;

        return filled($pattern) && request()->routeIs($pattern);
    };
@endphp

<nav class="pc-sidebar">
    <div class="navbar-wrapper">

        <div class="m-header flex items-center py-4 px-6 h-header-height">
            <a href="{{ route('dashboard') }}" class="b-brand flex items-center gap-3">
                <img src="{{ asset('assets/images/logo-dark.svg') }}" class="img-fluid logo logo-lg" alt="{{ config('app.name') }}" />
                <img src="{{ asset('assets/images/favicon.svg') }}" class="img-fluid logo logo-sm" alt="{{ config('app.name') }}" />
            </a>
        </div>

        <div class="navbar-content h-[calc(100vh_-_74px)] py-2.5">
            <ul class="pc-navbar">

                @foreach ($menu as $item)

                    @continue(! $allowed($item))

                    @if (isset($item['caption']))
                        <li class="pc-item pc-caption">
                            <label>{{ $item['caption'] }}</label>
                        </li>
                        @continue
                    @endif

                    @php
                        $children = collect($item['children'] ?? [])->filter($allowed);
                        $active = $isActive($item) || $children->contains($isActive);
                    @endphp

                    @if ($children->isNotEmpty())

                        <li @class(['pc-item', 'pc-hasmenu', 'pc-trigger active' => $active])>
                            <a href="#!" class="pc-link">
                                <span class="pc-micon"><i class="{{ $item['icon'] ?? 'ti ti-circle' }}"></i></span>
                                <span class="pc-mtext">{{ $item['label'] }}</span>
                                <span class="pc-arrow"><i class="ti ti-chevron-right"></i></span>
                            </a>
                            <ul class="pc-submenu" @style(['display: block' => $active])>
                                @foreach ($children as $child)
                                    <li @class(['pc-item', 'active' => $isActive($child)])>
                                        <a class="pc-link" href="{{ route($child['route']) }}">{{ $child['label'] }}</a>
                                    </li>
                                @endforeach
                            </ul>
                        </li>

                    @else

                        <li @class(['pc-item', 'active' => $active])>
                            <a href="{{ route($item['route']) }}" class="pc-link">
                                <span class="pc-micon"><i class="{{ $item['icon'] ?? 'ti ti-circle' }}"></i></span>
                                <span class="pc-mtext">{{ $item['label'] }}</span>
                            </a>
                        </li>

                    @endif

                @endforeach

            </ul>
        </div>

    </div>
</nav>
