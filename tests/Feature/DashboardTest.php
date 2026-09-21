<?php

use App\Models\Assessment;
use App\Models\Company;
use App\Models\Criterion;
use App\Models\CriterionOption;
use App\Models\User;
use App\Models\Workbook;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('the dashboard shows agency stats and recent audits instead of empty placeholders', function () {
    $this->actingAs(User::factory()->create());

    $workbook = Workbook::factory()->create(['weight' => 1]);
    $criterion = Criterion::factory()->for($workbook)->create(['group_label' => 'Grupa']);
    CriterionOption::factory()->for($criterion)->create(['label' => 'Da', 'points' => 10]);
    CriterionOption::factory()->for($criterion)->create(['label' => 'Ne', 'points' => 0]);

    $company = Company::factory()->create(['name' => 'Pekara Zlatno Zrno']);
    $assessment = Assessment::factory()->for($company)->create(['status' => 'completed']);
    $assessment->answers()->create([
        'criterion_id' => $criterion->id,
        'selected_option_id' => $criterion->options()->where('label', 'Da')->first()->id,
    ]);

    $response = $this->get(route('dashboard'));

    $response->assertOk();
    $response->assertSee('Pekara Zlatno Zrno');
    $response->assertSee('Završen');
    $response->assertDontSee('placeholder', escape: false);
});

test('the dashboard shows an empty state with a call to action when there are no audits yet', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Još nema nijednog audita.')
        ->assertSee('Kreiraj prvi audit');
});
