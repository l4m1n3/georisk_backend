<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ValidationHistory extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'entity_type',
        'entity_id',
        'user_id',
        'old_status',
        'new_status',
        'comment',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    // ─── Relations ──────────────────────────────────────────────────────────

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
