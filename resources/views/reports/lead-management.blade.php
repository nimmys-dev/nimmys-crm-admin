@extends('layouts.app')

@section('title', 'Lead Management Report')

@section('content')

<style>
    .metrics-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 16px;
        margin-bottom: 20px;
    }

    .metric-card {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 16px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    }

    .metric-title {
        font-size: 13px;
        font-weight: 500;
        color: #6b7280;
        margin-bottom: 8px;
    }

    .metric-value-container {
        display: flex;
        align-items: baseline;
        gap: 8px;
    }

    .metric-value {
        font-size: 24px;
        font-weight: 700;
        color: #111827;
        line-height: 1;
        margin: 0;
    }

    .metric-badge {
        font-size: 12px;
        font-weight: 600;
        color: #6b7280;
        background-color: #f3f4f6;
        padding: 2px 6px;
        border-radius: 4px;
    }

    .metric-badge.badge-danger {
        color: #dc2626;
        background-color: #fef2f2;
    }

    .text-danger {
        color: #dc2626 !important;
    }
</style>
<style>
    /* Card Container */
    .lead-card {
        background: #ffffff;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.04);
        border: 1px solid #e2e8f0;
        padding: 20px;
        margin-bottom: 24px;
    }

    .lead-card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 16px;
    }

    .lead-card-title {
        font-size: 16px;
        font-weight: 700;
        color: #0f172a;
        margin: 0;
    }

    /* Table Styling */
    .lead-table-wrapper {
        overflow-x: auto;
        border-radius: 8px;
        border: 1px solid #f1f5f9;
    }

    .lead-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
        text-align: left;
    }

    .lead-table thead th {
        background-color: #f8fafc;
        color: #475569;
        font-weight: 600;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 12px 16px;
        border-bottom: 2px solid #e2e8f0;
        white-space: nowrap;
    }

    .lead-table tbody tr {
        border-bottom: 1px solid #f1f5f9;
        transition: background-color 0.15s ease-in-out;
    }

    .lead-table tbody tr:last-child {
        border-bottom: none;
    }

    .lead-table tbody tr:hover {
        background-color: #f8fafc;
    }

    .lead-table td {
        padding: 12px 16px;
        color: #334155;
        vertical-align: middle;
        white-space: nowrap;
    }

    /* Badges & Status */
    .status-badge {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
        text-transform: capitalize;
    }

    .status-new {
        background-color: #e0f2fe;
        color: #0284c7;
    }

    .status-closed {
        background-color: #fee2e2;
        color: #dc2626;
    }

    .status-default {
        background-color: #f1f5f9;
        color: #64748b;
    }

    .source-pill {
        background-color: #f3f4f6;
        color: #4b5563;
        padding: 2px 8px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 500;
        text-transform: capitalize;
    }

    .assigned-user {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        padding: 3px 8px;
        border-radius: 6px;
        font-size: 12px;
        color: #334155;
        font-weight: 500;
    }
</style>

<div style="margin-bottom:20px;">
    <div style="display:flex; justify-content:space-between; align-items:center;">
        <div>
            <h1 style="font-size:20px; font-weight:700; margin-bottom:4px;">
                Lead Management Report
            </h1>
            <p style="font-size:13px; color:var(--text-muted); margin:0;">
                {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }}
                -
                {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}
            </p>
        </div>
    </div>
</div>

