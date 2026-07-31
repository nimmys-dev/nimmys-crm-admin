@extends('layouts.app')

@section('title', 'Lead Management')

@section('page-actions')
    <x-button icon="ti ti-plus" disabled>Add Lead</x-button>
@endsection

@section('content')

    <div class="grid grid-cols-12 gap-x-6">
        <div class="col-span-12">
            <x-card title="Leads">
                <x-datatable
                    :headers="['Name', 'Contact', 'Source', 'Owner', 'Status', '']"
                    empty-message="No leads yet. The lead module is not connected."
                    empty-icon="ti ti-target-arrow"
                />
            </x-card>
        </div>
    </div>

@endsection
