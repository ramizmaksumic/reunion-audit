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
 * @property-read float $overallScore
 * @property-read array{label: string, description: string, color: string} $status
 * @property-read Collection<int, array{area: Area, score: float, color: string, description: string}> $areaScores
 * @property-read Collection<int, array{criterion: Criterion, answer: AssessmentAnswer, applicable: bool, earned: float, max: float}> $applicableCriteria
 * @property-read Collection<int, array{criterion: Criterion, answer: AssessmentAnswer, applicable: bool, earned: float, max: float}> $strengths
 * @property-read Collection<int, array{criterion: Criterion, answer: AssessmentAnswer, applicable: bool, earned: float, max: float, gap: float}> $priorities
 * @property-read float $growthPotential
 */
#[Title('Rezultati audita')]
class ShowResults extends Component
{
    /**
     * Presentational metadata for each main area, keyed by Area::$key. Kept
     * here rather than in the database since it's copy for this report, not
     * methodology data the admin needs to edit (see docs/rds-methodology.md §2).
     *
     * @var array<string, array{color: string, description: string}>
     */
    private const AREA_PRESENTATION = [
        'digitalna_prisutnost' => [
            'color' => '#2563eb',
            'description' => 'Kvalitet i profesionalnost digitalne osnove — web, Google Business, SEO, reputacija, brend i povjerenje.',
        ],
        'korisnicko_iskustvo' => [
            'color' => '#d97706',
            'description' => 'Koliko je digitalni nastup optimizovan da posjetioca pretvori u kupca ili upit.',
        ],
        'digitalna_efikasnost' => [
            'color' => '#0d9488',
            'description' => 'Koliko digitalni alati i procesi stvarno podržavaju svakodnevno poslovanje.',
        ],
        'marketing_i_rast' => [
            'color' => '#7c3aed',
            'description' => 'Da li se marketing vodi planski — od strategije, preko akvizicije, do mjerenja rezultata.',
        ],
    ];

    private const TOP_ITEMS_LIMIT = 3;

    public Assessment $assessment;

    public function mount(Assessment $assessment): void
    {
        $this->assessment = $assessment->load('company');
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
            'color' => $this->areaColor($area->key),
            'description' => $this->areaDescription($area->key),
        ]);
    }

    private function areaColor(string $areaKey): string
    {
        return self::AREA_PRESENTATION[$areaKey]['color'] ?? '#71717a';
    }

    private function areaDescription(string $areaKey): string
    {
        return self::AREA_PRESENTATION[$areaKey]['description'] ?? '';
    }

    /**
     * Every applicable, answered criterion across the whole assessment — the
     * raw material for the strengths/priorities preview below. The full
     * weighted action-plan logic lands in Phase 6; this is a lightweight
     * preview built on the same breakdown data.
     *
     * @return Collection<int, array{criterion: Criterion, answer: AssessmentAnswer, applicable: bool, earned: float, max: float}>
     */
    #[Computed]
    public function applicableCriteria(): Collection
    {
        $scoring = app(ScoringService::class);

        return collect(
            Workbook::all()
                ->flatMap(fn (Workbook $workbook) => $scoring->criterionBreakdown($this->assessment, $workbook))
                ->filter(fn (array $row) => $row['applicable'] && $row['answer'] !== null)
                ->values()
                ->all()
        );
    }

    /**
     * Top fully-earned, high-priority criteria.
     *
     * @return Collection<int, array{criterion: Criterion, answer: AssessmentAnswer, applicable: bool, earned: float, max: float}>
     */
    #[Computed]
    public function strengths(): Collection
    {
        return $this->applicableCriteria
            ->filter(fn (array $row) => $row['max'] > 0 && $row['earned'] >= $row['max'])
            ->filter(fn (array $row) => in_array($row['criterion']->priority, ['kritican', 'vazan'], true))
            ->sortBy(fn (array $row) => $row['criterion']->priority === 'kritican' ? 0 : 1)
            ->take(self::TOP_ITEMS_LIMIT)
            ->values();
    }

    /**
     * Top criteria with the largest point gap, high-priority first.
     *
     * @return Collection<int, array{criterion: Criterion, answer: AssessmentAnswer, applicable: bool, earned: float, max: float, gap: float}>
     */
    #[Computed]
    public function priorities(): Collection
    {
        return $this->applicableCriteria
            ->map(fn (array $row) => [...$row, 'gap' => $row['max'] - $row['earned']])
            ->filter(fn (array $row) => $row['gap'] > 0)
            ->filter(fn (array $row) => in_array($row['criterion']->priority, ['kritican', 'vazan'], true))
            ->sortBy([
                fn (array $a, array $b) => ($a['criterion']->priority === 'kritican' ? 0 : 1) <=> ($b['criterion']->priority === 'kritican' ? 0 : 1),
                fn (array $a, array $b) => $b['gap'] <=> $a['gap'],
            ])
            ->take(self::TOP_ITEMS_LIMIT)
            ->values();
    }

    /**
     * Simple preview heuristic: how many points (0-100 scale) are left on
     * the table overall. Phase 6's action plan replaces this with a proper
     * weighted projection.
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
