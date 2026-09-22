<?php

namespace App\Models;

use Database\Factories\CriterionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'workbook_id',
    'external_id',
    'group_label',
    'text',
    'answer_type',
    'priority',
    'evidence_source',
    'self_service_eligible',
    'is_relevance_gate',
    'sort_order',
    'channel',
    'quick_audit',
    'quick_block',
    'quick_source',
    'quick_question',
    'quick_option_labels',
    'quick_note',
])]
class Criterion extends Model
{
    /** @use HasFactory<CriterionFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'self_service_eligible' => 'boolean',
            'is_relevance_gate' => 'boolean',
            'quick_audit' => 'boolean',
            'quick_option_labels' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Workbook, $this>
     */
    public function workbook(): BelongsTo
    {
        return $this->belongsTo(Workbook::class);
    }

    /**
     * @return HasMany<CriterionOption, $this>
     */
    public function options(): HasMany
    {
        return $this->hasMany(CriterionOption::class)->orderBy('sort_order');
    }

    /**
     * @return HasMany<AssessmentAnswer, $this>
     */
    public function answers(): HasMany
    {
        return $this->hasMany(AssessmentAnswer::class);
    }

    /**
     * Whether $option is this criterion's lowest-point ("negative") answer.
     * For a relevance-gate criterion, picking this option means the channel/
     * group it gates is not relevant and its sibling criteria become N/A.
     */
    public function isNegativeOption(CriterionOption $option): bool
    {
        $minPoints = $this->options->min('points');

        return $minPoints !== null && (float) $option->points <= (float) $minPoints;
    }
}
