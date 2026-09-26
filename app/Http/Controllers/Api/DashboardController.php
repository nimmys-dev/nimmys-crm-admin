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

    // public function dashboardCounts(Request $request): JsonResponse
    // {
    //     $user = auth()->user();

    //     // Request Inputs
    //     $filter  = $request->input('filter');            // todayDuty, overdueDuty, upcomingDuty, approvalPending, sendingApproval
    //     $scope   = $request->input('scope', 'my_tasks'); // my_tasks | all_tasks
    //     $perPage = $request->input('per_page', 10);
    //     $role    = is_object($user->role) ? ($user->role->value ?? null) : $user->role;

    //     /*
    //     |--------------------------------------------------------------------------
    //     | 1. UPDATE AUTOMATIC TASK STATUSES
    //     |--------------------------------------------------------------------------
    //     */
    //     Task::query()
    //         ->whereNotIn('status', ['completed', 'approval_pending', 'approved', 'closed'])
    //         ->get()
    //         ->each(fn (Task $task) => $task->updateAutomaticStatus());

    //     /*
    //     |--------------------------------------------------------------------------
    //     | 2. MY TASKS COUNTS (Only tasks assigned to logged-in user)
    //     |--------------------------------------------------------------------------
    //     */
    //     $myBaseQuery = Task::query()->where('assigned_to', $user->id);

    //     $myTaskCounts = [
    //         'todayDuty'       => (clone $myBaseQuery)->where('status', 'ongoing')->count(),
    //         'overdueDuty'     => (clone $myBaseQuery)->where('status', 'overdue')->count(),
    //         'upcomingDuty'    => (clone $myBaseQuery)->where('status', 'upcoming')->count(),
    //         // FIX 1: Filter count strictly for assigned_to AND approved_by
    //         'approvalPending' => (clone $myBaseQuery)->where('status', 'completed')->where('approved_by', $user->id)->count(),
    //         'sendingApproval' => (clone $myBaseQuery)->where('status', 'completed')->count(),
    //     ];

    //     /*
    //     |--------------------------------------------------------------------------
    //     | 3. ALL TASKS COUNTS
    //     |--------------------------------------------------------------------------
    //     */
    //     $allBaseQuery = Task::query();

    //     $allTaskCounts = [
    //         'todayDuty'       => (clone $allBaseQuery)->where('status', 'ongoing')->count(),
    //         'overdueDuty'     => (clone $allBaseQuery)->where('status', 'overdue')->count(),
    //         'upcomingDuty'    => (clone $allBaseQuery)->where('status', 'upcoming')->count(),
    //         'approvalPending' => Task::query()->where('status', 'completed')->count(),
    //     ];

    //     /*
    //     |--------------------------------------------------------------------------
    //     | 4. CARD CLICK FILTERING LOGIC
    //     |--------------------------------------------------------------------------
    //     */
    //     $filteredQuery = null;

    //     if ($filter) {
    //         switch ($filter) {
    //             case 'todayDuty':
    //             case 'today_duty':
    //                 $filteredQuery = Task::query()->where('status', 'ongoing');
    //                 if ($scope === 'my_tasks') {
    //                     $filteredQuery->where('assigned_to', $user->id);
    //                 }
    //                 break;

    //             case 'overdueDuty':
    //             case 'overdue_duty':
    //                 $filteredQuery = Task::query()->where('status', 'overdue');
    //                 if ($scope === 'my_tasks') {
    //                     $filteredQuery->where('assigned_to', $user->id);
    //                 }
    //                 break;

    //             case 'upcomingDuty':
    //             case 'upcoming_duty':
    //                 $filteredQuery = Task::query()->where('status', 'upcoming');
    //                 if ($scope === 'my_tasks') {
    //                     $filteredQuery->where('assigned_to', $user->id);
    //                 }
    //                 break;

    //             case 'approvalPending':
    //             case 'approval_pending':
    //                 $filteredQuery = Task::query()->where('status', 'completed');
                    
    //                 if ($scope === 'my_tasks') {
    //                     // FIX 2: Strict condition to fetch ONLY assigned user's tasks
    //                     $filteredQuery->where('assigned_to', $user->id)
    //                         ->where('approved_by', $user->id);
    //                 }
    //                 break;

    //             case 'sendingApproval':
    //             case 'sending_approval':
    //                 $filteredQuery = Task::query()
    //                     ->where('status', 'completed')
    //                     ->where('assigned_to', $user->id);
    //                 break;
    //         }
    //     }

    //     /*
    //     |--------------------------------------------------------------------------
    //     | 5. FETCH PAGINATED RESULTS
    //     |--------------------------------------------------------------------------
    //     */
    //     $tasks = null;

    //     if ($filteredQuery) {
    //         $tasks = $filteredQuery
    //             ->with([
    //                 'assignedUser:id,name,email',
    //                 'approvedBy:id,name,email',
    //                 'quarters:id,task_id,quarter,start_date,end_date',
    //             ])
    //             ->latest('id')
    //             ->paginate($perPage)
    //             ->withQueryString();
    //             $tasks->getCollection()->transform(function ($task) {
    //                 $task->assigned_to_name = $task->assignedUser?->name;
    //                 return $task;
    //             });
    //     }

    //     /*
    //     |--------------------------------------------------------------------------
    //     | 6. RETURN RESPONSE
    //     |--------------------------------------------------------------------------
    //     */
    //     return response()->json([
    //         'status'      => true,
    //         'status_code' => 200,
    //         'message'     => 'Task dashboard counts retrieved successfully.',

    //         'data' => [
    //             'filter' => $filter,
    //             'scope'  => $scope,

    //             'counts' => [
    //                 'myTasks'  => $myTaskCounts,
    //                 'allTasks' => $allTaskCounts,
    //             ],

    //             'tasks' => $tasks ? $tasks->items() : [],

    //             'pagination' => $tasks ? [
    //                 'current_page' => $tasks->currentPage(),
    //                 'per_page'     => $tasks->perPage(),
    //                 'total'        => $tasks->total(),
    //                 'last_page'    => $tasks->lastPage(),
    //                 'from'         => $tasks->firstItem(),
    //                 'to'           => $tasks->lastItem(),
    //             ] : null,
    //         ],
    //     ], 200);
    // }

    public function dashboardCounts(Request $request): JsonResponse
    {
        $user = auth()->user();

        // Request Inputs
        $filter  = $request->input('filter');
        $scope   = $request->input('scope', 'my_tasks'); // my_tasks | all_tasks
        $search  = trim($request->input('search', ''));
        $perPage = min((int) $request->input('per_page', 10), 100);

        $role = is_object($user->role)
            ? ($user->role->value ?? null)
            : $user->role;


        /*
        |--------------------------------------------------------------------------
        | 1. UPDATE AUTOMATIC TASK STATUSES
        |--------------------------------------------------------------------------
        */

        // Task::query()
        //     ->whereNotIn('status', [
        //         'completed',
        //         'approval_pending',
        //         'approved',
        //         'closed'
        //     ])
        //     ->get()
        //     ->each(fn (Task $task) => $task->updateAutomaticStatus());
        \Log::info('Dashboard status update started', [
            'time' => now()->toDateTimeString(),
            'timezone' => config('app.timezone'),
            'server_timezone' => date_default_timezone_get(),
        ]);

        Task::query()
        ->whereNotIn('status', [
            'completed',
            'approval_pending',
            'approved',
            'closed'
        ])
        ->get()
        ->each(function (Task $task) {

            \Log::info('Before automatic status', [
                'task_id' => $task->id,
                'status' => $task->status,
                'task_type' => $task->task_type,
                'created_at' => $task->created_at,
            ]);

            $task->updateAutomaticStatus();

            $task->refresh();

            \Log::info('After automatic status', [
                'task_id' => $task->id,
                'status' => $task->status,
            ]);
        });



        /*
        |--------------------------------------------------------------------------
        | 2. SEARCH FUNCTION
        |--------------------------------------------------------------------------
        |
        | One "search" parameter searches:
        | - Task title
        | - Assigned user name/email
        | - Approved user name/email
        |
        */

        $applySearch = function ($query) use ($search) {

            if ($search === '') {
                return $query;
            }

            $query->where(function ($q) use ($search) {

                // Task title
                $q->where('title', 'like', '%' . $search . '%')

                    // Assigned user
                    ->orWhereHas('assignedUser', function ($userQuery) use ($search) {

                        $userQuery
                            ->where('name', 'like', '%' . $search . '%')
                            ->orWhere('email', 'like', '%' . $search . '%');

                    })

                    // Approved user
                    ->orWhereHas('approvedBy', function ($userQuery) use ($search) {

                        $userQuery
                            ->where('name', 'like', '%' . $search . '%')
                            ->orWhere('email', 'like', '%' . $search . '%');

                    });

            });

            return $query;
        };


        /*
        |--------------------------------------------------------------------------
        | 3. MY TASKS COUNTS
        |--------------------------------------------------------------------------
        */

        $myBaseQuery = Task::query()
            ->where('assigned_to', $user->id);

        // Apply search to my tasks
        $mySearchQuery = $applySearch(clone $myBaseQuery);


        $myTaskCounts = [

            'todayDuty' => (clone $mySearchQuery)
                ->where('status', 'ongoing')
                ->count(),

            'overdueDuty' => (clone $mySearchQuery)
                ->where('status', 'overdue')
                ->count(),

            'upcomingDuty' => (clone $mySearchQuery)
                ->where('status', 'upcoming')
                ->count(),

            'approvalPending' => (clone $mySearchQuery)
                ->where('status', 'completed')
                ->where('approved_by', $user->id)
                ->count(),

            'sendingApproval' => (clone $mySearchQuery)
                ->where('status', 'completed')
                ->count(),
        ];


        /*
        |--------------------------------------------------------------------------
        | 4. ALL TASKS COUNTS
        |--------------------------------------------------------------------------
        */

        $allBaseQuery = Task::query();

        // Apply search to all tasks
        $allSearchQuery = $applySearch(clone $allBaseQuery);


        $allTaskCounts = [

            'todayDuty' => (clone $allSearchQuery)
                ->where('status', 'ongoing')
                ->count(),

            'overdueDuty' => (clone $allSearchQuery)
                ->where('status', 'overdue')
                ->count(),

            'upcomingDuty' => (clone $allSearchQuery)
                ->where('status', 'upcoming')
                ->count(),

            'approvalPending' => (clone $allSearchQuery)
                ->where('status', 'completed')
                ->count(),
        ];


        /*
        |--------------------------------------------------------------------------
        | 5. CARD CLICK FILTERING LOGIC
        |--------------------------------------------------------------------------
        */

        // $filteredQuery = null;

        // if ($filter) {

        //     switch ($filter) {

        //         /*
        //         |--------------------------------------------------------------------------
        //         | TODAY DUTY
        //         |--------------------------------------------------------------------------
        //         */

        //         case 'todayDuty':
        //         case 'today_duty':

        //             $filteredQuery = Task::query()
        //                 ->where('status', 'ongoing');

        //             if ($scope === 'my_tasks') {
        //                 $filteredQuery->where('assigned_to', $user->id);
        //             }

        //             $filteredQuery = $applySearch($filteredQuery);

        //             break;


        //         /*
        //         |--------------------------------------------------------------------------
        //         | OVERDUE DUTY
        //         |--------------------------------------------------------------------------
        //         */

        //         case 'overdueDuty':
        //         case 'overdue_duty':

        //             $filteredQuery = Task::query()
        //                 ->where('status', 'overdue');

        //             if ($scope === 'my_tasks') {
        //                 $filteredQuery->where('assigned_to', $user->id);
        //             }

        //             $filteredQuery = $applySearch($filteredQuery);

        //             break;


        //         /*
        //         |--------------------------------------------------------------------------
        //         | UPCOMING DUTY
        //         |--------------------------------------------------------------------------
        //         */

        //         case 'upcomingDuty':
        //         case 'upcoming_duty':

        //             $filteredQuery = Task::query()
        //                 ->where('status', 'upcoming');

        //             if ($scope === 'my_tasks') {
        //                 $filteredQuery->where('assigned_to', $user->id);
        //             }

        //             $filteredQuery = $applySearch($filteredQuery);

        //             break;


        //         /*
        //         |--------------------------------------------------------------------------
        //         | APPROVAL PENDING
        //         |--------------------------------------------------------------------------
        //         */

        //         case 'approvalPending':
        //         case 'approval_pending':

        //             $filteredQuery = Task::query()
        //                 ->where('status', 'completed');

        //             if ($scope === 'my_tasks') {

        //                 $filteredQuery
        //                     ->where('assigned_to', $user->id)
        //                     ->where('approved_by', $user->id);
        //             }

        //             $filteredQuery = $applySearch($filteredQuery);

        //             break;


        //         /*
        //         |--------------------------------------------------------------------------
        //         | SENDING APPROVAL
        //         |--------------------------------------------------------------------------
        //         */

        //         case 'sendingApproval':
        //         case 'sending_approval':

        //             $filteredQuery = Task::query()
        //                 ->where('status', 'completed')
        //                 ->where('assigned_to', $user->id);

        //             $filteredQuery = $applySearch($filteredQuery);

        //             break;
        //     }
        // }


        $filteredQuery = Task::query();

        /*
        |--------------------------------------------------------------------------
        | Scope
        |--------------------------------------------------------------------------
        | all_tasks → all tasks
        | my_tasks  → current user's tasks
        |--------------------------------------------------------------------------
        */
        if ($scope === 'my_tasks') {
            $filteredQuery->where('assigned_to', $user->id);
        }

        /*
        |--------------------------------------------------------------------------
        | Filter
        |--------------------------------------------------------------------------
        */
        if ($filter) {

            switch ($filter) {

                case 'todayDuty':
                case 'today_duty':
                    $filteredQuery->where('status', 'ongoing');
                    break;

                case 'overdueDuty':
                case 'overdue_duty':
                    $filteredQuery->where('status', 'overdue');
                    break;

                case 'upcomingDuty':
                case 'upcoming_duty':
                    $filteredQuery->where('status', 'upcoming');
                    break;

                case 'approvalPending':
                case 'approval_pending':
                    $filteredQuery->where('status', 'completed');

                    if ($scope === 'my_tasks') {
                        $filteredQuery->where('approved_by', $user->id);
                    }

                    break;

                case 'sendingApproval':
                case 'sending_approval':
                    $filteredQuery->where('status', 'completed');
                    break;
            }
        }

        $filteredQuery = $applySearch($filteredQuery);


        /*
        |--------------------------------------------------------------------------
        | 6. FETCH PAGINATED RESULTS
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


            /*
            |--------------------------------------------------------------------------
            | Add assigned_to_name
            |--------------------------------------------------------------------------
            */

            $tasks->getCollection()->transform(function ($task) {

                $task->assigned_to_name = $task->assignedUser?->name;

                return $task;
            });
        }


        /*
        |--------------------------------------------------------------------------
        | 7. RETURN RESPONSE
        |--------------------------------------------------------------------------
        */

        return response()->json([

            'status'      => true,
            'status_code' => 200,
            'message'     => 'Task dashboard counts retrieved successfully.',

            'data' => [

                'filter' => $filter,

                'scope' => $scope,

                'search' => $search,

                'counts' => [

                    'myTasks' => $myTaskCounts,

                    'allTasks' => $allTaskCounts,
                ],

                'tasks' => $tasks
                    ? $tasks->items()
                    : [],

                'pagination' => $tasks
                    ? [
                        'current_page' => $tasks->currentPage(),
                        'per_page'     => $tasks->perPage(),
                        'total'        => $tasks->total(),
                        'last_page'    => $tasks->lastPage(),
                        'from'         => $tasks->firstItem(),
                        'to'           => $tasks->lastItem(),
                    ]
                    : null,
            ],

        ], 200);
    }



