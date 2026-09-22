<?php

use App\Livewire\QuickAudit\Results;
use App\Models\Assessment;
use App\Models\Company;
use App\Models\Criterion;
use App\Models\User;
use App\Notifications\QuickAuditContactRequested;
use Database\Seeders\RdsMethodologySeeder;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RdsMethodologySeeder::class);
});

function makeQuickScanAssessment(array $channelRelevance = ['web' => 'has', 'gbp' => 'has', 'social' => 'has']): Assessment
{
    $company = Company::create(['name' => 'Test kompanija d.o.o.']);

    $assessment = $company->assessments()->create([
        'methodology_version' => 'v2.1',
        'mode' => 'quick_scan',
        'quick_channel_relevance' => $channelRelevance,
        'status' => 'completed',
        'started_at' => now(),
        'completed_at' => now(),
        'created_by' => User::quickAuditSystemUser()->id,
    ]);

    foreach (Criterion::where('quick_audit', true)->get() as $criterion) {
        if ($criterion->channel !== null && ($channelRelevance[$criterion->channel] ?? 'has') !== 'has') {
            continue;
        }

        $best = $criterion->options()->reorder('points', 'desc')->firstOrFail();
        $assessment->answers()->create(['criterion_id' => $criterion->id, 'selected_option_id' => $best->id]);
    }

    return $assessment;
}

test('shows the computed score and block breakdown for a quick-scan assessment', function () {
    $assessment = makeQuickScanAssessment();

    Livewire::test(Results::class, ['assessment' => $assessment])
        ->assertOk()
        ->assertSee('Test kompanija d.o.o.')
        ->assertSee('100');
});

test('it 404s for an assessment that is not a quick scan', function () {
    $assessment = Assessment::factory()->create(['mode' => 'full_audit']);

    Livewire::test(Results::class, ['assessment' => $assessment])
        ->assertStatus(404);
});

test('the public results URL is bound by public_token, not id', function () {
    $assessment = makeQuickScanAssessment();

    $this->get(route('quick-audit.results', $assessment))->assertOk();
    $this->get('/brzi-audit/rezultati/'.$assessment->id)->assertNotFound();
});

test('submitting the contact form saves contact details on the company and marks the assessment as followed up', function () {
    $assessment = makeQuickScanAssessment();

    Livewire::test(Results::class, ['assessment' => $assessment])
        ->set('contactName', 'Ramiz')
        ->set('contactEmail', 'ramiz@example.com')
        ->set('contactPhone', '060000000')
        ->set('contactMessage', 'Zanima me puni audit.')
        ->call('submitContactRequest')
        ->assertSet('contactSubmitted', true);

    $company = $assessment->company()->firstOrFail();
    expect($company->contact_name)->toBe('Ramiz')
        ->and($company->contact_email)->toBe('ramiz@example.com');

    expect($assessment->fresh()->contact_requested_at)->not->toBeNull()
        ->and($assessment->fresh()->lead_message)->toBe('Zanima me puni audit.');
});

test('submitting the contact form notifies every real staff account, but never the quick-audit system user', function () {
    Notification::fake();

    $staff = User::factory()->create();
    $assessment = makeQuickScanAssessment();

    Livewire::test(Results::class, ['assessment' => $assessment])
        ->set('contactName', 'Ramiz')
        ->set('contactEmail', 'ramiz@example.com')
        ->call('submitContactRequest');

    Notification::assertSentTo($staff, QuickAuditContactRequested::class);
    Notification::assertNotSentTo(User::quickAuditSystemUser(), QuickAuditContactRequested::class);
});

test('a filled honeypot on the contact form sends no notification either', function () {
    Notification::fake();

    User::factory()->create();
    $assessment = makeQuickScanAssessment();

    Livewire::test(Results::class, ['assessment' => $assessment])
        ->set('contactName', 'Bot')
        ->set('contactEmail', 'bot@example.com')
        ->set('website', 'https://spam.example')
        ->call('submitContactRequest');

    Notification::assertNothingSent();
});

test('a filled honeypot on the contact form no-ops without saving contact details', function () {
    $assessment = makeQuickScanAssessment();

    Livewire::test(Results::class, ['assessment' => $assessment])
        ->set('contactName', 'Bot')
        ->set('contactEmail', 'bot@example.com')
        ->set('website', 'https://spam.example')
        ->call('submitContactRequest');

    expect($assessment->company()->firstOrFail()->contact_name)->toBeNull()
        ->and($assessment->fresh()->contact_requested_at)->toBeNull();
});
