<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InterfaceTraffic extends Model
{
    use HasFactory;

    protected $fillable = [
        'mikrotik_id',
        'interface_name',
        'rx_bytes',
        'tx_bytes',
        'rx_rate',
        'tx_rate',
        'status',
    ];

    public function mikrotik(): BelongsTo
    {
        return $this->belongsTo(Mikrotik::class);
    }
}
