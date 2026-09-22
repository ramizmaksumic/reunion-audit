<?php

use App\Models\Area;
use App\Models\Assessment;
use App\Models\Company;
use App\Models\Criterion;
use App\Models\Workbook;
use App\Services\ScoringService;
use Database\Seeders\RdsMethodologySeeder;

/**
 * These tests run against the real seeded methodology (16 workbooks, 456
 * criteria, including the newly added drustvene_mreze/ai_vidljivost) rather
 * than synthetic factory data, to prove two things end to end:
 *
 * 1. The new ai_vidljivost workbook — which mixes binary, graded,
 *    audit_opinion and threshold criteria — scores correctly through the
 *    existing generic scoring engine, no special-casing needed.
 * 2. A pre-existing (methodology_version v2.0) assessment's score is not
 *    silently changed by the new workbooks being added to the database.
 */
beforeEach(function () {
    $this->seed(RdsMethodologySeeder::class);
});

function answerWithBestOption(Assessment $assessment, Criterion $criterion): void
{
    // options() applies its own orderBy('sort_order'); reorder() clears that
    // first so this actually finds the max-points option regardless of
    // which position it's listed in (some criteria list the higher-point
    // option second, e.g. WEB-030 "Da"=0pts, "Ne"=1.52pts).
    $best = $criterion->options()->reorder('points', 'desc')->firstOrFail();

    $assessment->answers()->create([
        'criterion_id' => $criterion->id,
        'selected_option_id' => $best->id,
    ]);
}

test('the new ai_vidljivost workbook scores 100 when every criterion (binary/graded/audit_opinion/threshold) is answered with its best option', function () {
    $workbook = Workbook::where('key', 'ai_vidljivost')->firstOrFail();
    $assessment = Assessment::factory()->create();

    expect($workbook->criteria)->toHaveCount(12);
    expect($workbook->criteria->pluck('answer_type')->unique()->sort()->values()->all())
        ->toBe(['audit_opinion', 'binary', 'graded', 'threshold']);

    foreach ($workbook->criteria as $criterion) {
        answerWithBestOption($assessment, $criterion);
    }

    expect((new ScoringService)->workbookScore($assessment, $workbook))->toBe(100.0);
});

test('an assessment created under methodology v2.0 is unaffected by the new drustvene_mreze/ai_vidljivost workbooks', function () {
    $company = Company::factory()->create();
    $assessment = Assessment::factory()->for($company)->create(['methodology_version' => 'v2.0']);

    // Answer every criterion of every workbook that predates v2.1 (i.e. all
    // 14 original workbooks) with its best option. Leave the two new
    // workbooks completely untouched, as a real v2.0 audit would have.
    $originalWorkbooks = Workbook::whereNull('introduced_in_version')->get();
    expect($originalWorkbooks)->toHaveCount(14);

    foreach ($originalWorkbooks as $workbook) {
        foreach ($workbook->criteria as $criterion) {
            answerWithBestOption($assessment, $criterion);
        }
    }

    $scoring = new ScoringService;

    // If the new workbooks silently counted as 0% for this assessment, the
    // "digitalna_prisutnost" area score (and thus the overall score) would
    // drop well below 100 despite every applicable criterion being fully
    // answered.
    $digitalnaPrisutnost = Area::where('key', 'digitalna_prisutnost')->firstOrFail();
    expect($scoring->areaScore($assessment, $digitalnaPrisutnost))->toBe(100.0);
    expect($scoring->overallScore($assessment))->toBe(100.0);
});

test('isWorkbookApplicable excludes a newer workbook from an older assessment unless it was actually answered', function () {
    $scoring = new ScoringService;

    $oldAssessment = Assessment::factory()->create(['methodology_version' => 'v2.0']);
    $newAssessment = Assessment::factory()->create(['methodology_version' => 'v2.1']);

    $originalWorkbook = Workbook::whereNull('introduced_in_version')->firstOrFail();
    $newWorkbook = Workbook::where('key', 'ai_vidljivost')->firstOrFail();

    expect($scoring->isWorkbookApplicable($oldAssessment, $originalWorkbook))->toBeTrue();
    expect($scoring->isWorkbookApplicable($oldAssessment, $newWorkbook))->toBeFalse();
    expect($scoring->isWorkbookApplicable($newAssessment, $newWorkbook))->toBeTrue();

    // An old assessment whose auditor went ahead and answered a criterion in
    // the new workbook anyway must still have it counted.
    answerWithBestOption($oldAssessment, $newWorkbook->criteria->first());
    expect($scoring->isWorkbookApplicable($oldAssessment->fresh(), $newWorkbook))->toBeTrue();
});
