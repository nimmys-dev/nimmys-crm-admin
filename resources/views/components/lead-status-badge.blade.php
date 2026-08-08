@props(['status'])

{{--
    Pipeline stage badge. Colour comes from the enum so the mapping lives
    with the domain rather than being restated in every template.
--}}

<span {{ $attributes->class(['badge', $status->badgeClass()]) }}>
    {{ $status->label() }}
</span>
