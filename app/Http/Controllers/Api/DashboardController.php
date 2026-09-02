<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\Lead;

class DashboardController extends Controller
{

    // public function dashboardCounts(): JsonResponse
    // {
    //     $user = auth()->user();

    //     $query = Task::query();

    //     // Admin → all tasks
    //     // Manager / Employee → assigned or approved tasks
    //     if ($user->role->value !== 'admin') {
    //         $query->where(function ($query) use ($user) {
    //             $query->where('assigned_to', $user->id);
    //         });
    //     }

    //     $approvalPendingQuery = Task::query()
    //         ->where('status', 'completed');

    //     // Non-admin → only tasks assigned for approval to logged-in user
    //     if ($user->role->value !== 'admin') {
    //         $approvalPendingQuery->where('approved_by', $user->id);
    //     }

    //     $counts = [
    //         'today_duty' => (clone $query)
    //             ->where('status', 'ongoing')
    //             ->count(),

    //         'overdue_duty' => (clone $query)
    //             ->where('status', 'overdue')
    //             ->count(),

    //         'upcoming_duty' => (clone $query)
    //             ->where('status', 'upcoming')
    //             ->count(),

            
    //         'approvalPending' => $approvalPendingQuery->count(),
    //     ];

    //     return response()->json([
    //         'status' => true,
    //         'status_code' => 200,
    //         'message' => 'Task dashboard counts retrieved successfully.',
    //         'data' => $counts,
    //     ], 200);
    // }

