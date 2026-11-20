<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'phone', 'email', 'password', 'role', 'commune_id', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

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
            'role' => UserRole::class,
            'is_active' => 'boolean',
        ];
    }

    // ─── Relations ──────────────────────────────────────────────────────────

    public function commune(): BelongsTo
    {
        return $this->belongsTo(Commune::class);
    }

    public function riskZones(): HasMany
    {
        return $this->hasMany(RiskZone::class, 'created_by');
    }

    public function drainageStructures(): HasMany
    {
        return $this->hasMany(DrainageStructure::class, 'created_by');
    }

    // ─── Helpers ────────────────────────────────────────────────────────────

    public function isValidator(): bool
    {
        return $this->role === UserRole::Validator;
    }

    public function isCitizen(): bool
    {
        return $this->role === UserRole::Citizen;
    }
}
