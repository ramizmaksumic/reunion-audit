<?php

use App\Livewire\Audits\RunAudit;
use App\Models\Assessment;
use App\Models\Criterion;
use App\Models\CriterionOption;
use App\Models\User;
use App\Models\Workbook;
use App\Services\ScoringService;
use Livewire\Livewire;

function makeGroupOfCriteria(Workbook $workbook, string $groupLabel, int $siblingCount = 2): array
{
    $gate = Criterion::factory()->for($workbook)->relevanceGate()->create(['group_label' => $groupLabel]);
    $gateYes = CriterionOption::factory()->for($gate)->create(['label' => 'Da', 'points' => 4.17]);
    $gateNo = CriterionOption::factory()->for($gate)->create(['label' => 'Ne', 'points' => 0]);

    $siblings = [];

    for ($i = 0; $i < $siblingCount; $i++) {
        $sibling = Criterion::factory()->for($workbook)->create(['group_label' => $groupLabel]);
        CriterionOption::factory()->for($sibling)->create(['label' => 'Da', 'points' => 4.17]);
        CriterionOption::factory()->for($sibling)->create(['label' => 'Ne', 'points' => 0]);
        $siblings[] = $sibling;
    }

    return [$gate, $gateYes, $gateNo, $siblings];
}

test('mount pre-populates every criterion key, even on a brand new assessment with zero answers', function () {
    $this->actingAs(User::factory()->create());

    $workbook = Workbook::factory()->create();
    $criterion = Criterion::factory()->for($workbook)->create(['group_label' => 'Grupa']);
    CriterionOption::factory()->for($criterion)->create(['label' => 'Da', 'points' => 10]);
    CriterionOption::factory()->for($criterion)->create(['label' => 'Ne', 'points' => 0]);

    // A completely fresh assessment: no assessment_answers rows exist at all.
    $assessment = Assessment::factory()->create(['status' => 'draft']);

    $component = Livewire::test(RunAudit::class, ['assessment' => $assessment]);

    // Every criterion's key must exist up front (as null/false/''), not just
    // answered ones — otherwise the very first click on a never-touched
    // criterion sends Livewire a bare property update with a null $key,
    // which crashed updatedSelectedOptions() with a TypeError in production.
    expect($component->get('selectedOptions'))->toHaveKey((string) $criterion->id);
    expect($component->get('selectedOptions')[$criterion->id])->toBeNull();
    expect($component->get('naFlags')[$criterion->id])->toBeFalse();
});

test('the updated hooks tolerate a bare (non-nested) property update instead of crashing', function () {
    $this->actingAs(User::factory()->create());

    $assessment = Assessment::factory()->create(['status' => 'draft']);

    // Replacing the whole array property (path "selectedOptions", no dotted
    // key) is exactly what a real browser click sent when a criterion's key
    // wasn't pre-populated — it reaches updatedSelectedOptions() with a null
    // $key and previously crashed with a TypeError.
    Livewire::test(RunAudit::class, ['assessment' => $assessment])
        ->set('selectedOptions', ['1' => 5])
        ->assertOk();

    $assessment->refresh();
    expect($assessment->status)->toBe('draft'); // nothing persisted, nothing crashed
});

test('answering a criterion auto-saves and flips the assessment to in_progress', function () {
    $this->actingAs(User::factory()->create());

    $workbook = Workbook::factory()->create();
    $criterion = Criterion::factory()->for($workbook)->create(['group_label' => 'Grupa']);
    $optionYes = CriterionOption::factory()->for($criterion)->create(['label' => 'Da', 'points' => 10]);
    CriterionOption::factory()->for($criterion)->create(['label' => 'Ne', 'points' => 0]);

    $assessment = Assessment::factory()->create(['status' => 'draft', 'started_at' => null]);

    Livewire::test(RunAudit::class, ['assessment' => $assessment])
        ->set("selectedOptions.{$criterion->id}", $optionYes->id)
        ->assertSet("selectedOptions.{$criterion->id}", $optionYes->id);

    $assessment->refresh();
    expect($assessment->status)->toBe('in_progress');
    expect($assessment->started_at)->not->toBeNull();

    $answer = $assessment->answers()->where('criterion_id', $criterion->id)->first();
    expect($answer->selected_option_id)->toBe($optionYes->id);
    expect($answer->is_na)->toBeFalse();
});

test('a negative relevance-gate answer auto-marks sibling criteria as N/A', function () {
    $this->actingAs(User::factory()->create());

    $workbook = Workbook::factory()->create();
    [$gate, , $gateNo, $siblings] = makeGroupOfCriteria($workbook, 'Google Business profil');

    $assessment = Assessment::factory()->create(['status' => 'draft']);

    Livewire::test(RunAudit::class, ['assessment' => $assessment])
        ->set("selectedOptions.{$gate->id}", $gateNo->id);

    foreach ($siblings as $sibling) {
        $answer = $assessment->answers()->where('criterion_id', $sibling->id)->first();
        expect($answer->is_na)->toBeTrue();
        expect($answer->na_reason)->not->toBeNull();
        expect($answer->selected_option_id)->toBeNull();
    }
});

