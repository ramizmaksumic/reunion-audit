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

    /**
     * @var array<string, string>
     */
    public const B2B_OR_B2C_LABELS = [
        'b2b' => 'B2B',
        'b2c' => 'B2C',
        'both' => 'Kombinovano',
    ];

    /**
     * @var array<string, string>
     */
    public const MARKET_SCOPE_LABELS = [
        'local' => 'Lokalno',
        'regional' => 'Regionalno',
        'national' => 'Nacionalno',
        'international' => 'Međunarodno',
    ];

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
     * Short labels for the "poslovni model" booleans that are true — used
     * wherever the company profile is summarized (results dashboard, PDF).
     *
     * @return array<int, string>
     */
    public function profileTags(): array
    {
        return array_values(array_filter([
            $this->has_physical_location ? 'Fizička lokacija' : null,
            $this->sells_online ? 'Online prodaja' : null,
            $this->provides_online_services ? 'Online usluge' : null,
            $this->works_by_appointment ? 'Rad po terminima/rezervaciji' : null,
            $this->has_multiple_locations ? 'Više poslovnica' : null,
        ]));
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
