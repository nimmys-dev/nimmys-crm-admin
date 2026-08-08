@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')

    @if (! $stats['shop'])
        {{--
            A Manager with no shop assigned. Better to say so plainly than to
            render a grid of zeroes that looks like a real reading.
        --}}
        <x-card>
            <x-empty-state icon="ti ti-building-store">
                No shop is assigned to your account yet. An Admin needs to set you
                as the manager of a shop before your dashboard can show anything.
            </x-empty-state>
        </x-card>
    @else

        <div class="grid grid-cols-12 gap-x-6">

            <div class="col-span-12 md:col-span-6 xl:col-span-3">
                <x-stat-card
                    label="My shop"
                    :value="$stats['shop']->name"
                    :meta="$stats['shop']->code"
                    icon="ti ti-building-store"
                    class="stat-card-text"
                />
            </div>

            <div class="col-span-12 md:col-span-6 xl:col-span-3">
                <x-stat-card
                    label="Employees in my shop"
                    :value="$stats['employees']"
                    icon="ti ti-users"
                />
            </div>

            <div class="col-span-12 md:col-span-6 xl:col-span-3">
                <x-stat-card
                    label="With lead access"
                    :value="$stats['employees_with_lead_access']"
                    :meta="$stats['employees_without_lead_access'] . ' without'"
                    icon="ti ti-lock-open"
                    tone="success"
                />
            </div>

            <div class="col-span-12 md:col-span-6 xl:col-span-3">
                <x-stat-card
                    label="Upcoming increments"
                    :value="$stats['upcoming_increments']"
                    :meta="'Next ' . \App\Models\User::INCREMENT_REMINDER_DAYS . ' days'"
                    icon="ti ti-calendar-dollar"
                    :tone="$stats['upcoming_increments'] > 0 ? 'warning' : 'default'"
                />
            </div>

        </div>

        <div class="grid grid-cols-12 gap-x-6">
            <div class="col-span-12">
                @include('dashboard.widgets.upcoming-increments')
            </div>
        </div>

        <div class="grid grid-cols-12 gap-x-6">
            <div class="col-span-12">
                @include('dashboard.widgets.recent-employees')
            </div>
        </div>

        @include('dashboard.widgets.future')

    @endif

@endsection
