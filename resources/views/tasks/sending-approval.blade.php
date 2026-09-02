@extends('layouts.app')

@section('content')
<style>
    .status-pending {
    background-color: #6c757d !important;
    color: #fff !important;
}

.status-progress {
    background-color: #0dcaf0 !important;
    color: #000 !important;
}

.status-completed {
    background-color: #198754 !important;
    color: #fff !important;
}

.status-approved {
    background-color: #0d6efd !important;
    color: #fff !important;
}

.status-closed {
    background-color: #212529 !important;
    color: #fff !important;
}

.status-overdue {
    background-color: #dc3545 !important;
    color: #fff !important;
}

.status-default {
    background-color: #ffc107 !important;
    color: #000 !important;
}
</style>
<div class="container-fluid">

    <div class="card">

        <div class="card-header">
            <h4 class="mb-0">Sending Approval</h4>
        </div>

        <div class="card-body">

            <div class="table-responsive">

                <table class="table table-bordered table-hover">

                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Task</th>
                            <th>Assigned To</th>
                            <th>Approved By</th>
                            <th>Type</th>
                            <th>Status</th>
                        </tr>
                    </thead>

                    <tbody>

                        @forelse($tasks as $task)

                            <tr>

                                <td>
                                    {{ $tasks->firstItem() + $loop->index }}
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
                                    {{ ucfirst($task->task_type) }}
                                </td>

                                <td>
                                    @php
                                        $statusClass = match ($task->status) {
                                            'pending'     => 'status-pending',
                                            'in_progress' => 'status-progress',
                                            'completed'   => 'status-completed',
                                            'approved'    => 'status-approved',
                                            'closed'      => 'status-closed',
                                            'overdue'     => 'status-overdue',
                                            default       => 'status-default',
                                        };

                                        $statusLabel = match ($task->status) {
                                            'completed' => 'Approval Pending',
                                            default => ucfirst(str_replace('_', ' ', $task->status)),
                                        };
                                    @endphp

                                    <span class="badge {{ $statusClass }}">
                                        {{ $statusLabel }}
                                    </span>
                                </td>
                            </tr>

                        @empty

                            <tr>
                                <td colspan="7" class="text-center">
                                    No tasks available for sending approval.
                                </td>
                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

            {{ $tasks->links() }}

        </div>

    </div>

</div>

@endsection