test('flipping the gate back to positive restores untouched auto-N/A siblings but preserves manual overrides', function () {
    $this->actingAs(User::factory()->create());

    $workbook = Workbook::factory()->create();
    [$gate, $gateYes, $gateNo, $siblings] = makeGroupOfCriteria($workbook, 'SEO', siblingCount: 2);
    [$untouchedSibling, $overriddenSibling] = $siblings;

    $assessment = Assessment::factory()->create(['status' => 'draft']);

    $component = Livewire::test(RunAudit::class, ['assessment' => $assessment])
        ->set("selectedOptions.{$gate->id}", $gateNo->id);

    // Auditor manually overrides one of the auto-N/A siblings with a real answer.
    $overriddenOption = $overriddenSibling->options()->where('label', 'Da')->first();
    $component->set("selectedOptions.{$overriddenSibling->id}", $overriddenOption->id);

    // Auditor changes their mind: the channel *is* relevant after all.
    $component->set("selectedOptions.{$gate->id}", $gateYes->id);

    // Restored to genuinely unanswered: the row is deleted outright, not left
    // behind as a blank one (which would otherwise still count as "answered"
    // in the progress indicator).
    $untouchedAnswer = $assessment->answers()->where('criterion_id', $untouchedSibling->id)->first();
    expect($untouchedAnswer)->toBeNull();

    $overriddenAnswer = $assessment->answers()->where('criterion_id', $overriddenSibling->id)->first();
    expect($overriddenAnswer->is_na)->toBeFalse();
    expect($overriddenAnswer->selected_option_id)->toBe($overriddenOption->id);
});

test('the progress counter does not over-count criteria restored by a gate flip-back', function () {
    $this->actingAs(User::factory()->create());

    $workbook = Workbook::factory()->create();
    [$gate, $gateYes, $gateNo, $siblings] = makeGroupOfCriteria($workbook, 'SEO', siblingCount: 2);

    $assessment = Assessment::factory()->create(['status' => 'draft']);

    $component = Livewire::test(RunAudit::class, ['assessment' => $assessment])
        ->set("selectedOptions.{$gate->id}", $gateNo->id);

    // Gate + 2 auto-N/A siblings = all 3 criteria in this group "answered".
    expect($component->get('workbookProgress')[$workbook->id]['answered'])->toBe(3);

    // Auditor changes their mind: only the gate itself remains answered.
    $component->set("selectedOptions.{$gate->id}", $gateYes->id);

    expect($component->get('workbookProgress')[$workbook->id]['answered'])->toBe(1);
});

test('unchecking N/A deletes the blank row instead of leaving it counted as answered', function () {
    $this->actingAs(User::factory()->create());

    $workbook = Workbook::factory()->create();
    $criterion = Criterion::factory()->for($workbook)->create(['group_label' => 'Grupa']);
    CriterionOption::factory()->for($criterion)->create(['label' => 'Da', 'points' => 10]);
    CriterionOption::factory()->for($criterion)->create(['label' => 'Ne', 'points' => 0]);

    $assessment = Assessment::factory()->create(['status' => 'draft']);

    $component = Livewire::test(RunAudit::class, ['assessment' => $assessment])
        ->set("naFlags.{$criterion->id}", true);

    expect($assessment->answers()->where('criterion_id', $criterion->id)->exists())->toBeTrue();

    $component->set("naFlags.{$criterion->id}", false);

    expect($assessment->answers()->where('criterion_id', $criterion->id)->exists())->toBeFalse();
});

test('finishing the audit marks it completed and computes the overall score', function () {
    $this->actingAs(User::factory()->create());

    $workbook = Workbook::factory()->create(['weight' => 1]);
    $criterion = Criterion::factory()->for($workbook)->create(['group_label' => 'Grupa']);
    $optionYes = CriterionOption::factory()->for($criterion)->create(['label' => 'Da', 'points' => 10]);
    CriterionOption::factory()->for($criterion)->create(['label' => 'Ne', 'points' => 0]);

    $assessment = Assessment::factory()->create(['status' => 'draft', 'completed_at' => null]);

    Livewire::test(RunAudit::class, ['assessment' => $assessment])
        ->set("selectedOptions.{$criterion->id}", $optionYes->id)
        ->call('finishAudit')
        ->assertRedirect(route('audits.results', $assessment));

    $assessment->refresh();
    expect($assessment->status)->toBe('completed');
    expect($assessment->completed_at)->not->toBeNull();

    expect(app(ScoringService::class)->overallScore($assessment))->toBeFloat();
});
