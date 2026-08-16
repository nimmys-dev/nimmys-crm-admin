@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')

    <div class="grid grid-cols-12 gap-x-6">

        @if ($shop)
            <div class="col-span-12 md:col-span-6 xl:col-span-3">
                <x-stat-card
                    label="My shop"
                    :value="$shop->name"
                    :meta="$shop->code"
                    icon="ti ti-building-store"
                    class="stat-card-text"
                />
            </div>
        @else
            <div class="col-span-12 md:col-span-6 xl:col-span-3">
                <x-stat-card
                    label="Employee Code"
                    :value="$user->employee_code ?? '—'"
                    icon="ti ti-id-badge-2"
                />
            </div>
        @endif

        @if ($leadStats)
            <div class="col-span-12 md:col-span-6 xl:col-span-3">
                <x-stat-card
                    label="My leads"
                    :value="$leadStats['total']"
                    :meta="$leadStats['open'] . ' open'"
                    icon="ti ti-target"
                />
            </div>

            <div class="col-span-12 md:col-span-6 xl:col-span-3">
                <x-stat-card
                    label="Won leads"
                    :value="$leadStats['won']"
                    icon="ti ti-trophy"
                    tone="success"
                />
            </div>

            <div class="col-span-12 md:col-span-6 xl:col-span-3">
                <x-stat-card
                    label="Conversion rate"
                    :value="$leadStats['conversion_rate'] !== null ? $leadStats['conversion_rate'].'%' : '—'"
                    icon="ti ti-percentage"
                    :tone="$leadStats['conversion_rate'] > 0 ? 'success' : 'default'"
                />
            </div>
        @else
            <div class="col-span-12 md:col-span-6 xl:col-span-3">
                <x-stat-card
                    label="Role"
                    :value="$user->role->label()"
                    icon="ti ti-user-check"
                />
            </div>

            <div class="col-span-12 md:col-span-6 xl:col-span-3">
                <x-stat-card
                    label="Status"
                    :value="$user->status->label()"
                    icon="ti ti-circle-check"
                    tone="success"
                />
            </div>

            <div class="col-span-12 md:col-span-6 xl:col-span-3">
                <x-stat-card
                    label="Lead module"
                    value="Disabled"
                    meta="Contact Admin to enable"
                    icon="ti ti-lock"
                    tone="default"
                />
            </div>
        @endif

    </div>

    @if ($leadStats)
        @include('dashboard.widgets.leads')
    @else
        <div class="mt-4">
            <x-card>
                <div class="p-4 text-center">
                    <i class="ti ti-user-circle text-4xl text-muted mb-2"></i>
                    <h5>Welcome, {{ $user->name }}</h5>
                    <p class="text-muted mb-0">
                        You are logged in as an Employee. Lead management and tasks assigned to you will appear here when enabled.
                    </p>
                </div>
            </x-card>
        </div>
    @endif

@endsection
