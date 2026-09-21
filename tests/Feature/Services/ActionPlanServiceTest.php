<?php

use App\Models\Assessment;
use App\Models\Criterion;
use App\Models\CriterionOption;
use App\Models\Workbook;
use App\Services\ActionPlanService;

function actionPlanService(): ActionPlanService
{
    return app(ActionPlanService::class);
}

function makePriorityCriterion(Workbook $workbook, string $priority, string $groupLabel = 'Grupa'): Criterion
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

function answerWith(Assessment $assessment, Criterion $criterion, string $label): void
{
    $option = $criterion->options()->where('label', $label)->firstOrFail();

    $assessment->answers()->create(['criterion_id' => $criterion->id, 'selected_option_id' => $option->id]);
}

test('generate only includes kritican/vazan criteria that lost points, grouped into the right horizon', function () {
    $workbook = Workbook::factory()->create();
    $assessment = Assessment::factory()->create();

    $kritican = makePriorityCriterion($workbook, 'kritican');
    answerWith($assessment, $kritican, 'Ne'); // full loss -> 0-14 dana

    $vazan = makePriorityCriterion($workbook, 'vazan');
    answerWith($assessment, $vazan, 'Djelimično'); // partial loss -> 31-90 dana

    $preporucen = makePriorityCriterion($workbook, 'preporucen');
    answerWith($assessment, $preporucen, 'Ne'); // excluded: not kritican/vazan

    $fullyEarned = makePriorityCriterion($workbook, 'kritican');
    answerWith($assessment, $fullyEarned, 'Da'); // excluded: no points lost

    $plan = actionPlanService()->generate($assessment);

    expect($plan->keys()->all())->toBe(['0–14 dana', '31–90 dana', '3–12 mjeseci']);
    expect($plan['0–14 dana']->pluck('criterion.id')->all())->toBe([$kritican->id]);
    expect($plan['31–90 dana']->pluck('criterion.id')->all())->toBe([$vazan->id]);
    expect($plan['3–12 mjeseci'])->toBeEmpty();
});

test('generate sorts each horizon by the biggest point loss first', function () {
    $workbook = Workbook::factory()->create();
    $assessment = Assessment::factory()->create();

    $smallLoss = makePriorityCriterion($workbook, 'kritican');
    answerWith($assessment, $smallLoss, 'Djelimično'); // gap 5

    $bigLoss = makePriorityCriterion($workbook, 'kritican');
    answerWith($assessment, $bigLoss, 'Ne'); // gap 10

    $plan = actionPlanService()->generate($assessment);

    expect($plan['0–14 dana']->pluck('criterion.id')->all())->toBe([$bigLoss->id, $smallLoss->id]);
    expect($plan['0–14 dana']->pluck('gap')->all())->toBe([10.0, 5.0]);
});

test('recommendation items carry the criterion text, workbook and area for context', function () {
    $workbook = Workbook::factory()->create();
    $assessment = Assessment::factory()->create();

    $criterion = makePriorityCriterion($workbook, 'kritican');
    answerWith($assessment, $criterion, 'Ne');

    $item = actionPlanService()
        ->generate($assessment)['0–14 dana']
        ->first();

    expect($item['criterion']->id)->toBe($criterion->id);
    expect($item['workbook']->id)->toBe($workbook->id);
    expect($item['area']->id)->toBe($workbook->area_id);
});

test('topStrengths returns only fully-earned kritican/vazan criteria', function () {
    $workbook = Workbook::factory()->create();
    $assessment = Assessment::factory()->create();

    $strength = makePriorityCriterion($workbook, 'kritican');
    answerWith($assessment, $strength, 'Da');

    $weak = makePriorityCriterion($workbook, 'kritican');
    answerWith($assessment, $weak, 'Ne');

    $preporucenStrength = makePriorityCriterion($workbook, 'preporucen');
    answerWith($assessment, $preporucenStrength, 'Da');

    $strengths = actionPlanService()->topStrengths($assessment);

    expect($strengths->pluck('criterion.id')->all())->toBe([$strength->id]);
});
