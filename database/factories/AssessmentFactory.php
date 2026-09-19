<?php

namespace Database\Factories;

use App\Models\Assessment;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Assessment>
 */
class AssessmentFactory extends Factory
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
            'methodology_version' => 'v2.0',
            'mode' => 'full_audit',
            'status' => 'in_progress',
            'started_at' => now(),
            'completed_at' => null,
            'created_by' => User::factory(),
        ];
    }
}