// public function getDashboardLeadStatistics(Request $request): JsonResponse
// {
//     $user = auth()->user();

//     $filter  = $request->input('filter');
//     $scope   = $request->input('scope', 'my_leads'); // my_leads | all_leads
//     $perPage = $request->input('per_page', 10);

//     /*
//     |--------------------------------------------------------------------------
//     | 1. BASE QUERIES
//     |--------------------------------------------------------------------------
//     */
//     // Logged-in user's assigned leads
//     $myLeadsBase = Lead::query()
//         ->where('assigned_to', $user->id)
//         ->whereNotIn('status', ['closed', 'lost', 'won']);

//     // All users' leads
//     $allLeadsBase = Lead::query()
//         ->whereNotIn('status', ['closed', 'lost', 'won']);

//     /*
//     |--------------------------------------------------------------------------
//     | 2. DASHBOARD COUNTS (MY LEADS & ALL LEADS)
//     |--------------------------------------------------------------------------
//     */
//     $myLeadCounts = [
//         'unattended' => (clone $myLeadsBase)
//             ->whereDoesntHave('callDetails')
//             ->count(),

//         'today_followup' => (clone $myLeadsBase)
//             ->whereHas('latestCall', function ($q) {
//                 $q->whereNotNull('next_followup_date')
//                   ->whereDate('next_followup_date', today());
//             })->count(),

