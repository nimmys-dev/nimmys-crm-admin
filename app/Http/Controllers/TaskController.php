<?php

namespace App\Http\Controllers;

use App\Http\Requests\Task\TaskRequest;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use App\Models\TaskQuarter;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TaskController extends Controller
{

//     public function index(Request $request): View
// {
//     $user = auth()->user();

//     /*
//     |--------------------------------------------------------------------------
//     | Automatically update task statuses
//     |--------------------------------------------------------------------------
//     */
//     Task::query()
//         ->whereNotIn('status', [
//             'completed',
//             'approved',
//             'closed',
//         ])
//         ->get()
//         ->each(function ($task) {

//             $task->updateAutomaticStatus();

//         });


//     /*
//     |--------------------------------------------------------------------------
//     | Get Tasks
//     |--------------------------------------------------------------------------
//     */
//     $tasks = Task::query()

//         // Role-based scoping
//         ->when(!$user->hasRole('admin'), function ($query) use ($user) {

//             match ($user->role->value) {

//                 'manager' =>
//                     $query->where(
//                         'assigned_to',
//                         $user->assigned_to
//                     ),

//                 'user' =>
//                     $query->where(
//                         'assigned_to',
//                         $user->id
//                     ),

//                 default =>
//                     $query->where(
//                         'assigned_to',
//                         $user->id
//                     ),
//             };
//         })

//         // Filter: Title Search
//         ->when($request->filled('title'), function ($query) use ($request) {

//             $query->where(
//                 'title',
//                 'like',
//                 '%' . $request->title . '%'
//             );

//         })

//         // Filter: Assigned To
//         ->when($request->filled('assigned_to'), function ($query) use ($request) {

//             $query->where(
//                 'assigned_to',
//                 $request->assigned_to
//             );

//         })

//         // Filter: Approved By
//         ->when($request->filled('approved_by'), function ($query) use ($request) {

//             $query->where(
//                 'approved_by',
//                 $request->approved_by
//             );

//         })

//         // Filter: Task Type
//         ->when($request->filled('task_type'), function ($query) use ($request) {

//             $query->where(
//                 'task_type',
//                 $request->task_type
//             );

//         })

//         ->with([
//             'assignedUser:id,name',
//             'approvedBy:id,name',
//         ])

//         ->latest()

//         ->paginate(10)

//         ->withQueryString();


//     /*
//     |--------------------------------------------------------------------------
//     | Users for Filters
//     |--------------------------------------------------------------------------
//     */
//     $users = User::select(
//         'id',
//         'name'
//     )
//     ->orderBy('name')
//     ->get();


//     return view(
//         'tasks.index',
//         compact(
//             'tasks',
//             'users'
//         )
//     );
// }

    // public function index(Request $request): View
    // {
    //     $user = auth()->user();

    //     /*
    //     |--------------------------------------------------------------------------
    //     | Automatically update task statuses
    //     |--------------------------------------------------------------------------
    //     */

    //     Task::query()
    //         ->whereNotIn('status', [
    //             'completed',
    //             'approved',
    //             'closed',
    //         ])
    //         ->get()
    //         ->each(function ($task) {
    //             $task->updateAutomaticStatus();
    //         });


    //     /*
    //     |--------------------------------------------------------------------------
    //     | Get Tasks
    //     |--------------------------------------------------------------------------
    //     */

    //     $tasks = Task::query()

    //         /*
    //         |--------------------------------------------------------------------------
    //         | Role-based scoping
    //         |--------------------------------------------------------------------------
    //         */

    //         // ->when(!$user->hasRole('admin'), function ($query) use ($user) {

    //         //     $query->where('assigned_to', $user->id);

    //         // })

    //         ->when(!$user->hasRole('admin'), function ($query) use ($user) {
    //             $query->where(function ($query) use ($user) {
    //                 $query->where('assigned_to', $user->id)
    //                     ->orWhere('approved_by', $user->id);
    //             });
    //         })

    //         /*
    //         |--------------------------------------------------------------------------
    //         | Filter: Title
    //         |--------------------------------------------------------------------------
    //         */

    //         ->when($request->filled('title'), function ($query) use ($request) {

    //             $query->where(
    //                 'title',
    //                 'like',
    //                 '%' . $request->title . '%'
    //             );

    //         })

    //         /*
    //         |--------------------------------------------------------------------------
    //         | Filter: Assigned To
    //         |--------------------------------------------------------------------------
    //         */

    //         ->when($request->filled('assigned_to'), function ($query) use ($request) {

    //             $query->where(
    //                 'assigned_to',
    //                 $request->assigned_to
    //             );

    //         })

    //         /*
    //         |--------------------------------------------------------------------------
    //         | Filter: Approved By
    //         |--------------------------------------------------------------------------
    //         */

    //         ->when($request->filled('approved_by'), function ($query) use ($request) {

    //             $query->where(
    //                 'approved_by',
    //                 $request->approved_by
    //             );

    //         })

    //         /*
    //         |--------------------------------------------------------------------------
    //         | Filter: Task Type
    //         |--------------------------------------------------------------------------
    //         */

    //         ->when($request->filled('task_type'), function ($query) use ($request) {

    //             $query->where(
    //                 'task_type',
    //                 $request->task_type
    //             );

    //         })

    //         ->with([
    //             'assignedUser:id,name',
    //             'approvedBy:id,name',
    //             'quarters',
    //         ])

    //         ->latest()

    //         ->paginate(10)

    //         ->withQueryString();


    //     /*
    //     |--------------------------------------------------------------------------
    //     | Users for Filters
    //     |--------------------------------------------------------------------------
    //     */

    //     $users = User::select(
    //         'id',
    //         'name'
    //     )
    //     ->orderBy('name')
    //     ->get();


    //     return view(
    //         'tasks.index',
    //         compact(
    //             'tasks',
    //             'users'
    //         )
    //     );
    // }

    public function index(Request $request): View
    {
        $user = auth()->user();

        /*
        |--------------------------------------------------------------------------
        | Automatically update task statuses
        |--------------------------------------------------------------------------
        */

        Task::query()
        ->whereNotIn('status', [
            'completed',
            'approved',
            'closed',
        ])
        ->get()
        ->each(function ($task) {
            $task->updateAutomaticStatus();
        });


        /*
        |--------------------------------------------------------------------------
        | Get Tasks
        |--------------------------------------------------------------------------
        */

        $tasks = Task::query()

            /*
            |--------------------------------------------------------------------------
            | Role-based scoping
            |--------------------------------------------------------------------------
            */

            ->when(!$user->hasRole('admin'), function ($query) use ($user) {

                $query->where(function ($query) use ($user) {

                    $query->where('assigned_to', $user->id);

                });

            })


            /*
            |--------------------------------------------------------------------------
            | Dashboard Filter
            |--------------------------------------------------------------------------
            */

            ->when($request->filled('filter'), function ($query) use ($request) {

                $query->where('status', $request->filter);

            })

            ->when($request->boolean('my_tasks'), function ($query) use ($user) {
                $query->where('assigned_to', $user->id);
            })


            /*
            |--------------------------------------------------------------------------
            | Filter: Title
            |--------------------------------------------------------------------------
            */

            ->when($request->filled('title'), function ($query) use ($request) {

                $query->where(
                    'title',
                    'like',
                    '%' . $request->title . '%'
                );

            })


            /*
            |--------------------------------------------------------------------------
            | Filter: Assigned To
            |--------------------------------------------------------------------------
            */

            ->when($request->filled('assigned_to'), function ($query) use ($request) {

                $query->where(
                    'assigned_to',
                    $request->assigned_to
                );

            })


            /*
            |--------------------------------------------------------------------------
            | Filter: Approved By
            |--------------------------------------------------------------------------
            */

            ->when($request->filled('approved_by'), function ($query) use ($request) {

                $query->where(
                    'approved_by',
                    $request->approved_by
                );

            })


            /*
            |--------------------------------------------------------------------------
            | Filter: Task Type
            |--------------------------------------------------------------------------
            */

            ->when($request->filled('task_type'), function ($query) use ($request) {

                $query->where(
                    'task_type',
                    $request->task_type
                );

            })


            /*
            |--------------------------------------------------------------------------
            | Relationships
            |--------------------------------------------------------------------------
            */

            ->with([
                'assignedUser:id,name',
                'approvedBy:id,name',
                'quarters',
            ])

           ->orderBy('id', 'desc')

            ->paginate(5)

            ->withQueryString();


        /*
        |--------------------------------------------------------------------------
        | Users for Filters
        |--------------------------------------------------------------------------
        */

        $users = User::select(
            'id',
            'name'
        )
        ->orderBy('name')
        ->get();


        return view(
            'tasks.index',
            compact(
                'tasks',
                'users'
            )
        );
    }
    /**
     * Create task page
     */
    public function create(): View
    {
        $users = User::select('id', 'name')
            ->orderBy('name')
            ->get();

        return view('tasks.create', compact('users'));
    }


    /**
     * Store task
     */

    public function store(TaskRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // Repeat mode
        $data['repeat_mode'] = $request->boolean('repeat_mode');

        // Get quarterly data before removing it from Task data
        $quarters = $data['quarters'] ?? [];

        // quarters belongs to task_quarters table, not tasks table
        unset($data['quarters']);

        switch ($data['task_type']) {

            /*
            |--------------------------------------------------------------------------
            | DAILY
            |--------------------------------------------------------------------------
            */
            case 'daily':

                // Weekly fields
                $data['week_start_day'] = null;
                $data['week_end_day'] = null;

                // Monthly fields
                $data['monthly_start_date'] = null;
                $data['monthly_end_date'] = null;

                // Quarterly old fields
                $data['quarter'] = null;
                $data['quarter_start_date'] = null;
                $data['quarter_end_date'] = null;

                // Yearly fields
                $data['yearly_start_date'] = null;
                $data['yearly_end_date'] = null;

                break;


            /*
            |--------------------------------------------------------------------------
            | WEEKLY
            |--------------------------------------------------------------------------
            */
            case 'weekly':

                // Daily fields
                $data['start_time'] = null;
                $data['end_time'] = null;

                // Monthly fields
                $data['monthly_start_date'] = null;
                $data['monthly_end_date'] = null;

                // Quarterly old fields
                $data['quarter'] = null;
                $data['quarter_start_date'] = null;
                $data['quarter_end_date'] = null;

                // Yearly fields
                $data['yearly_start_date'] = null;
                $data['yearly_end_date'] = null;

                break;


            /*
            |--------------------------------------------------------------------------
            | MONTHLY
            |--------------------------------------------------------------------------
            */
            case 'monthly':

                // Daily fields
                $data['start_time'] = null;
                $data['end_time'] = null;

                // Weekly fields
                $data['week_start_day'] = null;
                $data['week_end_day'] = null;

                // Quarterly old fields
                $data['quarter'] = null;
                $data['quarter_start_date'] = null;
                $data['quarter_end_date'] = null;

                // Yearly fields
                $data['yearly_start_date'] = null;
                $data['yearly_end_date'] = null;

                break;


            /*
            |--------------------------------------------------------------------------
            | QUARTERLY
            |--------------------------------------------------------------------------
            */
            case 'quarterly':

                // Daily fields
                $data['start_time'] = null;
                $data['end_time'] = null;

                // Weekly fields
                $data['week_start_day'] = null;
                $data['week_end_day'] = null;

                // Monthly fields
                $data['monthly_start_date'] = null;
                $data['monthly_end_date'] = null;

                // Yearly fields
                $data['yearly_start_date'] = null;
                $data['yearly_end_date'] = null;

                // Old single-quarter fields
                $data['quarter'] = null;
                $data['quarter_start_date'] = null;
                $data['quarter_end_date'] = null;

                break;


            /*
            |--------------------------------------------------------------------------
            | YEARLY
            |--------------------------------------------------------------------------
            */
            case 'yearly':

                // Daily fields
                $data['start_time'] = null;
                $data['end_time'] = null;

                // Weekly fields
                $data['week_start_day'] = null;
                $data['week_end_day'] = null;

                // Monthly fields
                $data['monthly_start_date'] = null;
                $data['monthly_end_date'] = null;

                // Quarterly old fields
                $data['quarter'] = null;
                $data['quarter_start_date'] = null;
                $data['quarter_end_date'] = null;

                break;
        }


        DB::transaction(function () use ($data, $quarters) {

            /*
            |--------------------------------------------------------------------------
            | Create Main Task
            |--------------------------------------------------------------------------
            */
            $task = Task::create($data);


            /*
            |--------------------------------------------------------------------------
            | Create Quarterly Records
            |--------------------------------------------------------------------------
            */
            if ($task->task_type === 'quarterly') {

                foreach ($quarters as $quarter) {

                    TaskQuarter::create([
                        'task_id'    => $task->id,
                        'quarter'    => $quarter['quarter'],
                        'start_date' => $quarter['start_date'],
                        'end_date'   => $quarter['end_date'],
                    ]);
                }
            }
        });


        return redirect()
            ->route('tasks.index')
            ->with('success', 'Task created successfully.');
    }


    /**
     * Show task
     */
    public function show(Task $task): View
    {
        $task->load([
            'assignedUser:id,name',
            'approvedBy:id,name',
        ]);

        return view('tasks.show', compact('task'));
    }


    /**
     * Edit page
     */
    public function edit(Task $task): View
    {
        $users = User::select('id', 'name')
            ->orderBy('name')
            ->get();
             
        $quarters = $task->quarters()
        ->get()
        ->map(function ($quarter) {
            return [
                'quarter' => $quarter->quarter,
                'start_date' => $quarter->start_date,
                'end_date' => $quarter->end_date,
            ];
        })
        ->toArray();
        return view('tasks.edit', compact(
            'task',
            'users',
            'quarters'
        ));
    }


    /**
     * Update task
     */

public function update(
    TaskRequest $request,
    Task $task
): RedirectResponse {

    $data = $request->validated();
     // Checkbox: ON = 1, OFF = 0
    $data['repeat_mode'] = $request->boolean('repeat_mode');

    DB::transaction(function () use ($data, $task) {

        /*
        |--------------------------------------------------------------------------
        | Remove quarters from task data
        |--------------------------------------------------------------------------
        | quarters belongs to task_quarters table, not tasks table.
        */
        $quarters = $data['quarters'] ?? [];

        unset($data['quarters']);


        /*
        |--------------------------------------------------------------------------
        | Task type specific fields
        |--------------------------------------------------------------------------
        */

        switch ($data['task_type']) {

            case 'daily':

                $data['week_start_day'] = null;
                $data['week_end_day'] = null;

                $data['monthly_start_date'] = null;
                $data['monthly_end_date'] = null;

                $data['quarter'] = null;
                $data['quarter_start_date'] = null;
                $data['quarter_end_date'] = null;

                $data['yearly_start_date'] = null;
                $data['yearly_end_date'] = null;

                break;


            case 'weekly':

                $data['start_time'] = null;
                $data['end_time'] = null;

                $data['monthly_start_date'] = null;
                $data['monthly_end_date'] = null;

                $data['quarter'] = null;
                $data['quarter_start_date'] = null;
                $data['quarter_end_date'] = null;

                $data['yearly_start_date'] = null;
                $data['yearly_end_date'] = null;

                break;


            case 'monthly':

                $data['start_time'] = null;
                $data['end_time'] = null;

                $data['week_start_day'] = null;
                $data['week_end_day'] = null;

                $data['quarter'] = null;
                $data['quarter_start_date'] = null;
                $data['quarter_end_date'] = null;

                $data['yearly_start_date'] = null;
                $data['yearly_end_date'] = null;

                break;


            case 'quarterly':

                $data['start_time'] = null;
                $data['end_time'] = null;

                $data['week_start_day'] = null;
                $data['week_end_day'] = null;

                $data['monthly_start_date'] = null;
                $data['monthly_end_date'] = null;

                $data['yearly_start_date'] = null;
                $data['yearly_end_date'] = null;

                // Old single-quarter fields are not required
                $data['quarter'] = null;
                $data['quarter_start_date'] = null;
                $data['quarter_end_date'] = null;

                break;


            case 'yearly':

                $data['start_time'] = null;
                $data['end_time'] = null;

                $data['week_start_day'] = null;
                $data['week_end_day'] = null;

                $data['monthly_start_date'] = null;
                $data['monthly_end_date'] = null;

                $data['quarter'] = null;
                $data['quarter_start_date'] = null;
                $data['quarter_end_date'] = null;

                break;
        }


        /*
        |--------------------------------------------------------------------------
        | Update Task
        |--------------------------------------------------------------------------
        */

        $task->update($data);


        /*
        |--------------------------------------------------------------------------
        | Save Multiple Quarters
        |--------------------------------------------------------------------------
        */

        if ($task->task_type === 'quarterly') {

            /*
             * Delete old quarter records first.
             *
             * This handles:
             * - existing quarters
             * - removed quarters
             * - newly added quarters
             */
            $task->quarters()->delete();


            /*
             * Insert the current quarters
             */
            foreach ($quarters as $quarter) {

                if (
                    empty($quarter['quarter']) ||
                    empty($quarter['start_date']) ||
                    empty($quarter['end_date'])
                ) {
                    continue;
                }

                $task->quarters()->create([
                    'quarter' => $quarter['quarter'],
                    'start_date' => $quarter['start_date'],
                    'end_date' => $quarter['end_date'],
                ]);
            }
        } else {

            /*
             * If task type is changed from quarterly
             * to daily/weekly/monthly/yearly,
             * remove old quarter records.
             */
            $task->quarters()->delete();
        }
    });


    return redirect()
        ->route('tasks.index')
        ->with('success', 'Task updated successfully.');
}


    /**
     * Delete task
     */
    public function destroy(Task $task): RedirectResponse
    {
        $task->delete();

        return redirect()
            ->route('tasks.index')
            ->with('success', 'Task deleted successfully.');
    }

    public function complete(Request $request,Task $task): RedirectResponse {

        $request->validate([
                'remarks' => [
                    'required',
                    'string',
                    'max:1000',
                ],
            ]);

            $task->update([
                'status'  => 'completed',
                'remarks' => $request->remarks,
            ]);
            if ($task->repeat_mode) {
                $this->createNextRepeatedTask($task);
            }
            return back()->with(
                'success',
                'Task completed successfully.'
            );
    }

    // private function createNextRepeatedTask(Task $task): Task
    // {
    //     $nextTask = $task->replicate();

    //     // New task status
    //     $nextTask->status = 'upcoming';

    //     // Keep same assigned user
    //     $nextTask->assigned_to = $task->assigned_to;

    //     // Keep same approver
    //     $nextTask->approved_by = $task->approved_by;

    //     /*
    //     |--------------------------------------------------------------------------
    //     | Daily
    //     |--------------------------------------------------------------------------
    //     */
    //     if ($task->task_type === 'daily') {

    //         $nextTask->save();

    //     }

    //     /*
    //     |--------------------------------------------------------------------------
    //     | Weekly
    //     |--------------------------------------------------------------------------
    //     */
    //     elseif ($task->task_type === 'weekly') {

    //         $nextTask->save();

    //     }

    //     /*
    //     |--------------------------------------------------------------------------
    //     | Monthly
    //     |--------------------------------------------------------------------------
    //     */
    //     elseif ($task->task_type === 'monthly') {

    //         $nextTask->monthly_start_date = \Carbon\Carbon::parse(
    //             $task->monthly_start_date
    //         )->addMonth();

    //         $nextTask->monthly_end_date = \Carbon\Carbon::parse(
    //             $task->monthly_end_date
    //         )->addMonth();

    //         $nextTask->save();

    //     }

    //     /*
    //     |--------------------------------------------------------------------------
    //     | Quarterly
    //     |--------------------------------------------------------------------------
    //     */
    //     elseif ($task->task_type === 'quarterly') {

    //         $nextTask->save();

    //         foreach ($task->quarters as $quarter) {

    //             $nextStartDate = \Carbon\Carbon::parse(
    //                 $quarter->start_date
    //             )->addYear();

    //             $nextEndDate = \Carbon\Carbon::parse(
    //                 $quarter->end_date
    //             )->addYear();

    //             $nextTask->quarters()->create([
    //                 'quarter'    => $quarter->quarter,
    //                 'start_date' => $nextStartDate,
    //                 'end_date'   => $nextEndDate,
    //             ]);
    //         }
    //     }

    //     /*
    //     |--------------------------------------------------------------------------
    //     | Yearly
    //     |--------------------------------------------------------------------------
    //     */
    //     elseif ($task->task_type === 'yearly') {

    //         $nextTask->yearly_start_date = \Carbon\Carbon::parse(
    //             $task->yearly_start_date
    //         )->addYear();

    //         $nextTask->yearly_end_date = \Carbon\Carbon::parse(
    //             $task->yearly_end_date
    //         )->addYear();

    //         $nextTask->save();
    //     }

    //     return $nextTask;
    // }

    private function createNextRepeatedTask(Task $task): Task
    {
        // Only create next task when repeat mode is ON
        if (!$task->repeat_mode) {
            return $task;
        }

        $nextTask = $task->replicate();

        // New repeated task always starts as upcoming
        $nextTask->status = 'upcoming';

        // Old completion remarks should NOT be copied
        $nextTask->remarks = null;

        // Keep assigned user
        $nextTask->assigned_to = $task->assigned_to;

        // Keep approver
        $nextTask->approved_by = $task->approved_by;

        /*
        |--------------------------------------------------------------------------
        | DAILY
        |--------------------------------------------------------------------------
        */
        if ($task->task_type === 'daily') {

            // Use created_at as the task occurrence date
            $nextTask->created_at = Carbon::parse($task->created_at)
                ->addDay();

            $nextTask->updated_at = now();

            $nextTask->save();
        }

        /*
        |--------------------------------------------------------------------------
        | WEEKLY
        |--------------------------------------------------------------------------
        */
        elseif ($task->task_type === 'weekly') {

            $nextTask->created_at = Carbon::parse($task->created_at)
                ->addWeek();

            $nextTask->updated_at = now();

            $nextTask->save();
        }

        /*
        |--------------------------------------------------------------------------
        | MONTHLY
        |--------------------------------------------------------------------------
        */
        elseif ($task->task_type === 'monthly') {

            $nextTask->monthly_start_date = Carbon::parse(
                $task->monthly_start_date
            )->addMonth();

            $nextTask->monthly_end_date = Carbon::parse(
                $task->monthly_end_date
            )->addMonth();

            $nextTask->save();
        }

        /*
        |--------------------------------------------------------------------------
        | QUARTERLY
        |--------------------------------------------------------------------------
        */
        elseif ($task->task_type === 'quarterly') {

            $nextTask->quarter_start_date = Carbon::parse(
                $task->quarter_start_date
            )->addQuarter();

            $nextTask->quarter_end_date = Carbon::parse(
                $task->quarter_end_date
            )->addQuarter();

            $nextTask->save();

            foreach ($task->quarters as $quarter) {

                $nextTask->quarters()->create([
                    'quarter' => $quarter->quarter,

                    'start_date' => Carbon::parse(
                        $quarter->start_date
                    )->addYear(),

                    'end_date' => Carbon::parse(
                        $quarter->end_date
                    )->addYear(),
                ]);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | YEARLY
        |--------------------------------------------------------------------------
        */
        elseif ($task->task_type === 'yearly') {

            $nextTask->yearly_start_date = Carbon::parse(
                $task->yearly_start_date
            )->addYear();

            $nextTask->yearly_end_date = Carbon::parse(
                $task->yearly_end_date
            )->addYear();

            $nextTask->save();
        }

        return $nextTask;
    }

    public function myTasks(Request $request): View
    {
        $user = auth()->user();

        $query = Task::with([
            'assignedUser:id,name,role',
            'approvedBy:id,name',
        ])
        ->whereNull('deleted_at');

        // Admin → all active tasks
        // Manager / Employee → only assigned active tasks
        if ($user->role->value !== 'admin') {
            $query->where('assigned_to', $user->id);
        }

        // Name filter
        if ($request->filled('name')) {
            $query->whereHas('assignedUser', function ($query) use ($request) {
                $query->where(
                    'name',
                    'like',
                    '%' . $request->name . '%'
                );
            });
        }

        // Role filter
        if ($request->filled('role')) {
            $query->whereHas('assignedUser', function ($query) use ($request) {
                $query->where('role', $request->role);
            });
        }

        $tasks = $query
            ->latest()
            ->paginate(10);

        $users = User::whereIn('role', [
            'manager',
            'employee'
        ])
        ->orderBy('name')
        ->get([
            'id',
            'name',
            'role'
        ]);

        return view(
            'tasks.my-tasks',
            compact('tasks', 'users')
        );
    }
    public function reassign(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'task_ids' => [
                'required',
                'array',
                'min:1',
            ],

            'task_ids.*' => [
                'required',
                'integer',
                'exists:tasks,id',
            ],

            'assigned_to' => [
                'required',
                'integer',
                'exists:users,id',
            ],
        ]);


        $currentUser = auth()->user();

        /*
        |--------------------------------------------------------------------------
        | New assigned user
        |--------------------------------------------------------------------------
        */

        $newUser = User::find($validated['assigned_to']);

        if (!$newUser) {
            return response()->json([
                'success' => false,
                'message' => 'Selected user not found.',
            ], 422);
        }


        /*
        |--------------------------------------------------------------------------
        | Prevent assigning to same user
        |--------------------------------------------------------------------------
        */

        if ((int) $newUser->id === (int) $currentUser->id) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot reassign tasks to yourself.',
            ], 422);
        }


        /*
        |--------------------------------------------------------------------------
        | Get selected tasks
        |--------------------------------------------------------------------------
        */

        $query = Task::whereIn(
            'id',
            $validated['task_ids']
        );


        /*
        |--------------------------------------------------------------------------
        | Admin
        |--------------------------------------------------------------------------
        |
        | Admin can reassign any selected task.
        |
        */

        if ($currentUser->role->value !== 'admin') {
            $query->where('assigned_to', $currentUser->id);
        }


        $tasks = $query->get();


        /*
        |--------------------------------------------------------------------------
        | Check whether tasks are available
        |--------------------------------------------------------------------------
        */

        if ($tasks->isEmpty()) {

            return response()->json([
                'success' => false,
                'message' => 'No eligible tasks found for reassignment.',
            ], 403);
        }


        /*
        |--------------------------------------------------------------------------
        | Reassign
        |--------------------------------------------------------------------------
        */

        DB::transaction(function () use ($tasks, $newUser) {

            foreach ($tasks as $task) {

                $task->update([
                    'assigned_to' => $newUser->id,
                ]);
            }
        });


        return response()->json([
            'success' => true,
            'message' => $tasks->count()
                . ' task(s) reassigned successfully to '
                . $newUser->name
                . '.',
        ]);
    }

  
    public function approvalIndex(Request $request): View
    {
        $user = auth()->user();

        $query = Task::with([
            'assignedUser:id,name',
            'approvedBy:id,name',
        ])
        ->where('status', 'completed');

        // Admin → all completed tasks pending for approval
        if ($user->role->value !== 'admin') {
            // Manager / Employee → only tasks assigned to them for approval
            $query->where('approved_by', $user->id);
        }elseif ($request->boolean('my_tasks')) { 
            // Admin → My Tasks only 
            $query->where('assigned_to', $user->id);
        }

        $tasks = $query
            ->latest()
            ->paginate(10);

        return view('tasks.approval', compact('tasks'));
    }



    public function approve(Task $task): RedirectResponse
    {
        $user = auth()->user();

        // Only the assigned approver can approve
        // if ($task->approved_by !== $user->id) {
        //     abort(403, 'You are not authorized to approve this task.');
        // }

        if (
            $user->role->value !== 'admin' &&
            (int) $task->approved_by !== (int) $user->id
        ) {
            abort(403, 'You are not authorized to approve this task.');
        }

        // Only completed tasks can be approved
        if ($task->status !== 'completed') {
            return back()->with('error', 'Only completed tasks can be approved.');
        }

        $task->update([
            'status' => 'approved',
            'approved_at' => now(),
        ]);

        return back()->with('success', 'Task approved successfully.');
    }


}