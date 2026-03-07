<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Odp extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'name',
        'odc_id',
        'lat',
        'lng',
        'address',
        'capacity',
        'used_ports',
        'splitter_ratio',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function odc(): BelongsTo
    {
        return $this->belongsTo(Odc::class);
    }

    public function onts(): HasMany
    {
        return $this->hasMany(Ont::class);
    }
}
