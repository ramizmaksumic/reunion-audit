<?php

use App\Models\Area;
use App\Models\Assessment;
use App\Models\Criterion;
use App\Models\CriterionOption;
use App\Models\Workbook;
use App\Services\ScoringService;

function makeBinaryCriterion(Workbook $workbook, string $groupLabel, bool $isGate = false): Criterion
{
    $criterion = Criterion::factory()
        ->for($workbook)
        ->state(['group_label' => $groupLabel, 'is_relevance_gate' => $isGate])
        ->create();

    CriterionOption::factory()->for($criterion)->create(['label' => 'Da', 'points' => 10]);
    CriterionOption::factory()->for($criterion)->create(['label' => 'Ne', 'points' => 0]);

    return $criterion;
}

function answerCriterion(Assessment $assessment, Criterion $criterion, string $label): void
{
    $option = $criterion->options()->where('label', $label)->firstOrFail();

    $assessment->answers()->create([
        'criterion_id' => $criterion->id,
        'selected_option_id' => $option->id,
    ]);
}

test('workbookScore averages fully answered criteria to 100', function () {
    $workbook = Workbook::factory()->create();
    $assessment = Assessment::factory()->create();

    $c1 = makeBinaryCriterion($workbook, 'I. Grupa');
    $c2 = makeBinaryCriterion($workbook, 'I. Grupa');
    $c3 = makeBinaryCriterion($workbook, 'I. Grupa');
    $c4 = makeBinaryCriterion($workbook, 'I. Grupa');

    answerCriterion($assessment, $c1, 'Da');
    answerCriterion($assessment, $c2, 'Da');
    answerCriterion($assessment, $c3, 'Da');
    answerCriterion($assessment, $c4, 'Da');

    expect((new ScoringService)->workbookScore($assessment, $workbook))->toBe(100.0);
});

test('workbookScore reflects a mix of correct and incorrect answers', function () {
    $workbook = Workbook::factory()->create();
    $assessment = Assessment::factory()->create();

    $c1 = makeBinaryCriterion($workbook, 'I. Grupa');
    $c2 = makeBinaryCriterion($workbook, 'I. Grupa');
    $c3 = makeBinaryCriterion($workbook, 'I. Grupa');
    $c4 = makeBinaryCriterion($workbook, 'I. Grupa');

    answerCriterion($assessment, $c1, 'Da');
    answerCriterion($assessment, $c2, 'Da');
    answerCriterion($assessment, $c3, 'Da');
    answerCriterion($assessment, $c4, 'Ne');

    // 3 * 10 earned out of 4 * 10 applicable = 75%
    expect((new ScoringService)->workbookScore($assessment, $workbook))->toBe(75.0);
});

test('workbookScore normalizes when some criteria are marked N/A', function () {
    $workbook = Workbook::factory()->create();
    $assessment = Assessment::factory()->create();

    $c1 = makeBinaryCriterion($workbook, 'I. Grupa');
    $c2 = makeBinaryCriterion($workbook, 'I. Grupa');
    $c3 = makeBinaryCriterion($workbook, 'I. Grupa');
    $c4 = makeBinaryCriterion($workbook, 'I. Grupa');

    answerCriterion($assessment, $c1, 'Da');
    answerCriterion($assessment, $c2, 'Da');
    answerCriterion($assessment, $c3, 'Ne');

    $assessment->answers()->create([
        'criterion_id' => $c4->id,
        'selected_option_id' => null,
        'is_na' => true,
        'na_reason' => 'Nije primjenjivo na ovaj poslovni model.',
    ]);

    // c4 is excluded entirely: 2 * 10 earned out of 3 * 10 applicable ≈ 66.67%
    expect((new ScoringService)->workbookScore($assessment, $workbook))->toBe(66.67);
});

test('a negative relevance-gate answer excludes the rest of its group without tanking the score', function () {
    $workbook = Workbook::factory()->create();
    $assessment = Assessment::factory()->create();

    // Channel A: fully answered positively, including its own gate question.
    $a1 = makeBinaryCriterion($workbook, 'Channel A');
    $a2 = makeBinaryCriterion($workbook, 'Channel A');
    $aGate = makeBinaryCriterion($workbook, 'Channel A', isGate: true);

    answerCriterion($assessment, $a1, 'Da');
    answerCriterion($assessment, $a2, 'Da');
    answerCriterion($assessment, $aGate, 'Da');

    // Channel B: irrelevant to this company — gate answered "Ne", the other two
    // criteria in the group are left unanswered (as the audit wizard would skip them).
    $b1 = makeBinaryCriterion($workbook, 'Channel B');
    $b2 = makeBinaryCriterion($workbook, 'Channel B');
    $bGate = makeBinaryCriterion($workbook, 'Channel B', isGate: true);

    answerCriterion($assessment, $bGate, 'Ne');

    // Applicable: a1, a2, aGate, bGate = 4 criteria, all but bGate earn full points.
    // Earned 30 / applicable max 40 = 75%. b1 and b2 are gated out entirely.
    expect((new ScoringService)->workbookScore($assessment, $workbook))->toBe(75.0);
});

test('areaScore is a weighted average of its workbooks', function () {
    $area = Area::factory()->create();
    $assessment = Assessment::factory()->create();

    $heavy = Workbook::factory()->for($area)->create(['weight' => 3]);
    $light = Workbook::factory()->for($area)->create(['weight' => 1]);

    $h1 = makeBinaryCriterion($heavy, 'I. Grupa');
    $l1 = makeBinaryCriterion($light, 'I. Grupa');

    answerCriterion($assessment, $h1, 'Da'); // heavy workbook scores 100
    answerCriterion($assessment, $l1, 'Ne'); // light workbook scores 0

    // (100 * 3 + 0 * 1) / (3 + 1) = 75
    expect((new ScoringService)->areaScore($assessment, $area))->toBe(75.0);
});

