<?php

namespace App\Contracts;

use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Finds staff whose salary increment is approaching and notifies the Admins.
 *
 * An interface rather than a concrete dependency so the scheduled command,
 * a queued job, or a test double can all depend on the same contract. Bound
 * to SalaryIncrementService in AppServiceProvider.
 */
interface SalaryIncrementReminderService
{
    /**
     * Active staff with the reminder enabled whose increment_date falls
     * inside the next $withinDays days.
     *
     * @return Collection<int, User>
     */
    public function dueForReminder(?int $withinDays = null): Collection;

    /**
     * Admins who should receive the reminder.
     *
     * @return Collection<int, User>
     */
    public function recipients(): Collection;

    /**
     * Send one reminder per due employee to every recipient.
     *
     * @return int Number of employees a reminder was raised for.
     */
    public function sendReminders(?int $withinDays = null): int;
}
