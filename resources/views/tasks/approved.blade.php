@extends('layouts.app')

@section('content')
 <style>
    .task-filter-form {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: nowrap;
    }

    .task-filter-form .search-input,
    .task-filter-form .assigned-select {
        width: 250px;
        flex: 0 0 250px;
    }

    .task-filter-form .filter-btn,
    .task-filter-form .reset-btn {
        flex: 0 0 auto;
        white-space: nowrap;
    }
</style>
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4>Approved Tasks</h4>
    </div>

    <!-- Filters Section -->
  

    <form method="GET" action="{{ route('tasks.approved') }}" class="mb-4 task-filter-form">

        <!-- Search -->
        <input
            type="text"
            name="title"
            class="form-control search-input"
            placeholder="Search by title..."
            value="{{ request('title') }}"
        >

        <!-- Assigned User -->
        @if(auth()->user()->role->value !== 'employee')
            <select name="assigned_to" class="form-control assigned-select">
                <option value="">Filter by Assigned User</option>

                @foreach($users as $user)
                    <option
                        value="{{ $user->id }}"
                        {{ request('assigned_to') == $user->id ? 'selected' : '' }}
                    >
                        {{ $user->name }}
                    </option>
                @endforeach
            </select>
        @endif

        <!-- Filter -->
        <button type="submit" class="btn btn-primary filter-btn">
            Filter
        </button>

        <!-- Reset -->
        <a href="{{ route('tasks.approved') }}" class="btn btn-outline-secondary reset-btn">
            Reset
        </a>

    </form>


    <!-- Tasks Table -->
    <div class="card">
        <div class="card-body">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Assigned To</th>
                        <th>Approved By</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tasks as $task)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $task->title }}</td>
                            <td>{{ $task->assignedUser->name ?? 'N/A' }}</td>
                            <td>{{ $task->approvedBy->name ?? 'N/A' }}</td>
                            <td><span class="badge bg-success">{{ ucfirst($task->status) }}</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center">No approved tasks found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <!-- Pagination Links -->
            <div class="mt-4">
                {{ $tasks->withQueryString()->links() }}
            </div>
        </div>
    </div>
</div>
@endsection