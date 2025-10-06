<?php

namespace App\Http\Controllers;

use App\Models\FinancialTransaction;
use App\Models\FinancialCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class TransactionController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'tenant']);
    }

    /**
     * Display a listing of transactions.
     */
    public function index(Request $request)
    {
        $query = FinancialTransaction::with(['category', 'user']);

        // Apply filters
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('start_date')) {
            $query->where('transaction_date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->where('transaction_date', '<=', $request->end_date);
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('description', 'like', '%' . $request->search . '%')
                  ->orWhere('reference_number', 'like', '%' . $request->search . '%')
                  ->orWhere('notes', 'like', '%' . $request->search . '%');
            });
        }

        $transactions = $query->orderBy('transaction_date', 'desc')
                             ->orderBy('created_at', 'desc')
                             ->paginate(20)
                             ->withQueryString();

        $categories = FinancialCategory::active()->orderBy('name')->get();

        return Inertia::render('Financial/Transactions/Index', [
            'transactions' => $transactions,
            'categories' => $categories,
            'filters' => $request->only(['type', 'category_id', 'status', 'start_date', 'end_date', 'search']),
        ]);
    }

    /**
     * Show the form for creating a new transaction.
     */
    public function create()
    {
        $categories = FinancialCategory::active()->orderBy('name')->get();

        return Inertia::render('Financial/Transactions/Create', [
            'categories' => $categories,
        ]);
    }

    /**
     * Store a newly created transaction.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:revenue,expense',
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'transaction_date' => 'required|date',
            'category_id' => 'required|exists:financial_categories,id',
            'status' => 'required|in:pending,completed,cancelled',
            'payment_method' => 'nullable|in:cash,credit_card,debit_card,bank_transfer,pix,other',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|mimes:pdf,jpg,jpeg,png|max:2048',
            'is_recurring' => 'boolean',
            'recurring_frequency' => 'nullable|required_if:is_recurring,true|in:daily,weekly,monthly,yearly',
            'recurring_end_date' => 'nullable|required_if:is_recurring,true|date|after:transaction_date',
        ]);

        // Handle file uploads
        $attachmentPaths = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('transactions', 'public');
                $attachmentPaths[] = $path;
            }
        }

        $validated['attachments'] = $attachmentPaths;
        $validated['user_id'] = Auth::id();

        $transaction = FinancialTransaction::create($validated);

        // Handle recurring transactions
        if ($validated['is_recurring']) {
            $this->createRecurringTransactions($transaction);
        }

        return redirect()->route('transactions.index')
                        ->with('success', 'Transação criada com sucesso!');
    }

    /**
     * Display the specified transaction.
     */
    public function show(FinancialTransaction $transaction)
    {
        $transaction->load(['category', 'user', 'childTransactions']);

        return Inertia::render('Financial/Transactions/Show', [
            'transaction' => $transaction,
        ]);
    }

    /**
     * Show the form for editing the specified transaction.
     */
    public function edit(FinancialTransaction $transaction)
    {
        $categories = FinancialCategory::active()->orderBy('name')->get();

        return Inertia::render('Financial/Transactions/Edit', [
            'transaction' => $transaction,
            'categories' => $categories,
        ]);
    }

    /**
     * Update the specified transaction.
     */
    public function update(Request $request, FinancialTransaction $transaction)
    {
        $validated = $request->validate([
            'type' => 'required|in:revenue,expense',
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'transaction_date' => 'required|date',
            'category_id' => 'required|exists:financial_categories,id',
            'status' => 'required|in:pending,completed,cancelled',
            'payment_method' => 'nullable|in:cash,credit_card,debit_card,bank_transfer,pix,other',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        // Handle file uploads
        if ($request->hasFile('attachments')) {
            // Delete old attachments
            if ($transaction->attachments) {
                foreach ($transaction->attachments as $oldPath) {
                    Storage::disk('public')->delete($oldPath);
                }
            }

            $attachmentPaths = [];
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('transactions', 'public');
                $attachmentPaths[] = $path;
            }
            $validated['attachments'] = $attachmentPaths;
        }

        $transaction->update($validated);

        return redirect()->route('transactions.index')
                        ->with('success', 'Transação atualizada com sucesso!');
    }

    /**
     * Remove the specified transaction.
     */
    public function destroy(FinancialTransaction $transaction)
    {
        // Delete attachments
        if ($transaction->attachments) {
            foreach ($transaction->attachments as $path) {
                Storage::disk('public')->delete($path);
            }
        }

        $transaction->delete();

        return redirect()->route('transactions.index')
                        ->with('success', 'Transação excluída com sucesso!');
    }

    /**
     * Create recurring transactions.
     */
    private function createRecurringTransactions(FinancialTransaction $parentTransaction)
    {
        $currentDate = $parentTransaction->transaction_date;
        $endDate = $parentTransaction->recurring_end_date;
        $frequency = $parentTransaction->recurring_frequency;

        while ($currentDate->lt($endDate)) {
            // Calculate next date based on frequency
            switch ($frequency) {
                case 'daily':
                    $currentDate = $currentDate->addDay();
                    break;
                case 'weekly':
                    $currentDate = $currentDate->addWeek();
                    break;
                case 'monthly':
                    $currentDate = $currentDate->addMonth();
                    break;
                case 'yearly':
                    $currentDate = $currentDate->addYear();
                    break;
            }

            if ($currentDate->lte($endDate)) {
                FinancialTransaction::create([
                    'type' => $parentTransaction->type,
                    'description' => $parentTransaction->description,
                    'amount' => $parentTransaction->amount,
                    'transaction_date' => $currentDate->copy(),
                    'category_id' => $parentTransaction->category_id,
                    'user_id' => $parentTransaction->user_id,
                    'status' => 'pending',
                    'payment_method' => $parentTransaction->payment_method,
                    'notes' => $parentTransaction->notes,
                    'is_recurring' => false,
                    'parent_transaction_id' => $parentTransaction->id,
                ]);
            }
        }
    }
}