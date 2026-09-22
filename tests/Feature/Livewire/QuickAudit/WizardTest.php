<?php

use App\Livewire\QuickAudit\Wizard;
use App\Models\Assessment;
use App\Models\Company;
use App\Models\Criterion;
use App\Models\User;
use Database\Seeders\RdsMethodologySeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RdsMethodologySeeder::class);
});

function bestOptionId(Criterion $criterion): int
{
    return $criterion->options()->reorder('points', 'desc')->firstOrFail()->id;
}

test('the intro step requires company name and all 3 channel answers before proceeding', function () {
    Livewire::test(Wizard::class)
        ->set('companyName', '')
        ->call('startQuestions')
        ->assertHasErrors(['companyName', 'channelRelevance.web', 'channelRelevance.gbp', 'channelRelevance.social'])
        ->assertSet('step', 'intro');
});

test('marking a channel not_relevant excludes its criteria from the questions the visitor has to answer', function () {
    $component = Livewire::test(Wizard::class)
        ->set('companyName', 'Test d.o.o.')
        ->set('channelRelevance.web', 'not_relevant')
        ->set('channelRelevance.gbp', 'not_relevant')
        ->set('channelRelevance.social', 'not_relevant')
        ->call('startQuestions')
        ->assertSet('step', 'questions');

    // With all 3 channels excluded, only the 6 channel=null criteria remain answerable.
    expect($component->instance()->answerableCriteria)->toHaveCount(6);
});

test('finishing creates a company, a quick_scan assessment, and answers, then redirects to the results page', function () {
    $component = Livewire::test(Wizard::class)
        ->set('companyName', 'Pekara Zlatno Zrno')
        ->set('website', 'https://example.com')
        ->set('channelRelevance.web', 'has')
        ->set('channelRelevance.gbp', 'has')
        ->set('channelRelevance.social', 'has')
        ->call('startQuestions')
        ->assertSet('step', 'questions');

    $answerable = $component->instance()->answerableCriteria;
    expect($answerable)->toHaveCount(25); // nothing excluded, every channel marked "has"

    foreach ($answerable as $criterion) {
        $component->set("answers.{$criterion->id}", bestOptionId($criterion));
    }

    $component->call('finish');

    $company = Company::where('name', 'Pekara Zlatno Zrno')->firstOrFail();
    expect($company->website)->toBe('https://example.com');

    $assessment = $company->assessments()->firstOrFail();
    expect($assessment->mode)->toBe('quick_scan')
        ->and($assessment->status)->toBe('completed')
        ->and($assessment->public_token)->not->toBeNull()
        ->and($assessment->answers()->count())->toBe(25)
        ->and($assessment->created_by)->toBe(User::quickAuditSystemUser()->id);

    $component->assertRedirect(route('quick-audit.results', $assessment));
});

test('finish refuses to proceed while an applicable question is still unanswered', function () {
    $component = Livewire::test(Wizard::class)
        ->set('companyName', 'Test d.o.o.')
        ->set('channelRelevance.web', 'has')
        ->set('channelRelevance.gbp', 'has')
        ->set('channelRelevance.social', 'has')
        ->call('startQuestions');

    $component->call('finish')->assertHasErrors('answers');

    expect(Assessment::count())->toBe(0);
});

test('a filled honeypot field silently drops the submission without creating any records', function () {
    $component = Livewire::test(Wizard::class)
        ->set('companyName', 'Bot Inc')
        ->set('channelRelevance.web', 'not_relevant')
        ->set('channelRelevance.gbp', 'not_relevant')
        ->set('channelRelevance.social', 'not_relevant')
        ->call('startQuestions');

    foreach ($component->instance()->answerableCriteria as $criterion) {
        $component->set("answers.{$criterion->id}", bestOptionId($criterion));
    }

    $component->set('companyPhone', '555-not-a-real-human')
        ->call('finish');

    expect(Company::count())->toBe(0)
        ->and(Assessment::count())->toBe(0);
});
