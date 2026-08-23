<?php

namespace App\Http\Controllers;

use App\Http\Requests\Task\TaskRequest;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    /**
     * Task list
     */


    // public function index(Request $request): View
    // {
    //     $user = auth()->user();

    //     $tasks = Task::query()
    //         // Role-based scoping
    //         ->when(!$user->hasRole('admin'), function ($query) use ($user) {
    //             match ($user->role) {
    //                 'manager' => $query->where('department_id', $user->department_id),
    //                 'user'    => $query->where('assigned_to', $user->id),
    //                 default   => $query->where('assigned_to', $user->id),
    //             };
    //         })
    //         // Filter: Title Search
    //         ->when($request->filled('title'), function ($query) use ($request) {
    //             $query->where('title', 'like', '%' . $request->title . '%');
    //         })
    //         // Filter: Assigned To
    //         ->when($request->filled('assigned_to'), function ($query) use ($request) {
    //             $query->where('assigned_to', $request->assigned_to);
    //         })
    //         // Filter: Approved By
    //         ->when($request->filled('approved_by'), function ($query) use ($request) {
    //             $query->where('approved_by', $request->approved_by);
    //         })
    //         // Filter: Task Type
    //         ->when($request->filled('task_type'), function ($query) use ($request) {
    //             $query->where('task_type', $request->task_type);
    //         })
    //         ->with([
    //             'assignedUser:id,name',
    //             'approvedBy:id,name',
    //         ])
    //         ->latest()
    //         ->paginate(10)
    //         ->withQueryString(); // Preserves filter params during pagination

    //     // Fetch users for the filter dropdowns
    //     $users = User::select('id', 'name')->orderBy('name')->get();

    //     return view('tasks.index', compact('tasks', 'users'));
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

        // Role-based scoping
        ->when(!$user->hasRole('admin'), function ($query) use ($user) {

            match ($user->role->value) {

                'manager' =>
                    $query->where(
                        'department_id',
                        $user->department_id
                    ),

                'user' =>
                    $query->where(
                        'assigned_to',
                        $user->id
                    ),

                default =>
                    $query->where(
                        'assigned_to',
                        $user->id
                    ),
            };
        })

        // Filter: Title Search
        ->when($request->filled('title'), function ($query) use ($request) {

            $query->where(
                'title',
                'like',
                '%' . $request->title . '%'
            );

        })

        // Filter: Assigned To
        ->when($request->filled('assigned_to'), function ($query) use ($request) {

            $query->where(
                'assigned_to',
                $request->assigned_to
            );

        })

        // Filter: Approved By
        ->when($request->filled('approved_by'), function ($query) use ($request) {

            $query->where(
                'approved_by',
                $request->approved_by
            );

        })

        // Filter: Task Type
        ->when($request->filled('task_type'), function ($query) use ($request) {

            $query->where(
                'task_type',
                $request->task_type
            );

        })

        ->with([
            'assignedUser:id,name',
            'approvedBy:id,name',
        ])

        ->latest()

        ->paginate(10)

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

    switch ($data['task_type']) {

        /*
        |--------------------------------------------------------------------------
        | Daily
        |--------------------------------------------------------------------------
        */

        case 'daily':

            $data['week_day'] = null;

            $data['monthly_start_date'] = null;
            $data['monthly_end_date'] = null;

            $data['quarter'] = null;
            $data['quarter_start_date'] = null;
            $data['quarter_end_date'] = null;

            $data['yearly_start_date'] = null;
            $data['yearly_end_date'] = null;

            break;


        /*
        |--------------------------------------------------------------------------
        | Weekly
        |--------------------------------------------------------------------------
        */

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


        /*
        |--------------------------------------------------------------------------
        | Monthly
        |--------------------------------------------------------------------------
        */

        case 'monthly':

            $data['start_time'] = null;
            $data['end_time'] = null;

            $data['week_day'] = null;

            $data['quarter'] = null;
            $data['quarter_start_date'] = null;
            $data['quarter_end_date'] = null;

            $data['yearly_start_date'] = null;
            $data['yearly_end_date'] = null;

            break;


        /*
        |--------------------------------------------------------------------------
        | Quarterly
        |--------------------------------------------------------------------------
        */

        case 'quarterly':

            $data['start_time'] = null;
            $data['end_time'] = null;

            $data['week_day'] = null;

            $data['monthly_start_date'] = null;
            $data['monthly_end_date'] = null;

            $data['yearly_start_date'] = null;
            $data['yearly_end_date'] = null;

            break;


        /*
        |--------------------------------------------------------------------------
        | Yearly
        |--------------------------------------------------------------------------
        */

        case 'yearly':

            $data['start_time'] = null;
            $data['end_time'] = null;

            $data['week_day'] = null;

            $data['monthly_start_date'] = null;
            $data['monthly_end_date'] = null;

            $data['quarter'] = null;
            $data['quarter_start_date'] = null;
            $data['quarter_end_date'] = null;

            break;
    }


    /*
    |--------------------------------------------------------------------------
    | Create Task
    |--------------------------------------------------------------------------
    */

    Task::create($data);


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

        return view('tasks.edit', compact(
            'task',
            'users'
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

            break;


        case 'yearly':

            // Daily fields not required
            $data['start_time'] = null;
            $data['end_time'] = null;

            // Weekly fields not required
            $data['week_start_day'] = null;
            $data['week_end_day'] = null;

            // Monthly fields not required
            $data['monthly_start_date'] = null;
            $data['monthly_end_date'] = null;

            // Quarterly fields not required
            $data['quarter'] = null;
            $data['quarter_start_date'] = null;
            $data['quarter_end_date'] = null;

            // Keep:
            // yearly_start_date
            // yearly_end_date

            break;
    }

    $task->update($data);

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

            return back()->with(
                'success',
                'Task completed successfully.'
            );
    }
}