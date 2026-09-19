<?php

namespace App\Livewire\Audits;

use App\Models\Assessment;
use App\Models\AssessmentAnswer;
use App\Models\Criterion;
use App\Models\CriterionOption;
use App\Models\Workbook;
use App\Services\ScoringService;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * @property-read Collection<int, Workbook> $workbooks
 * @property-read Workbook|null $currentWorkbook
 * @property-read Collection<int, AssessmentAnswer> $answers
 * @property-read Collection<string, Collection<int, Criterion>> $criteriaGroups
 * @property-read array<int, string> $gatedGroupLabels
 * @property-read array<int, array{answered: int, total: int}> $workbookProgress
 */
#[Title('Audit u toku')]
class RunAudit extends Component
{
    use WithFileUploads;

    private const AUTO_NA_REASON = 'Automatski postavljeno — kanal označen kao nerelevantan.';

    public Assessment $assessment;

    #[Url]
    public ?string $workbook = null;

    /** @var array<int, int|string|null> criterion_id => selected_option_id */
    public array $selectedOptions = [];

    /** @var array<int, bool> criterion_id => is_na */
    public array $naFlags = [];

    /** @var array<int, string> criterion_id => na_reason draft */
    public array $naReasonDrafts = [];

    /** @var array<int, string> criterion_id => notes draft */
    public array $noteDrafts = [];

    /** @var array<int, mixed> criterion_id => pending upload */
    public array $evidenceUploads = [];

    public function mount(Assessment $assessment): void
    {
        $this->assessment = $assessment;
        $this->workbook ??= $this->workbooks->first()?->key;

        foreach ($this->answers as $criterionId => $answer) {
            $this->selectedOptions[$criterionId] = $answer->selected_option_id;
            $this->naFlags[$criterionId] = $answer->is_na;
            $this->naReasonDrafts[$criterionId] = (string) $answer->na_reason;
            $this->noteDrafts[$criterionId] = (string) $answer->notes;
        }
    }

    /**
     * All 14 workbooks, ordered by area then workbook sort_order, with their
     * criteria and options eager loaded (small dataset, loaded once per request).
     *
     * @return Collection<int, Workbook>
     */
    #[Computed]
    public function workbooks(): Collection
    {
        return Workbook::query()
            ->with(['area', 'criteria.options'])
            ->get()
            ->sortBy(fn (Workbook $w) => sprintf('%03d-%03d', $w->area->sort_order, $w->sort_order))
            ->values();
    }

    #[Computed]
    public function currentWorkbook(): ?Workbook
    {
        return $this->workbooks->firstWhere('key', $this->workbook) ?? $this->workbooks->first();
    }

    /**
     * This assessment's answers, keyed by criterion_id. Read-only — the form
     * itself is driven by $selectedOptions/$naFlags, kept in sync manually.
     *
     * @return Collection<int, AssessmentAnswer>
     */
    #[Computed]
    public function answers(): Collection
    {
        return $this->assessment->answers()->get()->keyBy('criterion_id');
    }

    /**
     * The current workbook's criteria grouped by group_label (in display order),
     * with the relevance-gate criterion (if any) sorted first within its group.
     *
     * @return Collection<string, Collection<int, Criterion>>
     */
    #[Computed]
    public function criteriaGroups(): Collection
    {
        $workbook = $this->currentWorkbook;

        if (! $workbook) {
            return collect();
        }

        $groups = collect();

        /** @var Collection<int, Criterion> $criteriaInGroup */
        foreach ($workbook->criteria->groupBy('group_label') as $groupLabel => $criteriaInGroup) {
            $groups[(string) $groupLabel] = $criteriaInGroup->sortByDesc('is_relevance_gate')->values();
        }

        return $groups;
    }

    /**
     * group_label values in the current workbook that are N/A because their
     * relevance-gate criterion was answered negatively.
     *
     * @return array<int, string>
     */
    #[Computed]
    public function gatedGroupLabels(): array
    {
        $workbook = $this->currentWorkbook;

        if (! $workbook) {
            return [];
        }

        return app(ScoringService::class)->resolveGatedGroups($workbook->criteria, $this->answers);
    }

    /**
     * @return array<int, array{answered: int, total: int}>
     */
    #[Computed]
    public function workbookProgress(): array
    {
        $answeredIds = $this->answers->keys();
        $progress = [];

        foreach ($this->workbooks as $workbook) {
            $progress[$workbook->id] = [
                'answered' => $workbook->criteria->filter(fn (Criterion $c) => $answeredIds->contains($c->id))->count(),
                'total' => $workbook->criteria->count(),
            ];
        }

        return $progress;
    }

    public function selectWorkbook(string $workbookKey): void
    {
        $this->workbook = $workbookKey;
    }

