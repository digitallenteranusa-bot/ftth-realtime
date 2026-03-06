<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'phone',
        'email',
        'nik',
        'lat',
        'lng',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'string',
        ];
    }

    public function onts(): HasMany
    {
        return $this->hasMany(Ont::class);
    }

    public function troubleTickets(): HasMany
    {
        return $this->hasMany(TroubleTicket::class);
    }
}
