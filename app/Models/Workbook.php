<?php

namespace App\Models;

use Database\Factories\WorkbookFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read Area $area area_id is a required column, so this relation is never null.
 */
#[Fillable(['area_id', 'key', 'name', 'sort_order', 'weight', 'introduced_in_version'])]
class Workbook extends Model
{
    /** @use HasFactory<WorkbookFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'weight' => 'float',
        ];
    }

    /**
     * @return BelongsTo<Area, $this>
     */
    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    /**
     * @return HasMany<Criterion, $this>
     */
    public function criteria(): HasMany
    {
        return $this->hasMany(Criterion::class)->orderBy('sort_order');
    }
}
