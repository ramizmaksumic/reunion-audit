<?php

use App\Livewire\Audits\CreateAudit;
use App\Models\Company;
use App\Models\User;
use Livewire\Livewire;

test('creating a new company starts a draft full-audit assessment', function () {
    $this->actingAs(User::factory()->create());

    $component = Livewire::test(CreateAudit::class)
        ->set('name', 'Test Kompanija d.o.o.')
        ->set('industry', 'Ugostiteljstvo')
        ->set('b2bOrB2c', 'b2c')
        ->set('marketScope', 'local')
        ->set('sellsOnline', true)
        ->set('channelRelevance.web_stranica', 'critical')
        ->call('proceedToConfirm')
        ->assertSet('step', 'confirm')
        ->call('startAudit');

    $company = Company::where('name', 'Test Kompanija d.o.o.')->firstOrFail();

    expect($company->industry)->toBe('Ugostiteljstvo');
    expect($company->sells_online)->toBeTrue();
    expect($company->channelRelevances()->where('channel_key', 'web_stranica')->first()->relevance)->toBe('critical');

    $assessment = $company->assessments()->firstOrFail();
    expect($assessment->mode)->toBe('full_audit');
    expect($assessment->status)->toBe('draft');

    $component->assertRedirect(route('audits.run', $assessment));
});

test('selecting an existing company preloads its profile into the form', function () {
    $this->actingAs(User::factory()->create());

    $company = Company::factory()->create(['name' => 'Postojeća Firma', 'industry' => 'IT']);

    Livewire::test(CreateAudit::class)
        ->call('usingExistingCompany', $company->id)
        ->assertSet('name', 'Postojeća Firma')
        ->assertSet('industry', 'IT')
        ->assertSet('companyId', $company->id);
});

test('the company name is required to proceed', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(CreateAudit::class)
        ->set('name', '')
        ->call('proceedToConfirm')
        ->assertHasErrors(['name' => 'required'])
        ->assertSet('step', 'company');
});
