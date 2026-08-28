@extends('layouts.app')

@section('content')

<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Approval Pending Tasks</h4>
    </div>

    <div class="card">

        <div class="card-body">

            <div class="table-responsive">

                <table class="table table-bordered table-hover" id="approvalTable">

                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Title</th>
                            <th>Assigned To</th>
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

        </div>

    </div>

</div>

@endsection