<?php

namespace App\Livewire\QuickAudit;

use App\Models\Assessment;
use App\Models\User;
use App\Notifications\QuickAuditContactRequested;
use App\Services\QuickAuditScoringService;
use App\Services\ScoringService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
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

    /**
     * Reuses the full audit's 5-tier status language (same
     * ScoringService::scoreStatus() used on the main results page) so the
     * two results pages read as one consistent product, not two different
     * scoring vocabularies.
     *
     * @return array{label: string, description: string, color: string}
     */
    #[Computed]
    public function status(): array
    {
        $score = $this->result['score'];

        if ($score === null) {
            return [
                'label' => 'Nedovoljno podataka',
                'description' => 'Odgovori nisu bili dovoljni da se izračuna okvirna ocjena.',
                'color' => '#71717a',
            ];
        }

        return app(ScoringService::class)->scoreStatus($score);
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

        // The lead's own data is already saved above regardless of what
        // happens here — a mail server hiccup must never turn into a 500
        // for the visitor. Staff can still see the lead in Filament.
        try {
            Notification::send(User::staff(), new QuickAuditContactRequested($this->assessment));
        } catch (\Throwable $e) {
            Log::error('Failed to send QuickAuditContactRequested notification', [
                'assessment_id' => $this->assessment->id,
                'exception' => $e->getMessage(),
            ]);
        }

        $this->contactSubmitted = true;
    }

    public function render(): View
    {
        return view('livewire.quick-audit.results');
    }
}
