<?php

namespace Database\Factories;

use App\Models\Assessment;
use App\Models\AssessmentAnswer;
use App\Models\Criterion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssessmentAnswer>
 */
class AssessmentAnswerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'assessment_id' => Assessment::factory(),
            'criterion_id' => Criterion::factory(),
            'selected_option_id' => null,
            'is_na' => false,
            'na_reason' => null,
            'evidence_path' => null,
            'notes' => null,
        ];
    }
}
