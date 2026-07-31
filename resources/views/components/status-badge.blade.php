@props([
    'status' => null,
    'label' => null,
])

@php
    /**
     * Maps a domain status onto a theme badge colour. Unknown values fall
     * back to a neutral badge rather than rendering nothing.
     */
    $key = Str::of($status)->lower()->replace([' ', '-'], '_')->toString();

    $variants = [
        'active' => 'bg-success-500 text-white',
        'completed' => 'bg-success-500 text-white',
        'won' => 'bg-success-500 text-white',
        'in_progress' => 'bg-primary-500 text-white',
        'new' => 'bg-primary-500 text-white',
        'pending' => 'bg-warning-500 text-white',
        'on_hold' => 'bg-warning-500 text-white',
        'inactive' => 'bg-secondary-500 text-white',
        'cancelled' => 'bg-danger-500 text-white',
        'lost' => 'bg-danger-500 text-white',
        'overdue' => 'bg-danger-500 text-white',
    ];

    $variant = $variants[$key] ?? 'bg-secondary-500 text-white';
@endphp

<span {{ $attributes->class(['badge', $variant]) }}>
    {{ $label ?? Str::headline($status ?? 'Unknown') }}
</span>
