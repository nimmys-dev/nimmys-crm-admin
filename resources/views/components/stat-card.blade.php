@props([
    'label',
    'value' => null,
    'icon' => 'ti ti-chart-bar',
    'meta' => null,
    'href' => null,
    'tone' => 'default',
])

{{--
    Single figure with a label and an icon.

    A null $value renders an em dash rather than 0, so "not measured yet"
    stays visually distinct from "measured, and the answer is zero" — which
    matters while the Lead and Task modules are still placeholders.

    $tone adds a semantic accent (warning, success) for figures that need
    attention, kept separate from the brand colour.
--}}

@php
    $isLink = filled($href);
    $tag = $isLink ? 'a' : 'div';
@endphp

<{{ $tag }}
    @if ($isLink) href="{{ $href }}" @endif
    {{ $attributes->class(['card', 'stat-card', 'stat-card-link' => $isLink, 'stat-card-'.$tone => $tone !== 'default']) }}
>
    <div class="card-body">
        <div class="flex items-center justify-between gap-3">
            <div class="min-w-0">
                <p class="stat-tile-label">{{ $label }}</p>
                <h3 class="stat-tile-value">{{ $value ?? '—' }}</h3>

                @if ($meta)
                    <p class="stat-card-meta">{{ $meta }}</p>
                @endif
            </div>

            <span class="stat-card-icon" aria-hidden="true">
                <i class="{{ $icon }}"></i>
            </span>
        </div>
    </div>
</{{ $tag }}>
