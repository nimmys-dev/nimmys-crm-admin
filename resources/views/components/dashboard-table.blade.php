@props([
    'headers' => [],
    'rows' => null,
    'emptyMessage' => 'Nothing to show yet.',
    'emptyIcon' => 'ti ti-database-off',
])

{{--
    Compact table for a dashboard panel. Unlike <x-datatable> it carries no
    sorting or pagination — a dashboard shows a fixed top-N, and offering
    controls that do not work would be worse than offering none.

    A header may be a plain label or ['label' => …, 'class' => …].
    Pass the collection as :rows so the empty state is driven by the data
    rather than by whether the slot happens to be blank.
--}}

@php
    $headers = collect($headers)->map(
        fn ($header) => is_array($header)
            ? $header + ['label' => '', 'class' => null]
            : ['label' => $header, 'class' => null]
    );

    $isEmpty = $rows instanceof \Countable || $rows instanceof \Illuminate\Support\Collection
        ? count($rows) === 0
        : blank(trim($slot));
@endphp

@if ($isEmpty)
    <x-empty-state :message="$emptyMessage" :icon="$emptyIcon" />
@else
    <div class="table-responsive">
        <table {{ $attributes->class(['table', 'table-hover', 'dashboard-table']) }}>
            @if ($headers->isNotEmpty())
                <thead>
                    <tr>
                        @foreach ($headers as $header)
                            <th scope="col" @class([$header['class']])>
                                @if (filled($header['label']))
                                    {{ $header['label'] }}
                                @else
                                    <span class="sr-only">Details</span>
                                @endif
                            </th>
                        @endforeach
                    </tr>
                </thead>
            @endif

            <tbody>
                {{ $slot }}
            </tbody>
        </table>
    </div>
@endif
