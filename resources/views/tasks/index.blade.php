@extends('layouts.app')

@section('title', 'Task Management')

@section('page-actions')
    <x-button icon="ti ti-plus" disabled>Add Task</x-button>
@endsection

@section('content')

    <div class="grid grid-cols-12 gap-x-6">
        <div class="col-span-12">
            <x-card title="Tasks">
                <x-datatable
                    :headers="['Title', 'Assigned to', 'Priority', 'Due', 'Status', '']"
                    empty-message="No tasks yet. The task module is not connected."
                    empty-icon="ti ti-checklist"
                />
            </x-card>
        </div>
    </div>

@endsection
