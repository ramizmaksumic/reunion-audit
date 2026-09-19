<?php

namespace App\Models;

use Database\Factories\AssessmentAnswerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'assessment_id',
    'criterion_id',
    'selected_option_id',
    'is_na',
    'na_reason',
    'evidence_path',
    'notes',
])]
class AssessmentAnswer extends Model
{
    /** @use HasFactory<AssessmentAnswerFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_na' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Assessment, $this>
     */
    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class);
    }

    /**
     * @return BelongsTo<Criterion, $this>
     */
    public function criterion(): BelongsTo
    {
        return $this->belongsTo(Criterion::class);
    }

    /**
     * @return BelongsTo<CriterionOption, $this>
     */
    public function selectedOption(): BelongsTo
    {
        return $this->belongsTo(CriterionOption::class, 'selected_option_id');
    }
}
