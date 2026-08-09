@props([
    'date' => null,
    'showDate' => true,
])

{{--
    Follow-up urgency, derived from the date at render time rather than
    stored — a row written yesterday as "Upcoming" reads as "Overdue" today
    without anything having to rewrite it.

    Renders nothing when no follow-up is scheduled: absence of a date is not
    an urgency level.
--}}

@php
    $urgency = App\Enums\FollowUpUrgency::forDate($date);
@endphp

@if ($urgency)
    <span class="followup-badge">
        <span {{ $attributes->class(['badge', $urgency->badgeClass()]) }}>{{ $urgency->label() }}</span>

        @if ($showDate)
            <span class="followup-date">{{ $date->format('d-M-Y') }}</span>
        @endif
    </span>
@else
    <span class="text-muted">—</span>
@endif
