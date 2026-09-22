<?php

namespace App\Livewire\Audits;

use App\Models\Assessment;
use App\Models\Company;
use App\Models\CompanyChannelRelevance;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

/**
 * @property-read Collection<int, Company> $companies
 */
#[Title('Novi audit')]
class CreateAudit extends Component
{
    public string $step = 'company';

    public ?int $companyId = null;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('nullable|string|max:255')]
    public string $industry = '';

    #[Validate('nullable|string')]
    public string $businessModelNotes = '';

    #[Validate('nullable|in:b2b,b2c,both')]
    public string $b2bOrB2c = '';

    #[Validate('nullable|in:local,regional,national,international')]
    public string $marketScope = '';

    public bool $hasPhysicalLocation = false;

    public bool $sellsOnline = false;

    public bool $providesOnlineServices = false;

    public bool $worksByAppointment = false;

    public bool $hasMultipleLocations = false;

    /**
     * channel_key => relevance|''
     *
     * @var array<string, string>
     */
    public array $channelRelevance = [];

    public function mount(): void
    {
        foreach (CompanyChannelRelevance::CHANNELS as $key => $label) {
            $this->channelRelevance[$key] = '';
        }
    }

    /**
     * @return Collection<int, Company>
     */
    #[Computed]
    public function companies(): Collection
    {
        return Company::query()->orderBy('name')->get();
    }

    public function usingExistingCompany(int $companyId): void
    {
        $company = Company::findOrFail($companyId);

        $this->companyId = $company->id;
        $this->name = $company->name;
        $this->industry = (string) $company->industry;
        $this->businessModelNotes = (string) $company->business_model_notes;
        $this->b2bOrB2c = (string) $company->b2b_or_b2c;
        $this->marketScope = (string) $company->market_scope;
        $this->hasPhysicalLocation = $company->has_physical_location;
        $this->sellsOnline = $company->sells_online;
        $this->providesOnlineServices = $company->provides_online_services;
        $this->worksByAppointment = $company->works_by_appointment;
        $this->hasMultipleLocations = $company->has_multiple_locations;

        $existing = $company->channelRelevances->pluck('relevance', 'channel_key');

        foreach (CompanyChannelRelevance::CHANNELS as $key => $label) {
            $this->channelRelevance[$key] = $existing[$key] ?? '';
        }
    }

    public function usingNewCompany(): void
    {
        $this->reset([
            'companyId', 'name', 'industry', 'businessModelNotes', 'b2bOrB2c', 'marketScope',
            'hasPhysicalLocation', 'sellsOnline', 'providesOnlineServices', 'worksByAppointment', 'hasMultipleLocations',
        ]);

        foreach (CompanyChannelRelevance::CHANNELS as $key => $label) {
            $this->channelRelevance[$key] = '';
        }
    }

    public function proceedToConfirm(): void
    {
        $this->validate();

        $this->step = 'confirm';
    }

    public function backToCompany(): void
    {
        $this->step = 'company';
    }

    public function startAudit(): void
    {
        $this->validate();

        $assessment = DB::transaction(function (): Assessment {
            $attributes = [
                'name' => $this->name,
                'industry' => $this->industry ?: null,
                'business_model_notes' => $this->businessModelNotes ?: null,
                'b2b_or_b2c' => $this->b2bOrB2c ?: null,
                'market_scope' => $this->marketScope ?: null,
                'has_physical_location' => $this->hasPhysicalLocation,
                'sells_online' => $this->sellsOnline,
                'provides_online_services' => $this->providesOnlineServices,
                'works_by_appointment' => $this->worksByAppointment,
                'has_multiple_locations' => $this->hasMultipleLocations,
            ];

            if ($this->companyId) {
                $company = Company::findOrFail($this->companyId);
                $company->fill($attributes)->save();
            } else {
                $company = Company::create($attributes);
            }

            foreach ($this->channelRelevance as $channelKey => $relevance) {
                if ($relevance === '') {
                    $company->channelRelevances()->where('channel_key', $channelKey)->delete();

                    continue;
                }

                $company->channelRelevances()->updateOrCreate(
                    ['channel_key' => $channelKey],
                    ['relevance' => $relevance],
                );
            }

            return $company->assessments()->create([
                'methodology_version' => 'v2.1',
                'mode' => 'full_audit',
                'status' => 'draft',
                'created_by' => Auth::id(),
            ]);
        });

        $this->redirect(route('audits.run', $assessment), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.audits.create-audit');
    }
}
