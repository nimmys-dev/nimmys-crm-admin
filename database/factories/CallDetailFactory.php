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
            'call_status' => CallStatus::Connected,
            'remarks' => $this->faker->sentence(),
            'called_by' => null,
            'called_date' => today(),
            'called_time' => '10:30:00',
            'next_followup_date' => null,
            'duration' => $this->faker->numberBetween(15, 900),
        ];
    }

    public function status(CallStatus $status): static
    {
        return $this->state(fn () => ['call_status' => $status]);
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
