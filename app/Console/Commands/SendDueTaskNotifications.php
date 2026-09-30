<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Models\User;
use App\Services\FirebaseNotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendDueTaskNotifications extends Command
{
    protected $signature = 'tasks:send-notifications';

    protected $description = 'Send Firebase notifications for due tasks exactly at start time once per occurrence';

    public function handle(
        FirebaseNotificationService $firebaseService
    ): int {

        $now = Carbon::now();

        Task::query()
            ->whereNotIn('status', ['completed', 'approved'])
            ->whereNotNull('assigned_to')
            ->with('assignedUser')
            ->chunkById(100, function ($tasks) use ($now, $firebaseService) {

                foreach ($tasks as $task) {

                    if (!$this->isDue($task, $now)) {
                        continue;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Prevent duplicate notification for the current occurrence
                    |--------------------------------------------------------------------------
                    */

                    if ($task->last_notified_at) {
                        $isAlreadyNotified = match ($task->task_type) {
                            'daily' => $task->last_notified_at->isSameDay($now),
                            'weekly' => $task->last_notified_at->format('o-W') === $now->format('o-W'),
                            'monthly' => $task->last_notified_at->format('Y-m') === $now->format('Y-m'),
                            'quarterly' => $task->last_notified_at->format('Y') . '-Q' . $task->last_notified_at->quarter === $now->format('Y') . '-Q' . $now->quarter,
                            'yearly' => $task->last_notified_at->format('Y') === $now->format('Y'),
                            default => $task->last_notified_at->isSameDay($now),
                        };

                        if ($isAlreadyNotified) {
                            continue;
                        }
                    }

                    $user = $task->assignedUser;

                    if (!$user) {
                        continue;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Firebase Notification
                    |--------------------------------------------------------------------------
                    */

                    try {
                        $firebaseService->sendToUser(
                            $user,
                            'Task Reminder',
                            'Your task is due: ' . $task->title,
                            [
                                'type'    => 'task',
                                'task_id' => (string) $task->id,
                                'title'   => (string) $task->title,
                            ]
                        );

                        \Log::info('Cron task FCM notification sent', [
                            'task_id' => $task->id,
                            'user_id' => $user->id,
                        ]);
                    } catch (\Throwable $e) {
                        \Log::error('Cron task FCM notification failed', [
                            'task_id' => $task->id,
                            'error'   => $e->getMessage(),
                        ]);
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Mark Notification Sent
                    |--------------------------------------------------------------------------
                    */

                    $task->update([
                        'last_notified_at' => $now,
                    ]);
                }
            });

        return self::SUCCESS;
    }


    /*
    |--------------------------------------------------------------------------
    | Check Whether Task Is Due
    |--------------------------------------------------------------------------
    */

    private function isDue(Task $task, Carbon $now): bool
    {
        switch ($task->task_type) {

            case 'daily':
                return $this->isDailyDue($task, $now);

            case 'weekly':
                return $this->isWeeklyDue($task, $now);

            case 'monthly':
                return $this->isMonthlyDue($task, $now);

            case 'quarterly':
                return $this->isQuarterlyDue($task, $now);

            case 'yearly':
                return $this->isYearlyDue($task, $now);
        }

        return false;
    }

    private function isDailyDue(Task $task, Carbon $now): bool
    {
        if (!$task->start_time) {
            return false;
        }

        $startTime = Carbon::parse($now->format('Y-m-d') . ' ' . $task->start_time);
        
        // Trigger ONLY at the exact scheduled start time minute
        return $now->format('Y-m-d H:i') === $startTime->format('Y-m-d H:i');
    }

    private function isWeeklyDue(Task $task, Carbon $now): bool
    {
        if (!$task->week_start_day) {
            return false;
        }

        $currentDay = strtolower($now->format('l'));
        return $currentDay === strtolower($task->week_start_day);
    }

    private function isMonthlyDue(Task $task, Carbon $now): bool
    {
        if (!$task->monthly_start_date) {
            return false;
        }

        return $now->isSameDay(Carbon::parse($task->monthly_start_date));
    }

    private function isYearlyDue(Task $task, Carbon $now): bool
    {
        if (!$task->yearly_start_date) {
            return false;
        }

        $date = Carbon::parse($task->yearly_start_date);

        return $now->month === $date->month && $now->day === $date->day;
    }

    private function isQuarterlyDue(Task $task, Carbon $now): bool
    {
        return $task->quarters()
            ->whereDate('start_date', '<=', $now->toDateString())
            ->whereDate('end_date', '>=', $now->toDateString())
            ->exists();
    }
}