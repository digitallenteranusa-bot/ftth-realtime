<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Mikrotik extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'name',
        'host',
        'api_port',
        'api_username',
        'api_password',
        'snmp_community',
        'snmp_port',
        'is_active',
        'location',
        'lat',
        'lng',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'api_password' => 'encrypted',
        ];
    }

    public function pppoeSessions(): HasMany
    {
        return $this->hasMany(PppoeSession::class);
    }

    public function interfaceTraffics(): HasMany
    {
        return $this->hasMany(InterfaceTraffic::class);
    }
}