test('overallScore averages all areas evenly', function () {
    $assessment = Assessment::factory()->create();

    $areaOne = Area::factory()->create();
    $workbookOne = Workbook::factory()->for($areaOne)->create(['weight' => 1]);
    $criterionOne = makeBinaryCriterion($workbookOne, 'I. Grupa');
    answerCriterion($assessment, $criterionOne, 'Da'); // area scores 100

    $areaTwo = Area::factory()->create();
    $workbookTwo = Workbook::factory()->for($areaTwo)->create(['weight' => 1]);
    $criterionTwo = makeBinaryCriterion($workbookTwo, 'I. Grupa');
    answerCriterion($assessment, $criterionTwo, 'Ne'); // area scores 0

    expect((new ScoringService)->overallScore($assessment))->toBe(50.0);
});

test('scoreStatus maps scores to the 5 methodology tiers', function () {
    $scoring = new ScoringService;

    expect($scoring->scoreStatus(0.0)['label'])->toBe('Kritično');
    expect($scoring->scoreStatus(19.9)['label'])->toBe('Kritično');
    expect($scoring->scoreStatus(20.0)['label'])->toBe('Reaktivno');
    expect($scoring->scoreStatus(39.9)['label'])->toBe('Reaktivno');
    expect($scoring->scoreStatus(40.0)['label'])->toBe('Funkcionalno');
    expect($scoring->scoreStatus(59.9)['label'])->toBe('Funkcionalno');
    expect($scoring->scoreStatus(60.0)['label'])->toBe('Upravljano');
    expect($scoring->scoreStatus(79.9)['label'])->toBe('Upravljano');
    expect($scoring->scoreStatus(80.0)['label'])->toBe('Napredno');
    expect($scoring->scoreStatus(100.0)['label'])->toBe('Napredno');
});

test('isWorkbookApplicable is true for a workbook with no introduced_in_version, regardless of assessment version', function () {
    $workbook = Workbook::factory()->create(['introduced_in_version' => null]);
    $assessment = Assessment::factory()->create(['methodology_version' => 'v2.0']);

    expect((new ScoringService)->isWorkbookApplicable($assessment, $workbook))->toBeTrue();
});

test('isWorkbookApplicable excludes a newer workbook from an older, unanswered assessment', function () {
    $workbook = Workbook::factory()->create(['introduced_in_version' => 'v2.1']);
    $assessment = Assessment::factory()->create(['methodology_version' => 'v2.0']);

    expect((new ScoringService)->isWorkbookApplicable($assessment, $workbook))->toBeFalse();
});

test('isWorkbookApplicable includes a newer workbook once the assessment reaches its version', function () {
    $workbook = Workbook::factory()->create(['introduced_in_version' => 'v2.1']);
    $assessment = Assessment::factory()->create(['methodology_version' => 'v2.1']);

    expect((new ScoringService)->isWorkbookApplicable($assessment, $workbook))->toBeTrue();
});

test('isWorkbookApplicable includes a newer workbook on an older assessment that was answered anyway', function () {
    $workbook = Workbook::factory()->create(['introduced_in_version' => 'v2.1']);
    $assessment = Assessment::factory()->create(['methodology_version' => 'v2.0']);
    $criterion = makeBinaryCriterion($workbook, 'I. Grupa');

    answerCriterion($assessment, $criterion, 'Da');

    expect((new ScoringService)->isWorkbookApplicable($assessment, $workbook))->toBeTrue();
});

test('areaScore excludes a not-yet-applicable workbook from the weighted average entirely, rather than scoring it 0', function () {
    $area = Area::factory()->create();
    $assessment = Assessment::factory()->create(['methodology_version' => 'v2.0']);

    $original = Workbook::factory()->for($area)->create(['weight' => 1, 'introduced_in_version' => null]);
    $criterion = makeBinaryCriterion($original, 'I. Grupa');
    answerCriterion($assessment, $criterion, 'Da'); // fully answered, scores 100

    // Added later, unanswered on this older assessment — must not drag the average down.
    Workbook::factory()->for($area)->create(['weight' => 1, 'introduced_in_version' => 'v2.1']);

    expect((new ScoringService)->areaScore($assessment, $area))->toBe(100.0);
});

test('criterionBreakdown reports per-criterion earned/max and applicability', function () {
    $workbook = Workbook::factory()->create();
    $assessment = Assessment::factory()->create();

    $answered = makeBinaryCriterion($workbook, 'I. Grupa');
    answerCriterion($assessment, $answered, 'Da');

    $unanswered = makeBinaryCriterion($workbook, 'I. Grupa');

    $na = makeBinaryCriterion($workbook, 'I. Grupa');
    $assessment->answers()->create(['criterion_id' => $na->id, 'is_na' => true]);

    $breakdown = (new ScoringService)->criterionBreakdown($assessment, $workbook)->keyBy('criterion.id');

    expect($breakdown[$answered->id]['applicable'])->toBeTrue();
    expect($breakdown[$answered->id]['earned'])->toBe(10.0);
    expect($breakdown[$answered->id]['max'])->toBe(10.0);

    expect($breakdown[$unanswered->id]['applicable'])->toBeTrue();
    expect($breakdown[$unanswered->id]['earned'])->toBe(0.0);

    expect($breakdown[$na->id]['applicable'])->toBeFalse();
});
