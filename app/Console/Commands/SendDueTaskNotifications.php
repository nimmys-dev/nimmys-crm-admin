<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Models\TaskQuarter;
use App\Models\User;
use App\Services\FirebaseNotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SendDueTaskNotifications extends Command
{
    protected $signature = 'tasks:send-due-notifications';

    protected $description =
        'Send Firebase notifications at task start time only';

    public function handle(
        FirebaseNotificationService $firebase
    ): int {
        $now = now();

        $tasks = Task::query()
            ->whereNotNull('assigned_to')
            ->whereIn('task_type', [
                'daily',
                'weekly',
                'monthly',
                'quarterly',
                'yearly',
            ])
            ->whereDate('created_at', $now->toDateString())
            ->get();

        foreach ($tasks as $task) {
            try {
                $user = User::find($task->assigned_to);

                if (!$user || empty($user->fcm_token)) {
                    continue;
                }

                // শুধুমাত্র 'start'-এর জন্য লুপ বা চেক রাখা হয়েছে ('end' বাদ দেওয়া হয়েছে)
                $edge = 'start';

                if (!$this->isDue($task, $now, $edge)) {
                    continue;
                }

                $eventKey = $now->format('Y-m-d-H-i')
                    . '-' . $edge;

                $cacheKey = 'task-fcm:'
                    . $task->id . ':' . $eventKey;

                if (Cache::has($cacheKey)) {
                    continue;
                }

                $isStart = true;

                $sent = $firebase->sendToUser(
                    $user,
                    'Task Started',
                    'Your task "' . $task->title . '" has started.',
                    [
                        'type' => 'task_reminder',
                        'task_id' => (string) $task->id,
                        'title' => (string) $task->title,
                        'event' => $edge,
                    ]
                );

                if ($sent) {
                    Cache::put(
                        $cacheKey,
                        true,
                        now()->addDays(370)
                    );

                    $this->info(
                        "Notification sent for task {$task->id}: {$edge}"
                    );
                }
            } catch (\Throwable $e) {
                Log::error('Task reminder failed', [
                    'task_id' => $task->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info('Due task check completed.');

        return self::SUCCESS;
    }

    private function isDue(
    Task $task,
    Carbon $now,
    string $edge
): bool {
    // Start reminder only
    if ($edge !== 'start') {
        return false;
    }

    // Start time must match current time
    if (
        empty($task->start_time) ||
        !$this->matchesTime($task->start_time, $now)
    ) {
        return false;
    }

    switch (strtolower((string) $task->task_type)) {
        case 'daily':
            // Daily task: today's start time only
            return true;

        case 'weekly':
            return $this->matchesWeekday(
                $task->week_start_day,
                $now
            );

        case 'monthly':
            return $this->matchesMonthDay(
                $task->monthly_start_date,
                $now
            );

        case 'quarterly':
            return TaskQuarter::query()
                ->where('task_id', $task->id)
                ->whereDate(
                    'start_date',
                    $now->toDateString()
                )
                ->exists();

        case 'yearly':
            return $this->matchesYearlyDate(
                $task->yearly_start_date,
                $now
            );

        default:
            return false;
    }
}

    private function matchesTime(
        mixed $time,
        Carbon $now
    ): bool {
        try {
            return Carbon::parse((string) $time)
                ->format('H:i') === $now->format('H:i');
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function matchesWeekday(
        mixed $day,
        Carbon $now
    ): bool {
        if ($day === null || $day === '') {
            return false;
        }

        $value = strtolower(trim((string) $day));

        $days = [
            '0' => 'sunday',
            '1' => 'monday',
            '2' => 'tuesday',
            '3' => 'wednesday',
            '4' => 'thursday',
            '5' => 'friday',
            '6' => 'saturday',
            '7' => 'sunday',
        ];

        if (isset($days[$value])) {
            $value = $days[$value];
        }

        return $value === strtolower($now->format('l'))
            || $value === strtolower($now->format('D'));
    }

    private function matchesMonthDay(
        mixed $date,
        Carbon $now
    ): bool {
        if ($date === null || $date === '') {
            return false;
        }

        try {
            if (is_numeric($date)) {
                $day = (int) $date;
            } else {
                $day = (int) Carbon::parse(
                    (string) $date
                )->format('d');
            }

            return $day === (int) $now->format('d');
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function matchesYearlyDate(
        mixed $date,
        Carbon $now
    ): bool {
        if (!$date) {
            return false;
        }

        try {
            return Carbon::parse((string) $date)
                ->format('m-d') === $now->format('m-d');
        } catch (\Throwable $e) {
            return false;
        }
    }
}