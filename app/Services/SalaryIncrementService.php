<?php

namespace App\Services;

use App\Contracts\SalaryIncrementReminderService;
use App\Enums\UserRole;
use App\Models\User;
use App\Notifications\SalaryIncrementReminder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

/**
 * Salary increment reminders.
 *
 * The scheduler is deliberately not wired yet. When it is, the whole job is:
 *
 *   // routes/console.php
 *   Schedule::call(fn (SalaryIncrementReminderService $service) => $service->sendReminders())
 *       ->dailyAt('08:00')
 *       ->name('salary-increment-reminders')
 *       ->withoutOverlapping();
 *
 * Nothing else has to change — the query, the recipients and the notification
 * are all in place and covered by tests.
 */
class SalaryIncrementService implements SalaryIncrementReminderService
{
    /**
     * @return Collection<int, User>
     */
    public function dueForReminder(?int $withinDays = null): Collection
    {
        return User::query()
            ->dueForIncrementReminder($withinDays)
            ->with('shop:id,name')
            ->orderBy('increment_date')
            ->get();
    }

    /**
     * Only Admins are notified, per the brief. Managers do not receive these
     * even though they can be told about their own team elsewhere.
     *
     * @return Collection<int, User>
     */
    public function recipients(): Collection
    {
        return User::query()
            ->role(UserRole::Admin)
            ->active()
            ->get();
    }

    public function sendReminders(?int $withinDays = null): int
    {
        $due = $this->dueForReminder($withinDays);

        if ($due->isEmpty()) {
            return 0;
        }

        $recipients = $this->recipients();

        if ($recipients->isEmpty()) {
            return 0;
        }

        foreach ($due as $employee) {
            Notification::send($recipients, new SalaryIncrementReminder($employee));
        }

        return $due->count();
    }
}
