<?php

namespace App\Livewire\QuickAudit;

use App\Models\Assessment;
use App\Services\QuickAuditScoringService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

/**
 * @property-read array{score: ?float, confidence: float, is_approximate: bool, scored_block_count: int} $result
 * @property-read Collection<string, float> $blockScores
 */
#[Title('Rezultati brzog audita')]
#[Layout('layouts.public')]
class Results extends Component
{
    public Assessment $assessment;

    public bool $contactSubmitted = false;

    #[Validate('required|string|max:255')]
    public string $contactName = '';

    #[Validate('required|email|max:255')]
    public string $contactEmail = '';

    #[Validate('nullable|string|max:50')]
    public string $contactPhone = '';

    #[Validate('nullable|string|max:1000')]
    public string $contactMessage = '';

    // Honeypot — see Wizard::$companyPhone for the same pattern.
    public string $website = '';

    public function mount(Assessment $assessment): void
    {
        abort_unless($assessment->mode === 'quick_scan', 404);

        $this->assessment = $assessment->load('company');
        $this->contactSubmitted = $assessment->contact_requested_at !== null;

        if ($this->contactSubmitted) {
            $this->contactName = (string) $assessment->company->contact_name;
            $this->contactEmail = (string) $assessment->company->contact_email;
            $this->contactPhone = (string) $assessment->company->contact_phone;
        }
    }

    /**
     * @return array{score: ?float, confidence: float, is_approximate: bool, scored_block_count: int}
     */
    #[Computed]
    public function result(): array
    {
        return app(QuickAuditScoringService::class)->overallScore($this->assessment);
    }

    /**
     * @return Collection<string, float>
     */
    #[Computed]
    public function blockScores(): Collection
    {
        return app(QuickAuditScoringService::class)->blockScores($this->assessment);
    }

    #[Computed]
    public function blocks(): array
    {
        return QuickAuditScoringService::blocks();
    }

    public function submitContactRequest(): void
    {
        $this->validate();

        if ($this->website !== '') {
            // Honeypot tripped — silently no-op, same as Wizard::finish().
            $this->contactSubmitted = true;

            return;
        }

        $this->assessment->company->update([
            'contact_name' => $this->contactName,
            'contact_email' => $this->contactEmail,
            'contact_phone' => $this->contactPhone ?: null,
        ]);

        $this->assessment->update([
            'lead_message' => $this->contactMessage ?: null,
            'contact_requested_at' => now(),
        ]);

        $this->contactSubmitted = true;
    }

    public function render(): View
    {
        return view('livewire.quick-audit.results');
    }
}