    public function dashboardCounts(Request $request): JsonResponse
    {
        $user = auth()->user();

        $filter = $request->input('filter');

        $perPage = $request->input('per_page', 10);

        /*
        |--------------------------------------------------------------------------
        | Base Query
        |--------------------------------------------------------------------------
        */
        $query = Task::query();

        // Admin → all tasks
        // Manager / Employee → assigned tasks
        if ($user->role->value !== 'admin') {
            $query->where('assigned_to', $user->id);
        }

        /*
        |--------------------------------------------------------------------------
        | Approval Pending Query
        |--------------------------------------------------------------------------
        */
        $approvalPendingQuery = Task::query()
            ->where('status', 'completed');

        // Non-admin → tasks waiting for logged-in user's approval
        if ($user->role->value !== 'admin') {
            $approvalPendingQuery->where('approved_by', $user->id);
        }

        /*
        |--------------------------------------------------------------------------
        | Dashboard Counts
        |--------------------------------------------------------------------------
        */
        $counts = [
            'today_duty' => (clone $query)
                ->where('status', 'ongoing')
                ->count(),

            'overdue_duty' => (clone $query)
                ->where('status', 'overdue')
                ->count(),

            'upcoming_duty' => (clone $query)
                ->where('status', 'upcoming')
                ->count(),

            'approvalPending' => $approvalPendingQuery->count(),
        ];

        /*
        |--------------------------------------------------------------------------
        | Filtered Tasks Query
        |--------------------------------------------------------------------------
        */
        $filteredQuery = null;

        if ($filter === 'approval_pending') {

            $filteredQuery = $approvalPendingQuery;

        } else {

            $filteredQuery = clone $query;

            switch ($filter) {

                case 'today_duty':
                    $filteredQuery->where('status', 'ongoing');
                    break;

                case 'overdue_duty':
                    $filteredQuery->where('status', 'overdue');
                    break;

                case 'upcoming_duty':
                    $filteredQuery->where('status', 'upcoming');
                    break;

                default:
                    $filteredQuery = null;
                    break;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Paginated Tasks
        |--------------------------------------------------------------------------
        */
        $tasks = null;

        if ($filteredQuery) {

            $tasks = $filteredQuery
                ->with([
                    'assignedUser:id,name,email',
                    'approvedBy:id,name,email',
                    'quarters:id,task_id,quarter,start_date,end_date',
                ])
                ->latest('id')
                ->paginate($perPage)
                ->withQueryString();
        }

        /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */
        return response()->json([
            'status' => true,
            'status_code' => 200,
            'message' => 'Task dashboard counts retrieved successfully.',

            'data' => [
                'filter' => $filter,

                'counts' => $counts,

                'tasks' => $tasks ? $tasks->items() : [],

                'pagination' => $tasks ? [
                    'current_page' => $tasks->currentPage(),
                    'per_page' => $tasks->perPage(),
                    'total' => $tasks->total(),
                    'last_page' => $tasks->lastPage(),
                    'from' => $tasks->firstItem(),
                    'to' => $tasks->lastItem(),
                ] : null,
            ],
        ], 200);
    }

   public function dashboardLeadStatistics(Request $request): JsonResponse
{
    $user = auth()->user();

    $filter = $request->input('filter');
    $perPage = $request->input('per_page', 10);

    /*
    |--------------------------------------------------------------------------
    | My Leads Query
    |--------------------------------------------------------------------------
    */
    $myLeadsQuery = Lead::query()
        ->where('assigned_to', $user->id)
        ->where('status', '!=', 'closed');

    /*
    |--------------------------------------------------------------------------
    | Total Leads Query
    |--------------------------------------------------------------------------
    */
    $totalLeadsQuery = Lead::query()
        ->where('status', '!=', 'closed');

    /*
    |--------------------------------------------------------------------------
    | Dashboard Counts
    |--------------------------------------------------------------------------
    */
    $counts = [
        'unattended' => (clone $myLeadsQuery)
            ->whereDoesntHave('callDetails')
            ->count(),

        'today_followup' => (clone $myLeadsQuery)
            ->whereHas('latestCall', function ($q) {
                $q->whereNotNull('next_followup_date')
                    ->whereDate('next_followup_date', today());
            })
            ->count(),

        'overdue_followup' => (clone $myLeadsQuery)
            ->whereHas('latestCall', function ($q) {
                $q->whereNotNull('next_followup_date')
                    ->whereDate('next_followup_date', '<', today());
            })
            ->count(),

        'upcoming_followup' => (clone $myLeadsQuery)
            ->whereHas('latestCall', function ($q) {
                $q->whereNotNull('next_followup_date')
                    ->whereDate('next_followup_date', '>', today());
            })
            ->count(),

        'my_leads' => (clone $myLeadsQuery)
            ->count(),

        'total_leads' => (clone $totalLeadsQuery)
            ->count(),
    ];

    /*
    |--------------------------------------------------------------------------
    | Filter Query
    |--------------------------------------------------------------------------
    */
    $filteredQuery = null;

    switch ($filter) {

        case 'unattended':
            $filteredQuery = (clone $myLeadsQuery)
                ->whereDoesntHave('callDetails');
            break;

        case 'today_followup':
            $filteredQuery = (clone $myLeadsQuery)
                ->whereHas('latestCall', function ($q) {
                    $q->whereNotNull('next_followup_date')
                        ->whereDate('next_followup_date', today());
                });
            break;

        case 'overdue_followup':
            $filteredQuery = (clone $myLeadsQuery)
                ->whereHas('latestCall', function ($q) {
                    $q->whereNotNull('next_followup_date')
                        ->whereDate('next_followup_date', '<', today());
                });
            break;

        case 'upcoming_followup':
            $filteredQuery = (clone $myLeadsQuery)
                ->whereHas('latestCall', function ($q) {
                    $q->whereNotNull('next_followup_date')
                        ->whereDate('next_followup_date', '>', today());
                });
            break;

        case 'my_leads':
            // Logged-in user's assigned open leads
            $filteredQuery = clone $myLeadsQuery;
            break;

        case 'total_leads':
            // All open leads
            $filteredQuery = clone $totalLeadsQuery;
            break;
    }

    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    */
    $leads = null;

    if ($filteredQuery) {
        $leads = $filteredQuery
            ->with([
                'owner',
                'latestCall',
            ])
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /*
    |--------------------------------------------------------------------------
    | Response
    |--------------------------------------------------------------------------
    */
    return response()->json([
        'status' => true,
        'status_code' => 200,
        'message' => 'Lead dashboard statistics retrieved successfully.',

        'data' => [
            'filter' => $filter,

            'counts' => $counts,

            'filtered_count' => $leads
                ? $leads->total()
                : null,

            'leads' => $leads
                ? $leads->items()
                : [],

            'pagination' => $leads ? [
                'current_page' => $leads->currentPage(),
                'per_page' => $leads->perPage(),
                'total' => $leads->total(),
                'last_page' => $leads->lastPage(),
                'from' => $leads->firstItem(),
                'to' => $leads->lastItem(),
            ] : null,
        ],
    ], 200);
}
}