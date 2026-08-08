@props([
    'label' => 'Chart',
    'note' => 'Available once this module is built.',
    'height' => '180px',
])

{{--
    Reserves the space a future chart will occupy, so adding the real chart
    later does not reflow the dashboard around it.

    Deliberately not a fake chart with invented data: a placeholder that
    looks like a real reading is worse than an obvious gap.
--}}

<div
    {{ $attributes->class('chart-placeholder') }}
    style="min-height: {{ $height }}"
    role="img"
    aria-label="{{ $label }} — no data yet"
>
    <div class="chart-placeholder-bars" aria-hidden="true">
        @foreach ([38, 62, 45, 78, 55, 88, 48] as $barHeight)
            <span style="height: {{ $barHeight }}%"></span>
        @endforeach
    </div>

    <p class="chart-placeholder-note">{{ $note }}</p>
</div>
