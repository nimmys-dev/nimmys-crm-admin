@extends('layouts.app')

@section('content')


@section('page-actions')

    <x-button
        variant="outline-secondary"
        :href="route('tasks.index')"
        icon="ti ti-arrow-left">
        Back
    </x-button>

    <x-button
        :href="route('tasks.edit', $task)"
        icon="ti ti-pencil">
        Edit
    </x-button>

@endsection

<div class="container-fluid task-page">

    {{-- ========================================================= --}}
{{-- PAGE HEADER --}}
{{-- ========================================================= --}}

<div class="task-header">

    <h3 class="task-title">
        Task Management
    </h3>

</div>


{{-- ========================================================= --}}
{{-- TASK DETAILS GRID --}}
{{-- ========================================================= --}}

<div class="task-grid">

    {{-- Task --}}
    <div class="task-grid-item">
        <div class="task-info-card">
            <div class="card-body">

                <span class="task-label">
                    Task
                </span>

                <div class="task-value">
                    {{ $task->title ?? '—' }}
                </div>

            </div>
        </div>
    </div>


    {{-- Task Type --}}
    <div class="task-grid-item">
        <div class="task-info-card">
            <div class="card-body">

                <span class="task-label">
                    Task Type
                </span>

                <div class="task-value">

                    @if($task->task_type === 'daily')

                        <span class="badge bg-success task-badge">
                            Daily
                        </span>

                    @elseif($task->task_type === 'weekly')

                        <span class="badge bg-info task-badge">
                            Weekly
                        </span>

                    @elseif($task->task_type === 'monthly')

                        <span class="badge bg-warning text-dark task-badge">
                            Monthly
                        </span>

                    @elseif($task->task_type === 'quarterly')

                        <span class="badge bg-primary task-badge">
                            Quarterly
                        </span>

                    @else

                        <span class="badge bg-secondary task-badge">
                            -
                        </span>

                    @endif

                </div>

            </div>
        </div>
    </div>


    {{-- Assigned To --}}
    <div class="task-grid-item">
        <div class="task-info-card">
            <div class="card-body">

                <span class="task-label">
                    Assigned To
                </span>

                <div class="task-value">
                    {{ $task->assignedUser->name ?? '—' }}
                </div>

            </div>
        </div>
    </div>


    {{-- Approved By --}}
    <div class="task-grid-item">
        <div class="task-info-card">
            <div class="card-body">

                <span class="task-label">
                    Approved By
                </span>

                <div class="task-value">
                    {{ $task->approvedBy->name ?? '—' }}
                </div>

            </div>
        </div>
    </div>


    {{-- ===================================================== --}}
    {{-- DAILY --}}
    {{-- ===================================================== --}}

    @if($task->task_type === 'daily')

        <div class="task-grid-item">
            <div class="task-info-card">
                <div class="card-body">

                    <span class="task-label">
                        Start Time
                    </span>

                    <div class="task-value">
                        {{ $task->start_time
                            ? date('h:i A', strtotime($task->start_time))
                            : '—' }}
                    </div>

                </div>
            </div>
        </div>

        <div class="task-grid-item">
            <div class="task-info-card">
                <div class="card-body">

                    <span class="task-label">
                        End Time
                    </span>

                    <div class="task-value">
                        {{ $task->end_time
                            ? date('h:i A', strtotime($task->end_time))
                            : '—' }}
                    </div>

                </div>
            </div>
        </div>

    @endif


    {{-- ===================================================== --}}
    {{-- WEEKLY --}}
    {{-- ===================================================== --}}

    @if($task->task_type === 'weekly')

        <div class="task-grid-item">
            <div class="task-info-card">
                <div class="card-body">

                    <span class="task-label">
                        Start Day
                    </span>

                    <div class="task-value">
                        {{ $task->week_start_day
                            ? ucfirst($task->week_start_day)
                            : '—' }}
                    </div>

                </div>
            </div>
        </div>

        <div class="task-grid-item">
            <div class="task-info-card">
                <div class="card-body">

                    <span class="task-label">
                        End Day
                    </span>

                    <div class="task-value">
                        {{ $task->week_end_day
                            ? ucfirst($task->week_end_day)
                            : '—' }}
                    </div>

                </div>
            </div>
        </div>

    @endif


    {{-- ===================================================== --}}
    {{-- MONTHLY --}}
    {{-- ===================================================== --}}

    @if($task->task_type === 'monthly')

        <div class="task-grid-item">
            <div class="task-info-card">
                <div class="card-body">

                    <span class="task-label">
                        Start Date
                    </span>

                    <div class="task-value">
                        {{ $task->monthly_start_date
                            ? \Carbon\Carbon::parse($task->monthly_start_date)->format('d M Y')
                            : '—' }}
                    </div>

                </div>
            </div>
        </div>

        <div class="task-grid-item">
            <div class="task-info-card">
                <div class="card-body">

                    <span class="task-label">
                        End Date
                    </span>

                    <div class="task-value">
                        {{ $task->monthly_end_date
                            ? \Carbon\Carbon::parse($task->monthly_end_date)->format('d M Y')
                            : '—' }}
                    </div>

                </div>
            </div>
        </div>

    @endif


    {{-- ===================================================== --}}
    {{-- QUARTERLY --}}
    {{-- ===================================================== --}}

    @if($task->task_type === 'quarterly')

        <div class="task-grid-item">
            <div class="task-info-card">
                <div class="card-body">

                    <span class="task-label">
                        Quarter
                    </span>

                    <div class="task-value">

                        @switch($task->quarter)

                            @case('q1')
                                Q1 - Apr, May, Jun
                                @break

                            @case('q2')
                                Q2 - Jul, Aug, Sep
                                @break

                            @case('q3')
                                Q3 - Oct, Nov, Dec
                                @break

                            @case('q4')
                                Q4 - Jan, Feb, Mar
                                @break

                            @default
                                —

                        @endswitch

                    </div>

                </div>
            </div>
        </div>

        <div class="task-grid-item">
            <div class="task-info-card">
                <div class="card-body">

                    <span class="task-label">
                        Quarter Start Date
                    </span>

                    <div class="task-value">
                        {{ $task->quarter_start_date
                            ? \Carbon\Carbon::parse($task->quarter_start_date)->format('d M Y')
                            : '—' }}
                    </div>

                </div>
            </div>
        </div>

        <div class="task-grid-item">
            <div class="task-info-card">
                <div class="card-body">

                    <span class="task-label">
                        Quarter End Date
                    </span>

                    <div class="task-value">
                        {{ $task->quarter_end_date
                            ? \Carbon\Carbon::parse($task->quarter_end_date)->format('d M Y')
                            : '—' }}
                    </div>

                </div>
            </div>
        </div>

    @endif


    {{-- Created At --}}
    <div class="task-grid-item">
        <div class="task-info-card">
            <div class="card-body">

                <span class="task-label">
                    Created At
                </span>

                <div class="task-value">
                    {{ $task->created_at
                        ? $task->created_at->format('d M Y, h:i A')
                        : '—' }}
                </div>

            </div>
        </div>
    </div>


    {{-- Last Updated --}}
    <div class="task-grid-item">
        <div class="task-info-card">
            <div class="card-body">

                <span class="task-label">
                    Last Updated
                </span>

                <div class="task-value">
                    {{ $task->updated_at
                        ? $task->updated_at->format('d M Y, h:i A')
                        : '—' }}
                </div>

            </div>
        </div>
    </div>


    {{-- Description --}}
  <div class="task-info-card">
            <div class="card-body">

                <span class="task-label">
                    Description
                </span>

                <div class="task-description">
                    @if($task->description)
                        {!! nl2br(e($task->description)) !!}
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </div>

            </div>
        </div>

</div>

</div>

@endsection