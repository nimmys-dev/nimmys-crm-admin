@props(['status'])

{{-- Colour and icon come from the enum, so the mapping lives with the domain. --}}

<span {{ $attributes->class(['badge', $status->badgeClass()]) }}>
    <i class="{{ $status->icon() }}" aria-hidden="true"></i>
    {{ $status->label() }}
</span>
