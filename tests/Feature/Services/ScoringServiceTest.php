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
