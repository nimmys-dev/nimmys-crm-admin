<?php

namespace App\Http\Controllers;

use App\Http\Requests\task\TaskRequest;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TaskController extends Controller
{
    /**
     * Task list
     */
    public function index(): View
    {
        $tasks = Task::with([
            'assignedUser:id,name',
            'approvedBy:id,name',
        ])
        ->latest()
        ->paginate(10);

        return view('tasks.index', compact('tasks'));
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

        /*
        |--------------------------------------------------------------------------
        | Remove fields that do not belong to selected task type
        |--------------------------------------------------------------------------
        */

        switch ($data['task_type']) {

            case 'daily':

                $data['week_day'] = null;

                $data['monthly_start_date'] = null;
                $data['monthly_end_date'] = null;

                $data['quarter'] = null;
                $data['quarter_start_date'] = null;
                $data['quarter_end_date'] = null;

                break;


            case 'weekly':

                $data['start_time'] = null;
                $data['end_time'] = null;

                $data['monthly_start_date'] = null;
                $data['monthly_end_date'] = null;

                $data['quarter'] = null;
                $data['quarter_start_date'] = null;
                $data['quarter_end_date'] = null;

                break;


            case 'monthly':

                $data['start_time'] = null;
                $data['end_time'] = null;

                $data['week_day'] = null;

                $data['quarter'] = null;
                $data['quarter_start_date'] = null;
                $data['quarter_end_date'] = null;

                break;


            case 'quarterly':

                $data['start_time'] = null;
                $data['end_time'] = null;

                $data['week_day'] = null;

                $data['monthly_start_date'] = null;
                $data['monthly_end_date'] = null;

                break;
        }

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

                $data['week_day'] = null;

                $data['monthly_start_date'] = null;
                $data['monthly_end_date'] = null;

                $data['quarter'] = null;
                $data['quarter_start_date'] = null;
                $data['quarter_end_date'] = null;

                break;


            case 'weekly':

                $data['start_time'] = null;
                $data['end_time'] = null;

                $data['monthly_start_date'] = null;
                $data['monthly_end_date'] = null;

                $data['quarter'] = null;
                $data['quarter_start_date'] = null;
                $data['quarter_end_date'] = null;

                break;


            case 'monthly':

                $data['start_time'] = null;
                $data['end_time'] = null;

                $data['week_day'] = null;

                $data['quarter'] = null;
                $data['quarter_start_date'] = null;
                $data['quarter_end_date'] = null;

                break;


            case 'quarterly':

                $data['start_time'] = null;
                $data['end_time'] = null;

                $data['week_day'] = null;

                $data['monthly_start_date'] = null;
                $data['monthly_end_date'] = null;

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
}