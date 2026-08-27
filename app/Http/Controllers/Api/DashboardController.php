<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{

    public function dashboardCounts(): JsonResponse
    {
        $user = auth()->user();

        $query = Task::query();

        // Admin → all tasks
        // Manager / Employee → assigned or approved tasks
        if ($user->role->value !== 'admin') {
            $query->where(function ($query) use ($user) {
                $query->where('assigned_to', $user->id)
                    ->orWhere('approved_by', $user->id);
            });
        }

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

            'approval_pending' => (clone $query)
                ->where('status', 'pending')
                ->count(),
        ];

        return response()->json([
            'status' => true,
            'status_code' => 200,
            'message' => 'Task dashboard counts retrieved successfully.',
            'data' => $counts,
        ], 200);
    }
}