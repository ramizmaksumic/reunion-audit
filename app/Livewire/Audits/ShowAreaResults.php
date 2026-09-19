<?php

namespace App\Livewire\Audits;

use App\Models\Area;
use App\Models\Assessment;
use App\Models\AssessmentAnswer;
use App\Models\Criterion;
use App\Models\Workbook;
use App\Services\ScoringService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * @property-read float $areaScore
 * @property-read Collection<int, array{workbook: Workbook, score: float, breakdown: Collection<int|string, Collection<int, array{criterion: Criterion, answer: ?AssessmentAnswer, applicable: bool, earned: float, max: float}>>}> $workbooks
 */
#[Title('Detalji oblasti')]
class ShowAreaResults extends Component
{
    public Assessment $assessment;

    public Area $area;

    public function mount(Assessment $assessment, Area $area): void
    {
        $this->assessment = $assessment->load('company');
        $this->area = $area;
    }

    #[Computed]
    public function areaScore(): float
    {
        return app(ScoringService::class)->areaScore($this->assessment, $this->area);
    }

    /**
     * @return Collection<int, array{workbook: Workbook, score: float, breakdown: Collection<int|string, Collection<int, array{criterion: Criterion, answer: ?AssessmentAnswer, applicable: bool, earned: float, max: float}>>}>
     */
    #[Computed]
    public function workbooks(): Collection
    {
        $scoring = app(ScoringService::class);

        return $this->area->workbooks()->with('criteria.options')->get()->map(fn (Workbook $workbook) => [
            'workbook' => $workbook,
            'score' => $scoring->workbookScore($this->assessment, $workbook),
            'breakdown' => $scoring->criterionBreakdown($this->assessment, $workbook)->groupBy('criterion.group_label'),
        ]);
    }

    public function render(): View
    {
        return view('livewire.audits.show-area-results');
    }
}
