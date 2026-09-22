<?php

namespace App\Livewire\QuickAudit;

use App\Models\Assessment;
use App\Models\Company;
use App\Models\Criterion;
use App\Models\User;
use App\Services\QuickAuditScoringService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

/**
 * Public, unauthenticated quick-audit flow: a light intro (company name,
 * website, per-channel relevance) followed by the applicable subset of the
 * 25 quick_audit criteria, one at a time. Nothing touches the database
 * until finish() — an abandoned attempt never creates a row, which matters
 * since this is open to anyone on the internet.
 *
 * @property-read Collection<int, Criterion> $allCriteria
 * @property-read Collection<int, Criterion> $answerableCriteria
 */
#[Title('Brzi audit')]
#[Layout('layouts.public')]
class Wizard extends Component
{
    public string $step = 'intro';

    #[Validate('required|string|max:255')]
    public string $companyName = '';

    #[Validate('nullable|url|max:255')]
    public string $website = '';

    /** @var array<string, string> channel => has|critical|recommended|not_relevant */
    public array $channelRelevance = [
        'web' => '',
        'gbp' => '',
        'social' => '',
    ];

    /**
     * Honeypot: a real visitor never sees or fills this field (hidden off
     * screen in the view); a bot filling every field on the form will.
     */
    public string $companyPhone = '';

    /** @var array<int, int> criterion_id => selected_option_id */
    public array $answers = [];

    public int $currentIndex = 0;

    #[Computed]
    public function blocks(): array
    {
        return QuickAuditScoringService::blocks();
    }

    /**
     * All 25 quick_audit criteria, ordered by the methodology's block order.
     *
     * @return Collection<int, Criterion>
     */
    #[Computed]
    public function allCriteria(): Collection
    {
        $blockOrder = collect(QuickAuditScoringService::blocks())->pluck('key')->flip();

        return Criterion::query()->where('quick_audit', true)->with('options')->get()
            ->sortBy(fn (Criterion $c) => sprintf('%02d-%04d', $blockOrder[$c->quick_block] ?? 99, $c->sort_order))
            ->values();
    }

    /**
     * The subset of allCriteria this visitor actually needs to answer —
     * criteria whose channel is null, or whose channel they said they have.
     * The rest are already resolved (excluded/missing_zero) by their
     * channel relevance answer from the intro step.
     *
     * @return Collection<int, Criterion>
     */
    #[Computed]
    public function answerableCriteria(): Collection
    {
        return $this->allCriteria->filter(
            fn (Criterion $c) => $c->channel === null || ($this->channelRelevance[$c->channel] ?? '') === 'has'
        )->values();
    }

    #[Computed]
    public function currentCriterion(): ?Criterion
    {
        return $this->answerableCriteria->get($this->currentIndex);
    }

    public function startQuestions(): void
    {
        $this->validate([
            'companyName' => 'required|string|max:255',
            'website' => 'nullable|url|max:255',
            'channelRelevance.web' => 'required|in:has,critical,recommended,not_relevant',
            'channelRelevance.gbp' => 'required|in:has,critical,recommended,not_relevant',
            'channelRelevance.social' => 'required|in:has,critical,recommended,not_relevant',
        ]);

        $this->step = 'questions';
        $this->currentIndex = 0;
    }

    public function backToIntro(): void
    {
        $this->step = 'intro';
    }

    public function next(): void
    {
        if ($this->currentIndex < $this->answerableCriteria->count() - 1) {
            $this->currentIndex++;
        }
    }

    public function previous(): void
    {
        if ($this->currentIndex > 0) {
            $this->currentIndex--;
        }
    }

    public function finish(): void
    {
        $unanswered = $this->answerableCriteria->first(
            fn (Criterion $c) => ! isset($this->answers[$c->id])
        );

        if ($unanswered) {
            $this->currentIndex = $this->answerableCriteria->search(
                fn (Criterion $c) => $c->id === $unanswered->id
            );

            $this->addError('answers', 'Molimo odgovorite na sva pitanja prije nego pogledate rezultat.');

            return;
        }

        $rateLimitKey = 'quick-audit-submit:'.request()->ip();

        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            $this->addError('answers', 'Previše pokušaja. Pokušajte ponovo za nekoliko minuta.');

            return;
        }

        // A real visitor never touches this field — a filled honeypot means
        // a bot submitted the form. Pretend to succeed (no redirect to a
        // dead end) without writing anything, so the bot doesn't learn to
        // adjust and retry.
        if ($this->companyPhone !== '') {
            return;
        }

        RateLimiter::hit($rateLimitKey, 60);

        $assessment = DB::transaction(function (): Assessment {
            $company = Company::create([
                'name' => $this->companyName,
                'website' => $this->website ?: null,
            ]);

            $assessment = $company->assessments()->create([
                'methodology_version' => 'v2.1',
                'mode' => 'quick_scan',
                'quick_channel_relevance' => $this->channelRelevance,
                'status' => 'completed',
                'started_at' => now(),
                'completed_at' => now(),
                'created_by' => User::quickAuditSystemUser()->id,
            ]);

            foreach ($this->answers as $criterionId => $optionId) {
                $assessment->answers()->create([
                    'criterion_id' => $criterionId,
                    'selected_option_id' => $optionId,
                ]);
            }

            return $assessment;
        });

        $this->redirect(route('quick-audit.results', $assessment), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.quick-audit.wizard');
    }
}
