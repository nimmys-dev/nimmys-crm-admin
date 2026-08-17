<?php

namespace Database\Factories;

use App\Enums\CallStatus;
use App\Models\CallDetail;
use App\Models\Lead;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CallDetail>
 */
class CallDetailFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lead_id' => Lead::factory(),
            'call_status' => CallStatus::Answered,
            'interest' => true,
            'is_item_sold' => false,
            'reason' => null,
            'invoice_number' => null,
            'invoice_file_path' => null,
            'remarks' => $this->faker->sentence(),
            'called_by' => null,
            'called_date' => today(),
            'called_time' => '10:30:00',
            'next_followup_date' => today()->addDays(3),
            'duration' => $this->faker->numberBetween(15, 900),
        ];
    }

    public function status(CallStatus $status): static
    {
        return $this->state(fn () => ['call_status' => $status]);
    }

    public function notAnswered(): static
    {
        return $this->state(fn () => [
            'call_status' => CallStatus::NotAnswered,
            'interest' => null,
            'reason' => null,
            'is_item_sold' => null,
            'invoice_number' => null,
            'invoice_file_path' => null,
            'duration' => null,
            'next_followup_date' => today()->addDays(2),
        ]);
    }

    public function notInterested(string $reason = 'Not needed'): static
    {
        return $this->state(fn () => [
            'call_status' => CallStatus::Answered,
            'interest' => false,
            'reason' => $reason,
            'is_item_sold' => null,
            'invoice_number' => null,
            'invoice_file_path' => null,
            'next_followup_date' => null,
        ]);
    }

    public function sold(string $invoiceNumber = 'INV-1001'): static
    {
        return $this->state(fn () => [
            'call_status' => CallStatus::Answered,
            'interest' => true,
            'is_item_sold' => true,
            'reason' => null,
            'invoice_number' => $invoiceNumber,
            'next_followup_date' => null,
        ]);
    }

    public function by(int $userId): static
    {
        return $this->state(fn () => ['called_by' => $userId]);
    }

    /**
     * A call made $daysAgo days ago at a given time.
     */
    public function madeOn(int $daysAgo, string $time = '10:30:00'): static
    {
        return $this->state(fn () => [
            'called_date' => today()->subDays($daysAgo),
            'called_time' => $time,
        ]);
    }

    /**
     * Negative $days schedules the follow-up in the past (overdue).
     */
    public function followUpIn(int $days): static
    {
        return $this->state(fn () => ['next_followup_date' => today()->addDays($days)]);
    }
}
