<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Alarm extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'device_type',
        'device_id',
        'severity',
        'title',
        'description',
        'is_resolved',
        'resolved_at',
        'resolved_by',
    ];

    protected function casts(): array
    {
        return [
            'is_resolved' => 'boolean',
            'resolved_at' => 'datetime',
        ];
    }

    public function device(): MorphTo
    {
        return $this->morphTo('device', 'device_type', 'device_id');
    }

    public function resolvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
