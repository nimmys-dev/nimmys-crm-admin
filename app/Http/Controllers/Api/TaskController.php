<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Task\TaskRequest;
use App\Models\Task;
use App\Models\TaskQuarter;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    /**
     * Create Task API
     */
    public function store(TaskRequest $request): JsonResponse
    {
        try {

            $data = $request->validated();
            $data['repeat_mode'] = $request->boolean('repeat_mode');
            $quarters = $data['quarters'] ?? [];
            unset($data['quarters']);
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


                /*
                |--------------------------------------------------------------------------
                | MONTHLY
                |--------------------------------------------------------------------------
                */

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


                /*
                |--------------------------------------------------------------------------
                | QUARTERLY
                |--------------------------------------------------------------------------
                */

                case 'quarterly':

                    $data['start_time'] = null;
                    $data['end_time'] = null;

                    $data['week_start_day'] = null;
                    $data['week_end_day'] = null;

                    $data['monthly_start_date'] = null;
                    $data['monthly_end_date'] = null;

                    // Old quarterly fields
                    $data['quarter'] = null;
                    $data['quarter_start_date'] = null;
                    $data['quarter_end_date'] = null;

                    $data['yearly_start_date'] = null;
                    $data['yearly_end_date'] = null;

                    break;


                /*
                |--------------------------------------------------------------------------
                | YEARLY
                |--------------------------------------------------------------------------
                */

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
            | Create Task
            |--------------------------------------------------------------------------
            */

            $task = DB::transaction(function () use (
                $data,
                $quarters
            ) {

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


                return $task;
            });


            /*
            |--------------------------------------------------------------------------
            | Load Relations
            |--------------------------------------------------------------------------
            */

            $task->load([
                'assignedUser:id,name,role',
                'approvedBy:id,name',
                'quarters',
            ]);


            /*
            |--------------------------------------------------------------------------
            | Success Response
            |--------------------------------------------------------------------------
            */

            return response()->json([
                'status' => true,
                'status_code' => 201,
                'message' => 'Task created successfully.',
                'data' => $task,
            ], 201);

        } catch (Throwable $e) {

            /*
            |--------------------------------------------------------------------------
            | Error Response
            |--------------------------------------------------------------------------
            */

            return response()->json([
                'status' => false,
                'status_code' => 500,
                'message' => 'Failed to create task.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function taskType(): JsonResponse
    {
        $taskTypes = [
            [
                'value' => 'daily',
                'label' => 'Daily',
            ],
            [
                'value' => 'weekly',
                'label' => 'Weekly',
            ],
            [
                'value' => 'monthly',
                'label' => 'Monthly',
            ],
            [
                'value' => 'quarterly',
                'label' => 'Quarterly',
            ],
            [
                'value' => 'yearly',
                'label' => 'Yearly',
            ],
        ];

        return response()->json([
            'status' => true,
            'status_code' => 200,
            'message' => 'Task types retrieved successfully.',
            'data' => $taskTypes,
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        try {

            $user = auth()->user();

            /*
            |--------------------------------------------------------------------------
            | Validation
            |--------------------------------------------------------------------------
            */

            $validated = $request->validate([

                'search' => [
                    'nullable',
                    'string',
                    'max:255',
                ],

                'task_type' => [
                    'nullable',
                    'in:daily,weekly,monthly,quarterly,yearly',
                ],

                'status' => [
                    'nullable',
                    'string',
                    'max:50',
                ],

                'assigned_to' => [
                    'nullable',
                    'integer',
                    'exists:users,id',
                ],

                'per_page' => [
                    'nullable',
                    'integer',
                    'min:1',
                    'max:100',
                ],
            ]);


            /*
            |--------------------------------------------------------------------------
            | Task Query
            |--------------------------------------------------------------------------
            */

            $query = Task::with([
                'assignedUser:id,name,email,role',
                'approvedBy:id,name,email,role',
                'quarters:id,task_id,quarter,start_date,end_date',
            ]);


            /*
            |--------------------------------------------------------------------------
            | Role Based Task Access
            |--------------------------------------------------------------------------
            */

            // Admin → all tasks
            if ($user->role->value !== 'admin') {

                // Other roles → only their assigned tasks
                $query->where('assigned_to', $user->id);
            }


            /*
            |--------------------------------------------------------------------------
            | Search
            |--------------------------------------------------------------------------
            */

            if (!empty($validated['search'])) {

                $search = $validated['search'];

                $query->where(function ($q) use ($search) {

                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            }


            /*
            |--------------------------------------------------------------------------
            | Task Type Filter
            |--------------------------------------------------------------------------
            */

            if (!empty($validated['task_type'])) {

                $query->where(
                    'task_type',
                    $validated['task_type']
                );
            }


            /*
            |--------------------------------------------------------------------------
            | Status Filter
            |--------------------------------------------------------------------------
            */

            if (!empty($validated['status'])) {

                $query->where(
                    'status',
                    $validated['status']
                );
            }


            /*
            |--------------------------------------------------------------------------
            | Assigned User Filter
            |--------------------------------------------------------------------------
            */

            if (!empty($validated['assigned_to'])) {

                // Non-admin cannot access another user's tasks
                if ($user->role->value === 'admin') {

                    $query->where(
                        'assigned_to',
                        $validated['assigned_to']
                    );
                }
            }


            /*
            |--------------------------------------------------------------------------
            | Pagination
            |--------------------------------------------------------------------------
            */

            $perPage = $validated['per_page'] ?? 10;

            $tasks = $query
                ->latest()
                ->paginate($perPage)
                ->withQueryString();


            /*
            |--------------------------------------------------------------------------
            | Success Response
            |--------------------------------------------------------------------------
            */

            return response()->json([
                'status' => true,
                'status_code' => 200,
                'message' => 'Tasks retrieved successfully.',
                'data' => $tasks->items(),

                'pagination' => [
                    'current_page' => $tasks->currentPage(),
                    'per_page' => $tasks->perPage(),
                    'total' => $tasks->total(),
                    'last_page' => $tasks->lastPage(),
                    'from' => $tasks->firstItem(),
                    'to' => $tasks->lastItem(),
                ],
            ], 200);


        } catch (\Illuminate\Validation\ValidationException $e) {

            return response()->json([
                'status' => false,
                'status_code' => 422,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);


        } catch (\Throwable $e) {

            \Log::error('Task list API failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'status_code' => 500,
                'message' => 'Something went wrong while retrieving tasks.',
            ], 500);
        }
    }

    public function viewTask(Task $task): JsonResponse
    {
        $user = auth()->user();

        // Non-admin can view only their assigned task
        if (
            $user->role->value !== 'admin'
            && $task->assigned_to !== $user->id
        ) {
            return response()->json([
                'status' => false,
                'status_code' => 403,
                'message' => 'You are not authorized to view this task.',
            ], 403);
        }

        $task->load([
            'assignedUser:id,name,email,role',
            'approvedBy:id,name,email,role',
            'quarters:id,task_id,quarter,start_date,end_date',
        ]);

        return response()->json([
            'status' => true,
            'status_code' => 200,
            'message' => 'Task details retrieved successfully.',
            'data' => $task,
        ]);
    }

    public function updateTask(Request $request, Task $task): JsonResponse
    {
        try {

            /*
            |--------------------------------------------------------------------------
            | Validation
            |--------------------------------------------------------------------------
            */

            $validated = $request->validate([

                'title' => [
                    'required',
                    'string',
                    'max:255',
                ],

                'description' => [
                    'nullable',
                    'string',
                ],

                'assigned_to' => [
                    'required',
                    'integer',
                    'exists:users,id',
                ],

                'approved_by' => [
                    'required',
                    'integer',
                    'exists:users,id',
                ],

                'task_type' => [
                    'required',
                    'in:daily,weekly,monthly,quarterly,yearly',
                ],

                'repeat_mode' => [
                    'nullable',
                    'boolean',
                ],

                /*
                |--------------------------------------------------------------------------
                | Daily
                |--------------------------------------------------------------------------
                */

                'start_time' => [
                    'nullable',
                    'date_format:H:i',
                ],

                'end_time' => [
                    'nullable',
                    'date_format:H:i',
                    'after:start_time',
                ],

                /*
                |--------------------------------------------------------------------------
                | Weekly
                |--------------------------------------------------------------------------
                */

                'week_start_day' => [
                    'nullable',
                    'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
                ],

                'week_end_day' => [
                    'nullable',
                    'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
                ],

                /*
                |--------------------------------------------------------------------------
                | Monthly
                |--------------------------------------------------------------------------
                */

                'monthly_start_date' => [
                    'nullable',
                    'date',
                ],

                'monthly_end_date' => [
                    'nullable',
                    'date',
                    'after_or_equal:monthly_start_date',
                ],

                /*
                |--------------------------------------------------------------------------
                | Quarterly
                |--------------------------------------------------------------------------
                */

                'quarters' => [
                    'nullable',
                    'array',
                ],

                'quarters.*.quarter' => [
                    'required',
                    'in:q1,q2,q3,q4',
                ],

                'quarters.*.start_date' => [
                    'required',
                    'date',
                ],

                'quarters.*.end_date' => [
                    'required',
                    'date',
                    'after_or_equal:quarters.*.start_date',
                ],

                /*
                |--------------------------------------------------------------------------
                | Yearly
                |--------------------------------------------------------------------------
                */

                'yearly_start_date' => [
                    'nullable',
                    'date',
                ],

                'yearly_end_date' => [
                    'nullable',
                    'date',
                    'after_or_equal:yearly_start_date',
                ],
            ]);


            /*
            |--------------------------------------------------------------------------
            | Database Transaction
            |--------------------------------------------------------------------------
            */

            $updatedTask = DB::transaction(function () use ($validated, $task) {

                /*
                |--------------------------------------------------------------------------
                | Get Quarterly Data
                |--------------------------------------------------------------------------
                */

                $quarters = $validated['quarters'] ?? [];

                // quarters belongs to task_quarters table
                unset($validated['quarters']);


                /*
                |--------------------------------------------------------------------------
                | Repeat Mode
                |--------------------------------------------------------------------------
                */

                $validated['repeat_mode'] =
                    $validated['repeat_mode'] ?? false;


                /*
                |--------------------------------------------------------------------------
                | Clear Fields Based On Task Type
                |--------------------------------------------------------------------------
                */

                switch ($validated['task_type']) {

                    case 'daily':

                        $validated['week_start_day'] = null;
                        $validated['week_end_day'] = null;

                        $validated['monthly_start_date'] = null;
                        $validated['monthly_end_date'] = null;

                        $validated['yearly_start_date'] = null;
                        $validated['yearly_end_date'] = null;

                        break;


                    case 'weekly':

                        $validated['start_time'] = null;
                        $validated['end_time'] = null;

                        $validated['monthly_start_date'] = null;
                        $validated['monthly_end_date'] = null;

                        $validated['yearly_start_date'] = null;
                        $validated['yearly_end_date'] = null;

                        break;


                    case 'monthly':

                        $validated['start_time'] = null;
                        $validated['end_time'] = null;

                        $validated['week_start_day'] = null;
                        $validated['week_end_day'] = null;

                        $validated['yearly_start_date'] = null;
                        $validated['yearly_end_date'] = null;

                        break;


                    case 'quarterly':

                        $validated['start_time'] = null;
                        $validated['end_time'] = null;

                        $validated['week_start_day'] = null;
                        $validated['week_end_day'] = null;

                        $validated['monthly_start_date'] = null;
                        $validated['monthly_end_date'] = null;

                        $validated['yearly_start_date'] = null;
                        $validated['yearly_end_date'] = null;

                        break;


                    case 'yearly':

                        $validated['start_time'] = null;
                        $validated['end_time'] = null;

                        $validated['week_start_day'] = null;
                        $validated['week_end_day'] = null;

                        $validated['monthly_start_date'] = null;
                        $validated['monthly_end_date'] = null;

                        break;
                }


                /*
                |--------------------------------------------------------------------------
                | Update Main Task
                |--------------------------------------------------------------------------
                */

                $task->update($validated);


                /*
                |--------------------------------------------------------------------------
                | Delete Existing Quarterly Records
                |--------------------------------------------------------------------------
                */

                $task->quarters()->delete();


                /*
                |--------------------------------------------------------------------------
                | Create New Quarterly Records
                |--------------------------------------------------------------------------
                */

                if ($task->task_type === 'quarterly') {

                    foreach ($quarters as $quarter) {

                        $task->quarters()->create([
                            'quarter'    => $quarter['quarter'],
                            'start_date' => $quarter['start_date'],
                            'end_date'   => $quarter['end_date'],
                        ]);
                    }
                }


                /*
                |--------------------------------------------------------------------------
                | Load Updated Task
                |--------------------------------------------------------------------------
                */

                $task->load([
                    'assignedUser:id,name,email,role',
                    'approvedBy:id,name,email,role',
                    'quarters:id,task_id,quarter,start_date,end_date',
                ]);


                return $task;
            });


            /*
            |--------------------------------------------------------------------------
            | Success Response
            |--------------------------------------------------------------------------
            */

            return response()->json([
                'status' => true,
                'status_code' => 200,
                'message' => 'Task updated successfully.',
                'data' => $updatedTask,
            ], 200);


        } catch (ValidationException $e) {

            /*
            |--------------------------------------------------------------------------
            | Validation Error
            |--------------------------------------------------------------------------
            */

            return response()->json([
                'status' => false,
                'status_code' => 422,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);


        } catch (Throwable $e) {

            /*
            |--------------------------------------------------------------------------
            | Other Errors
            |--------------------------------------------------------------------------
            */

            return response()->json([
                'status' => false,
                'status_code' => 500,
                'message' => 'Something went wrong while updating the task.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}