//         'overdue_followup' => (clone $myLeadsBase)
//             ->whereHas('latestCall', function ($q) {
//                 $q->whereNotNull('next_followup_date')
//                   ->whereDate('next_followup_date', '<', today());
//             })->count(),

//         'upcoming_followup' => (clone $myLeadsBase)
//             ->whereHas('latestCall', function ($q) {
//                 $q->whereNotNull('next_followup_date')
//                   ->whereDate('next_followup_date', '>', today());
//             })->count(),

//         'your_leads' => (clone $myLeadsBase)->count(),
//         'total_leads' => (clone $allLeadsBase)->count(),
//     ];

//     $allLeadCounts = [
//         'unattended' => (clone $allLeadsBase)
//             ->whereDoesntHave('callDetails')
//             ->count(),

//         'today_followup' => (clone $allLeadsBase)
//             ->whereHas('latestCall', function ($q) {
//                 $q->whereNotNull('next_followup_date')
//                   ->whereDate('next_followup_date', today());
//             })->count(),

//         'overdue_followup' => (clone $allLeadsBase)
//             ->whereHas('latestCall', function ($q) {
//                 $q->whereNotNull('next_followup_date')
//                   ->whereDate('next_followup_date', '<', today());
//             })->count(),

//         'upcoming_followup' => (clone $allLeadsBase)
//             ->whereHas('latestCall', function ($q) {
//                 $q->whereNotNull('next_followup_date')
//                   ->whereDate('next_followup_date', '>', today());
//             })->count(),

