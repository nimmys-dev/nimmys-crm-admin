<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Services\FirebaseNotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SendDueTaskNotifications extends Command
{
    protected $signature = 'tasks:send-notifications';

    protected $description = 'Send one Firebase notification to the assigned user only';

    public function handle(
        FirebaseNotificationService $firebaseService
    ): int {

        $now = Carbon::now();

        Task::query()
            ->whereNotIn('status', ['completed', 'approved'])
            ->whereNotNull('assigned_to')
            ->whereNull('last_notified_at')
            ->with('assignedUser')
            ->chunkById(100, function ($tasks) use ($now, $firebaseService) {

                foreach ($tasks as $task) {

                    try {

                        DB::transaction(function () use (
                            $task,
                            $now,
                            $firebaseService
                        ) {

                            $lockedTask = Task::query()
                                ->where('id', $task->id)
                                ->lockForUpdate()
                                ->first();

                            if (!$lockedTask) {
                                return;
                            }

                            if ($lockedTask->last_notified_at !== null) {
                                return;
                            }

                            if (in_array(
                                $lockedTask->status,
                                ['completed', 'approved'],
                                true
                            )) {
                                return;
                            }

                            if (!$lockedTask->assigned_to) {
                                return;
                            }

                            if (!$this->isDue($lockedTask, $now)) {
                                return;
                            }

                            $user = $lockedTask->assignedUser;

                            if (!$user) {
                                Log::warning(
                                    'Assigned user not found',
                                    [
                                        'task_id' => $lockedTask->id,
                                        'assigned_to' => $lockedTask->assigned_to,
                                    ]
                                );
                                return;
                            }

                            if (empty($user->fcm_token)) {
                                Log::warning(
                                    'Assigned user has no FCM token',
                                    [
                                        'task_id' => $lockedTask->id,
                                        'assigned_to' => $user->id,
                                    ]
                                );
                                return;
                            }

                            // 1. ആദ്യം തന്നെ last_notified_at അപ്ഡേറ്റ് ചെയ്യുക 
                            // (ഇത് വഴി ഒന്നിച്ച് വീണ്ടും നോട്ടിഫിക്കേഷൻ പോകുന്ന റേസ് കണ്ടീഷൻ ഒഴിവാക്കാം)
                            $lockedTask->update([
                                'last_notified_at' => $now,
                            ]);

                            // 2. ഫയർബേസ് നോട്ടിഫിക്കേഷൻ അയക്കുക
                            $sent = $firebaseService->sendToUser(
                                $user,
                                'Task Reminder',
                                'Your task is due: ' . $lockedTask->title,
                                [
                                    'type' => 'task',
                                    'task_id' => (string) $lockedTask->id,
                                    'title' => (string) $lockedTask->title,
                                ]
                            );

                            Log::info(
                                'Task reminder sent ONCE to assigned user',
                                [
                                    'task_id' => $lockedTask->id,
                                    'assigned_to' => $user->id,
                                    'last_notified_at' => $now->toDateTimeString(),
                                ]
                            );
                        });

                    } catch (\Throwable $e) {
                        Log::error(
                            'Task reminder notification failed',
                            [
                                'task_id' => $task->id,
                                'error' => $e->getMessage(),
                            ]
                        );
                    }
                }
            });

        return self::SUCCESS;
    }

    private function isDue(Task $task, Carbon $now): bool
    {
        return match ($task->task_type) {
            'daily' => $this->isDailyDue($task, $now),
            'weekly' => $this->isWeeklyDue($task, $now),
            'monthly' => $this->isMonthlyDue($task, $now),
            'quarterly' => $this->isQuarterlyDue($task, $now),
            'yearly' => $this->isYearlyDue($task, $now),
            default => false,
        };
    }

    private function isDailyDue(Task $task, Carbon $now): bool
    {
        if (!$task->start_time) {
            return false;
        }

        $startTime = Carbon::parse(
            $now->format('Y-m-d') . ' ' . $task->start_time
        );

        return $now->format('Y-m-d H:i') === $startTime->format('Y-m-d H:i');
    }

    private function isWeeklyDue(Task $task, Carbon $now): bool
    {
        if (!$task->week_start_day || !$task->start_time) {
            return false;
        }

        if (strtolower($now->format('l')) !== strtolower($task->week_start_day)) {
            return false;
        }

        $startTime = Carbon::parse(
            $now->format('Y-m-d') . ' ' . $task->start_time
        );

        return $now->format('Y-m-d H:i') === $startTime->format('Y-m-d H:i');
    }

    private function isMonthlyDue(Task $task, Carbon $now): bool
    {
        if (!$task->monthly_start_date || !$task->start_time) {
            return false;
        }

        $date = Carbon::parse($task->monthly_start_date);

        if (!$now->isSameDay($date)) {
            return false;
        }

        $startTime = Carbon::parse(
            $now->format('Y-m-d') . ' ' . $task->start_time
        );

        return $now->format('Y-m-d H:i') === $startTime->format('Y-m-d H:i');
    }

    private function isQuarterlyDue(Task $task, Carbon $now): bool
    {
        if (!$task->start_time) {
            return false;
        }

        $quarter = $task->quarters()
            ->whereDate('start_date', '<=', $now->toDateString())
            ->whereDate('end_date', '>=', $now->toDateString())
            ->first();

        if (!$quarter) {
            return false;
        }

        if (!$now->isSameDay(Carbon::parse($quarter->start_date))) {
            return false;
        }

        $startTime = Carbon::parse(
            $now->format('Y-m-d') . ' ' . $task->start_time
        );

        return $now->format('Y-m-d H:i') === $startTime->format('Y-m-d H:i');
    }

    private function isYearlyDue(Task $task, Carbon $now): bool
    {
        if (!$task->yearly_start_date || !$task->start_time) {
            return false;
        }

        $date = Carbon::parse($task->yearly_start_date);

        if ($now->month !== $date->month || $now->day !== $date->day) {
            return false;
        }

        $startTime = Carbon::parse(
            $now->format('Y-m-d') . ' ' . $task->start_time
        );

        return $now->format('Y-m-d H:i') === $startTime->format('Y-m-d H:i');
    }
}