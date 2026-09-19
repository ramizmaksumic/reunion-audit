<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\CompanyChannelRelevance;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompanyChannelRelevance>
 */
class CompanyChannelRelevanceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'channel_key' => $this->faker->unique()->word(),
            'relevance' => $this->faker->randomElement(['critical', 'recommended', 'not_relevant']),
        ];
    }
}
