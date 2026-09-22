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
        $applicable = $this->criterionBreakdown($assessment, $workbook)->where('applicable', true);

        $applicableMaxPoints = (float) $applicable->sum('max');

        if ($applicableMaxPoints <= 0.0) {
            return 0.0;
        }

        $earnedPoints = (float) $applicable->sum('earned');

        return round(($earnedPoints / $applicableMaxPoints) * 100, 2);
    }

    /**
     * Per-criterion detail for a workbook: whether each criterion is applicable
     * (not N/A, not gated out by a relevance-gate answered negatively) and its
     * earned/max points. Powers workbookScore() as well as the results
     * dashboard and area drill-down views, which need row-level detail rather
     * than just the aggregate score.
     *
     * @return Collection<int, array{criterion: Criterion, answer: ?AssessmentAnswer, applicable: bool, earned: float, max: float}>
     */
    public function criterionBreakdown(Assessment $assessment, Workbook $workbook): Collection
    {
        $criteria = $workbook->criteria()->with('options')->get();

        if ($criteria->isEmpty()) {
            return collect();
        }

        $answers = $assessment->answers()
            ->whereIn('criterion_id', $criteria->pluck('id'))
            ->get()
            ->keyBy('criterion_id');

        $gatedGroups = $this->resolveGatedGroups($criteria, $answers);

        return $criteria->map(function (Criterion $criterion) use ($answers, $gatedGroups) {
            $answer = $answers->get($criterion->id);

            $isGatedOut = ! $criterion->is_relevance_gate
                && in_array($criterion->group_label, $gatedGroups, true);

            $applicable = ! $isGatedOut && ! ($answer?->is_na ?? false);

            $earned = 0.0;

            if ($applicable && $answer?->selected_option_id) {
                $selected = $criterion->options->firstWhere('id', $answer->selected_option_id);
                $earned = (float) $selected->points;
            }

            return [
                'criterion' => $criterion,
                'answer' => $answer,
                'applicable' => $applicable,
                'earned' => $earned,
                'max' => (float) $criterion->options->max('points'),
            ];
        });
    }

    /**
     * Group labels within this workbook that are N/A because their relevance-gate
     * criterion was answered with its lowest-point ("negative") option. Also used
     * by the audit wizard to grey out and auto-skip a gated group's criteria.
     *
     * @param  Collection<int, Criterion>  $criteria
     * @param  Collection<int, AssessmentAnswer>  $answers  Keyed by criterion_id.
     * @return array<int, string>
     */
    public function resolveGatedGroups(Collection $criteria, Collection $answers): array
    {
        $gatedGroups = [];

        foreach ($criteria->where('is_relevance_gate', true) as $gate) {
            $answer = $answers->get($gate->id);

            if (! $answer || $answer->is_na || ! $answer->selected_option_id) {
                continue;
            }

            $selected = $gate->options->firstWhere('id', $answer->selected_option_id);

            if ($selected && $gate->isNegativeOption($selected)) {
                $gatedGroups[] = $gate->group_label;
            }
        }

        return $gatedGroups;
    }

    /**
     * Whether $workbook should count toward $assessment's score. A workbook
     * added to the methodology after the assessment's methodology_version
     * is excluded — unless the assessment actually has an answer in it
     * (e.g. an older, still in-progress assessment whose auditor went ahead
     * and filled in the new workbook anyway; those answers must still
     * count, not be silently dropped). This is what keeps a newly-seeded
     * workbook from quietly pulling down the score of an assessment that
     * predates it.
     */
    public function isWorkbookApplicable(Assessment $assessment, Workbook $workbook): bool
    {
        if ($workbook->introduced_in_version === null) {
            return true;
        }

        $assessmentVersion = ltrim($assessment->methodology_version, 'v');
        $workbookVersion = ltrim($workbook->introduced_in_version, 'v');

        if (version_compare($assessmentVersion, $workbookVersion, '>=')) {
            return true;
        }

        $criterionIds = $workbook->relationLoaded('criteria')
            ? $workbook->criteria->pluck('id')
            : $workbook->criteria()->pluck('id');

        return $assessment->answers()->whereIn('criterion_id', $criterionIds)->exists();
    }

    /**
     * Score of a main area (0-100): the weighted average of its applicable
     * workbooks' scores, weighted by each workbook's `weight` column.
     * Workbooks not yet applicable to this assessment (see
     * isWorkbookApplicable()) are excluded entirely, not scored as 0 — that
     * would silently drag down older assessments whenever the methodology
     * grows a new workbook.
     */
    public function areaScore(Assessment $assessment, Area $area): float
    {
        $workbooks = $area->workbooks->filter(
            fn (Workbook $workbook) => $this->isWorkbookApplicable($assessment, $workbook)
        );

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

    /**
     * The 5 status tiers named in the build plan (Kritično/Reaktivno/
     * Funkcionalno/Upravljano/Napredno), mapped to a 0-100 score. The
     * methodology doesn't define numeric cutoffs for these yet, so this
     * splits the range evenly (20 points per tier) — the same "start even,
     * calibrate later from real audits" approach already used for weights.
     *
     * @return array{label: string, description: string, color: string}
     */
    public function scoreStatus(float $score): array
    {
        return match (true) {
            $score < 20 => [
                'label' => 'Kritično',
                'description' => 'Digitalni nastup ima ozbiljne nedostatke koji direktno štete poslovanju. Hitno su potrebne intervencije na osnovnim elementima.',
                'color' => '#dc2626',
            ],
            $score < 40 => [
                'label' => 'Reaktivno',
                'description' => 'Osnove postoje, ali su nekonzistentne i uglavnom reaktivne umjesto planske. Veliki prostor za unapređenje u većini oblasti.',
                'color' => '#ea580c',
            ],
            $score < 60 => [
                'label' => 'Funkcionalno',
                'description' => 'Digitalni nastup pokriva osnovne potrebe, ali bez punog iskorištavanja potencijala. Postoje jasne prilike za rast.',
                'color' => '#d97706',
            ],
            $score < 80 => [
                'label' => 'Upravljano',
                'description' => 'Imate solidnu i uglavnom dobro vođenu digitalnu osnovu. Preostale prilike mogu donijeti dodatni rast i efikasnost.',
                'color' => '#2563eb',
            ],
            default => [
                'label' => 'Napredno',
                'description' => 'Digitalni nastup je zreo i dobro upravljan u većini oblasti. Fokus je na finom podešavanju i održavanju prednosti.',
                'color' => '#16a34a',
            ],
        };
    }
}
