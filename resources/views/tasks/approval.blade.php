@extends('layouts.app')

@section('content')
<style>
    #approvalTable {
        width: 100%;
        table-layout: fixed;
        font-size: 12px;
        border-collapse: collapse;
    }

    #approvalTable th,
    #approvalTable td {
        padding: 6px 4px;
        vertical-align: middle;
        overflow-wrap: anywhere;
    }

    #approvalTable th:nth-child(1) { width: 4%; }
    #approvalTable th:nth-child(2) { width: 14%; }
    #approvalTable th:nth-child(3) { width: 12%; }
    #approvalTable th:nth-child(4) { width: 12%; }
    #approvalTable th:nth-child(5) { width: 10%; }
    #approvalTable th:nth-child(6) { width: 16%; }
    #approvalTable th:nth-child(7) { width: 13%; }
    #approvalTable th:nth-child(8) { width: 19%; }

    #approvalTable .btn {
        font-size: 11px;
        padding: 4px 7px;
        white-space: nowrap;
    }

    #approvalTable .badge {
        font-size: 10px;
        white-space: normal;
    }
    #approvalTable {
    width: 100%;
    table-layout: fixed;
}

#approvalTable th,
#approvalTable td {
    vertical-align: middle;
    padding: 8px 6px;
}

/* Remarks column */
#approvalTable th:nth-child(6),
#approvalTable td:nth-child(6) {
    width: 16%;
}

#approvalTable .remarks-cell {
    white-space: normal !important;
    overflow-wrap: anywhere;
    word-break: break-word;
    min-width: 0;
}

/* Status column */
#approvalTable th:nth-child(7),
#approvalTable td:nth-child(7) {
    width: 13%;
    white-space: normal;
}

/* Action column */
#approvalTable th:nth-child(8),
#approvalTable td:nth-child(8) {
    width: 12%;
    text-align: center;
    white-space: nowrap;
}  
</style>


<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Approval Pending Tasks</h4>
    </div>

    <div class="card">

        <div class="card-body">
<form method="GET" action="{{ route('tasks.approval.index') }}" class="mb-4">

    @if(request('my_tasks'))
        <input type="hidden" name="my_tasks" value="1">
    @endif

    <div class="row g-3 align-items-end">

        {{-- Title --}}
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
                    <option
                        value="{{ $user->id }}"
                        {{ request('assigned_to') == $user->id ? 'selected' : '' }}
                    >
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
                    <option
                        value="{{ $user->id }}"
                        {{ request('approved_by') == $user->id ? 'selected' : '' }}
                    >
                        {{ $user->name }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Task Type --}}
        <div class="col-md-2">
            <label class="form-label">Task Type</label>

            <select name="task_type" class="form-select">
                <option value="">All</option>

                <option value="daily"
                    {{ request('task_type') == 'daily' ? 'selected' : '' }}>
                    Daily
                </option>

                <option value="weekly"
                    {{ request('task_type') == 'weekly' ? 'selected' : '' }}>
                    Weekly
                </option>

                <option value="monthly"
                    {{ request('task_type') == 'monthly' ? 'selected' : '' }}>
                    Monthly
                </option>

                <option value="quarterly"
                    {{ request('task_type') == 'quarterly' ? 'selected' : '' }}>
                    Quarterly
                </option>

                <option value="yearly"
                    {{ request('task_type') == 'yearly' ? 'selected' : '' }}>
                    Yearly
                </option>
            </select>
        </div>

        {{-- Buttons --}}
        <div class="col-md-3 d-flex gap-2">

            <button type="submit" class="btn btn-primary">
                <i class="ti ti-search"></i>
                Search
            </button>

            <a
                href="{{ route('tasks.approval.index', request('my_tasks') ? ['my_tasks' => 1] : []) }}"
                class="btn btn-light"
            >
                <i class="ti ti-refresh"></i>
                Reset
            </a>

        </div>

    </div>

</form>
            <div class="table-responsive">

                <table class="table table-bordered table-hover" id="approvalTable">

                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Title</th>
                            <th>Assigned To</th>
                            <th>Approved By</th>
                            <th>Task Type</th>
                            <th>Remarks</th>
                            <th>Status</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>

                    <tbody>

                        @forelse($tasks as $task)

                            <tr>

                                <td>
                                    {{ $loop->iteration }}
                                </td>

                                <td  class="remarks-cell">
                                    {{ $task->title }}
                                </td>

                                <td>
                                    {{ $task->assignedUser?->name ?? '-' }}
                                </td>
                                <td>
                                    {{ $task->approvedBy?->name ?? '-' }}
                                </td>
                                <td>
                                    <span class="badge bg-info">
                                        {{ ucfirst($task->task_type) }}
                                    </span>
                                </td>

                                <td class="remarks-cell">
                                    {{ $task->remarks ?? '-' }}
                                </td>

                                <td>

                                    <span class="badge bg-warning text-dark">
                                        Approval Pending
                                    </span>

                                </td>

                                <td class="text-center">

                                    <form
                                        action="{{ route('tasks.approve', $task->id) }}"
                                        method="POST"
                                        class="d-inline"
                                    >

                                        @csrf

                                        <button
                                            type="submit"
                                            class="btn btn-success btn-sm"
                                            onclick="return confirm('Are you sure you want to approve this task?')"
                                        >
                                            Approve
                                        </button>

                                    </form>

                                </td>

                            </tr>

                        @empty

                            <tr>
                                <td colspan="8" class="text-center">
                                    No tasks pending for approval.
                                </td>
                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>
            @if($tasks->hasPages())
                <div class="mt-3">
                    {{ $tasks->withQueryString()->links() }}
                </div>
            @endif
        </div>

    </div>

</div>


@endsection