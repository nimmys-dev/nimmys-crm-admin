@extends('layouts.app')

@section('content')

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

                                <td>
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

                                <td>
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