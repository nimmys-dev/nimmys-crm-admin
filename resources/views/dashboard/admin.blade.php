@extends('layouts.app') 

@section('title', 'Dashboard') 

@section('content') 
<style>
    .dashboard-stats-mytask {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: 24px;
}

@media (max-width: 1200px) {
    .dashboard-stats-mytask {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (max-width: 768px) {
    .dashboard-stats-mytask {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 480px) {
    .dashboard-stats-mytask {
        grid-template-columns: 1fr;
    }
}
</style>
    {{-- Dashboard Duty Summary --}}
    <h4 class="mb-3">My Tasks</h4>
    <div class="dashboard-stats-mytask">

        {{-- Today's Duty --}}
        <a href="{{ route('tasks.index', ['filter' => 'ongoing','my_tasks' => 1]) }}"
        class="stat-card">
            <div class="stat-content">
                <p>Today's Duty</p>
                <h3>{{ $adminOngoingTaskCount }}</h3>
            </div>

            <div class="stat-icon today-icon">
                <i class="ti ti-calendar-event"></i>
            </div>
        </a>

        {{-- Overdue Duty --}}
        <a href="{{ route('tasks.index', ['filter' => 'overdue','my_tasks' => 1]) }}"
        class="stat-card">
            <div class="stat-content">
                <p>Overdue Duty</p>
                <h3>{{ $adminOverdueTaskCount }}</h3>
            </div>

            <div class="stat-icon overdue-icon">
                <i class="ti ti-alert-circle"></i>
            </div>
        </a>

        {{-- Upcoming Duty --}}
        <a href="{{ route('tasks.index', ['filter' => 'upcoming','my_tasks' => 1]) }}"
        class="stat-card">
            <div class="stat-content">
                <p>Upcoming Duty</p>
                <h3>{{ $adminUpcomingTaskCount }}</h3>
            </div>

            <div class="stat-icon upcoming-icon">
                <i class="ti ti-calendar-time"></i>
            </div>
        </a>

        {{-- Approval Pending --}}
        <a href="{{ route('tasks.approval.index', ['filter' => 'pending','my_tasks' => 1]) }}" class="stat-card">
            <div class="stat-content">
                <p>Approval Pending</p>
                <h3>{{ $adminApprovalPendingTaskCount }}</h3>
            </div>
            <div class="stat-icon pending-icon">
                <i class="ti ti-clock-hour-4"></i>
            </div>
        </a>

        <a href="{{ route('tasks.sending-approval') }}" class="stat-card">
            <div class="stat-content">
                <p>Sending Approval</p>
                <h3>View</h3>
            </div>

            <div class="stat-icon pending-icon">
                <i class="ti ti-send"></i>
            </div>
        </a>

    </div>
   
    <h4 class="mb-3">All Tasks</h4>
    <div class="dashboard-stats">

        {{-- Today's Duty --}}
        <a href="{{ route('tasks.index', ['filter' => 'ongoing']) }}"
        class="stat-card">
            <div class="stat-content">
                <p>Today's Duty</p>
                <h3>{{ $todayDuty }}</h3>
            </div>

            <div class="stat-icon today-icon">
                <i class="ti ti-calendar-event"></i>
            </div>
        </a>

        {{-- Overdue Duty --}}
        <a href="{{ route('tasks.index', ['filter' => 'overdue']) }}"
        class="stat-card">
            <div class="stat-content">
                <p>Overdue Duty</p>
                <h3>{{ $overdueDuty }}</h3>
            </div>

            <div class="stat-icon overdue-icon">
                <i class="ti ti-alert-circle"></i>
            </div>
        </a>

        {{-- Upcoming Duty --}}
        <a href="{{ route('tasks.index', ['filter' => 'upcoming']) }}"
        class="stat-card">
            <div class="stat-content">
                <p>Upcoming Duty</p>
                <h3>{{ $upcomingDuty }}</h3>
            </div>

            <div class="stat-icon upcoming-icon">
                <i class="ti ti-calendar-time"></i>
            </div>
        </a>

        {{-- Approval Pending --}}
        <a href="{{ route('tasks.approval.index', ['filter' => 'pending']) }}" class="stat-card">
            <div class="stat-content">
                <p>Approval Pending</p>
                <h3>{{ $approvalPending }}</h3>
            </div>
            <div class="stat-icon pending-icon">
                <i class="ti ti-clock-hour-4"></i>
            </div>
        </a>

    </div>


    {{-- Leads Card --}}
    <div class="mt-6 bg-white rounded-xl border border-gray-200 shadow-sm p-5">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-800">
               MY Leads
            </h3>
            <a href="{{ route('leads.index') }}"
            class="text-sm text-primary">
                View All
            </a>
        </div>

        <div class="leads-inner-grid">
            {{-- Unattended Leads --}}
            <a href="{{ route('leads.index', ['filter' => 'unattended']) }}"
            class="lead-stat-box">
                <div>
                    <p>Unattended Leads</p>
                    <h4>{{ $leadStats['unattended'] ?? 0 }}</h4>
                </div>
                <div class="lead-stat-icon unattended-icon">
                    <i class="ti ti-user-question"></i>
                </div>
            </a>

            {{-- Today's Follow Up --}}
            <a href="{{ route('leads.index', ['filter' => 'today_followup']) }}"class="lead-stat-box">
                <div>
                    <p>Today's Follow Up</p>
                    <h4>{{ $leadStats['today_followup'] ?? 0 }}</h4>
                </div>
                <div class="lead-stat-icon today-followup-icon">
                    <i class="ti ti-calendar-event"></i>
                </div>
            </a>

            {{-- Overdue Follow Up --}}
            <a href="{{ route('leads.index', ['filter' => 'overdue_followup']) }}"
            class="lead-stat-box">
                <div>
                    <p>Overdue Follow Up</p>
                    <h4>{{ $leadStats['overdue_followup'] ?? 0 }}</h4>
                </div>
                <div class="lead-stat-icon overdue-followup-icon">
                    <i class="ti ti-alert-circle"></i>
                </div>
            </a>

            {{-- Upcoming Follow Up --}}
            <a href="{{ route('leads.index', ['filter' => 'upcoming_followup']) }}"
            class="lead-stat-box">
                <div>
                    <p>Upcoming Follow Up</p>
                    <h4>{{ $leadStats['upcoming_followup'] ?? 0 }}</h4>
                </div>
                <div class="lead-stat-icon upcoming-followup-icon">
                    <i class="ti ti-calendar-time"></i>
                </div>
            </a>
        </div>
    </div>

    {{-- Your Leads & Total Leads --}}
    <div class="leads-total-row">
        {{-- Your Leads --}}
        <a href="{{ route('leads.index', ['filter' => 'my_leads']) }}" class="lead-total-box your-leads-box">
            <div>
                <p>Your Leads</p>
                <h4>{{ $leadStats['your_leads'] ?? 0 }}</h4>
            </div>
            <div class="lead-total-icon your-leads-icon">
                <i class="ti ti-user"></i>
            </div>
        </a>

        {{-- Total Leads --}}
        <a href="{{ route('leads.index', ['filter' => 'total_leads']) }}"class="lead-total-box total-leads-box">
            <div>
                <p>Total Leads</p>
                <h4>{{ $leadStats['total_leads'] ?? 0 }}</h4>
            </div>
            <div class="lead-total-icon total-leads-icon">
                <i class="ti ti-users"></i>
            </div>
        </a>
    </div>
    {{-- Reports Card --}}
    <div class="mt-6 bg-white rounded-xl border border-gray-200 shadow-sm p-5">
        {{-- Performance Action --}}
        <a href="{{ route('reports.index') }}" class="performance-action">
            <div class="performance-action-icon">
                <i class="ti ti-chart-bar"></i>
            </div>
            <div class="performance-action-content">
                <h4>Track Your Performance</h4>
                <p>
                    Treak your leads and performance
                </p>
            </div>
            <div class="performance-arrow">
                <i class="ti ti-arrow-right"></i>
            </div>
        </a>

    </div>
@endsection