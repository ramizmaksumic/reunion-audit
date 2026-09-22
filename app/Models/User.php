<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        $initials = Str::initials($this->name, true);

        return Str::length($initials) > 1
            ? Str::substr($initials, 0, 1).Str::substr($initials, -1)
            : $initials;
    }

    /**
     * The account `assessments.created_by` points to for anonymous
     * quick-scan submissions (no visitor login exists to attribute them
     * to). Not a real, usable login: password is random and never shared.
     * Created lazily on first use rather than via a seeder, so it exists
     * in every environment (including a fresh test database) without
     * needing an extra seeding step.
     */
    public static function quickAuditSystemUser(): self
    {
        return self::firstOrCreate(
            ['email' => 'quick-audit@system.internal'],
            ['name' => 'Brzi audit (sistem)', 'password' => Str::random(40)],
        );
    }

    /**
     * Real staff accounts (agency team members with an actual login) —
     * everyone except the non-loginable quickAuditSystemUser(). Used to
     * decide who receives internal notifications (e.g. a new quick-audit
     * lead) without hardcoding anyone's email: it automatically includes
     * whoever the team registers next.
     *
     * @return Collection<int, self>
     */
    public static function staff(): Collection
    {
        return self::query()->where('email', '!=', 'quick-audit@system.internal')->get();
    }
}
