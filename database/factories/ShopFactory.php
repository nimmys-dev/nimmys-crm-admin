<?php

namespace Database\Factories;

use App\Enums\ShopStatus;
use App\Models\Shop;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Shop>
 */
class ShopFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'SHP-'.$this->faker->unique()->numberBetween(1000, 9999),
            'name' => $this->faker->company(),
            'manager_id' => null,
            'email' => $this->faker->unique()->companyEmail(),
            'phone' => $this->faker->numerify('##########'),
            'address_line' => $this->faker->streetAddress(),
            'city' => $this->faker->city(),
            'state' => $this->faker->state(),
            'postal_code' => $this->faker->postcode(),
            'country' => 'India',
            'opened_on' => $this->faker->dateTimeBetween('-5 years')->format('Y-m-d'),
            'status' => ShopStatus::Active,
            'notes' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => ShopStatus::Inactive]);
    }
}
