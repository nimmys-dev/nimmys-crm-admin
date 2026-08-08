@props([
    'title',
    'subtitle' => null,
    'icon' => null,
    'actionLabel' => null,
    'actionHref' => null,
])

{{--
    Card wrapper for a dashboard panel: title, optional subtitle, and an
    optional "view all" link in the header.

    <x-dashboard-widget title="Recent employees" action-label="View all"
                        :action-href="route('staff.index')">
        …
    </x-dashboard-widget>
--}}

<div {{ $attributes->class('card dashboard-widget') }}>

    <div class="card-header flex items-center justify-between gap-3 flex-wrap">
        <div class="flex items-center gap-2 min-w-0">
            @if ($icon)
                <i class="{{ $icon }} text-muted" aria-hidden="true"></i>
            @endif

            <div class="min-w-0">
                <h5>{{ $title }}</h5>
                @if ($subtitle)
                    <p class="m-0 text-muted text-sm">{{ $subtitle }}</p>
                @endif
            </div>
        </div>

        @if ($actionLabel && $actionHref)
            <a href="{{ $actionHref }}" class="widget-action">
                {{ $actionLabel }}
                <i class="ti ti-chevron-right" aria-hidden="true"></i>
            </a>
        @endif
    </div>

    <div class="card-body">
        {{ $slot }}
    </div>

</div>
