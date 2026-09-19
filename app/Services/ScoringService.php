<?php

namespace App\Services;

use App\Models\Area;
use App\Models\Assessment;
use App\Models\AssessmentAnswer;
use App\Models\Criterion;
use App\Models\Workbook;
use Illuminate\Support\Collection;

class ScoringService
{
    /**
     * Score of a single workbook (0-100), normalized to the criteria that are
     * actually applicable (i.e. not N/A, and not gated out by a relevance-gate
     * criterion answered negatively).
     */
    public function workbookScore(Assessment $assessment, Workbook $workbook): float
    {
        $criteria = $workbook->criteria()->with('options')->get();

        if ($criteria->isEmpty()) {
            return 0.0;
        }

        $answers = $assessment->answers()
            ->whereIn('criterion_id', $criteria->pluck('id'))
            ->get()
            ->keyBy('criterion_id');

        $gatedGroups = $this->resolveGatedGroups($criteria, $answers);

        $earnedPoints = 0.0;
        $applicableMaxPoints = 0.0;

        foreach ($criteria as $criterion) {
            $isGatedOut = ! $criterion->is_relevance_gate
                && in_array($criterion->group_label, $gatedGroups, true);

            if ($isGatedOut) {
                continue;
            }

            $answer = $answers->get($criterion->id);

            if ($answer && $answer->is_na) {
                continue;
            }

            $applicableMaxPoints += (float) $criterion->options->max('points');

            if ($answer && $answer->selected_option_id) {
                $selected = $criterion->options->firstWhere('id', $answer->selected_option_id);
                $earnedPoints += (float) $selected->points;
            }

            // Criteria without an answer yet (in-progress assessment) contribute
            // 0 earned points but still count toward the applicable maximum.
        }

        if ($applicableMaxPoints <= 0.0) {
            return 0.0;
        }

        return round(($earnedPoints / $applicableMaxPoints) * 100, 2);
    }

    /**
     * Group labels within this workbook that are N/A because their relevance-gate
     * criterion was answered with its lowest-point ("negative") option.
     *
     * @param  Collection<int, Criterion>  $criteria
     * @param  Collection<int, AssessmentAnswer>  $answers  Keyed by criterion_id.
     * @return array<int, string>
     */
    private function resolveGatedGroups(Collection $criteria, Collection $answers): array
    {
        $gatedGroups = [];

        foreach ($criteria->where('is_relevance_gate', true) as $gate) {
            $answer = $answers->get($gate->id);

            if (! $answer || $answer->is_na || ! $answer->selected_option_id) {
                continue;
            }

            $selected = $gate->options->firstWhere('id', $answer->selected_option_id);

            if (! $selected) {
                continue;
            }

            $minPoints = (float) $gate->options->min('points');

            if ((float) $selected->points <= $minPoints) {
                $gatedGroups[] = $gate->group_label;
            }
        }

        return $gatedGroups;
    }

    /**
     * Score of a main area (0-100): the weighted average of its workbooks'
     * scores, weighted by each workbook's `weight` column.
     */
    public function areaScore(Assessment $assessment, Area $area): float
    {
        $workbooks = $area->workbooks;

        $totalWeight = (float) $workbooks->sum('weight');

        if ($totalWeight <= 0.0) {
            return 0.0;
        }

        $weightedSum = $workbooks->sum(
            fn (Workbook $workbook) => $this->workbookScore($assessment, $workbook) * (float) $workbook->weight
        );

        return round($weightedSum / $totalWeight, 2);
    }

    /**
     * Overall Reunion Digital Score (0-100): the average of all main areas'
     * scores. The `areas` table carries no weight column (methodology v2.0
     * calls for even weighting across areas until real audit data justifies
     * calibrating them), so every area counts equally here.
     */
    public function overallScore(Assessment $assessment): float
    {
        $areas = Area::query()->with('workbooks.criteria.options')->get();

        if ($areas->isEmpty()) {
            return 0.0;
        }

        $sum = $areas->sum(fn (Area $area) => $this->areaScore($assessment, $area));

        return round($sum / $areas->count(), 2);
    }
}
