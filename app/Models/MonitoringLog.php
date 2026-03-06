<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class MonitoringLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_type',
        'device_id',
        'data',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
        ];
    }

    public function device(): MorphTo
    {
        return $this->morphTo();
    }
}
