<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Group extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'user_id',
        'sort_order',
        'icon_key',
        'color',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'sort_order' => 'integer',
    ];

    /**
     * The accessors to append to the model's array form.
     * This ensures 'effective_budget' is included in JSON responses.
     */
    protected $appends = ['effective_budget'];

    /**
     * Get the effective budget for the group.
     * If categories have their own budgets, their sum takes priority to avoid logical gaps.
     */
    public function getEffectiveBudgetAttribute(): float
    {
        // 1. Sum up all budgets of categories belonging to this group for the current month
        $categoriesTotal = $this->categories()
            ->with(['budgets' => function ($query) {
                $query->where('start_date', '>=', now()->startOfMonth());
            }])
            ->get()
            ->pluck('budgets')
            ->collapse()
            ->sum('amount');

        // 2. If there are category budgets, they define the group's total limit
        if ($categoriesTotal > 0) {
            return (float) $categoriesTotal;
        }

        // 3. Fallback: check if a direct budget was set for the group itself
        $groupBudget = $this->budgets()
            ->where('start_date', '>=', now()->startOfMonth())
            ->first();

        return $groupBudget ? (float) $groupBudget->amount : 0.0;
    }

    public function getCurrentSpendingAttribute(): float
    {
        return $this->transactions()
            ->whereBetween('transaction_date', [now()->startOfMonth(), now()->endOfMonth()])
            ->where('type', 'expense')
            ->sum('amount');
    }

    /**
     * Get the user that owns the group.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the categories associated with this group.
     */
    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    /**
     * Get all of the budgets for the Group.
     */
    public function budgets()
    {
        return $this->hasMany(Budget::class);
    }

    /**
     * Get all real transactions associated with this group.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get all planned transactions associated with this group.
     */
    public function plannedTransactions(): HasMany
    {
        return $this->hasMany(PlannedTransaction::class);
    }
}
