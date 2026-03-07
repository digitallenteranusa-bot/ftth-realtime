<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Olt extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'name',
        'vendor',
        'host',
        'telnet_port',
        'ssh_port',
        'username',
        'password',
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
            'password' => 'encrypted',
        ];
    }

    public function ponPorts(): HasMany
    {
        return $this->hasMany(PonPort::class);
    }

    public function onts(): HasMany
    {
        return $this->hasMany(Ont::class);
    }
}
