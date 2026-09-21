<?php

namespace App\Livewire\Audits;

use App\Models\Area;
use App\Models\Assessment;
use App\Models\Criterion;
use App\Models\Workbook;
use App\Services\ActionPlanService;
use App\Services\AreaPresentation;
use App\Services\ScoringService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * @property-read float $overallScore
 * @property-read array{label: string, description: string, color: string} $status
 * @property-read Collection<int, array{area: Area, score: float, color: string, description: string}> $areaScores
 * @property-read Collection<int, array{criterion: Criterion, workbook: Workbook, area: Area, earned: float, max: float, gap: float}> $strengths
 * @property-read Collection<int, array{criterion: Criterion, workbook: Workbook, area: Area, earned: float, max: float, gap: float}> $priorities
 * @property-read float $growthPotential
 */
#[Title('Rezultati audita')]
class ShowResults extends Component
{
    private const TOP_ITEMS_LIMIT = 3;

    public Assessment $assessment;

    public function mount(Assessment $assessment): void
    {
        $this->assessment = $assessment->load(['company.channelRelevances']);
    }

    #[Computed]
    public function overallScore(): float
    {
        return app(ScoringService::class)->overallScore($this->assessment);
    }

    /**
     * @return array{label: string, description: string, color: string}
     */
    #[Computed]
    public function status(): array
    {
        return app(ScoringService::class)->scoreStatus($this->overallScore);
    }

    /**
     * @return Collection<int, array{area: Area, score: float, color: string, description: string}>
     */
    #[Computed]
    public function areaScores(): Collection
    {
        $scoring = app(ScoringService::class);

        return Area::query()->orderBy('sort_order')->get()->map(fn (Area $area) => [
            'area' => $area,
            'score' => $scoring->areaScore($this->assessment, $area),
            'color' => AreaPresentation::color($area->key),
            'description' => AreaPresentation::description($area->key),
        ]);
    }

    /**
     * Top fully-earned, high-priority criteria.
     *
     * @return Collection<int, array{criterion: Criterion, workbook: Workbook, area: Area, earned: float, max: float, gap: float}>
     */
    #[Computed]
    public function strengths(): Collection
    {
        return app(ActionPlanService::class)->topStrengths($this->assessment, self::TOP_ITEMS_LIMIT);
    }

    /**
     * The most urgent action-plan items overall: 0–14 dana (kritičan) items
     * first, then 31–90 dana (važan) ones, each sorted by biggest point loss.
     *
     * @return Collection<int, array{criterion: Criterion, workbook: Workbook, area: Area, earned: float, max: float, gap: float}>
     */
    #[Computed]
    public function priorities(): Collection
    {
        $items = collect();

        foreach (app(ActionPlanService::class)->generate($this->assessment) as $itemsInHorizon) {
            $items = $items->concat($itemsInHorizon);
        }

        return $items->take(self::TOP_ITEMS_LIMIT)->values();
    }

    /**
     * Simple preview heuristic: how many points (0-100 scale) are left on
     * the table overall. The full action plan (see ActionPlanService) is
     * the actionable breakdown behind this number.
     */
    #[Computed]
    public function growthPotential(): float
    {
        return round(100 - $this->overallScore, 1);
    }

    public function render(): View
    {
        return view('livewire.audits.show-results');
    }
}
