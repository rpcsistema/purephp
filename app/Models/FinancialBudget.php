<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialBudget extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'category_id',
        'planned_amount',
        'spent_amount',
        'period_type',
        'start_date',
        'end_date',
        'is_active',
        'alert_percentage',
        'alert_enabled',
        'created_by',
    ];

    protected $casts = [
        'planned_amount' => 'decimal:2',
        'spent_amount' => 'decimal:2',
        'alert_percentage' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
        'alert_enabled' => 'boolean',
    ];

    /**
     * Get the category that owns the budget.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(FinancialCategory::class, 'category_id');
    }

    /**
     * Get the user who created the budget.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope a query to only include active budgets.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include current budgets.
     */
    public function scopeCurrent($query)
    {
        $now = now();
        return $query->where('start_date', '<=', $now)
                    ->where('end_date', '>=', $now);
    }

    /**
     * Scope a query to filter by period type.
     */
    public function scopeOfPeriod($query, $periodType)
    {
        return $query->where('period_type', $periodType);
    }

    /**
     * Get the remaining amount.
     */
    public function getRemainingAmountAttribute(): float
    {
        return $this->planned_amount - $this->spent_amount;
    }

    /**
     * Get the spent percentage.
     */
    public function getSpentPercentageAttribute(): float
    {
        if ($this->planned_amount == 0) {
            return 0;
        }

        return ($this->spent_amount / $this->planned_amount) * 100;
    }

    /**
     * Get the formatted planned amount.
     */
    public function getFormattedPlannedAmountAttribute(): string
    {
        return 'R$ ' . number_format($this->planned_amount, 2, ',', '.');
    }

    /**
     * Get the formatted spent amount.
     */
    public function getFormattedSpentAmountAttribute(): string
    {
        return 'R$ ' . number_format($this->spent_amount, 2, ',', '.');
    }

    /**
     * Get the formatted remaining amount.
     */
    public function getFormattedRemainingAmountAttribute(): string
    {
        return 'R$ ' . number_format($this->remaining_amount, 2, ',', '.');
    }

    /**
     * Get the period type label.
     */
    public function getPeriodTypeLabel(): string
    {
        return match($this->period_type) {
            'monthly' => 'Mensal',
            'quarterly' => 'Trimestral',
            'yearly' => 'Anual',
            default => 'Desconhecido'
        };
    }

    /**
     * Check if budget is over the alert threshold.
     */
    public function isOverAlert(): bool
    {
        return $this->alert_enabled && $this->spent_percentage >= $this->alert_percentage;
    }

    /**
     * Check if budget is exceeded.
     */
    public function isExceeded(): bool
    {
        return $this->spent_amount > $this->planned_amount;
    }

    /**
     * Check if budget is current (within date range).
     */
    public function isCurrent(): bool
    {
        $now = now();
        return $now->between($this->start_date, $this->end_date);
    }

    /**
     * Update spent amount based on transactions.
     */
    public function updateSpentAmount(): void
    {
        $spentAmount = FinancialTransaction::where('category_id', $this->category_id)
            ->where('type', 'expense')
            ->where('status', 'completed')
            ->whereBetween('transaction_date', [$this->start_date, $this->end_date])
            ->sum('amount');

        $this->update(['spent_amount' => $spentAmount]);
    }

    /**
     * Get budget status color.
     */
    public function getStatusColor(): string
    {
        if ($this->isExceeded()) {
            return 'red';
        }

        if ($this->isOverAlert()) {
            return 'yellow';
        }

        return 'green';
    }
}