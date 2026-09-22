@extends('layouts.app') 

@section('title', 'Dashboard') 

@section('content') 

<style>
    /* Global Dashboard Styles */
    :root {
        --bg-overdue: #ffebee;
        --text-overdue: #d32f2f;
        --bg-today: #e3f2fd;
        --text-today: #1976d2;
        --bg-upcoming: #f3e5f5;
        --text-upcoming: #7b1fa2;
        --bg-pending: #fff8e1;
        --text-pending: #f57c00;
        --bg-sending: #e8f5e9;
        --text-sending: #388e3c;
        --bg-unattended: #efebe9;
        --text-unattended: #5d4037;
    }

    .dashboard-layout {
        display: grid;
        grid-template-columns: 1fr 340px;
        gap: 24px;
    }

    @media (max-width: 1024px) {
        .dashboard-layout {
            grid-template-columns: 1fr;
        }
    }

    /* Section Header */
    .section-title-wrap {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 14px;
        margin-top: 20px;
    }

    .section-title {
        font-size: 16px;
        font-weight: 700;
        color: #1e293b;
        display: flex;
        align-items: center;
        gap: 8px;
        margin: 0;
    }

    .section-title::before {
        content: '';
        display: inline-block;
        width: 4px;
        height: 18px;
        background-color: #ef4444;
        border-radius: 4px;
    }

    .view-all-link {
        font-size: 13px;
        color: #ef4444;
        font-weight: 600;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .view-all-link:hover {
        text-decoration: underline;
    }

    /* Grid Layouts */
    .stats-grid-5 {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 16px;
    }

    .stats-grid-4 {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 16px;
    }

    @media (max-width: 1200px) {
        .stats-grid-5 { grid-template-columns: repeat(3, 1fr); }
        .stats-grid-4 { grid-template-columns: repeat(2, 1fr); }
    }

    @media (max-width: 640px) {
        .stats-grid-5, .stats-grid-4 { grid-template-columns: repeat(2, 1fr); }
    }

    /* Stat Card Style */
    .dash-card {
        background: #ffffff;
        border-radius: 12px;
        padding: 16px;
        text-decoration: none;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        position: relative;
        transition: transform 0.2s, box-shadow 0.2s;
        border: 1px solid rgba(0,0,0,0.03);
    }

    .dash-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }

    .dash-card-icon {
        width: 42px;
        height: 42px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        margin-bottom: 8px;
    }

    .dash-card-value {
        font-size: 20px;
        font-weight: 700;
        color: #0f172a;
        margin: 0;
        line-height: 1.2;
    }

    .dash-card-label {
        font-size: 12px;
        color: #64748b;
        font-weight: 500;
        margin-top: 4px;
        text-align: center;
    }

    .dash-card-arrow {
        position: absolute;
        bottom: 12px;
        right: 12px;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 10px;
        background: #ffffff;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }

    /* Dynamic Card Color Themes */
    .theme-overdue { background-color: var(--bg-overdue); }
    .theme-overdue .dash-card-icon { color: var(--text-overdue); }
    .theme-overdue .dash-card-arrow { color: var(--text-overdue); }

    .theme-today { background-color: var(--bg-today); }
    .theme-today .dash-card-icon { color: var(--text-today); }
    .theme-today .dash-card-arrow { color: var(--text-today); }

    .theme-upcoming { background-color: var(--bg-upcoming); }
    .theme-upcoming .dash-card-icon { color: var(--text-upcoming); }
    .theme-upcoming .dash-card-arrow { color: var(--text-upcoming); }

    .theme-pending { background-color: var(--bg-pending); }
    .theme-pending .dash-card-icon { color: var(--text-pending); }
    .theme-pending .dash-card-arrow { color: var(--text-pending); }

    .theme-sending { background-color: var(--bg-sending); }
    .theme-sending .dash-card-icon { color: var(--text-sending); }
    .theme-sending .dash-card-arrow { color: var(--text-sending); }

    .theme-unattended { background-color: var(--bg-unattended); }
    .theme-unattended .dash-card-icon { color: var(--text-unattended); }
    .theme-unattended .dash-card-arrow { color: var(--text-unattended); }

    /* Total Leads Strip */
    .leads-summary-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
        margin-top: 16px;
    }

    .summary-strip-card {
        background: #ffffff;
        border-radius: 12px;
        padding: 14px 18px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        text-decoration: none;
        border: 1px solid #f1f5f9;
    }

    /* Performance Report Strip */
    .report-banner {
        background: #fff5f5;
        border: 1px solid #ffe3e3;
        border-radius: 12px;
        padding: 16px 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        text-decoration: none;
        margin-top: 10px;
    }

    /* Sidebar Widgets */
    .sidebar-widget {
        background: #ffffff;
        border-radius: 16px;
        padding: 20px;
        margin-bottom: 20px;
        border: 1px solid #f1f5f9;
        box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    }

    .quick-action-btn {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 10px;
        border-radius: 10px;
        text-decoration: none;
        font-weight: 600;
        font-size: 13px;
        
    }
    
