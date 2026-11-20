<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class DrainageStructure extends Model
{
    use HasUuids;

    protected $fillable = [
        'commune_id', 'neighborhood_id', 'latitude', 'longitude', 'structure_type',
        'condition', 'material', 'last_maintenance_date', 'photo_url',
        'validation_status', 'comment', 'created_by',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'last_maintenance_date' => 'date',
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
