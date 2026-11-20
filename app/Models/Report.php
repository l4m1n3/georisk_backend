<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        // UUID généré par l'application mobile : on le conserve pour pouvoir
        // retrouver le signalement lors de la synchronisation.
        'id',
        'category',
        'latitude',
        'longitude',
        'gps_accuracy',
        'severity',
        'commune_id',
        'neighborhood_id',
        'collector_type',
        'collector_id',
        'photo_url',
        'validation_status',
        'comment',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'gps_accuracy' => 'float',
        'severity' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function commune()
    {
        return $this->belongsTo(Commune::class);
    }

    public function neighborhood()
    {
        return $this->belongsTo(Neighborhood::class);
    }

    // Pas de contrainte FK en base (collector_id est un simple unsignedBigInteger,
    // car un signalement peut être anonyme) — la relation reste utile côté Eloquent.
    public function collector()
    {
        return $this->belongsTo(User::class, 'collector_id');
    }
}
