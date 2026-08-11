@props([
    'action',
    'search' => null,
    'placeholder' => 'Search…',
    'hasActiveFilters' => false,
])

{{--
    GET form driving a listing's search and filters.

    Method is GET so filters live in the URL — shareable, bookmarkable, and
    survivable across a browser refresh. Any extra <x-form.select> passed in
    the slot is submitted alongside the search box.

    <x-filter-bar :action="route('shops.index')" :search="$filters['q']">
        <x-form.select name="status" :options="$statusOptions" col="…" />
    </x-filter-bar>
--}}

<form method="GET" action="{{ $action }}" class="mb-6" role="search">
    <div class="grid grid-cols-12 gap-4 items-end">

        <div class="col-span-6 md:col-span-4">
            <label class="form-label" for="filter-search">Search</label>
            <input
                type="search"
                name="q"
                id="filter-search"
                value="{{ $search }}"
                class="form-control"
                placeholder="{{ $placeholder }}"
            />
        </div>

        {{ $slot }}

        <div class="col-span-12 md:col-span-auto flex items-center gap-2">
            <x-button type="submit" icon="ti ti-filter">Apply</x-button>

            @if ($hasActiveFilters)
                <x-button variant="outline-secondary" :href="$action" icon="ti ti-x">Reset</x-button>
            @endif
        </div>

    </div>
</form>
