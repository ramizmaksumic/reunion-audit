<?php

use App\Livewire\Audits\RunAudit;
use App\Models\Assessment;
use App\Models\Criterion;
use App\Models\CriterionOption;
use App\Models\User;
use App\Models\Workbook;
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

    $untouchedAnswer = $assessment->answers()->where('criterion_id', $untouchedSibling->id)->first();
    expect($untouchedAnswer->is_na)->toBeFalse();
    expect($untouchedAnswer->na_reason)->toBeNull();
    expect($untouchedAnswer->selected_option_id)->toBeNull();

    $overriddenAnswer = $assessment->answers()->where('criterion_id', $overriddenSibling->id)->first();
    expect($overriddenAnswer->is_na)->toBeFalse();
    expect($overriddenAnswer->selected_option_id)->toBe($overriddenOption->id);
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
        ->assertSet('overallScore', fn ($score) => $score !== null);

    $assessment->refresh();
    expect($assessment->status)->toBe('completed');
    expect($assessment->completed_at)->not->toBeNull();
});
