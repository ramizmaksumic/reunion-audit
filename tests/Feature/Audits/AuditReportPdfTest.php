<?php

use App\Models\Assessment;
use App\Models\Criterion;
use App\Models\CriterionOption;
use App\Models\User;
use App\Models\Workbook;

test('the PDF export requires authentication', function () {
    $assessment = Assessment::factory()->create();

    $this->get(route('audits.results.pdf', $assessment))->assertRedirect(route('login'));
});

test('the PDF export downloads a report for the assessment', function () {
    $this->actingAs(User::factory()->create());

    $workbook = Workbook::factory()->create(['weight' => 1]);
    $criterion = Criterion::factory()->for($workbook)->create(['group_label' => 'Grupa', 'priority' => 'kritican']);
    CriterionOption::factory()->for($criterion)->create(['label' => 'Da', 'points' => 10]);
    CriterionOption::factory()->for($criterion)->create(['label' => 'Ne', 'points' => 0]);

    $assessment = Assessment::factory()->create(['status' => 'completed']);
    $assessment->answers()->create([
        'criterion_id' => $criterion->id,
        'selected_option_id' => $criterion->options()->where('label', 'Ne')->first()->id,
    ]);

    $response = $this->get(route('audits.results.pdf', $assessment));

    $response->assertOk();
    $response->assertHeader('content-type', 'application/pdf');
    expect($response->headers->get('content-disposition'))->toContain('.pdf');
    expect(strlen($response->getContent()))->toBeGreaterThan(1000);
});