//         'total_leads' => (clone $allLeadsBase)->count(),
//     ];

//     /*
//     |--------------------------------------------------------------------------
//     | 3. FILTERING LOGIC BASED ON SCOPE & FILTER
//     |--------------------------------------------------------------------------
//     */
//     $filteredQuery = null;

//     // Direct Scope selection (`my_leads` / `all_leads`)
//     $activeBaseQuery = ($scope === 'all_leads') ? (clone $allLeadsBase) : (clone $myLeadsBase);

//     if ($filter) {
//         switch ($filter) {
//             case 'unattended':
//                 $filteredQuery = $activeBaseQuery->whereDoesntHave('callDetails');
//                 break;

//             case 'today_followup':
//                 $filteredQuery = $activeBaseQuery->whereHas('latestCall', function ($q) {
//                     $q->whereNotNull('next_followup_date')
//                       ->whereDate('next_followup_date', today());
//                 });
//                 break;

//             case 'overdue_followup':
//                 $filteredQuery = $activeBaseQuery->whereHas('latestCall', function ($q) {
//                     $q->whereNotNull('next_followup_date')
//                       ->whereDate('next_followup_date', '<', today());
//                 });
//                 break;

//             case 'upcoming_followup':
//                 $filteredQuery = $activeBaseQuery->whereHas('latestCall', function ($q) {
//                     $q->whereNotNull('next_followup_date')
//                       ->whereDate('next_followup_date', '>', today());
//                 });
//                 break;

