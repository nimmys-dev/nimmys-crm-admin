@extends('layouts.app')

@section('content')

<div class="grid grid-cols-12 gap-x-6">

    <div class="col-span-12">
        <a href="{{ route('tasks.create') }}"
            class="btn btn-primary">
            <i class="ti ti-plus"></i>
            Add Task
        </a>
        <x-card title="Tasks">
            <div class="task-datatable-wrapper">
                <x-datatable
                    class="task-datatable"
                    :headers="[
                        'Title',
                        'Assigned To',
                        'Approved By',
                        'Type',
                        'Schedule',
                        'Status',
                        'Actions'
                    ]"
                    empty-message="No tasks found."
                    empty-icon="ti ti-checklist"
                >

                    @forelse($tasks as $task)

                        <tr>

                            {{-- Title --}}
                            <td class="task-title">

                                <span class="task-name">
                                    {{ $task->title }}
                                </span>

                                <!-- @if($task->description)

                                    <div class="task-description">
                                        {{ Str::limit($task->description, 60) }}
                                    </div>

                                @endif -->

                            </td>


                            {{-- Assigned To --}}
                            <td>
                                {{ $task->assignedUser?->name ?? '-' }}
                            </td>


                            {{-- Approved By --}}
                            <td>
                                {{ $task->approvedBy?->name ?? '-' }}
                            </td>


                            {{-- Type --}}
                            <td>

                                @php
                                    $typeClass = match($task->task_type) {
                                        'daily' => 'task-type daily',
                                        'weekly' => 'task-type weekly',
                                        'monthly' => 'task-type monthly',
                                        'quarterly' => 'task-type quarterly',
                                        default => 'task-type',
                                    };
                                @endphp

                                <span class="{{ $typeClass }}">
                                    {{ ucfirst($task->task_type) }}
                                </span>

                            </td>


                            {{-- Schedule --}}
                            <td class="task-schedule">

                                @if($task->task_type === 'daily')

                                    <div>
                                        {{ \Carbon\Carbon::parse($task->start_time)->format('h:i A') }}
                                        -
                                        {{ \Carbon\Carbon::parse($task->end_time)->format('h:i A') }}
                                    </div>

                                @elseif($task->task_type === 'weekly')

                                    <div>
                                        {{ ucfirst($task->week_start_day) }}
                                        -
                                        {{ ucfirst($task->week_end_day) }}
                                    </div>

                                @elseif($task->task_type === 'monthly')

                                    <div>
                                        {{ $task->monthly_start_date?->format('d M Y') }}
                                        -
                                        {{ $task->monthly_end_date?->format('d M Y') }}
                                    </div>

                                @elseif($task->task_type === 'quarterly')

                                    <strong>
                                        {{ strtoupper($task->quarter) }}
                                    </strong>

                                    <div class="schedule-sub">
                                        {{ $task->quarter_start_date?->format('d M Y') }}
                                        -
                                        {{ $task->quarter_end_date?->format('d M Y') }}
                                    </div>

                                @endif

                            </td>


                            {{-- Status --}}
                            <td>

                                @php
                                    $statusClass = match($task->status) {
                                        'pending' => 'task-status pending',
                                        'in_progress' => 'task-status progress',
                                        'completed' => 'task-status completed',
                                        default => 'task-status',
                                    };
                                @endphp

                                <span class="{{ $statusClass }}">
                                    {{ ucwords(str_replace('_', ' ', $task->status)) }}
                                </span>

                            </td>


                            {{-- Actions --}}
                            <td>

                                <div class="task-actions">

                                    {{-- View --}}
                                    <a href="{{ route('tasks.show', $task) }}"
                                       class="task-action-btn"
                                       title="View">

                                        <i class="ti ti-eye"></i>

                                    </a>


                                    {{-- Edit --}}
                                    <a href="{{ route('tasks.edit', $task) }}"
                                       class="task-action-btn"
                                       title="Edit">

                                        <i class="ti ti-edit"></i>

                                    </a>


                                    {{-- Delete --}}
                                    <form action="{{ route('tasks.destroy', $task) }}"
                                          method="POST"
                                          onsubmit="return confirm('Are you sure you want to delete this task?');">

                                        @csrf
                                        @method('DELETE')

                                        <button type="submit"
                                                class="task-action-btn delete"
                                                title="Delete">

                                            <i class="ti ti-trash"></i>

                                        </button>

                                    </form>

                                </div>

                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="7"
                                class="text-center py-5 text-muted">

                                No tasks found.

                            </td>
                        </tr>

                    @endforelse

                </x-datatable>

            </div>


            {{-- Pagination --}}
            @if($tasks->hasPages())

                <div class="mt-4">
                    {{ $tasks->links() }}
                </div>

            @endif

        </x-card>

    </div>

