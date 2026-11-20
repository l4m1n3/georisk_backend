<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Neighborhood extends Model
{
    protected $fillable = ['commune_id', 'name'];

    // ─── Relations ──────────────────────────────────────────────────────────

    public function commune(): BelongsTo
    {
        return $this->belongsTo(Commune::class);
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
