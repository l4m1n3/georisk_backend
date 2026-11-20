<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class RiskZone extends Model
{
    use HasUuids;

    protected $fillable = [
        'commune_id', 'neighborhood_id', 'latitude', 'longitude', 'risk_level',
        'historical_frequency', 'last_flood_date', 'water_stagnation_duration',
        'estimated_exposed_population', 'main_cause', 'photo_url',
        'validation_status', 'comment', 'created_by',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'last_flood_date' => 'date',
    ];

    public function commune()
    {
        return $this->belongsTo(Commune::class);
    }

    public function neighborhood()
    {
        return $this->belongsTo(Neighborhood::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
