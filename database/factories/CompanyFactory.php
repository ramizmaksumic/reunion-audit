<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'industry' => $this->faker->word(),
            'business_model_notes' => $this->faker->sentence(),
            'b2b_or_b2c' => $this->faker->randomElement(['b2b', 'b2c', 'both']),
            'market_scope' => $this->faker->randomElement(['local', 'regional', 'national', 'international']),
            'has_physical_location' => $this->faker->boolean(),
            'sells_online' => $this->faker->boolean(),
            'provides_online_services' => $this->faker->boolean(),
            'works_by_appointment' => $this->faker->boolean(),
            'has_multiple_locations' => $this->faker->boolean(),
        ];
    }
}
