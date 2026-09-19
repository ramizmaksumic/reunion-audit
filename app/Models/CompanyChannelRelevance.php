<?php

namespace App\Models;

use Database\Factories\CompanyChannelRelevanceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('company_channel_relevance')]
#[Fillable(['company_id', 'channel_key', 'relevance'])]
class CompanyChannelRelevance extends Model
{
    /** @use HasFactory<CompanyChannelRelevanceFactory> */
    use HasFactory;

    /**
     * "Očekivani digitalni kanali" iz Profil kompanije upitnika
     * (docs/rds-methodology.md, sekcija 9).
     *
     * @var array<string, string>
     */
    public const CHANNELS = [
        'web_stranica' => 'Web stranica',
        'webshop' => 'Webshop',
        'google_business_profil' => 'Google Business profil',
        'facebook' => 'Facebook',
        'instagram' => 'Instagram',
        'linkedin' => 'LinkedIn',
        'youtube' => 'YouTube',
        'tiktok' => 'TikTok',
        'specijalizovane_platforme' => 'Booking / Airbnb / TripAdvisor ili druge specijalizovane platforme',
        'email_marketing' => 'Email marketing',
        'crm_sistem' => 'CRM sistem',
    ];

    /**
     * @var array<string, string>
     */
    public const RELEVANCE_LEVELS = [
        'critical' => 'Kritičan',
        'recommended' => 'Preporučen',
        'not_relevant' => 'Nije relevantan',
    ];

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
