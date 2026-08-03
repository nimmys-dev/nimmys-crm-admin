@props([
    'name' => '',
    'url' => null,
    'size' => null,
])

{{--
    Photo when there is one, initial when there is not. StaffPhotoService::url()
    returns null for a missing file, so a deleted upload degrades to the
    initial rather than a broken image.
--}}

@php
    $classes = ['staff-avatar', 'staff-avatar-lg' => $size === 'lg'];
    $initial = Str::of($name)->trim()->substr(0, 1)->upper();
@endphp

@if ($url)
    <img src="{{ $url }}" alt="{{ $name }}" {{ $attributes->class($classes) }} />
@else
    <span {{ $attributes->class($classes) }} aria-hidden="true">{{ $initial }}</span>
@endif
