<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinancialTransaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'type',
        'description',
        'amount',
        'transaction_date',
        'category_id',
        'user_id',
        'status',
        'payment_method',
        'reference_number',
        'notes',
        'attachments',
        'is_recurring',
        'recurring_frequency',
        'recurring_end_date',
        'parent_transaction_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_date' => 'date',
        'recurring_end_date' => 'date',
        'attachments' => 'array',
        'is_recurring' => 'boolean',
    ];

    /**
     * Get the category that owns the transaction.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(FinancialCategory::class, 'category_id');
    }

    /**
     * Get the user that owns the transaction.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the parent transaction (for recurring transactions).
     */
    public function parentTransaction(): BelongsTo
    {
        return $this->belongsTo(FinancialTransaction::class, 'parent_transaction_id');
    }

    /**
     * Get the child transactions (for recurring transactions).
     */
    public function childTransactions(): HasMany
    {
        return $this->hasMany(FinancialTransaction::class, 'parent_transaction_id');
    }

    /**
     * Scope a query to only include completed transactions.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope a query to only include transactions of a specific type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope a query to only include revenue transactions.
     */
    public function scopeRevenue($query)
    {
        return $query->where('type', 'revenue');
    }

    /**
     * Scope a query to only include expense transactions.
     */
    public function scopeExpense($query)
    {
        return $query->where('type', 'expense');
    }

    /**
     * Scope a query to filter by date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }

    /**
     * Scope a query to filter by current month.
     */
    public function scopeCurrentMonth($query)
    {
        return $query->whereMonth('transaction_date', now()->month)
                    ->whereYear('transaction_date', now()->year);
    }

    /**
     * Scope a query to filter by current year.
     */
    public function scopeCurrentYear($query)
    {
        return $query->whereYear('transaction_date', now()->year);
    }

    /**
     * Get the formatted amount with currency symbol.
     */
    public function getFormattedAmountAttribute(): string
    {
        return 'R$ ' . number_format($this->amount, 2, ',', '.');
    }

    /**
     * Get the transaction type label.
     */
    public function getTypeLabel(): string
    {
        return $this->type === 'revenue' ? 'Receita' : 'Despesa';
    }

    /**
     * Get the status label.
     */
    public function getStatusLabel(): string
    {
        return match($this->status) {
            'pending' => 'Pendente',
            'completed' => 'Concluída',
            'cancelled' => 'Cancelada',
            default => 'Desconhecido'
        };
    }

    /**
     * Get the payment method label.
     */
    public function getPaymentMethodLabel(): string
    {
        return match($this->payment_method) {
            'cash' => 'Dinheiro',
            'credit_card' => 'Cartão de Crédito',
            'debit_card' => 'Cartão de Débito',
            'bank_transfer' => 'Transferência Bancária',
            'pix' => 'PIX',
            'other' => 'Outro',
            default => 'Não informado'
        };
    }

    /**
     * Check if transaction is revenue.
     */
    public function isRevenue(): bool
    {
        return $this->type === 'revenue';
    }

    /**
     * Check if transaction is expense.
     */
    public function isExpense(): bool
    {
        return $this->type === 'expense';
    }

    /**
     * Check if transaction is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }
}