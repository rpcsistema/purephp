<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinancialCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'type',
        'color',
        'icon',
        'is_active',
        'parent_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the parent category.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(FinancialCategory::class, 'parent_id');
    }

    /**
     * Get the child categories.
     */
    public function children(): HasMany
    {
        return $this->hasMany(FinancialCategory::class, 'parent_id');
    }

    /**
     * Get the transactions for this category.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(FinancialTransaction::class, 'category_id');
    }

    /**
     * Get the budgets for this category.
     */
    public function budgets(): HasMany
    {
        return $this->hasMany(FinancialBudget::class, 'category_id');
    }

    /**
     * Scope a query to only include active categories.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include categories of a specific type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope a query to only include parent categories.
     */
    public function scopeParents($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Get the total amount for this category in a date range.
     */
    public function getTotalAmount($startDate = null, $endDate = null)
    {
        $query = $this->transactions()->where('status', 'completed');

        if ($startDate) {
            $query->where('transaction_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('transaction_date', '<=', $endDate);
        }

        return $query->sum('amount');
    }

    /**
     * Check if category has transactions.
     */
    public function hasTransactions(): bool
    {
        return $this->transactions()->exists();
    }
}