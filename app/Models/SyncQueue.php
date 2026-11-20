<?php

namespace App\Models;

use App\Enums\SyncStatus;
use Illuminate\Database\Eloquent\Model;

class SyncQueue extends Model
{
    protected $table = 'sync_queue';

    protected $fillable = [
        'entity_type',
        'entity_id',
        'operation',
        'payload',
        'status',
        'attempts',
        'last_attempt_at',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'status' => SyncStatus::class,
            'attempts' => 'integer',
            'last_attempt_at' => 'datetime',
        ];
    }

    // ─── Scopes ─────────────────────────────────────────────────────────────

    public function scopePending($query)
    {
        return $query->where('status', SyncStatus::Pending->value);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', SyncStatus::Failed->value);
    }
}
