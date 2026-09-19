<?php

namespace App\Models;

use Database\Factories\AreaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['key', 'name', 'sort_order'])]
class Area extends Model
{
    /** @use HasFactory<AreaFactory> */
    use HasFactory;

    /**
     * @return HasMany<Workbook, $this>
     */
    public function workbooks(): HasMany
    {
        return $this->hasMany(Workbook::class)->orderBy('sort_order');
    }
}
