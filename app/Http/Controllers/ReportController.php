<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class ReportController extends Controller
{
    /**
     * Build Task Management Report Query
     */
    private function taskManagementQuery(Request $request)
    {
        $user = auth()->user();

        $query = Task::query()
            ->with([
                'assignedTo:id,name',
                'approvedBy:id,name',
            ]);

        /*
        |--------------------------------------------------------------------------
        | Role Based Access
        |--------------------------------------------------------------------------
        */

        if ($user->role === 'employee') {

            $query->where('assigned_to', $user->id);

        } elseif ($user->role === 'manager') {

            $query->where(function ($q) use ($user) {

                $q->where('assigned_to', $user->id)
                    ->orWhere('created_by', $user->id);

            });
        }


        /*
        |--------------------------------------------------------------------------
        | Task Search
        |--------------------------------------------------------------------------
        */

        if ($request->filled('search')) {

            $search = trim($request->search);

            $query->where(
                'title',
                'like',
                '%' . $search . '%'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Assigned Staff
        |--------------------------------------------------------------------------
        */

        if ($request->filled('assigned_to')) {

            $query->where(
                'assigned_to',
                $request->assigned_to
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Period
        |--------------------------------------------------------------------------
        */

        if ($request->filled('task_type')) {

            $query->where(
                'task_type',
                $request->task_type
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Status
        |--------------------------------------------------------------------------
        */

        if ($request->filled('status')) {

            $query->where(
                'status',
                $request->status
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Date
        |--------------------------------------------------------------------------
        | Your page sends: ?date=
        */

        if ($request->filled('date')) {

            $query->whereDate(
                'created_at',
                $request->date
            );
        }


        return $query;
    }


    /**
     * Task Management Report
     */
    public function taskManagement(Request $request)
    {
        $query = $this->taskManagementQuery($request);

        $totalTasks = (clone $query)->count();

        $completedTasks = (clone $query)
            ->where('status', 'completed')
            ->count();

        $inProgressTasks = (clone $query)
            ->whereIn('status', [
                'in_progress',
                'ongoing',
            ])
            ->count();

        $pendingTasks = (clone $query)
            ->where('status', 'pending')
            ->count();


        $tasks = $query
            ->latest('created_at')
            ->paginate(10)
            ->withQueryString();


        $staff = User::query()
            ->select('id', 'name')
            ->orderBy('name')
            ->get();


        return view(
            'reports.task-management',
            compact(
                'tasks',
                'staff',
                'totalTasks',
                'completedTasks',
                'inProgressTasks',
                'pendingTasks'
            )
        );
    }


    /**
     * Export Task Management Report to Excel
     */
    public function exportExcel(Request $request)
{
    // Filter parameters include aayittulla task query execute aakum
    $query = $this->taskManagementQuery($request);

    $tasks = $query
        ->latest('created_at')
        ->get();

    $filename = 'task-management-report-' . now()->format('Y-m-d-H-i-s') . '.xls';

    return response()
        ->view('reports.export.task-management-excel', compact('tasks'))
        ->header('Content-Type', 'application/vnd.ms-excel')
        ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
}

public function exportPdf(Request $request)
{
    // SAME FILTER QUERY
    $query = $this->taskManagementQuery($request);

    // Get only filtered data
    $tasks = $query
        ->latest('created_at')
        ->get();

    return view(
        'reports.export.task-management-pdf',
        compact('tasks')
    );
}

public function leadManagement(Request $request)
{
    $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
    $endDate = $request->input('end_date', now()->endOfMonth()->toDateString());
    $branchId = $request->input('branch_id');
    $salesmanId = $request->input('salesman_id');

    $leadQuery = Lead::whereBetween('created_at', [
        $startDate . ' 00:00:00',
        $endDate . ' 23:59:59'
    ]);

    if ($branchId) {
        $leadQuery->where('branch_id', $branchId);
    }

    if ($salesmanId) {
        $leadQuery->where('assigned_to', $salesmanId);
    }

    $totalLeads = (clone $leadQuery)->count();

    $followedUpLeads = (clone $leadQuery)
        ->where('status', 'Followed Up')
        ->count();

    $closedWonLeads = (clone $leadQuery)
        ->where('status', 'Won')
        ->count();

    $lostLeads = (clone $leadQuery)
        ->where('status', 'Lost')
        ->count();

    $reassignedLeads = (clone $leadQuery)
        ->whereNotNull('assigned_to')
        ->count();

    $followUpRate = $totalLeads > 0
        ? round(($followedUpLeads / $totalLeads) * 100)
        : 0;

    $conversionRate = $totalLeads > 0
        ? round(($closedWonLeads / $totalLeads) * 100)
        : 0;

    $lossRate = $totalLeads > 0
        ? round(($lostLeads / $totalLeads) * 100)
        : 0;

    $leadSources = (clone $leadQuery)
        ->select('source', DB::raw('COUNT(*) as count'))
        ->groupBy('source')
        ->pluck('count', 'source');

    $salesmenPerformance = (clone $leadQuery)
        ->select(
            'assigned_to',
            DB::raw('COUNT(*) as total_leads'),
            DB::raw("SUM(CASE WHEN status = 'Won' THEN 1 ELSE 0 END) as won_leads"),
            DB::raw("SUM(CASE WHEN status = 'Lost' THEN 1 ELSE 0 END) as lost_leads")
        )
        ->whereNotNull('assigned_to')
        ->groupBy('assigned_to')
        ->get();

    $leads = (clone $leadQuery)
        ->latest()
        ->get();

    return view('reports.lead-management', compact(
        'totalLeads',
        'followedUpLeads',
        'closedWonLeads',
        'lostLeads',
        'reassignedLeads',
        'followUpRate',
        'conversionRate',
        'lossRate',
        'leadSources',
        'salesmenPerformance',
        'startDate',
        'endDate',
        'leads'
    ));
}
}