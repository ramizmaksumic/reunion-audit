<?php

namespace App\Models;

use Database\Factories\CriterionOptionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['criterion_id', 'label', 'points', 'sort_order'])]
class CriterionOption extends Model
{
    /** @use HasFactory<CriterionOptionFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'points' => 'float',
        ];
    }

    /**
     * @return BelongsTo<Criterion, $this>
     */
    public function criterion(): BelongsTo
    {
        return $this->belongsTo(Criterion::class);
    }
}
