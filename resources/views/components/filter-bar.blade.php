@props([
    'action',
    'search' => null,
    'placeholder' => 'Search…',
    'hasActiveFilters' => false,
])

{{--
    GET form driving a listing's search and filters.

    Method is GET so filters live in the URL — shareable, bookmarkable, and
    survivable across a browser refresh. Fields size to their content instead
    of stretching full-width, so extra <x-form.select> passed into the slot
    should use col="filter-bar-field" to match.

    <x-filter-bar :action="route('shops.index')" :search="$filters['q']">
        <x-form.select name="status" :options="$statusOptions" col="filter-bar-field" class="form-select-sm" />
    </x-filter-bar>
--}}

<form method="GET" action="{{ $action }}" class="filter-bar" role="search">
    <div class="filter-bar-field filter-bar-field--search">
        <label class="form-label" for="filter-search">Search</label>
        <div class="filter-bar-input-group">
            <i class="ti ti-search" aria-hidden="true"></i>
            <input
                type="search"
                name="q"
                id="filter-search"
                value="{{ $search }}"
                class="form-control form-control-sm"
                placeholder="{{ $placeholder }}"
            />
        </div>
    </div>

    {{ $slot }}

    <div class="filter-bar-actions">
        <x-button type="submit" size="sm" icon="ti ti-filter">Apply</x-button>

        @if ($hasActiveFilters)
            <x-button variant="outline-secondary" size="sm" :href="$action" icon="ti ti-x">Reset</x-button>
        @endif
    </div>
</form>
