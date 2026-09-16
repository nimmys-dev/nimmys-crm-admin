@extends('layouts.app')

@section('title', 'Lead Management Report')

@section('content')



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
                <label style="font-size:12px; font-weight:600;">Branch</label>
                <input
                    type="text"
                    name="branch_id"
                    value="{{ request('branch_id') }}"
                    class="form-control"
                    placeholder="Branch ID"
                >
            </div>

            <div>
                <label style="font-size:12px; font-weight:600;">Salesman</label>
                <input
                    type="text"
                    name="salesman_id"
                    value="{{ request('salesman_id') }}"
                    class="form-control"
                    placeholder="Salesman ID"
                >
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
<div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:16px; margin-bottom:20px;">

    <div class="card">
        <p style="font-size:12px; color:var(--text-muted); margin-bottom:5px;">Total Leads</p>
        <h2 style="font-size:24px; font-weight:700;">{{ $totalLeads }}</h2>
    </div>

    <div class="card">
        <p style="font-size:12px; color:var(--text-muted); margin-bottom:5px;">Followed Up</p>
        <h2 style="font-size:24px; font-weight:700;">{{ $followedUpLeads }}</h2>
        <small>{{ $followUpRate }}%</small>
    </div>

    <div class="card">
        <p style="font-size:12px; color:var(--text-muted); margin-bottom:5px;">Won Leads</p>
        <h2 style="font-size:24px; font-weight:700;">{{ $closedWonLeads }}</h2>
        <small>{{ $conversionRate }}%</small>
    </div>

    <div class="card">
        <p style="font-size:12px; color:var(--text-muted); margin-bottom:5px;">Lost Leads</p>
        <h2 style="font-size:24px; font-weight:700;">{{ $lostLeads }}</h2>
        <small>{{ $lossRate }}%</small>
    </div>

    <div class="card">
        <p style="font-size:12px; color:var(--text-muted); margin-bottom:5px;">Reassigned Leads</p>
        <h2 style="font-size:24px; font-weight:700;">{{ $reassignedLeads }}</h2>
    </div>

</div>

{{-- Lead Source --}}
<div class="card" style="margin-bottom:20px;">
    <h3 style="font-size:15px; font-weight:600; margin-bottom:16px;">
        Lead Source Breakdown
    </h3>

    @forelse($leadSources as $source => $count)

        <div style="display:flex; justify-content:space-between; padding:10px 0; border-bottom:1px solid var(--border-color);">
            <span>{{ $source ?: 'Unknown' }}</span>
            <strong>{{ $count }}</strong>
        </div>

    @empty

        <p style="color:var(--text-muted); font-size:13px;">
            No lead source data found.
        </p>

    @endforelse
</div>

{{-- Salesman Performance --}}
<div class="card" style="margin-bottom:20px;">

    <h3 style="font-size:15px; font-weight:600; margin-bottom:16px;">
        Salesman Performance
    </h3>

    <div style="overflow-x:auto;">
        <table style="width:100%; border-collapse:collapse; font-size:13px;">

            <thead>
                <tr style="border-bottom:1px solid var(--border-color);">
                    <th style="text-align:left; padding:10px;">Salesman ID</th>
                    <th style="text-align:center; padding:10px;">Total Leads</th>
                    <th style="text-align:center; padding:10px;">Won</th>
                    <th style="text-align:center; padding:10px;">Lost</th>
                </tr>
            </thead>

            <tbody>

                @forelse($salesmenPerformance as $salesman)

                    <tr style="border-bottom:1px solid var(--border-color);">

                        <td style="padding:10px;">
                            {{ $salesman->assigned_to }}
                        </td>

                        <td style="text-align:center; padding:10px;">
                            {{ $salesman->total_leads }}
                        </td>

                        <td style="text-align:center; padding:10px;">
                            {{ $salesman->won_leads }}
                        </td>

                        <td style="text-align:center; padding:10px;">
                            {{ $salesman->lost_leads }}
                        </td>

                    </tr>

                @empty

                    <tr>
                        <td colspan="4" style="text-align:center; padding:20px; color:var(--text-muted);">
                            No salesman data found.
                        </td>
                    </tr>

                @endforelse

            </tbody>

        </table>
    </div>

</div>

{{-- Leads --}}
<div class="card">

    <h3 style="font-size:15px; font-weight:600; margin-bottom:16px;">
        Lead Details
    </h3>

    <div style="overflow-x:auto;">

        <table style="width:100%; border-collapse:collapse; font-size:13px;">

            <thead>
                <tr style="border-bottom:1px solid var(--border-color);">
                    <th style="padding:10px; text-align:left;">Name</th>
                    <th style="padding:10px; text-align:left;">Phone</th>
                    <th style="padding:10px; text-align:left;">Source</th>
                    <th style="padding:10px; text-align:left;">Status</th>
                    <th style="padding:10px; text-align:left;">Assigned To</th>
                    <th style="padding:10px; text-align:left;">Created</th>
                </tr>
            </thead>

            <tbody>

                @forelse($leads as $lead)

                    <tr style="border-bottom:1px solid var(--border-color);">

                        <td style="padding:10px;">
                            {{ $lead->name ?? 'N/A' }}
                        </td>

                        <td style="padding:10px;">
                            {{ $lead->phone ?? 'N/A' }}
                        </td>

                        <td style="padding:10px;">
                            {{ $lead->source ?? 'N/A' }}
                        </td>

                        <td style="padding:10px;">
                            {{ $lead->status ?? 'N/A' }}
                        </td>

                        <td style="padding:10px;">
                            {{ $lead->assigned_to ?? 'Unassigned' }}
                        </td>

                        <td style="padding:10px;">
                            {{ $lead->created_at?->format('d M Y') }}
                        </td>

                    </tr>

                @empty

                    <tr>
                        <td colspan="6" style="text-align:center; padding:20px; color:var(--text-muted);">
                            No leads found.
                        </td>
                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>

</div>

@endsection