@extends('layouts.app')

@section('title', 'Staff Management')

@section('page-actions')
    <x-button icon="ti ti-plus" disabled>Add Staff</x-button>
@endsection

@section('content')

    <div class="grid grid-cols-12 gap-x-6">
        <div class="col-span-12">
            <x-card title="Staff">
                <x-datatable
                    :headers="['Name', 'Email', 'Role', 'Status', '']"
                    empty-message="No staff yet. The staff module is not connected."
                    empty-icon="ti ti-users"
                />
            </x-card>
        </div>
    </div>

@endsection
