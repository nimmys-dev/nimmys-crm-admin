@props([
    'title' => null,
    'breadcrumbs' => [],
])

@if (filled($title) || filled($breadcrumbs) || filled(trim($slot)))
    <div class="page-header">
        <div class="page-block">

            <div class="page-header-title">
                <h5 class="mb-0 font-medium">{{ $title }}</h5>
            </div>

            <x-breadcrumb :items="$breadcrumbs" />

            @if (filled(trim($slot)))
                <div class="flex items-center gap-3">
                    {{ $slot }}
                </div>
            @endif

        </div>
    </div>
@endif
