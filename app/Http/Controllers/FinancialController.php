<?php

namespace App\Http\Controllers;

use App\Models\FinancialTransaction;
use App\Models\FinancialCategory;
use App\Models\FinancialBudget;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class FinancialController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'tenant']);
    }

    /**
     * Display the financial dashboard.
     */
    public function index()
    {
        $currentMonth = Carbon::now();
        $startOfMonth = $currentMonth->copy()->startOfMonth();
        $endOfMonth = $currentMonth->copy()->endOfMonth();

        // Get monthly statistics
        $monthlyRevenue = FinancialTransaction::revenue()
            ->completed()
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        $monthlyExpenses = FinancialTransaction::expense()
            ->completed()
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        $monthlyProfit = $monthlyRevenue - $monthlyExpenses;

        // Get recent transactions
        $recentTransactions = FinancialTransaction::with(['category', 'user'])
            ->orderBy('transaction_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Get category breakdown for current month
        $categoryBreakdown = FinancialTransaction::with('category')
            ->completed()
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->get()
            ->groupBy('category.name')
            ->map(function ($transactions) {
                return [
                    'name' => $transactions->first()->category->name,
                    'amount' => $transactions->sum('amount'),
                    'color' => $transactions->first()->category->color,
                    'type' => $transactions->first()->category->type,
                ];
            })
            ->values();

        // Get budget alerts
        $budgetAlerts = FinancialBudget::with('category')
            ->active()
            ->current()
            ->get()
            ->filter(function ($budget) {
                $budget->updateSpentAmount();
                return $budget->isOverAlert();
            });

        return Inertia::render('Financial/Dashboard', [
            'stats' => [
                'monthlyRevenue' => $monthlyRevenue,
                'monthlyExpenses' => $monthlyExpenses,
                'monthlyProfit' => $monthlyProfit,
                'currentMonth' => $currentMonth->format('F Y'),
            ],
            'recentTransactions' => $recentTransactions,
            'categoryBreakdown' => $categoryBreakdown,
            'budgetAlerts' => $budgetAlerts,
        ]);
    }

    /**
     * Display financial reports.
     */
    public function reports(Request $request)
    {
        $period = $request->get('period', 'monthly');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        // Set default date range based on period
        if (!$startDate || !$endDate) {
            switch ($period) {
                case 'weekly':
                    $startDate = Carbon::now()->startOfWeek();
                    $endDate = Carbon::now()->endOfWeek();
                    break;
                case 'yearly':
                    $startDate = Carbon::now()->startOfYear();
                    $endDate = Carbon::now()->endOfYear();
                    break;
                default: // monthly
                    $startDate = Carbon::now()->startOfMonth();
                    $endDate = Carbon::now()->endOfMonth();
                    break;
            }
        }

        // Get transactions for the period
        $transactions = FinancialTransaction::with(['category', 'user'])
            ->completed()
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->orderBy('transaction_date', 'desc')
            ->get();

        // Calculate totals
        $totalRevenue = $transactions->where('type', 'revenue')->sum('amount');
        $totalExpenses = $transactions->where('type', 'expense')->sum('amount');
        $netProfit = $totalRevenue - $totalExpenses;

        // Group by category
        $categoryData = $transactions->groupBy('category.name')->map(function ($categoryTransactions) {
            $category = $categoryTransactions->first()->category;
            return [
                'name' => $category->name,
                'type' => $category->type,
                'color' => $category->color,
                'amount' => $categoryTransactions->sum('amount'),
                'count' => $categoryTransactions->count(),
            ];
        })->values();

        // Monthly trend data (last 12 months)
        $monthlyTrend = collect();
        for ($i = 11; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $monthStart = $month->copy()->startOfMonth();
            $monthEnd = $month->copy()->endOfMonth();

            $monthRevenue = FinancialTransaction::revenue()
                ->completed()
                ->whereBetween('transaction_date', [$monthStart, $monthEnd])
                ->sum('amount');

            $monthExpenses = FinancialTransaction::expense()
                ->completed()
                ->whereBetween('transaction_date', [$monthStart, $monthEnd])
                ->sum('amount');

            $monthlyTrend->push([
                'month' => $month->format('M Y'),
                'revenue' => $monthRevenue,
                'expenses' => $monthExpenses,
                'profit' => $monthRevenue - $monthExpenses,
            ]);
        }

        return Inertia::render('Financial/Reports', [
            'period' => $period,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'totals' => [
                'revenue' => $totalRevenue,
                'expenses' => $totalExpenses,
                'profit' => $netProfit,
            ],
            'transactions' => $transactions,
            'categoryData' => $categoryData,
            'monthlyTrend' => $monthlyTrend,
        ]);
    }
}