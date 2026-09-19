<?php

use App\Livewire\Audits\ShowResults;
use App\Models\Assessment;
use App\Models\Criterion;
use App\Models\CriterionOption;
use App\Models\User;
use App\Models\Workbook;
use Livewire\Livewire;

function makeGradedCriterion(Workbook $workbook, string $priority, string $groupLabel = 'Grupa'): Criterion
{
    $criterion = Criterion::factory()->for($workbook)->create([
        'group_label' => $groupLabel,
        'priority' => $priority,
    ]);

    CriterionOption::factory()->for($criterion)->create(['label' => 'Da', 'points' => 10]);
    CriterionOption::factory()->for($criterion)->create(['label' => 'Djelimično', 'points' => 5]);
    CriterionOption::factory()->for($criterion)->create(['label' => 'Ne', 'points' => 0]);

    return $criterion;
}

function answer(Assessment $assessment, Criterion $criterion, string $label): void
{
    $option = $criterion->options()->where('label', $label)->firstOrFail();

    $assessment->answers()->create(['criterion_id' => $criterion->id, 'selected_option_id' => $option->id]);
}

test('the overall score and status render for a completed assessment', function () {
    $this->actingAs(User::factory()->create());

    $workbook = Workbook::factory()->create(['weight' => 1]);
    $criterion = makeGradedCriterion($workbook, 'kritican');
    $assessment = Assessment::factory()->create(['status' => 'completed']);
    answer($assessment, $criterion, 'Da');

    Livewire::test(ShowResults::class, ['assessment' => $assessment])
        ->assertSet('overallScore', 100.0)
        ->assertSee('NAPREDNO')
        ->assertOk();
});

test('priorities are sorted by priority tier first, then by the largest point gap', function () {
    $this->actingAs(User::factory()->create());

    $workbook = Workbook::factory()->create(['weight' => 1]);
    $assessment = Assessment::factory()->create();

    // Important priority, small gap (5 points missing).
    $vazanSmallGap = makeGradedCriterion($workbook, 'vazan');
    answer($assessment, $vazanSmallGap, 'Djelimično');

    // Critical priority, small gap — should still rank above the "vazan" ones despite a smaller gap.
    $kriticanSmallGap = makeGradedCriterion($workbook, 'kritican');
    answer($assessment, $kriticanSmallGap, 'Djelimično');

    // Critical priority, full gap (10 points missing) — largest gap, same tier as above.
    $kriticanFullGap = makeGradedCriterion($workbook, 'kritican');
    answer($assessment, $kriticanFullGap, 'Ne');

    // Recommended priority — excluded entirely from the preview.
    $preporucen = makeGradedCriterion($workbook, 'preporucen');
    answer($assessment, $preporucen, 'Ne');

    $component = Livewire::test(ShowResults::class, ['assessment' => $assessment]);

    $priorityIds = $component->get('priorities')->pluck('criterion.id')->all();

    expect($priorityIds)->toBe([
        $kriticanFullGap->id,
        $kriticanSmallGap->id,
        $vazanSmallGap->id,
    ]);
});

test('the area detail page lists criteria with their answers and evidence', function () {
    $this->actingAs(User::factory()->create());

    $workbook = Workbook::factory()->create(['weight' => 1]);
    $criterion = makeGradedCriterion($workbook, 'kritican', 'I. Grupa');
    $assessment = Assessment::factory()->create();
    answer($assessment, $criterion, 'Da');

    $this->get(route('audits.results.area', [$assessment, $workbook->area]))
        ->assertOk()
        ->assertSee($workbook->name)
        ->assertSee($criterion->external_id)
        ->assertSee('Odgovor: Da');
});
