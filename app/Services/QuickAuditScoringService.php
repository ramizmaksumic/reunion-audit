<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\Criterion;
use Illuminate\Support\Collection;

/**
 * Implements the scoring rules from the seed JSON's top-level "quick_audit"
 * section verbatim (blocks/weights/channel_rule/confidence) — see
 * database/seeders/data/rds_methodology_seed.json. Deliberately separate
 * from ScoringService: the full audit and the quick audit use unrelated
 * scoring models (workbook/area weighted averages vs. a flat 6-block,
 * channel-aware one over a 25-item subset of the same criteria).
 */
class QuickAuditScoringService
{
    /**
     * @var array<string, int>
     */
    private const BLOCK_WEIGHTS = [
        'pronalazljivost' => 20,
        'web' => 15,
        'reputacija_povjerenje' => 20,
        'drustvene_mreze' => 15,
        'odziv_procesi' => 15,
        'mjerenje_rast' => 15,
    ];

    /** A block is only scored once it has at least this many scored items. */
    private const MIN_SCORED_PER_BLOCK = 2;

    /** Below this share of applicable items being scored, the result is flagged "okvirna ocjena". */
    private const CONFIDENCE_THRESHOLD = 0.7;

    /** Fewer than this many scored blocks also flags the result "okvirna ocjena". */
    private const MIN_SCORED_BLOCKS_FOR_CONFIDENCE = 4;

    /**
     * Per-item state and value for every quick_audit=true criterion.
     *
     * @return Collection<int, array{criterion: Criterion, block: string, state: 'scored'|'missing_zero'|'excluded', value: ?float, weight: float}>
     */
    public function itemBreakdown(Assessment $assessment): Collection
    {
        $criteria = Criterion::query()->where('quick_audit', true)->with('options')->get();
        $answers = $assessment->answers()->whereIn('criterion_id', $criteria->pluck('id'))->get()->keyBy('criterion_id');
        $channelRelevance = $assessment->quick_channel_relevance ?? [];

        return $criteria->map(function (Criterion $criterion) use ($answers, $channelRelevance) {
            $answer = $answers->get($criterion->id);

            if ($criterion->channel !== null) {
                $relevance = $channelRelevance[$criterion->channel] ?? 'has';

                if ($relevance === 'not_relevant') {
                    return $this->item($criterion, 'excluded', null, 1.0);
                }

                if ($relevance === 'critical' || $relevance === 'recommended') {
                    return $this->item($criterion, 'missing_zero', 0.0, $relevance === 'critical' ? 1.0 : 0.5);
                }
            }

            if ($answer?->selected_option_id) {
                $selected = $criterion->options->firstWhere('id', $answer->selected_option_id);
                $maxPoints = (float) $criterion->options->max('points');

                $value = $maxPoints > 0 ? (float) $selected->points / $maxPoints : 0.0;

                return $this->item($criterion, 'scored', $value, 1.0);
            }

            // Applicable but not yet answered — shouldn't happen once a scan
            // is complete (the wizard requires every applicable item
            // answered first); excluded here rather than crashing or
            // silently counting as 0.
            return $this->item($criterion, 'excluded', null, 1.0);
        });
    }

    /**
     * @return array{criterion: Criterion, block: string, state: 'scored'|'missing_zero'|'excluded', value: ?float, weight: float}
     */
    private function item(Criterion $criterion, string $state, ?float $value, float $weight): array
    {
        return [
            'criterion' => $criterion,
            'block' => $criterion->quick_block,
            'state' => $state,
            'value' => $value,
            'weight' => $weight,
        ];
    }

    /**
     * Score (0-100) per block, only for blocks with at least
     * MIN_SCORED_PER_BLOCK scored items. A block missing that threshold is
     * simply absent from the returned collection.
     *
     * @return Collection<string, float>
     */
    public function blockScores(Assessment $assessment): Collection
    {
        return $this->itemBreakdown($assessment)
            ->groupBy('block')
            ->map(function (Collection $items) {
                $scoredCount = $items->where('state', 'scored')->count();

                if ($scoredCount < self::MIN_SCORED_PER_BLOCK) {
                    return null;
                }

                $applicable = $items->whereIn('state', ['scored', 'missing_zero']);
                $totalWeight = (float) $applicable->sum('weight');

                if ($totalWeight <= 0.0) {
                    return null;
                }

                $weightedSum = $applicable->sum(fn (array $item) => $item['value'] * $item['weight']);

                return round(($weightedSum / $totalWeight) * 100, 1);
            })
            ->filter(fn (?float $score) => $score !== null);
    }

    /**
     * Overall quick-audit result: weighted average of the scored blocks
     * (weights renormalized to only those blocks), plus a confidence ratio
     * and whether the result should be flagged as approximate.
     *
     * @return array{score: ?float, confidence: float, is_approximate: bool, scored_block_count: int}
     */
    public function overallScore(Assessment $assessment): array
    {
        $blockScores = $this->blockScores($assessment);

        $totalWeight = $blockScores->keys()
            ->sum(fn (string $block) => self::BLOCK_WEIGHTS[$block] ?? 0);

        $score = null;

        if ($totalWeight > 0) {
            $weightedSum = $blockScores->sum(
                fn (float $score, string $block) => $score * (self::BLOCK_WEIGHTS[$block] ?? 0)
            );

            $score = round($weightedSum / $totalWeight, 1);
        }

        $items = $this->itemBreakdown($assessment);
        $applicable = $items->whereIn('state', ['scored', 'missing_zero']);
        $scoredCount = $items->where('state', 'scored')->count();

        $confidence = $applicable->isNotEmpty() ? round($scoredCount / $applicable->count(), 2) : 0.0;
        $scoredBlockCount = $blockScores->count();

        return [
            'score' => $score,
            'confidence' => $confidence,
            'is_approximate' => $confidence < self::CONFIDENCE_THRESHOLD || $scoredBlockCount < self::MIN_SCORED_BLOCKS_FOR_CONFIDENCE,
            'scored_block_count' => $scoredBlockCount,
        ];
    }

    /**
     * All 6 quick-audit blocks with their weight and display name, in the
     * order defined by the methodology — used to render the wizard's
     * accordion regardless of scoring state.
     *
     * @return array<int, array{key: string, name: string, weight: int}>
     */
    public static function blocks(): array
    {
        $data = json_decode(
            file_get_contents(database_path('seeders/data/rds_methodology_seed.json')),
            associative: true,
            flags: JSON_THROW_ON_ERROR,
        );

        return $data['quick_audit']['blocks'];
    }
}
