<?php

namespace App\Models;

use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'industry',
    'business_model_notes',
    'b2b_or_b2c',
    'market_scope',
    'has_physical_location',
    'sells_online',
    'provides_online_services',
    'works_by_appointment',
    'has_multiple_locations',
])]
class Company extends Model
{
    /** @use HasFactory<CompanyFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'has_physical_location' => 'boolean',
            'sells_online' => 'boolean',
            'provides_online_services' => 'boolean',
            'works_by_appointment' => 'boolean',
            'has_multiple_locations' => 'boolean',
        ];
    }

    /**
     * @return HasMany<CompanyChannelRelevance, $this>
     */
    public function channelRelevances(): HasMany
    {
        return $this->hasMany(CompanyChannelRelevance::class);
    }

    /**
     * @return HasMany<Assessment, $this>
     */
    public function assessments(): HasMany
    {
        return $this->hasMany(Assessment::class);
    }
}