</style>
{{-- Dashboard Duty Summary --}}
<div class="dashboard-layout mt-5">

    <div class="main-dashboard-content">

    {{-- SECTION 1: MY TASK --}}
    <div class="section-title-wrap" style="margin-top: 0;">
        <h4 class="section-title">My Task</h4>
        <a href="{{ route('tasks.index', ['my_tasks' => 1]) }}" class="view-all-link">View All <i class="ti ti-arrow-right"></i></a>
    </div>
    <div class="stats-grid-5">
        <a href="{{ route('tasks.index', ['filter' => 'overdue','my_tasks' => 1]) }}" class="dash-card theme-overdue">
            <div class="dash-card-icon"><i class="ti ti-alert-circle"></i></div>
            <h3 class="dash-card-value">{{ $overdueDuty }}</h3>
            <span class="dash-card-label">Overdue Duty</span>
            <div class="dash-card-arrow"><i class="ti ti-arrow-right"></i></div>
        </a>

        <a href="{{ route('tasks.index', ['filter' => 'ongoing','my_tasks' => 1]) }}" class="dash-card theme-today">
            <div class="dash-card-icon"><i class="ti ti-calendar-event"></i></div>
            <h3 class="dash-card-value">{{ $todayDuty }}</h3>
            <span class="dash-card-label">Today's Duty</span>
            <div class="dash-card-arrow"><i class="ti ti-arrow-right"></i></div>
        </a>

        <a href="{{ route('tasks.index', ['filter' => 'upcoming','my_tasks' => 1]) }}" class="dash-card theme-upcoming">
            <div class="dash-card-icon"><i class="ti ti-clock"></i></div>
            <h3 class="dash-card-value">{{ $upcomingDuty }}</h3>
            <span class="dash-card-label">Upcoming Duty</span>
            <div class="dash-card-arrow"><i class="ti ti-arrow-right"></i></div>
        </a>

        <a href="{{ route('tasks.approval.index', ['filter' => 'pending','my_tasks' => 1]) }}" class="dash-card theme-pending">
            <div class="dash-card-icon"><i class="ti ti-briefcase"></i></div>
            <h3 class="dash-card-value">{{ $approvalPending }}</h3>
            <span class="dash-card-label">Approval Pending</span>
            <div class="dash-card-arrow"><i class="ti ti-arrow-right"></i></div>
        </a>

        <a href="{{ route('tasks.sending-approval') }}" class="dash-card theme-sending">
            <div class="dash-card-icon"><i class="ti ti-send"></i></div>
            <h3 class="dash-card-value">{{ $sendingApproval }}</h3>
            <span class="dash-card-label">Sending Approval</span>
            <div class="dash-card-arrow"><i class="ti ti-arrow-right"></i></div>
        </a>
    </div>

    {{-- SECTION 3: MY LEADS --}}
    <div class="section-title-wrap">
        <h4 class="section-title">My Leads</h4>
        <a href="{{ route('leads.index', ['filter' => 'my_leads']) }}" class="view-all-link">View All <i class="ti ti-arrow-right"></i></a>
    </div>
    <div class="stats-grid-4">
        <a href="{{ route('leads.index', ['filter' => 'my_unattended']) }}" class="dash-card theme-unattended">
            <div class="dash-card-icon"><i class="ti ti-users"></i></div>
            <h3 class="dash-card-value">{{ $leadStats['unattended'] ?? 0 }}</h3>
            <span class="dash-card-label">Unattended Leads</span>
            <div class="dash-card-arrow"><i class="ti ti-arrow-right"></i></div>
        </a>

        <a href="{{ route('leads.index', ['filter' => 'my_overdue_followup']) }}" class="dash-card theme-overdue">
            <div class="dash-card-icon"><i class="ti ti-user-plus"></i></div>
            <h3 class="dash-card-value">{{ $leadStats['overdue_followup'] ?? 0 }}</h3>
            <span class="dash-card-label">Overdue Follow Up</span>
            <div class="dash-card-arrow"><i class="ti ti-arrow-right"></i></div>
        </a>

        <a href="{{ route('leads.index', ['filter' => 'my_today_followup']) }}" class="dash-card theme-today">
            <div class="dash-card-icon"><i class="ti ti-calendar-event"></i></div>
            <h3 class="dash-card-value">{{ $leadStats['today_followup'] ?? 0 }}</h3>
            <span class="dash-card-label">Today's Follow Up</span>
            <div class="dash-card-arrow"><i class="ti ti-arrow-right"></i></div>
        </a>

        <a href="{{ route('leads.index', ['filter' => 'my_upcoming_followup']) }}" class="dash-card theme-upcoming">
            <div class="dash-card-icon"><i class="ti ti-clock"></i></div>
            <h3 class="dash-card-value">{{ $leadStats['upcoming_followup'] ?? 0 }}</h3>
            <span class="dash-card-label">Upcoming Follow Up</span>
            <div class="dash-card-arrow"><i class="ti ti-arrow-right"></i></div>
        </a>
    </div>

    {{-- YOUR LEADS & TOTAL LEADS STRIP --}}
    <div class="leads-summary-row">
        <a href="{{ route('leads.index', ['filter' => 'my_leads']) }}" class="summary-strip-card" style="background-color: #fefce8;">
            <div class="d-flex align-items-center gap-3">
                <div style="width: 40px; height: 40px; background: #fef08a; border-radius: 10px; display:flex; align-items:center; justify-content:center; color: #ca8a04; font-size: 20px;">
                    <i class="ti ti-users"></i>
                </div>
                <div>
                    <span style="font-size: 12px; color: #854d0e; font-weight: 500;">Your Leads</span>
                    <h4 style="margin: 0; font-weight: 700; color: #713f12;">{{ $leadStats['your_leads'] ?? 0 }}</h4>
                </div>
            </div>
            <i class="ti ti-chevron-right" style="color: #ca8a04;"></i>
        </a>

        <a href="{{ route('leads.index', ['filter' => 'total_leads']) }}" class="summary-strip-card" style="background-color: #f0fdf4;">
            <div class="d-flex align-items-center gap-3">
                <div style="width: 40px; height: 40px; background: #bbf7d0; border-radius: 10px; display:flex; align-items:center; justify-content:center; color: #16a34a; font-size: 20px;">
                    <i class="ti ti-layers-intersect"></i>
                </div>
                <div>
                    <span style="font-size: 12px; color: #166534; font-weight: 500;">Total Leads</span>
                    <h4 style="margin: 0; font-weight: 700; color: #14532d;">{{ $leadStats['total_leads'] ?? 0 }}</h4>
                </div>
            </div>
            <i class="ti ti-chevron-right" style="color: #16a34a;"></i>
        </a>
    </div>


    {{-- SECTION 5: REPORT --}}
    <div class="section-title-wrap">
        <h4 class="section-title">Report</h4>
    </div>
    <a href="{{ route('lead-management-report.index') }}" class="report-banner">
        <div class="d-flex align-items-center gap-3">
            <div style="width: 42px; height: 42px; background: #ef4444; color: #ffffff; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px;">
                <i class="ti ti-trending-up"></i>
            </div>
            <div>
                <h5 style="margin: 0; font-size: 15px; font-weight: 700; color: #1e293b;">Your Performance</h5>
                <p style="margin: 0; font-size: 12px; color: #64748b;">Track your leads and performance</p>
            </div>
        </div>
        <i class="ti ti-chevron-right" style="color: #ef4444; font-size: 18px;"></i>
    </a>

