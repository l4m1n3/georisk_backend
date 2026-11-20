<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Commune extends Model
{
    protected $fillable = ['name', 'code'];

    // ─── Relations ──────────────────────────────────────────────────────────

    public function neighborhoods(): HasMany
    {
        return $this->hasMany(Neighborhood::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function riskZones(): HasMany
    {
        return $this->hasMany(RiskZone::class);
    }

    public function drainageStructures(): HasMany
    {
        return $this->hasMany(DrainageStructure::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }
}