</div>

@endsection


@push('styles')

<style>

    /* =========================================================
       TASK DATATABLE
    ========================================================= */

    .task-datatable-wrapper {
        width: 100%;
        overflow-x: auto;
    }


    .task-datatable {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        font-size: 14px;
        color: #111827;
    }


    /* =========================================================
       TABLE HEADER
    ========================================================= */

    .task-datatable thead th {
        background: #ffffff;
        color: #111827;
        font-size: 14px;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.2px;

        padding: 17px 14px;

        border-top: 1px solid #e5e7eb;
        border-bottom: 1px solid #e5e7eb;

        white-space: nowrap;
    }


    /* =========================================================
       TABLE BODY
    ========================================================= */

    .task-datatable tbody td {
        padding: 20px 14px;

        font-size: 14px;
        font-weight: 400;

        color: #111827;

        border-bottom: 1px solid #edf0f2;

        vertical-align: middle;
    }


    .task-datatable tbody tr {
        background: #ffffff;
        transition: background 0.15s ease;
    }


    .task-datatable tbody tr:hover {
        background: #fafafa;
    }


    /* =========================================================
       TITLE
    ========================================================= */

    .task-title {
        min-width: 180px;
    }


    .task-name {
        font-size: 14px;
        font-weight: 500;
        color: #111827;
    }


    .task-description {
        margin-top: 5px;

        font-size: 13px;
        line-height: 1.5;

        color: #6b7280;

        max-width: 280px;
    }


    /* =========================================================
       TYPE BADGES
    ========================================================= */

    .task-type {
        display: inline-flex;
        align-items: center;

        padding: 5px 10px;

        border-radius: 6px;

        font-size: 12px;
        font-weight: 500;

        white-space: nowrap;
    }


    .task-type.daily {
        background: #e8f1ff;
        color: #2563eb;
    }


    .task-type.weekly {
        background: #e8f8f7;
        color: #0f766e;
    }


    .task-type.monthly {
        background: #fff7df;
        color: #b7791f;
    }


    .task-type.quarterly {
        background: #eaf8ee;
        color: #16803c;
    }


    /* =========================================================
       SCHEDULE
    ========================================================= */

    .task-schedule {
        white-space: nowrap;
        color: #374151 !important;
    }


    .schedule-sub {
        margin-top: 3px;
        font-size: 12px;
        color: #6b7280;
    }


    /* =========================================================
       STATUS
    ========================================================= */

    .task-status {
        display: inline-flex;
        align-items: center;

        padding: 5px 11px;

        border-radius: 6px;

        font-size: 12px;
        font-weight: 500;

        white-space: nowrap;
    }


    .task-status.pending {
        background: #fff3cd;
        color: #946200;
    }


    .task-status.progress {
        background: #e5f3ff;
        color: #1677c8;
    }


    .task-status.completed {
        background: #dff7eb;
        color: #168653;
    }


    /* =========================================================
       ACTIONS
    ========================================================= */

    .task-actions {
        display: flex;
        align-items: center;
        gap: 8px;
    }


    .task-action-btn {
        width: 40px;
        height: 40px;

        display: inline-flex;
        align-items: center;
        justify-content: center;

        border: 0;
        border-radius: 6px;

        background: #f7f8fa;

        color: #111827;

        text-decoration: none;

        font-size: 18px;

        transition: all 0.15s ease;
    }


    .task-action-btn:hover {
        background: #eef1f4;
        color: #111827;
    }


    .task-action-btn.delete {
        color: #dc3545;
        cursor: pointer;
    }


    .task-action-btn.delete:hover {
        background: #fff0f0;
        color: #dc3545;
    }


    /* =========================================================
       MOBILE
    ========================================================= */

    @media (max-width: 768px) {

        .task-datatable {
            min-width: 1000px;
        }

    }

</style>

@endpush