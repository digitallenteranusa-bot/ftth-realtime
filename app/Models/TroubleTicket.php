<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TroubleTicket extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'ticket_number',
        'customer_id',
        'title',
        'description',
        'priority',
        'status',
        'assigned_to',
        'resolved_at',
        'closed_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
