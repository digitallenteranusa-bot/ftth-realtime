<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ont extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'odp_id',
        'customer_id',
        'olt_id',
        'pon_port_id',
        'name',
        'serial_number',
        'ont_id_number',
        'status',
        'rx_power',
        'tx_power',
        'lat',
        'lng',
        'last_online_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'last_online_at' => 'datetime',
            'rx_power' => 'decimal:2',
            'tx_power' => 'decimal:2',
        ];
    }

    public function odp(): BelongsTo
    {
        return $this->belongsTo(Odp::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function olt(): BelongsTo
    {
        return $this->belongsTo(Olt::class);
    }

    public function ponPort(): BelongsTo
    {
        return $this->belongsTo(PonPort::class);
    }
}
