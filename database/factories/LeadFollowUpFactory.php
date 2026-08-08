<?php

namespace Database\Factories;

use App\Enums\FollowUpType;
use App\Models\Lead;
use App\Models\LeadFollowUp;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeadFollowUp>
 */
class LeadFollowUpFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lead_id' => Lead::factory(),
            'user_id' => null,
            'type' => FollowUpType::Call,
            'notes' => $this->faker->sentence(),
            'scheduled_at' => now()->addDay(),
            'completed_at' => null,
            'outcome' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'completed_at' => now(),
            'outcome' => 'Spoke with the contact.',
        ]);
    }

    public function overdue(int $days = 3): static
    {
        return $this->state(fn () => [
            'scheduled_at' => now()->subDays($days),
            'completed_at' => null,
        ]);
    }
}
