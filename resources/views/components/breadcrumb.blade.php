@props(['items' => []])

@php
    /**
     * $items is a list of ['label' => string, 'route' => ?string].
     * The final entry is always rendered as plain text (current page).
     */
    $items = collect($items)->filter(fn ($item) => filled($item['label'] ?? null))->values();
@endphp

@if ($items->isNotEmpty())
    <ul class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('dashboard') }}">Home</a>
        </li>

        @foreach ($items as $item)
            @if ($loop->last || blank($item['route'] ?? null))
                <li class="breadcrumb-item" aria-current="page">{{ $item['label'] }}</li>
            @else
                <li class="breadcrumb-item">
                    <a href="{{ route($item['route']) }}">{{ $item['label'] }}</a>
                </li>
            @endif
        @endforeach
    </ul>
@endif
