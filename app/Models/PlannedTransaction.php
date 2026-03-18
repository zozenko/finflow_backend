<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlannedTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'account_id',
        'to_account_id',
        'category_id',
        'group_id',
        'title',
        'amount',
        'type',
        'frequency',
        'next_payment_date',
        'is_active',
        'auto_execute',
    ];

    protected $casts = [
        'next_payment_date' => 'date',
        'amount' => 'decimal:2',
        'is_active' => 'boolean',
        'auto_execute' => 'boolean',
    ];

    /**
     * Get the user who owns this planned transaction.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the source account.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    /**
     * Get the group associated with the transaction.
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * Get the destination account (for transfers).
     */
    public function toAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'to_account_id');
    }

    /**
     * Get all transactions generated from this plan.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'planned_transaction_id');
    }

    /**
     * Get the category associated with the transaction.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
