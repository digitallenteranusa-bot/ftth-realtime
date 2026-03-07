<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Odc extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'name',
        'lat',
        'lng',
        'address',
        'capacity',
        'used_ports',
        'splitter_ratio',
        'geojson_area',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'geojson_area' => 'array',
        ];
    }

    public function odps(): HasMany
    {
        return $this->hasMany(Odp::class);
    }
}
