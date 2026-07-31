@props([
    'headers' => [],
    'rows' => null,
    'emptyMessage' => 'Nothing here yet.',
    'emptyIcon' => 'ti ti-database-off',
])

{{--
    Usage:
    <x-datatable :headers="['Name', 'Email', 'Status', '']">
        @forelse ($shops as $shop)
            <tr>…</tr>
        @empty
        @endforelse
    </x-datatable>

    Pass an empty $slot to get the empty state. $rows is optional: when a
    Paginator is passed, its links render under the table automatically.
--}}

@php
    $hasRows = filled(trim($slot));
@endphp

<div class="table-responsive">
    <table {{ $attributes->class(['table', 'table-hover']) }}>
        @if (filled($headers))
            <thead>
                <tr>
                    @foreach ($headers as $header)
                        <th scope="col">{{ $header }}</th>
                    @endforeach
                </tr>
            </thead>
        @endif

        @if ($hasRows)
            <tbody>
                {{ $slot }}
            </tbody>
        @endif
    </table>
</div>

@unless ($hasRows)
    <div class="empty-state">
        <i class="{{ $emptyIcon }}"></i>
        <p>{{ $emptyMessage }}</p>
    </div>
@endunless

@if ($rows instanceof \Illuminate\Contracts\Pagination\Paginator && $rows->hasPages())
    <div class="mt-4">
        {{ $rows->withQueryString()->links() }}
    </div>
@endif
