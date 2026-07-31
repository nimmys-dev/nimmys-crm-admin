@props([
    'variant' => 'primary',
    'size' => null,
    'href' => null,
    'type' => 'button',
    'icon' => null,
])

@php
    $classes = array_filter([
        'btn',
        'btn-' . $variant,
        $size ? 'btn-' . $size : null,
        'inline-flex items-center justify-center gap-2',
    ]);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>
        @if ($icon)
            <i class="{{ $icon }}"></i>
        @endif
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->class($classes) }}>
        @if ($icon)
            <i class="{{ $icon }}"></i>
        @endif
        {{ $slot }}
    </button>
@endif
