<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PppoeSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'mikrotik_id',
        'username',
        'caller_id',
        'service',
        'uptime',
        'ip_address',
        'rx_bytes',
        'tx_bytes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function mikrotik(): BelongsTo
    {
        return $this->belongsTo(Mikrotik::class);
    }
}
