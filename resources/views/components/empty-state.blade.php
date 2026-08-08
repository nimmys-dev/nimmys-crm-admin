@props([
    'message' => 'Nothing here yet.',
    'icon' => 'ti ti-database-off',
])

{{--
    Shared empty state. Used by <x-datatable>, <x-dashboard-table> and any
    widget with nothing to show, so the "no data" treatment is defined once.
--}}

<div {{ $attributes->class('empty-state') }}>
    <i class="{{ $icon }}" aria-hidden="true"></i>
    <p>{{ filled(trim($slot)) ? $slot : $message }}</p>
</div>
