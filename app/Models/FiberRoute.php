<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class FiberRoute extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'name',
        'source_type',
        'source_id',
        'destination_type',
        'destination_id',
        'coordinates',
        'color',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'coordinates' => 'array',
        ];
    }

    public function source(): MorphTo
    {
        return $this->morphTo('source', 'source_type', 'source_id');
    }

    public function destination(): MorphTo
    {
        return $this->morphTo('destination', 'destination_type', 'destination_id');
    }
}
