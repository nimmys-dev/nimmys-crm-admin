@extends('layouts.app')

@section('content')

<style>
.d-flex.gap-1.flex-nowrap {
    white-space: nowrap;
}

.d-flex.gap-1.flex-nowrap form {
    margin: 0 !important;
}
.export-dropdown {
    position: relative;
    display: inline-block;
    z-index: 999999;
}

.export-btn {
    border: 0;
    background: #0d6efd;
    color: #fff;
    padding: 9px 16px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
}

.export-dropdown-menu {
    position: absolute;
    top: calc(100% + 5px);
    right: 0;
    width: 190px;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 7px;
    padding: 5px 0;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
    display: none;
    z-index: 999999;
}

.export-dropdown:hover .export-dropdown-menu {
    display: block;
}

.export-dropdown-menu a {
    display: flex;
    align-items: center;
    padding: 10px 14px;
    color: #333;
    text-decoration: none;
    font-size: 14px;
    white-space: nowrap;
}

.export-dropdown-menu a:hover {
    background: #f5f5f5;
}
</style>
<style>
 
.task-summary-row {
    display: flex;
    flex-wrap: nowrap;
    gap: 16px; /* boxes തമ്മിലുള്ള space */
}

.task-summary-col {
    flex: 1 1 0;
    min-width: 0;
    display: flex;
}

.task-summary-col .card {
    width: 100%;
    height: 70%;
}
/* =========================================================
   TASK REPORT FILTER - ONE LINE FLEX UI
   ========================================================= */
.task-filter-form .row {
    display: flex !important;
    flex-wrap: nowrap !important;
    align-items: flex-end !important;
    gap: 12px !important;
    margin-left: 0 !important;
    margin-right: 0 !important;
}

/* Inputs, Selects & Buttons flexible wrapping */
.task-filter-form .col-filter-input {
    flex: 1 1 0% !important;
    min-width: 0 !important;
    padding: 0 !important;
}

.task-filter-form .col-filter-btn {
    flex: 0 0 auto !important;
    padding: 0 !important;
}

.task-filter-form .btn {
    white-space: nowrap !important;
}

/* Responsive view for Tablet & Mobile */
@media (max-width: 991px) {
    .task-filter-form .row {
        flex-wrap: wrap !important;
    }
    .task-filter-form .col-filter-input {
        flex: 0 0 calc(50% - 6px) !important;
    }
    .task-filter-form .col-filter-btn {
        flex: 1 1 0% !important;
    }
}
</style>
<div class="container-fluid">

    {{-- =========================================================
        PAGE HEADER
    ========================================================== --}}
<div class="mb-4">

    <div>
        <h3 class="mb-1 fw-bold">
            Task Management Reports
        </h3>

        <p class="text-muted mb-3">
            Track, review and manage all assigned tasks in one place
        </p>
    </div>

    <div class="d-flex justify-content-end">

        <div class="export-dropdown">

            <button type="button" class="export-btn">
                <i class="ti ti-download me-1"></i>
                Export Report
                <i class="ti ti-chevron-down ms-1"></i>
            </button>

            <div class="export-dropdown-menu">

                <!-- <a href="{{ route('task-management-report.export.excel') }}">
                    <i class="ti ti-file-spreadsheet me-2 text-success"></i>
                    Export Excel
                </a> -->
                <a href="{{ route('task-management-report.export.excel', request()->query()) }}">
                    <i class="ti ti-file me-2 text-success"></i>
                    Export Excel
                </a>

                <a href="{{ route('task-management-report.export.pdf', request()->query()) }}" target="_blank">
                    <i class="ti ti-file-text me-2 text-danger"></i>
                    Export PDF
                </a>

            </div>

        </div>

    </div>

