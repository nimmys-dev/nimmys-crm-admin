@props([
    'state' => false,
    'on' => 'Enabled',
    'off' => 'Disabled',
])

{{--
    Badge for a boolean setting. Semantic colour, not the brand accent, so
    on/off reads at a glance in a dense listing.
--}}

<span {{ $attributes->class(['badge', $state ? 'badge-on' : 'badge-off']) }}>
    {{ $state ? $on : $off }}
</span>
