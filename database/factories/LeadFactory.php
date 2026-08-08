<?php

namespace Database\Factories;

use App\Enums\LeadPriority;
use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Support\LeadReference;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lead>
 */
class LeadFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reference' => LeadReference::PREFIX.$this->faker->unique()->numberBetween(1000, 99999),
            'name' => $this->faker->name(),
            'company' => $this->faker->company(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->numerify('##########'),
            'city' => $this->faker->city(),
            'source' => $this->faker->randomElement(LeadSource::cases()),
            'status' => LeadStatus::New,
            'priority' => LeadPriority::Medium,
            'value' => $this->faker->randomFloat(2, 1000, 500000),
            'shop_id' => null,
            'assigned_to' => null,
            'created_by' => null,
            'description' => '<p>'.$this->faker->sentence().'</p>',
        ];
    }

    public function status(LeadStatus $status): static
    {
        return $this->state(fn () => [
            'status' => $status,
            'closed_at' => $status->isClosed() ? now() : null,
            'lost_reason' => $status === LeadStatus::Lost ? 'Went with a competitor.' : null,
        ]);
    }

    public function priority(LeadPriority $priority): static
    {
        return $this->state(fn () => ['priority' => $priority]);
    }

    public function assignedTo(int $userId): static
    {
        return $this->state(fn () => ['assigned_to' => $userId]);
    }

    public function forShop(int $shopId): static
    {
        return $this->state(fn () => ['shop_id' => $shopId]);
    }

    /**
     * A lead whose next follow-up is due in $days (negative = overdue).
     */
    public function followUpDueIn(int $days = 0): static
    {
        return $this->state(fn () => ['next_follow_up_at' => today()->addDays($days)]);
    }
}
