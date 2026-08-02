@props([
    'title' => null,
    'bodyClass' => 'card-body',
])

<div {{ $attributes->class('card') }}>

    @if (filled($title) || isset($actions))
        <div class="card-header flex items-center justify-between gap-3 flex-wrap">
            <h5>{{ $title }}</h5>

            @isset($actions)
                <div class="flex items-center gap-2">
                    {{ $actions }}
                </div>
            @endisset
        </div>
    @endif

    <div class="{{ $bodyClass }}">
        {{ $slot }}
    </div>

    @isset($footer)
        <div class="card-footer">
            {{ $footer }}
        </div>
    @endisset

</div>
