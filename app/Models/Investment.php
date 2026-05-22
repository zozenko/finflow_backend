<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Investment extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'type',
        'quantity',
        'purchase_date',
    ];

    protected $casts = [
        'quantity' => 'decimal:8',
        'purchase_date' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
