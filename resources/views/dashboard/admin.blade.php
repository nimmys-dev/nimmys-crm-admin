@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')

    {{--
        Stat row. 12-column grid: one card per row on mobile, two on tablet,
        three from xl. The counts are tabular-figure aligned so the row reads
        as a set rather than five unrelated numbers.
    --}}
    <div class="grid grid-cols-12 gap-x-6">

        <div class="col-span-12 md:col-span-6 xl:col-span-4">
            <x-stat-card
                label="Total shops"
                :value="$stats['shops']"
                :meta="$stats['active_shops'] . ' active'"
                icon="ti ti-building-store"
                :href="route('shops.index')"
            />
        </div>

        <div class="col-span-12 md:col-span-6 xl:col-span-4">
            <x-stat-card
                label="Total managers"
                :value="$stats['managers']"
                icon="ti ti-user-shield"
                :href="route('staff.index', ['role' => \App\Enums\UserRole::Manager->value])"
            />
        </div>

        <div class="col-span-12 md:col-span-6 xl:col-span-4">
            <x-stat-card
                label="Total employees"
                :value="$stats['employees']"
                icon="ti ti-users"
                :href="route('staff.index', ['role' => \App\Enums\UserRole::Employee->value])"
            />
        </div>

        <div class="col-span-12 md:col-span-6 xl:col-span-4">
            <x-stat-card
                label="Employees with lead access"
                :value="$stats['employees_with_lead_access']"
                icon="ti ti-lock-open"
                tone="success"
            />
        </div>

        <div class="col-span-12 md:col-span-6 xl:col-span-4">
            <x-stat-card
                label="Employees without lead access"
                :value="$stats['employees_without_lead_access']"
                icon="ti ti-lock"
            />
        </div>

        <div class="col-span-12 md:col-span-6 xl:col-span-4">
            <x-stat-card
                label="Upcoming increments"
                :value="$stats['upcoming_increments']"
                :meta="'Next ' . \App\Models\User::INCREMENT_REMINDER_DAYS . ' days'"
                icon="ti ti-calendar-dollar"
                :tone="$stats['upcoming_increments'] > 0 ? 'warning' : 'default'"
            />
        </div>

    </div>

    {{-- Increments span the full width: five columns need the room. --}}
    <div class="grid grid-cols-12 gap-x-6">
        <div class="col-span-12">
            @include('dashboard.widgets.upcoming-increments', ['canManageStaff' => true])
        </div>
    </div>

    <div class="grid grid-cols-12 gap-x-6">
        <div class="col-span-12 xl:col-span-6">
            @include('dashboard.widgets.recent-employees', ['canManageStaff' => true])
        </div>

        <div class="col-span-12 xl:col-span-6">
            @include('dashboard.widgets.recent-shops')
        </div>
    </div>

    @include('dashboard.widgets.future')

@endsection
