<?php

use App\Livewire\Audits\ShowAreaResults;
use App\Models\Area;
use App\Models\Assessment;
use App\Models\Criterion;
use App\Models\CriterionOption;
use App\Models\User;
use App\Models\Workbook;
use Livewire\Livewire;

function makeAreaResultsCriterion(Workbook $workbook, string $groupLabel = 'Grupa'): Criterion
{
    $criterion = Criterion::factory()->for($workbook)->create(['group_label' => $groupLabel]);

    CriterionOption::factory()->for($criterion)->create(['label' => 'Da', 'points' => 10]);
    CriterionOption::factory()->for($criterion)->create(['label' => 'Ne', 'points' => 0]);

    return $criterion;
}

test('a workbook applicable to the assessment shows its numeric score', function () {
    $this->actingAs(User::factory()->create());

    $area = Area::factory()->create();
    $assessment = Assessment::factory()->create(['methodology_version' => 'v2.0']);
    $workbook = Workbook::factory()->for($area)->create(['weight' => 1, 'introduced_in_version' => null]);
    $criterion = makeAreaResultsCriterion($workbook);
    $assessment->answers()->create(['criterion_id' => $criterion->id, 'selected_option_id' => $criterion->options()->where('label', 'Da')->first()->id]);

    Livewire::test(ShowAreaResults::class, ['assessment' => $assessment, 'area' => $area])
        ->assertSee('100/100')
        ->assertDontSee('Nije popunjeno');
});

test('a workbook newer than the assessment is shown as "Nije popunjeno" instead of a score', function () {
    $this->actingAs(User::factory()->create());

    $area = Area::factory()->create();
    $assessment = Assessment::factory()->create(['methodology_version' => 'v2.0']);
    Workbook::factory()->for($area)->create(['weight' => 1, 'introduced_in_version' => 'v2.1']);

    Livewire::test(ShowAreaResults::class, ['assessment' => $assessment, 'area' => $area])
        ->assertSee('Nije popunjeno')
        ->assertOk();
});
