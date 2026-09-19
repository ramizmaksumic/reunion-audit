<?php

namespace Database\Factories;

use App\Models\Area;
use App\Models\Workbook;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Workbook>
 */
class WorkbookFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'area_id' => Area::factory(),
            'key' => $this->faker->unique()->slug(2),
            'name' => $this->faker->words(3, true),
            'sort_order' => 0,
            'weight' => 1.0,
        ];
    }
}
