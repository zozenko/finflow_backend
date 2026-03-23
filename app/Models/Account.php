<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'balance',
        'currency',
        'type',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
    ];

    /**
     * Get the user that owns the account.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all transactions where this account is the primary source.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'account_id');
    }

    /**
     * Get all planned transactions linked to this specific account.
     */
    public function plannedTransactions(): HasMany
    {
        return $this->hasMany(PlannedTransaction::class, 'account_id');
    }

    /**
     * Get incoming transfers where this account is the destination (to_account_id).
     */
    public function incomingTransfers(): HasMany
    {
        return $this->hasMany(Transaction::class, 'to_account_id');
    }

    /**
     * Get outgoing transfers (where type is 'transfer' and this account is the source).
     */
    public function outgoingTransfers(): HasMany
    {
        return $this->hasMany(Transaction::class, 'account_id')
            ->where('type', 'transfer');
    }

    /**
     * Get planned incoming transfers where this account is the destination.
     */
    public function incomingPlannedTransfers(): HasMany
    {
        return $this->hasMany(PlannedTransaction::class, 'to_account_id');
    }
}
