@props([
    'headers' => [],
    'rows' => null,
    'sort' => null,
    'direction' => 'asc',
    'emptyMessage' => 'Nothing here yet.',
    'emptyIcon' => 'ti ti-database-off',
])

{{--
    Listing table with optional sortable headers, empty state and pagination.

    A header is either a plain label, or an array:
        ['label' => 'Name', 'sort' => 'name']   sortable column
        ['label' => '', 'class' => 'w-px']      plain, with extra classes

    <x-datatable
        :headers="[['label' => 'Code', 'sort' => 'employee_code'], 'Role']"
        :sort="$filters['sort']"
        :direction="$filters['direction']"
        :rows="$staff"
    >
        @foreach ($staff as $member) <tr>…</tr> @endforeach
    </x-datatable>

    Pass a Paginator as :rows and its links render underneath. An empty slot
    produces the empty state instead of a bare table.
--}}

@php
    $hasRows = filled(trim($slot));

    $headers = collect($headers)->map(function ($header) {
        return is_array($header)
            ? $header + ['label' => '', 'sort' => null, 'class' => null]
            : ['label' => $header, 'sort' => null, 'class' => null];
    });
@endphp

<div class="table-responsive">
    <table {{ $attributes->class(['table', 'table-hover']) }}>
        @if ($headers->isNotEmpty())
            <thead>
                <tr>
                    @foreach ($headers as $header)
                        <th scope="col" @class([$header['class']])>
                            @if ($header['sort'])
                                <x-sort-link
                                    :column="$header['sort']"
                                    :label="$header['label']"
                                    :sort="$sort"
                                    :direction="$direction"
                                />
                            @elseif (filled($header['label']))
                                {{ $header['label'] }}
                            @else
                                <span class="sr-only">Actions</span>
                            @endif
                        </th>
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

@if ($rows instanceof \Illuminate\Contracts\Pagination\Paginator)
    {{ $rows->links() }}
@endif
