<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells an Admin that an employee's salary increment is coming up.
 *
 * Queued so a large sweep never blocks the scheduler. The database channel
 * needs the notifications table:
 *
 *   php artisan make:notification-table && php artisan migrate
 *
 * Until then `via()` returns mail only — see channels below.
 */
class SalaryIncrementReminder extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly User $employee) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        // Add 'database' once the notifications table exists, so reminders
        // also surface in the header bell.
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Salary increment reminder: '.$this->employee->name)
            ->greeting('Salary Increment Reminder')
            ->line('An upcoming salary increment needs your review.')
            ->line('**Employee:** '.$this->employee->name.' ('.$this->employee->employee_code.')')
            ->line('**Current salary:** '.$this->formatMoney($this->employee->salary))
            ->line('**Increment date:** '.$this->employee->increment_date?->format('d-M-Y'))
            ->line('**Increment amount:** '.$this->formatMoney($this->employee->increment_amount))
            ->line('**Salary after increment:** '.$this->formatMoney($this->employee->projectedSalary()))
            ->action('Review employee', route('staff.show', $this->employee));
    }

    /**
     * Payload for the database channel and the future mobile push.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'salary_increment_reminder',
            'employee_id' => $this->employee->id,
            'employee_name' => $this->employee->name,
            'employee_code' => $this->employee->employee_code,
            'current_salary' => $this->employee->salary,
            'increment_date' => $this->employee->increment_date?->toDateString(),
            'increment_amount' => $this->employee->increment_amount,
            'projected_salary' => $this->employee->projectedSalary(),
            'url' => route('staff.show', $this->employee),
        ];
    }

    private function formatMoney(int|float|string|null $value): string
    {
        return blank($value)
            ? '—'
            : config('app.currency_symbol', '₹').number_format((float) $value, 2);
    }
}