//             case 'your_leads':
//                 $filteredQuery = clone $myLeadsBase;
//                 break;

//             case 'total_leads':
//                 $filteredQuery = clone $allLeadsBase;
//                 break;
//         }
//     }

//     /*
//     |--------------------------------------------------------------------------
//     | 4. PAGINATED DATA & RESPONSE
//     |--------------------------------------------------------------------------
//     */
//     $leads = null;

//     if ($filteredQuery) {
//         $leads = $filteredQuery
//             ->with([
//                 'owner',
//                 'latestCall',
//                 'assignedUser:id,name',
//                 'createdUser:id,name',
//             ])
//             ->latest('id')
//             ->paginate($perPage)
//             ->withQueryString();
//     }

//     return response()->json([
//         'status'      => true,
//         'status_code' => 200,
//         'message'     => 'Dashboard lead statistics retrieved successfully.',

//         'data' => [
//             'filter' => $filter,
//             'scope'  => $scope,

//             'counts' => [
//                 'my_leads'  => $myLeadCounts,
//                 'all_leads' => $allLeadCounts,
//             ],

//             'filtered_count' => $leads ? $leads->total() : null,

//             'leads' => $leads ? $leads->items() : [],

//             'pagination' => $leads ? [
//                 'current_page' => $leads->currentPage(),
//                 'per_page'     => $leads->perPage(),
//                 'total'        => $leads->total(),
//                 'last_page'    => $leads->lastPage(),
//                 'from'         => $leads->firstItem(),
//                 'to'           => $leads->lastItem(),
//             ] : null,
//         ],
//     ], 200);
// }

    public function getDashboardLeadStatistics(Request $request): JsonResponse
    {
        $user = auth()->user();

        $filter  = $request->input('filter');
        $scope   = $request->input('scope', 'my_leads'); // my_leads | all_leads
        $search  = trim($request->input('search', ''));
        $perPage = min((int) $request->input('per_page', 10), 100);

        /*
        |--------------------------------------------------------------------------
        | 1. BASE QUERIES
        |--------------------------------------------------------------------------
        */

        // Logged-in user's assigned leads
        $myLeadsBase = Lead::query()
            ->where('assigned_to', $user->id)
            ->whereNotIn('status', ['closed', 'lost', 'won']);

        // All users' leads
        $allLeadsBase = Lead::query()
            ->whereNotIn('status', ['closed', 'lost', 'won']);


        /*
        |--------------------------------------------------------------------------
        | 2. SEARCH LOGIC
        |--------------------------------------------------------------------------
        |
        | One "search" parameter will search:
        | - Lead name
        | - Phone number
        | - Status
        |
        */

        $applySearch = function ($query) use ($search) {

            if ($search === '') {
                return $query;
            }

            $search = strtolower($search);

            return $query->where(function ($q) use ($search) {

                $q->where('name', 'like', '%' . $search . '%')
                ->orWhere('phone', 'like', '%' . $search . '%')
                ->orWhere('status', 'like', '%' . $search . '%');

            });
        };


        /*
        |--------------------------------------------------------------------------
        | 3. DASHBOARD COUNTS
        |--------------------------------------------------------------------------
        |
        | Search is also applied to counts so the dashboard counts reflect
        | the searched leads.
        |
        */

        $myLeadsCountBase = $applySearch(clone $myLeadsBase);

        $allLeadsCountBase = $applySearch(clone $allLeadsBase);


        $myLeadCounts = [
            'unattended' => (clone $myLeadsCountBase)
                ->whereDoesntHave('callDetails')
                ->count(),

            'today_followup' => (clone $myLeadsCountBase)
                ->whereHas('latestCall', function ($q) {
                    $q->whereNotNull('next_followup_date')
                    ->whereDate('next_followup_date', today());
                })
                ->count(),

            'overdue_followup' => (clone $myLeadsCountBase)
                ->whereHas('latestCall', function ($q) {
                    $q->whereNotNull('next_followup_date')
                    ->whereDate('next_followup_date', '<', today());
                })
                ->count(),

            'upcoming_followup' => (clone $myLeadsCountBase)
                ->whereHas('latestCall', function ($q) {
                    $q->whereNotNull('next_followup_date')
                    ->whereDate('next_followup_date', '>', today());
                })
                ->count(),

            'your_leads' => (clone $myLeadsCountBase)->count(),

            'total_leads' => (clone $allLeadsCountBase)->count(),
        ];


        $allLeadCounts = [
            'unattended' => (clone $allLeadsCountBase)
                ->whereDoesntHave('callDetails')
                ->count(),

            'today_followup' => (clone $allLeadsCountBase)
                ->whereHas('latestCall', function ($q) {
                    $q->whereNotNull('next_followup_date')
                    ->whereDate('next_followup_date', today());
                })
                ->count(),

            'overdue_followup' => (clone $allLeadsCountBase)
                ->whereHas('latestCall', function ($q) {
                    $q->whereNotNull('next_followup_date')
                    ->whereDate('next_followup_date', '<', today());
                })
                ->count(),

            'upcoming_followup' => (clone $allLeadsCountBase)
                ->whereHas('latestCall', function ($q) {
                    $q->whereNotNull('next_followup_date')
                    ->whereDate('next_followup_date', '>', today());
                })
                ->count(),

            'total_leads' => (clone $allLeadsCountBase)->count(),
        ];


        /*
        |--------------------------------------------------------------------------
        | 4. ACTIVE BASE QUERY
        |--------------------------------------------------------------------------
        */

        $activeBaseQuery = ($scope === 'all_leads')
            ? clone $allLeadsBase
            : clone $myLeadsBase;


        /*
        |--------------------------------------------------------------------------
        | 5. APPLY SEARCH TO ACTIVE QUERY
        |--------------------------------------------------------------------------
        */

        $filteredQuery = $applySearch($activeBaseQuery);


        /*
        |--------------------------------------------------------------------------
        | 6. FILTER LOGIC
        |--------------------------------------------------------------------------
        */

        if ($filter) {

            switch ($filter) {

                case 'unattended':

                    $filteredQuery->whereDoesntHave('callDetails');

                    break;


                case 'today_followup':

                    $filteredQuery->whereHas('latestCall', function ($q) {

                        $q->whereNotNull('next_followup_date')
                        ->whereDate('next_followup_date', today());

                    });

                    break;


                case 'overdue_followup':

                    $filteredQuery->whereHas('latestCall', function ($q) {

                        $q->whereNotNull('next_followup_date')
                        ->whereDate('next_followup_date', '<', today());

                    });

                    break;


                case 'upcoming_followup':

                    $filteredQuery->whereHas('latestCall', function ($q) {

                        $q->whereNotNull('next_followup_date')
                        ->whereDate('next_followup_date', '>', today());

                    });

                    break;


                case 'your_leads':

                    $filteredQuery = $applySearch(clone $myLeadsBase);

                    break;


                case 'total_leads':

                    $filteredQuery = $applySearch(clone $allLeadsBase);

                    break;
            }
        }


        /*
        |--------------------------------------------------------------------------
        | 7. PAGINATED DATA
        |--------------------------------------------------------------------------
        */

        $leads = null;

        if ($filteredQuery) {

            $leads = $filteredQuery
                ->with([
                    'owner',
                    'latestCall',
                    'assignedUser:id,name',
                    'createdUser:id,name',
                ])
                ->latest('id')
                ->paginate($perPage)
                ->withQueryString();
        }


        /*
        |--------------------------------------------------------------------------
        | 8. RESPONSE
        |--------------------------------------------------------------------------
        */

        return response()->json([

            'status'      => true,
            'status_code' => 200,
            'message'     => 'Dashboard lead statistics retrieved successfully.',

            'data' => [

                'filter' => $filter,

                'scope' => $scope,

                'search' => $search,

                'counts' => [

                    'my_leads' => $myLeadCounts,

                    'all_leads' => $allLeadCounts,
                ],

                'filtered_count' => $leads
                    ? $leads->total()
                    : null,

                'leads' => $leads
                    ? $leads->items()
                    : [],

                'pagination' => $leads
                    ? [
                        'current_page' => $leads->currentPage(),
                        'per_page'     => $leads->perPage(),
                        'total'        => $leads->total(),
                        'last_page'    => $leads->lastPage(),
                        'from'         => $leads->firstItem(),
                        'to'           => $leads->lastItem(),
                    ]
                    : null,
            ],

        ], 200);
    }
}