<?php

namespace App\Http\Controllers;

use App\Models\FinancialBudget;
use App\Models\FinancialCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class BudgetController extends Controller
{
    public function index(Request $request)
    {
        $query = FinancialBudget::with(['category', 'creator'])
            ->orderBy('created_at', 'desc');

        // Filter by period
        if ($request->filled('period')) {
            $query->where('period_type', $request->period);
        }

        // Filter by status
        if ($request->filled('status')) {
            switch ($request->status) {
                case 'active':
                    $query->active();
                    break;
                case 'exceeded':
                    $query->whereRaw('spent_amount > planned_amount');
                    break;
                case 'warning':
                    $query->whereRaw('(spent_amount / planned_amount) * 100 >= alert_percentage')
                          ->whereRaw('spent_amount <= planned_amount');
                    break;
            }
        }

        // Filter by category
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $budgets = $query->paginate(15)->withQueryString();

        // Get categories for filter
        $categories = FinancialCategory::active()->orderBy('name')->get();

        // Calculate summary stats
        $totalPlanned = FinancialBudget::active()->sum('planned_amount');
        $totalSpent = FinancialBudget::active()->sum('spent_amount');
        $budgetsWithAlerts = FinancialBudget::active()
            ->whereRaw('(spent_amount / planned_amount) * 100 >= alert_percentage')
            ->count();

        return Inertia::render('Financial/Budgets/Index', [
            'budgets' => $budgets,
            'categories' => $categories,
            'filters' => $request->only(['period', 'status', 'category_id']),
            'stats' => [
                'total_planned' => $totalPlanned,
                'total_spent' => $totalSpent,
                'remaining' => $totalPlanned - $totalSpent,
                'alerts_count' => $budgetsWithAlerts,
            ],
        ]);
    }

    public function create()
    {
        $categories = FinancialCategory::active()->orderBy('name')->get();

        return Inertia::render('Financial/Budgets/Create', [
            'categories' => $categories,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'required|exists:financial_categories,id',
            'planned_amount' => 'required|numeric|min:0',
            'period_type' => 'required|in:monthly,quarterly,yearly,custom',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'alert_percentage' => 'required|numeric|min:0|max:100',
            'alert_enabled' => 'boolean',
        ]);

        $validated['created_by'] = auth()->id();
        $validated['alert_enabled'] = $request->boolean('alert_enabled');

        FinancialBudget::create($validated);

        return redirect()->route('financial.budgets.index')
            ->with('success', 'Orçamento criado com sucesso!');
    }

    public function show(FinancialBudget $budget)
    {
        $budget->load(['category', 'creator']);

        // Get transactions for this budget
        $transactions = $budget->category->transactions()
            ->whereBetween('transaction_date', [$budget->start_date, $budget->end_date])
            ->with('user')
            ->orderBy('transaction_date', 'desc')
            ->paginate(10);

        // Calculate monthly spending trend
        $monthlySpending = $budget->category->transactions()
            ->whereBetween('transaction_date', [$budget->start_date, $budget->end_date])
            ->selectRaw('YEAR(transaction_date) as year, MONTH(transaction_date) as month, SUM(amount) as total')
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get()
            ->map(function ($item) {
                return [
                    'period' => Carbon::create($item->year, $item->month)->format('M Y'),
                    'amount' => $item->total,
                ];
            });

        return Inertia::render('Financial/Budgets/Show', [
            'budget' => $budget,
            'transactions' => $transactions,
            'monthly_spending' => $monthlySpending,
        ]);
    }

    public function edit(FinancialBudget $budget)
    {
        $categories = FinancialCategory::active()->orderBy('name')->get();

        return Inertia::render('Financial/Budgets/Edit', [
            'budget' => $budget,
            'categories' => $categories,
        ]);
    }

    public function update(Request $request, FinancialBudget $budget)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'required|exists:financial_categories,id',
            'planned_amount' => 'required|numeric|min:0',
            'period_type' => 'required|in:monthly,quarterly,yearly,custom',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'alert_percentage' => 'required|numeric|min:0|max:100',
            'alert_enabled' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $validated['alert_enabled'] = $request->boolean('alert_enabled');
        $validated['is_active'] = $request->boolean('is_active', true);

        $budget->update($validated);

        return redirect()->route('financial.budgets.index')
            ->with('success', 'Orçamento atualizado com sucesso!');
    }

    public function destroy(FinancialBudget $budget)
    {
        $budget->delete();

        return redirect()->route('financial.budgets.index')
            ->with('success', 'Orçamento excluído com sucesso!');
    }

    public function updateSpent(Request $request, FinancialBudget $budget)
    {
        $budget->updateSpentAmount();

        return response()->json([
            'success' => true,
            'spent_amount' => $budget->fresh()->spent_amount,
            'spent_percentage' => $budget->fresh()->spent_percentage,
        ]);
    }
}