<?php

namespace Database\Factories;

use App\Models\Criterion;
use App\Models\CriterionOption;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CriterionOption>
 */
class CriterionOptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'criterion_id' => Criterion::factory(),
            'label' => $this->faker->words(2, true),
            'points' => $this->faker->randomFloat(2, 0, 5),
            'sort_order' => 0,
        ];
    }
}