</div>


    {{-- =========================================================
        SUMMARY CARDS
    ========================================================== --}}
        <div class="row g-3 mb-4 task-summary-row">

            {{-- TOTAL --}}
            <div class="task-summary-col">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">

                        <div class="d-flex align-items-center justify-content-between">

                            <div>
                                <p class="text-muted mb-1">
                                    Total Tasks
                                </p>

                                <h3 class="mb-0 fw-bold">
                                    {{ $totalTasks ?? 48 }}
                                </h3>
                            </div>
                        </div>

                    </div>
                </div>
            </div>


            {{-- COMPLETED --}}
            <div class="task-summary-col">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">

                        <div class="d-flex align-items-center justify-content-between">

                            <div>
                                <p class="text-muted mb-1">
                                    Completed
                                </p>

                                <h3 class="mb-0 fw-bold">
                                    {{ $completedTasks ?? 32 }}
                                </h3>

                                @php
                                    $completedPercentage = ($totalTasks ?? 48) > 0
                                        ? round((($completedTasks ?? 32) / ($totalTasks ?? 48)) * 100)
                                        : 0;
                                @endphp
                            </div>
                        </div>

                    </div>
                </div>
            </div>


            {{-- IN PROGRESS --}}
            <div class="task-summary-col">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">

                        <div class="d-flex align-items-center justify-content-between">

                            <div>
                                <p class="text-muted mb-1">
                                    Ongoing
                                </p>

                                <h3 class="mb-0 fw-bold">
                                    {{ $ongoingTasks ?? 10 }}
                                </h3>

                                @php
                                    $progressPercentage = ($totalTasks ?? 48) > 0
                                        ? round((($ongoingTasks ?? 10) / ($totalTasks ?? 48)) * 100)
                                        : 0;
                                @endphp
                            </div>
                        </div>

                    </div>
                </div>
            </div>


            {{-- PENDING --}}
            <div class="task-summary-col">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">

                        <div class="d-flex align-items-center justify-content-between">

                            <div>
                                <p class="text-muted mb-1">
                                    Upcoming
                                </p>

                                <h3 class="mb-0 fw-bold">
                                    {{ $upcomingTasks ?? 6 }}
                                </h3>

                                @php
                                    $pendingPercentage = ($totalTasks ?? 48) > 0
                                        ? round((($upcomingTasks ?? 6) / ($totalTasks ?? 48)) * 100)
                                        : 0;
                                @endphp
                            </div>
                        </div>

                    </div>
                </div>
            </div>

        </div>


    {{-- =========================================================
        FILTER CARD
    ========================================================== --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">

            <form method="GET" action="{{ route('task-management-report.index') }}" class="task-filter-form">

                <div class="row g-3 align-items-end">

                    {{-- TASK SEARCH --}}
                    <div class="col-filter-input">
                        <label class="form-label fw-bold">Task Name</label>
                        <input
                            type="text"
                            name="search"
                            class="form-control"
                            placeholder="Search task name..."
                            value="{{ request('search') }}"
                        >
                    </div>

                    {{-- STAFF --}}
                    <div class="col-filter-input">
                        <label class="form-label fw-bold">Assigned Staff</label>
                        <select name="assigned_to" class="form-select">
                            <option value="">All Staff</option>
                            @foreach(($staff ?? []) as $user)
                                <option
                                    value="{{ $user->id }}"
                                    @selected(request('assigned_to') == $user->id)
                                >
                                    {{ $user->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- PERIOD --}}
                    <div class="col-filter-input">
                        <label class="form-label fw-bold">Period</label>
                        <select name="task_type" class="form-select">
                            <option value="">All Periods</option>
                            <option value="daily" @selected(request('task_type') == 'daily')>Daily</option>
                            <option value="weekly" @selected(request('task_type') == 'weekly')>Weekly</option>
                            <option value="monthly" @selected(request('task_type') == 'monthly')>Monthly</option>
                            <option value="quarterly" @selected(request('task_type') == 'quarterly')>Quarterly</option>
                            <option value="yearly" @selected(request('task_type') == 'yearly')>Yearly</option>
                        </select>
                    </div>

                    {{-- STATUS --}}
                    <div class="col-filter-input">
                        <label class="form-label fw-bold">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All Status</option>
                            <option value="ongoing" @selected(request('status') == 'ongoing')>Ongoing</option>
                            <option value="upcoming" @selected(request('status') == 'upcoming')>Upcoming</option>
                            <option value="overdue" @selected(request('status') == 'overdue')>Overdue</option>
                            <option value="completed" @selected(request('status') == 'completed')>Completed</option>
                            <option value="approved" @selected(request('status') == 'approved')>Approved</option>
                        </select>
                    </div>

                    {{-- DATE RANGE --}}
                    <div class="col-filter-input">
                        <label class="form-label fw-bold">Date Range</label>
                        <input
                            type="date"
                            name="date"
                            class="form-control"
                            value="{{ request('date') }}"
                        >
                    </div>

                    {{-- SEARCH BUTTON --}}
                    <div class="col-filter-btn">
                        <button type="submit" class="btn btn-primary px-3">
                            <i class="ti ti-search me-1"></i> Search
                        </button>
                    </div>

                    {{-- RESET BUTTON --}}
                    <div class="col-filter-btn">
                        <a href="{{ route('task-management-report.index') }}" class="btn btn-outline-secondary px-3">
                            Reset
                        </a>
                    </div>

                </div>

            </form>

        </div>
    </div>


    {{-- =========================================================
        TASK REPORT TABLE
    ========================================================== --}}
    <div class="card border-0 shadow-sm">

        <div class="card-header bg-transparent border-0 py-3">

            <div class="d-flex justify-content-between align-items-center">

                <h5 class="mb-0 fw-bold">
                    Task Reports
                    <span class="text-muted">
                        ({{ $tasks->total() ?? 48 }})
                    </span>
                </h5>

                @if(isset($tasks))
                    <span class="text-muted small">
                        Showing
                        {{ $tasks->firstItem() ?? 0 }}
                        -
                        {{ $tasks->lastItem() ?? 0 }}
                        of
                        {{ $tasks->total() }}
                    </span>
                @endif

            </div>

        </div>


        <div class="table-responsive">

            <table class="table table-hover align-middle mb-0">

                <thead class="table-light">

                    <tr>

                        <th width="60">
                            #
                        </th>

                        <th>
                            Task Name
                        </th>

                        <th>
                            Assigned Staff
                        </th>

                        <th>
                            Period
                        </th>

                        <th>
                            Approved By
                        </th>

                        <th>
                            Status
                        </th>

                        <th width="130">
                            Actions
                        </th>

                    </tr>

                </thead>


                <tbody>

                    @forelse(($tasks ?? []) as $index => $task)

                        <tr>

                            {{-- NUMBER --}}
                            <td>
                                {{ (($tasks->currentPage() ?? 1) - 1) * ($tasks->perPage() ?? 10) + $index + 1 }}
                            </td>


                            {{-- TASK --}}
                            <td>

                                <div class="fw-semibold">
                                    {{ $task->title }}
                                </div>

                            </td>


                            {{-- ASSIGNED STAFF --}}
                            <td>

                                {{ optional($task->assignedTo)->name ?? '-' }}

                            </td>


                            {{-- PERIOD --}}
                            <td>

                                @php
                                    $period = strtolower($task->task_type ?? '');
                                @endphp

                                @if($period === 'daily')

                                    <span class="badge bg-primary-subtle text-primary">
                                        Daily
                                    </span>

                                @elseif($period === 'weekly')

                                    <span class="badge bg-info-subtle text-info">
                                        Weekly
                                    </span>

                                @elseif($period === 'monthly')

                                    <span class="badge bg-success-subtle text-success">
                                        Monthly
                                    </span>

                                @elseif($period === 'quarterly')

                                    <span class="badge bg-warning-subtle text-warning">
                                        Quarterly
                                    </span>

                                @elseif($period === 'yearly')

                                    <span class="badge bg-danger-subtle text-danger">
                                        Yearly
                                    </span>

                                @else

                                    <span class="badge bg-light text-dark">
                                        -
                                    </span>

                                @endif

                            </td>


                            {{-- APPROVED BY --}}
                            <td>

                                {{ optional($task->approvedBy)->name ?? '-' }}

                            </td>


                            {{-- STATUS --}}
                            <td>

                                @php
                                    $status = strtolower($task->status ?? 'pending');

                                    $statusMap = [

                                        'pending' => [
                                            'label' => 'Pending',
                                            'class' => 'bg-danger-subtle text-danger'
                                        ],

                                        'in_progress' => [
                                            'label' => 'In Progress',
                                            'class' => 'bg-warning-subtle text-warning'
                                        ],

                                        'ongoing' => [
                                            'label' => 'Ongoing',
                                            'class' => 'bg-warning-subtle text-warning'
                                        ],

                                        'upcoming' => [
                                            'label' => 'Upcoming',
                                            'class' => 'bg-info-subtle text-info'
                                        ],

                                        'overdue' => [
                                            'label' => 'Overdue',
                                            'class' => 'bg-danger-subtle text-danger'
                                        ],

                                        'completed' => [
                                            'label' => 'Completed',
                                            'class' => 'bg-success-subtle text-success'
                                        ],

                                        'approval_pending' => [
                                            'label' => 'Approval Pending',
                                            'class' => 'bg-warning-subtle text-warning'
                                        ],

                                        'approved' => [
                                            'label' => 'Approved',
                                            'class' => 'bg-success-subtle text-success'
                                        ],

                                        'closed' => [
                                            'label' => 'Closed',
                                            'class' => 'bg-secondary-subtle text-secondary'
                                        ],

                                    ];

                                    $statusData = $statusMap[$status] ?? [
                                        'label' => ucfirst(str_replace('_', ' ', $status)),
                                        'class' => 'bg-light text-dark'
                                    ];
                                @endphp


                                <span class="badge {{ $statusData['class'] }}">

                                    @if($status === 'approved' || $status === 'completed')

                                        <i class="ti ti-circle-check me-1"></i>

                                    @elseif(
                                        $status === 'pending' ||
                                        $status === 'overdue'
                                    )

                                        <i class="ti ti-alert-circle me-1"></i>

                                    @else

                                        <i class="ti ti-clock me-1"></i>

                                    @endif

                                    {{ $statusData['label'] }}

                                </span>

                            </td>


                            {{-- ACTIONS --}}
                            <td style="white-space: nowrap;">

                                <div style="display: flex; flex-wrap: nowrap; align-items: center; gap: 4px;">

                                    {{-- VIEW --}}
                                    @if(isset($task->id))

                                        <a
                                            href="{{ route('tasks.show', $task->id) }}"
                                            class="btn btn-sm btn-light"
                                            title="View"
                                        >
                                            <i class="ti ti-eye"></i>
                                        </a>

                                    @else

                                        <button
                                            class="btn btn-sm btn-light"
                                            type="button"
                                        >
                                            <i class="ti ti-eye"></i>
                                        </button>

                                    @endif


                                    {{-- EDIT --}}
                                    @if(isset($task->id))

                                        <a
                                            href="{{ route('tasks.edit', $task->id) }}"
                                            class="btn btn-sm btn-light"
                                            title="Edit"
                                        >
                                            <i class="ti ti-pencil"></i>
                                        </a>

                                    @else

                                        <button
                                            class="btn btn-sm btn-light"
                                            type="button"
                                        >
                                            <i class="ti ti-pencil"></i>
                                        </button>

                                    @endif


                                    {{-- DELETE --}}
                                    @if(isset($task->id))

                                        <form
                                            action="{{ route('tasks.destroy', $task->id) }}"
                                            method="POST"
                                            onsubmit="return confirm('Are you sure you want to delete this task?')"
                                        >

                                            @csrf
                                            @method('DELETE')

                                            <button
                                                type="submit"
                                                class="btn btn-sm btn-light text-danger"
                                                title="Delete"
                                            >
                                                <i class="ti ti-trash"></i>
                                            </button>

                                        </form>

                                    @endif

                                </div>

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td
                                colspan="7"
                                class="text-center py-5"
                            >

                                <div class="text-muted">

                                    <i class="ti ti-file-search fs-1 d-block mb-2"></i>

                                    <h6>
                                        No task reports found
                                    </h6>

                                    <p class="mb-0">
                                        Try changing your filters.
                                    </p>

                                </div>

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        {{-- =====================================================
            PAGINATION
        ====================================================== --}}
        @if(isset($tasks) && method_exists($tasks, 'links'))

            <div class="card-footer bg-transparent border-0">

                {{ $tasks->withQueryString()->links() }}

            </div>

        @endif

    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const button = document.getElementById('exportBtn');
    const menu = document.getElementById('exportMenu');

    button.onclick = function (e) {

        e.stopPropagation();

        if (menu.style.display === 'none') {
            menu.style.display = 'block';
        } else {
            menu.style.display = 'none';
        }

    };

    document.addEventListener('click', function () {
        menu.style.display = 'none';
    });

    menu.onclick = function (e) {
        e.stopPropagation();
    };

});
</script>
@endsection