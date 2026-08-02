@extends('layouts.app')

@section('title', 'Reports')

@section('content')

    <div class="grid grid-cols-12 gap-x-6">
        <div class="col-span-12">
            <x-card title="Reports">
                <x-datatable
                    :headers="['Report', 'Period', 'Generated', '']"
                    empty-message="No reports yet. Reporting is not connected."
                    empty-icon="ti ti-chart-bar"
                />
            </x-card>
        </div>
    </div>

@endsection
