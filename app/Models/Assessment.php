<?php

namespace App\Models;

use Database\Factories\AssessmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable([
    'company_id',
    'public_token',
    'methodology_version',
    'mode',
    'quick_channel_relevance',
    'lead_message',
    'contact_requested_at',
    'status',
    'started_at',
    'completed_at',
    'created_by',
])]
class Assessment extends Model
{
    /** @use HasFactory<AssessmentFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (Assessment $assessment): void {
            if ($assessment->mode === 'quick_scan' && ! $assessment->public_token) {
                $assessment->public_token = (string) Str::uuid();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'quick_channel_relevance' => 'array',
            'contact_requested_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return request()->routeIs('quick-audit.*') ? 'public_token' : 'id';
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<AssessmentAnswer, $this>
     */
    public function answers(): HasMany
    {
        return $this->hasMany(AssessmentAnswer::class);
    }
}
