@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')

    {{--
        Placeholder tiles. $stats is supplied by DashboardController with null
        values; swap in real counts there when the modules land — this view
        does not change.
    --}}
    <div class="grid grid-cols-12 gap-x-6">
        @foreach ($stats as $stat)
            <div class="col-span-12 md:col-span-6 xl:col-span-4">
                <x-card>
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="stat-tile-label">{{ $stat['label'] }}</p>
                            <h3 class="stat-tile-value">{{ $stat['value'] ?? '—' }}</h3>
                        </div>
                        <i class="{{ $stat['icon'] }} text-[28px] text-muted"></i>
                    </div>
                </x-card>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-12 gap-x-6">
        <div class="col-span-12">
            <x-card title="Recent activity">
                <x-datatable
                    :headers="['Date', 'Module', 'Description', 'Status']"
                    empty-message="Activity will appear here once modules are connected."
                    empty-icon="ti ti-activity"
                />
            </x-card>
        </div>
    </div>

@endsection
