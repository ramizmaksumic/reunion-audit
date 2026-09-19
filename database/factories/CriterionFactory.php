<?php

namespace Database\Factories;

use App\Models\Criterion;
use App\Models\Workbook;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Criterion>
 */
class CriterionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workbook_id' => Workbook::factory(),
            'external_id' => 'TST-'.$this->faker->unique()->numerify('###'),
            'group_label' => 'I. '.$this->faker->sentence(2),
            'text' => $this->faker->sentence(),
            'answer_type' => 'binary',
            'priority' => $this->faker->randomElement(['kritican', 'vazan', 'preporucen']),
            'evidence_source' => $this->faker->words(2, true),
            'self_service_eligible' => $this->faker->boolean(),
            'is_relevance_gate' => false,
            'sort_order' => 0,
        ];
    }

    public function relevanceGate(): static
    {
        return $this->state(fn () => ['is_relevance_gate' => true]);
    }
}
