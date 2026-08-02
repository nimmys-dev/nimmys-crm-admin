@props([
    'column',
    'label',
    'sort' => null,
    'direction' => 'asc',
])

@php
    /**
     * Column header that toggles sort direction while preserving every other
     * filter in the query string.
     */
    $isActive = $sort === $column;
    $nextDirection = $isActive && $direction === 'asc' ? 'desc' : 'asc';

    $icon = match (true) {
        ! $isActive => 'ti ti-arrows-sort',
        $direction === 'asc' => 'ti ti-sort-ascending',
        default => 'ti ti-sort-descending',
    };

    $url = request()->fullUrlWithQuery([
        'sort' => $column,
        'direction' => $nextDirection,
        'page' => null,
    ]);
@endphp

<a
    href="{{ $url }}"
    {{ $attributes->class(['inline-flex items-center gap-1', 'text-primary-500' => $isActive]) }}
    aria-sort="{{ $isActive ? ($direction === 'asc' ? 'ascending' : 'descending') : 'none' }}"
>
    <span>{{ $label }}</span>
    <i class="{{ $icon }} text-[14px]" aria-hidden="true"></i>
</a>
