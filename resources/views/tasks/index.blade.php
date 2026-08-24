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

    {{-- Filters --}}
    <form method="GET" action="{{ route('tasks.index') }}" class="mb-4">
        <div class="row g-3 align-items-end">

            {{-- Title Search --}}
            <div class="col-md-3">
                <label class="form-label">Search</label>
                <input
                    type="text"
                    name="title"
                    class="form-control"
                    placeholder="Search task title..."
                    value="{{ request('title') }}"
                >
            </div>

            {{-- Assigned To --}}
            <div class="col-md-2">
                <label class="form-label">Assigned To</label>
                <select name="assigned_to" class="form-select">
                    <option value="">All</option>

                    @foreach($users as $user)
                        <option value="{{ $user->id }}"
                            {{ request('assigned_to') == $user->id ? 'selected' : '' }}>
                            {{ $user->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Approved By --}}
            <div class="col-md-2">
                <label class="form-label">Approved By</label>
                <select name="approved_by" class="form-select">
                    <option value="">All</option>

                    @foreach($users as $user)
                        <option value="{{ $user->id }}"
                            {{ request('approved_by') == $user->id ? 'selected' : '' }}>
                            {{ $user->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Buttons --}}
            <div class="col-md-3 d-flex gap-2 mt-3">

                <button type="submit" class="btn btn-primary">
                    <i class="ti ti-search"></i>
                    Search
                </button>

                <a href="{{ route('tasks.index') }}" class="btn btn-light">
                    <i class="ti ti-refresh"></i>
                    Reset
                </a>

            </div>

        </div>
    </form>


    {{-- Task Table --}}
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

                    <td class="task-title">
                        <span class="task-name">
                            {{ $task->title }}
                        </span>
                    </td>

                    <td>
                        {{ $task->assignedUser?->name ?? '-' }}
                    </td>

                    <td>
                        {{ $task->approvedBy?->name ?? '-' }}
                    </td>

                    <td>
                        @php
                            $typeClass = match($task->task_type) {
                                'daily' => 'task-type daily',
                                'weekly' => 'task-type weekly',
                                'monthly' => 'task-type monthly',
                                'quarterly' => 'task-type quarterly',
                                'yearly' => 'task-type yearly',
                                default => 'task-type',
                            };
                        @endphp

                        <span class="{{ $typeClass }}">
                            {{ ucfirst($task->task_type) }}
                        </span>
                    </td>

                    <td class="task-schedule">

                        @if($task->task_type === 'daily')

                            {{ \Carbon\Carbon::parse($task->start_time)->format('h:i A') }}
                            -
                            {{ \Carbon\Carbon::parse($task->end_time)->format('h:i A') }}

                        @elseif($task->task_type === 'weekly')

                            {{ ucfirst($task->week_start_day) }}
                            -
                            {{ ucfirst($task->week_end_day) }}

                        @elseif($task->task_type === 'monthly')

                            {{ $task->monthly_start_date?->format('d M Y') }}
                            -
                            {{ $task->monthly_end_date?->format('d M Y') }}

                        @elseif($task->task_type === 'quarterly')

                            @foreach($task->quarters as $quarter)

                                <div class="mb-2">

                                    <strong>
                                        {{ strtoupper($quarter->quarter) }}
                                    </strong>

                                    <div class="schedule-sub">
                                        {{ $quarter->start_date?->format('d M Y') }}
                                        -
                                        {{ $quarter->end_date?->format('d M Y') }}
                                    </div>

                                </div>

                            @endforeach

                        @elseif($task->task_type === 'yearly')

                            <div class="schedule-sub">
                                {{ $task->yearly_start_date
                                    ? \Carbon\Carbon::parse($task->yearly_start_date)->format('d M Y')
                                    : '-' }}
                                -
                                {{ $task->yearly_end_date
                                    ? \Carbon\Carbon::parse($task->yearly_end_date)->format('d M Y')
                                    : '-' }}
                            </div>

                        @endif

                    </td>

                    <td>
                        @php
                            $statusClass = match($task->status) {
                                'pending' => 'task-status pending',
                                'overdue' => 'task-status overdue',
                                'upcoming' => 'task-status upcoming',
                                'ongoing' => 'task-status ongoing',
                                'completed' => 'task-status completed',
                                'closed' => 'task-status closed',
                                default => 'task-status',
                            };
                        @endphp

                        <span class="{{ $statusClass }}">
                            {{ ucwords(str_replace('_', ' ', $task->status)) }}
                        </span>
                    </td>

                    <td>
                        <div class="task-actions">

                            <!-- 1. View Button -->
                            <a href="{{ route('tasks.show', $task) }}"
                            class="task-action-btn"
                            title="View">
                                <i class="ti ti-eye"></i>
                            </a>
                            {{-- Only Admin / Manager --}}
                            {{-- Edit & Delete: Admin / Manager only --}}
                            @if(in_array(auth()->user()->role->value, ['admin', 'manager']))

                                {{-- Edit Button --}}
                                <a href="{{ route('tasks.edit', $task) }}"
                                class="task-action-btn"
                                title="Edit">

                                    <i class="ti ti-edit"></i>

                                </a>


                                {{-- Delete Button --}}
                                <form action="{{ route('tasks.destroy', $task) }}"
                                    method="POST"
                                    class="delete-form"
                                    onsubmit="return confirm('Are you sure you want to delete this task?');">

                                    @csrf
                                    @method('DELETE')

                                    <button type="submit"
                                            class="task-action-btn delete"
                                            title="Delete">

                                        <i class="ti ti-trash"></i>

                                    </button>

                                </form>

                            @endif

                            {{-- Complete Button --}}
                            @if(
                                $task->status !== 'completed'
                            )

                                <button type="button"
                                        class="task-action-btn complete-btn"
                                        title="Complete Task"
                                        data-id="{{ $task->id }}"
                                        data-title="{{ $task->title }}"
                                        data-remarks="{{ $task->remarks ?? '' }}">

                                    <i class="ti ti-circle-check"></i>

                                </button>

                            @endif
                        </div>
                        <!-- Modal Overlay (Placed Outside task-actions to avoid flex issues) -->
                        <!-- Modal Overlay -->
                        <div class="custom-modal-overlay" id="customModal">
                            <div class="custom-modal-dialog">
                                <div class="custom-modal-content">
                                    
                                    <!-- Standard Form Submission Route -->
                                    <form id="completeTaskForm" action="" method="POST">
                                        @csrf
                                        @method('PATCH')

                                        <div class="custom-modal-header">
                                            <h5 class="custom-modal-title">
                                                <i class="ti ti-circle-check text-success"></i> Complete Task: <span id="modalTaskTitle"></span>
                                            </h5>
                                            <button type="button" class="custom-modal-close" id="closeModalX">&times;</button>
                                        </div>

                                        <div class="custom-modal-body">
                                            <div class="modal-field">
                                                <label for="modalRemarks"><strong>Completion Remarks / Notes:</strong></label>
                                                <!-- Added name="remarks" so value sends with POST request -->
                                                <textarea id="modalRemarks" name="remarks" class="form-remarks" rows="4" placeholder="Enter any completion notes or remarks..."></textarea>
                                            </div>
                                        </div>

                                        <div class="custom-modal-footer">
                                            <button type="button" class="btn-cancel" id="closeModalBtn">Cancel</button>
                                            <!-- type="submit" triggers standard form submission -->
                                            <button type="submit" class="btn-save" id="saveRemarksBtn">
                                                <i class="ti ti-check"></i> Complete & Save
                                            </button>
                                        </div>
                                    </form>

                                </div>
                            </div>
                        </div>
                    </td>

                </tr>

            @empty

                <tr>
                    <td colspan="7" class="text-center py-5 text-muted">
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
<!-- <script>
    document.addEventListener('DOMContentLoaded', () => {
  const modal = document.getElementById('customModal');
  const openBtn = document.getElementById('openModalBtn');
  const closeBtn = document.getElementById('closeModalBtn');
  const closeX = document.getElementById('closeModalX');

  // Open modal
  openBtn.addEventListener('click', () => {
    modal.classList.add('show');
  });

  // Close modal helper function
  const closeModal = () => {
    modal.classList.remove('show');
  };

  // Close triggers
  closeBtn.addEventListener('click', closeModal);
  closeX.addEventListener('click', closeModal);

  // Close on backdrop click
  modal.addEventListener('click', (e) => {
    if (e.target === modal) {
      closeModal();
    }
  });

  // Close on ESC key
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && modal.classList.contains('show')) {
      closeModal();
    }
  });
});
</script> -->

<script>
  document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('customModal');
    const completeForm = document.getElementById('completeTaskForm');
    const modalTitle = document.getElementById('modalTaskTitle');
    const modalRemarks = document.getElementById('modalRemarks');
    
    const closeBtn = document.getElementById('closeModalBtn');
    const closeX = document.getElementById('closeModalX');

    // Attach click event to all complete buttons in the table
    document.querySelectorAll('.complete-btn').forEach(button => {
        button.addEventListener('click', function() {
            const taskId = this.getAttribute('data-id');
            const taskTitle = this.getAttribute('data-title');
            const taskRemarks = this.getAttribute('data-remarks');

            // Dynamically point form action to your Laravel route
            completeForm.action = `/tasks/${taskId}/complete`;
            
            modalTitle.textContent = taskTitle;
            modalRemarks.value = taskRemarks;

            modal.classList.add('show');
        });
    });

    // Close logic
    const closeModal = () => modal.classList.remove('show');

    closeBtn.addEventListener('click', closeModal);
    closeX.addEventListener('click', closeModal);

    modal.addEventListener('click', (e) => {
        if (e.target === modal) closeModal();
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modal.classList.contains('show')) closeModal();
    });
});
</script>