</div>
<div class="sidebar-column">

    {{-- Welcome Widget --}}
    <div class="sidebar-widget d-flex align-items-center justify-content-between" style="background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);">
        <div>
            <h5 class="fw-bold text-dark mb-1" style="font-size: 16px;">Welcome Back!</h5>
            <h6 class="fw-bold text-dark mb-2" style="font-size: 14px;">
                {{ auth()->user()->name ?? 'Admin' }}
            </h6>

            <p class="text-muted m-0" style="font-size: 11px; line-height: 1.4;">Let's make today productive<br>and grow your business.</p>
        </div>
        <div style="width: 60px; height: 60px; background: #eff6ff; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #2563eb; font-size: 32px;">
            <i class="ti ti-target"></i>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="sidebar-widget">
        <h6 class="fw-bold text-dark mb-3" style="font-size: 14px;">Quick Actions</h6>
        <div class="row g-2">
            
            {{-- Add Lead: Protected by 'leads' middleware (Admin, Manager, or Employee with lead_module_access) --}}
            @if(in_array(auth()->user()->role, ['admin', 'manager']) || auth()->user()->lead_module_access)
                <div class="col-6">
                    <a href="{{ route('leads.create') }}" class="quick-action-btn" style="background: #fef2f2; color: #dc2626;">
                        <i class="ti ti-plus"></i> Add Lead
                    </a>
                </div>
            @endif

            {{-- Add Task: Protected by tasks policy/management --}}
            @can('tasks.manage') {{-- Or use @can('create', App\Models\Task::class) if using policies --}}
                <div class="col-6">
                    <a href="{{ route('tasks.create') }}" class="quick-action-btn" style="background: #eff6ff; color: #2563eb;">
                        <i class="ti ti-check"></i> Add Task
                    </a>
                </div>
            @endcan

            {{-- View Reports: Inside the 'can:staff.manage' middleware group in your web.php --}}
            @can('staff.manage')
                <div class="col-6">
                    <a href="{{ route('lead-management-report.index') }}" class="quick-action-btn" style="background: #f0fdf4; color: #16a34a;">
                        <i class="ti ti-chart-bar"></i> View Reports
                    </a>
                </div>
            @endcan

            {{-- Manage Staff: Inside the 'can:staff.manage' middleware group in your web.php --}}
            @can('staff.manage')
                <div class="col-6">
                    <a href="{{ route('staff.index') }}" class="quick-action-btn" style="background: #faf5ff; color: #9333ea;">
                        <i class="ti ti-users"></i> Manage Staff
                    </a>
                </div>
            @endcan

        </div>
    </div>

    {{-- Recent Activities --}}
    <!-- <div class="sidebar-widget">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h6 class="fw-bold text-dark m-0" style="font-size: 14px;">Recent Activities</h6>
            <a href="#" class="view-all-link" style="font-size: 11px;">View All <i class="ti ti-arrow-right"></i></a>
        </div>

        <div class="d-flex flex-column gap-3">
            @forelse($recentActivities as $activity)
                @php
                    // activity_type ഫീൽഡ് അനുസരിച്ച് Icon-ഉം Colors-ഉം മാറ്റുന്നു
                    $type = strtolower($activity->activity_type ?? '');

                    if ($type === 'reassigned') {
                        $bg = '#fefce8'; $color = '#ca8a04'; $icon = 'ti-user-check';
                    } elseif (str_contains($type, 'created') || str_contains($type, 'added')) {
                        $bg = '#f0fdf4'; $color = '#16a34a'; $icon = 'ti-file-text';
                    } elseif (str_contains($type, 'updated') || str_contains($type, 'status')) {
                        $bg = '#eff6ff'; $color = '#2563eb'; $icon = 'ti-refresh';
                    } elseif (str_contains($type, 'deleted')) {
                        $bg = '#fef2f2'; $color = '#dc2626'; $icon = 'ti-trash';
                    } else {
                        $bg = '#faf5ff'; $color = '#9333ea'; $icon = 'ti-bell';
                    }
                @endphp

                <div class="d-flex align-items-center gap-2.5">
                    <div style="width: 32px; height: 32px; background: {{ $bg }}; color: {{ $color }}; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 16px;">
                        <i class="ti {{ $icon }}"></i>
                    </div>
                    <div>
                        <h6 class="m-0 fw-semibold text-dark" style="font-size: 12px;">{{ $activity->description }}</h6>
                        <span class="text-muted" style="font-size: 10px;">
                            {{ \Carbon\Carbon::parse($activity->created_at)->diffForHumans() }}
                        </span>
                    </div>
                </div>
            @empty
                <div class="text-center text-muted py-2" style="font-size: 12px;">
                    No recent activities found.
                </div>
            @endforelse
        </div>
    </div> -->

    {{-- Motivational Quote Box --}}
    <div class="sidebar-widget" style="background: linear-gradient(180deg, #ffffff 0%, #fef2f2 100%); border-color: #fee2e2;">
        <i class="ti ti-quote" style="font-size: 32px; color: #f87171; display: block; margin-bottom: 8px;"></i>
        <h5 class="fw-bold text-dark mb-1" style="font-size: 15px;">Better Leads.<br>Bigger Opportunities.</h5>
        <span class="text-muted" style="font-size: 11px;">Nimmys Camera Centre</span>
    </div>

</div>

@endsection