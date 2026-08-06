<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),

            // Least privilege by default, matching the column default. Tests
            // must opt in to an elevated role explicitly.
            'role' => UserRole::Employee,
            'status' => UserStatus::Active,
        ];
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => ['role' => UserRole::Admin]);
    }

    public function manager(): static
    {
        return $this->state(fn (array $attributes) => ['role' => UserRole::Manager]);
    }

    public function employee(): static
    {
        return $this->state(fn (array $attributes) => ['role' => UserRole::Employee]);
    }

    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => ['status' => UserStatus::Suspended]);
    }

    public function withLeadAccess(): static
    {
        return $this->state(fn (array $attributes) => ['lead_module_access' => true]);
    }

    /**
     * An increment scheduled inside the reminder window.
     */
    public function incrementDueIn(int $days = 3): static
    {
        return $this->state(fn (array $attributes) => [
            'salary' => '25000.00',
            'increment_amount' => '2500.00',
            'increment_date' => today()->addDays($days),
            'increment_notification' => true,
        ]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