    public function updatedSelectedOptions(mixed $value, string $key): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $this->persistAnswer((int) $key, (int) $value);
    }

    public function updatedNaFlags(mixed $value, string $key): void
    {
        $this->persistNaToggle((int) $key, (bool) $value);
    }

    public function updatedNaReasonDrafts(mixed $value, string $key): void
    {
        $criterionId = (int) $key;
        $reason = trim((string) $value) ?: null;

        AssessmentAnswer::updateOrCreate(
            ['assessment_id' => $this->assessment->id, 'criterion_id' => $criterionId],
            ['is_na' => true, 'na_reason' => $reason],
        );

        $this->naFlags[$criterionId] = true;
        $this->markInProgress();
        unset($this->answers);
    }

    public function updatedNoteDrafts(mixed $value, string $key): void
    {
        $criterionId = (int) $key;

        AssessmentAnswer::updateOrCreate(
            ['assessment_id' => $this->assessment->id, 'criterion_id' => $criterionId],
            ['notes' => trim((string) $value) ?: null],
        );

        $this->markInProgress();
        unset($this->answers);
    }

    public function updatedEvidenceUploads(TemporaryUploadedFile $value, string $key): void
    {
        $criterionId = (int) $key;

        $this->validate([
            "evidenceUploads.{$key}" => ['file', 'max:10240', 'mimes:jpg,jpeg,png,webp,pdf'],
        ]);

        $path = $value->store('assessment-evidence/'.$this->assessment->id, 'local');

        AssessmentAnswer::updateOrCreate(
            ['assessment_id' => $this->assessment->id, 'criterion_id' => $criterionId],
            ['evidence_path' => $path],
        );

        $this->markInProgress();
        unset($this->answers);
        unset($this->evidenceUploads[$criterionId]);

        Flux::toast(variant: 'success', text: 'Dokaz sačuvan.');
    }

    public function finishAudit(): void
    {
        $this->assessment->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        Flux::toast(variant: 'success', text: 'Audit je završen. Rezultati su izračunati.');

        $this->redirect(route('audits.results', $this->assessment), navigate: true);
    }

    private function persistAnswer(int $criterionId, int $optionId): void
    {
        $criterion = Criterion::with('options')->findOrFail($criterionId);
        $option = $criterion->options->firstWhere('id', $optionId);

        abort_unless($option !== null, 404);

        AssessmentAnswer::updateOrCreate(
            ['assessment_id' => $this->assessment->id, 'criterion_id' => $criterionId],
            ['selected_option_id' => $optionId, 'is_na' => false, 'na_reason' => null],
        );

        $this->naFlags[$criterionId] = false;

        if ($criterion->is_relevance_gate) {
            $this->applyRelevanceGate($criterion, $option);
        }

        $this->markInProgress();
        unset($this->answers);
    }

    private function persistNaToggle(int $criterionId, bool $isNa): void
    {
        AssessmentAnswer::updateOrCreate(
            ['assessment_id' => $this->assessment->id, 'criterion_id' => $criterionId],
            $isNa
                ? ['is_na' => true, 'selected_option_id' => null]
                : ['is_na' => false, 'na_reason' => null],
        );

        if ($isNa) {
            $this->selectedOptions[$criterionId] = null;
        } else {
            $this->naReasonDrafts[$criterionId] = '';
        }

        $this->markInProgress();
        unset($this->answers);
    }

    /**
     * When a relevance-gate criterion is answered, its sibling criteria (same
     * workbook + group_label) are auto-marked N/A if the answer is negative,
     * or restored to answerable if a previous auto-N/A no longer applies.
     * Siblings the auditor already answered or manually marked N/A themselves
     * are left untouched either way.
     */
    private function applyRelevanceGate(Criterion $gate, CriterionOption $selectedOption): void
    {
        $siblingIds = Criterion::query()
            ->where('workbook_id', $gate->workbook_id)
            ->where('group_label', $gate->group_label)
            ->where('id', '!=', $gate->id)
            ->pluck('id');

        if ($gate->isNegativeOption($selectedOption)) {
            foreach ($siblingIds as $siblingId) {
                AssessmentAnswer::updateOrCreate(
                    ['assessment_id' => $this->assessment->id, 'criterion_id' => $siblingId],
                    ['is_na' => true, 'na_reason' => self::AUTO_NA_REASON, 'selected_option_id' => null],
                );

                $this->naFlags[$siblingId] = true;
                $this->selectedOptions[$siblingId] = null;
                $this->naReasonDrafts[$siblingId] = self::AUTO_NA_REASON;
            }

            return;
        }

        $restoredIds = AssessmentAnswer::where('assessment_id', $this->assessment->id)
            ->whereIn('criterion_id', $siblingIds)
            ->where('na_reason', self::AUTO_NA_REASON)
            ->pluck('criterion_id');

        AssessmentAnswer::where('assessment_id', $this->assessment->id)
            ->whereIn('criterion_id', $restoredIds)
            ->update(['is_na' => false, 'na_reason' => null]);

        foreach ($restoredIds as $restoredId) {
            $this->naFlags[$restoredId] = false;
            $this->naReasonDrafts[$restoredId] = '';
        }
    }

    private function markInProgress(): void
    {
        if ($this->assessment->status === 'draft') {
            $this->assessment->update([
                'status' => 'in_progress',
                'started_at' => $this->assessment->started_at ?? now(),
            ]);
        }
    }

    public function render(): View
    {
        return view('livewire.audits.run-audit');
    }
}
