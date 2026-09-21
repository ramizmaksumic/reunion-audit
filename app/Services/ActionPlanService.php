<?php

namespace App\Services;

use App\Models\Area;
use App\Models\Assessment;
use App\Models\Criterion;
use App\Models\Workbook;
use Illuminate\Support\Collection;

class ActionPlanService
{
    /**
     * Time horizon per criterion priority, per the build plan. Only
     * kritican/vazan criteria ever produce a recommendation (see
     * applicableAnsweredCriteria()), so "3–12 mjeseci" is defined here for
     * completeness but stays empty until self-service/preporucen items are
     * ever included in the plan.
     *
     * @var array<string, string>
     */
    private const HORIZONS = [
        'kritican' => '0–14 dana',
        'vazan' => '31–90 dana',
        'preporucen' => '3–12 mjeseci',
    ];

    public function __construct(private ScoringService $scoring) {}

    /**
     * The full action plan for an assessment: every kritičan/važan criterion
     * that didn't earn full points, grouped into the 3 methodology time
     * horizons and sorted by biggest point loss first within each horizon.
     * All 3 horizons are always present (possibly empty) so callers can
     * render a stable structure without existence checks.
     *
     * @return Collection<string, Collection<int, array{criterion: Criterion, workbook: Workbook, area: Area, earned: float, max: float, gap: float}>>
     */
    public function generate(Assessment $assessment): Collection
    {
        $recommendations = $this->applicableAnsweredCriteria($assessment)
            ->filter(fn (array $row) => $row['earned'] < $row['max']);

        $grouped = $recommendations->groupBy(fn (array $row) => self::HORIZONS[$row['criterion']->priority]);

        $plan = collect();

        foreach (array_values(self::HORIZONS) as $horizon) {
            /** @var Collection<int, array{criterion: Criterion, workbook: Workbook, area: Area, earned: float, max: float, gap: float}> $itemsInHorizon */
            $itemsInHorizon = $grouped->get($horizon) ?? collect();

            $plan[$horizon] = $itemsInHorizon->sortByDesc('gap')->values();
        }

        return $plan;
    }

    /**
     * Top N fully-earned kritičan/važan criteria — the "glavne snage" preview
     * on the dashboard and in the PDF report.
     *
     * @return Collection<int, array{criterion: Criterion, workbook: Workbook, area: Area, earned: float, max: float, gap: float}>
     */
    public function topStrengths(Assessment $assessment, int $limit = 3): Collection
    {
        return $this->applicableAnsweredCriteria($assessment)
            ->filter(fn (array $row) => $row['max'] > 0 && $row['earned'] >= $row['max'])
            ->sortBy(fn (array $row) => $row['criterion']->priority === 'kritican' ? 0 : 1)
            ->take($limit)
            ->values();
    }

    /**
     * Every applicable, answered kritičan/važan criterion across all
     * workbooks — the shared basis for both the action plan and the
     * strengths preview.
     *
     * @return Collection<int, array{criterion: Criterion, workbook: Workbook, area: Area, earned: float, max: float, gap: float}>
     */
    private function applicableAnsweredCriteria(Assessment $assessment): Collection
    {
        return collect(
            Workbook::with('area')->get()
                ->flatMap(function (Workbook $workbook) use ($assessment) {
                    return $this->scoring->criterionBreakdown($assessment, $workbook)
                        ->filter(fn (array $row) => $row['applicable'] && $row['answer'] !== null)
                        ->filter(fn (array $row) => in_array($row['criterion']->priority, ['kritican', 'vazan'], true))
                        ->map(fn (array $row) => [
                            'criterion' => $row['criterion'],
                            'workbook' => $workbook,
                            'area' => $workbook->area,
                            'earned' => $row['earned'],
                            'max' => $row['max'],
                            'gap' => $row['max'] - $row['earned'],
                        ]);
                })
                ->values()
                ->all()
        );
    }
}