{{-- Filters --}}
<div style="margin-bottom:20px;">
    <form method="GET" action="{{ route('lead-management-report.index') }}">
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:12px; align-items:end;">

            <div>
                <label style="font-size:12px; font-weight:600;">Start Date</label>
                <input
                    type="date"
                    name="start_date"
                    value="{{ $startDate }}"
                    class="form-control"
                >
            </div>

            <div>
                <label style="font-size:12px; font-weight:600;">End Date</label>
                <input
                    type="date"
                    name="end_date"
                    value="{{ $endDate }}"
                    class="form-control"
                >
            </div>

            <div>
                <label style="font-size:12px; font-weight:600;">Staff</label>

                <select name="salesman_id" class="form-control">
                    <option value="">All Staff</option>

                    @foreach($staff as $user)
                        <option value="{{ $user->id }}"
                            {{ request('salesman_id') == $user->id ? 'selected' : '' }}>
                            {{ $user->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <button type="submit" class="btn btn-primary">
                    Filter
                </button>

                <a href="{{ route('lead-management-report.index') }}" class="btn">
                    Reset
                </a>
            </div>

        </div>
    </form>
</div>

{{-- Summary Cards --}}
<div class="metrics-grid">
    <div class="metric-card">
        <span class="metric-title">Total Leads</span>
        <div class="metric-value-container">
            <h2 class="metric-value">{{ $totalLeads }}</h2>
        </div>
    </div>

    <div class="metric-card">
        <span class="metric-title">Open Leads</span>
        <div class="metric-value-container">
            <h2 class="metric-value">{{ $openLeads }}</h2>
        </div>
    </div>

    <div class="metric-card">
        <span class="metric-title">Closed Leads</span>
        <div class="metric-value-container">
            <h2 class="metric-value">{{ $closedLeads }}</h2>
        </div>
    </div>

    <div class="metric-card">
        <span class="metric-title">Overdue Leads</span>
        <div class="metric-value-container">
            <h2 class="metric-value text-danger">{{ $overdueLeads }}</h2>
        </div>
    </div>
</div>

{{-- Salesman Performance --}}
<div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); margin-bottom: 20px; overflow: hidden;">
    {{-- Card Header --}}
    <div style="padding: 14px 20px; border-bottom: 1px solid #e2e8f0; background-color: #ffffff; display: flex; align-items: center; justify-content: space-between;">
        <h3 style="font-size: 15px; font-weight: 600; color: #1e293b; margin: 0;">
            Staff Performance
        </h3>
        <span style="font-size: 12px; color: #64748b; background-color: #f8fafc; border: 1px solid #e2e8f0; padding: 2px 8px; border-radius: 12px;">
            Total Staff: {{ count($salesmenPerformance) }}
        </span>
    </div>

    {{-- Table Container --}}
    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; font-size: 13px; text-align: left;">
            <thead>
                <tr style="background-color: #f8fafc; border-bottom: 1px solid #e2e8f0; color: #475569; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px;">
                    <th style="padding: 12px 20px; font-weight: 600;">Staff Name</th>
                    <th style="padding: 12px 16px; font-weight: 600; text-align: center;">Total Leads</th>
                    <th style="padding: 12px 16px; font-weight: 600; text-align: center;">Open Leads</th>
                </tr>
            </thead>
            <tbody style="color: #334155;">
                @forelse($salesmenPerformance as $salesman)
                    <tr style="border-bottom: 1px solid #f1f5f9; transition: background-color 0.2s;">
                        <td style="padding: 12px 20px; font-weight: 500; color: #0f172a;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div style="width: 30px; height: 30px; border-radius: 50%; background-color: #e0f2fe; color: #0369a1; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 12px;">
                                    {{ strtoupper(substr($salesman->salesman_name ?? 'S', 0, 1)) }}
                                </div>
                                <span>{{ $salesman->salesman_name }}</span>
                            </div>
                        </td>
                        <td style="padding: 12px 16px; text-align: center;">
                            <span style="display: inline-block; padding: 2px 10px; border-radius: 12px; background-color: #f1f5f9; color: #334155; font-weight: 600;">
                                {{ $salesman->total_leads ?? 0 }}
                            </span>
                        </td>
                        <td style="padding: 12px 16px; text-align: center;">
                            <span style="display: inline-block; padding: 2px 10px; border-radius: 12px; background-color: #e0f2fe; color: #0369a1; font-weight: 600;">
                                {{ $salesman->open_leads ?? 0 }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" style="text-align: center; padding: 24px; color: #94a3b8;">
                            No salesman data found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Leads --}}
<div class="lead-card">
    <div class="lead-card-header">
        <h3 class="lead-card-title">
            Lead Details
        </h3>
    </div>

    <div class="lead-table-wrapper">
        <table class="lead-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Source</th>
                    <th>Status</th>
                    <th>Next Follow Up</th>
                    <th>Assigned To</th>
                    <th>Created</th>
                </tr>
            </thead>
            <tbody>
                @forelse($leads as $lead)
                    <tr>
                        <td style="font-weight: 600; color: #0f172a;">
                            {{ $lead->name ?? 'N/A' }}
                        </td>
                        <td style="color: #475569;">
                            {{ $lead->phone ?? 'N/A' }}
                        </td>
                        <td>
                            <span class="source-pill">
                                {{ $lead->source ?? 'N/A' }}
                            </span>
                        </td>
                        <td>
                            @php
                                $rawStatus = $lead->status->value ?? $lead->status ?? '';
                                $statusVal = strtolower($rawStatus);
                            @endphp

                            <span class="status-badge {{ in_array($statusVal, ['new', 'open']) ? 'status-new' : ($statusVal === 'closed' ? 'status-closed' : 'status-default') }}">
                                {{ in_array($statusVal, ['new', 'open']) ? 'Open' : ($lead->status->label() ?? ucfirst($statusVal) ?: 'N/A') }}
                            </span>
                        </td>
                        <td style="color: #64748b;">
                            @php
                                // lead_call_details-ലെ തീയതി എടുക്കുന്നു, ഇല്ലെങ്കിൽ leads table-ലെ അടുത്ത തീയതി എടുക്കും
                                $nextFollowup = $lead->latestCallDetail?->next_followup_date ?? $lead->next_follow_up_at;
                            @endphp

                            @if($nextFollowup)
                                @php
                                    $date = \Carbon\Carbon::parse($nextFollowup);
                                    $today = \Carbon\Carbon::today();
                                @endphp

                                {{ $date->format('d M Y') }}

                                @if($date->lt($today))
                                    <span class="badge bg-danger ms-1">Overdue</span>
                                @elseif($date->isToday())
                                    <span class="badge bg-warning text-dark ms-1">Ongoing</span>
                                @else
                                    <span class="badge bg-info ms-1">Upcoming</span>
                                @endif
                            @else
                                <span class="text-muted">N/A</span>
                            @endif
                        </td>
                        <td>
                            @if($lead->assigned_to)
                                <span class="assigned-user">
                                    {{ $lead->assignedUser->name ?? $lead->assigned_to }}
                                </span>
                            @else
                                <span style="color: #94a3b8; font-style: italic;">Unassigned</span>
                            @endif
                        </td>
                        <td style="color: #64748b;">
                            {{ $lead->created_at?->format('d M Y') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align:center; padding:24px; color:#94a3b8;">
                            No leads found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        {{-- Pagination Links Container --}}
        <div class="mt-3 d-flex justify-content-end">
            {{ $leads->links() }}
        </div>
    </div>
</div>
@endsection