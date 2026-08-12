<?php

namespace Database\Factories;

use App\Models\Lead;
use App\Models\Quotation;
use App\Support\QuotationReference;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Quotation>
 */
class QuotationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lead_id' => Lead::factory(),
            'reference' => QuotationReference::PREFIX.$this->faker->unique()->numberBetween(1000, 99999),
            'customer_name' => $this->faker->name(),
            'customer_address' => $this->faker->address(),
            'issue_date' => today(),
            'valid_until' => today()->addDays(14),
            'subtotal' => 0,
            'total' => 0,
        ];
    }
}
