<?php

namespace App\Http\Controllers;

use App\Models\FinancialCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'tenant']);
        $this->middleware('can:category.view')->only(['index', 'show']);
        $this->middleware('can:category.create')->only(['create', 'store']);
        $this->middleware('can:category.edit')->only(['edit', 'update']);
        $this->middleware('can:category.delete')->only(['destroy']);
    }

    /**
     * Display a listing of categories.
     */
    public function index(Request $request)
    {
        $query = FinancialCategory::query();

        // Apply filters
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        $categories = $query->with(['parent', 'children'])
                           ->orderBy('type')
                           ->orderBy('name')
                           ->paginate(20)
                           ->withQueryString();

        return Inertia::render('Financial/Categories/Index', [
            'categories' => $categories,
            'filters' => $request->only(['type', 'search']),
        ]);
    }

    /**
     * Show the form for creating a new category.
     */
    public function create()
    {
        $parentCategories = FinancialCategory::parents()->active()->orderBy('name')->get();

        return Inertia::render('Financial/Categories/Create', [
            'parentCategories' => $parentCategories,
        ]);
    }

    /**
     * Store a newly created category.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:financial_categories,name',
            'description' => 'nullable|string|max:500',
            'type' => 'required|in:revenue,expense',
            'color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'icon' => 'nullable|string|max:50',
            'parent_id' => 'nullable|exists:financial_categories,id',
            'is_active' => 'boolean',
        ]);

        // Validate parent category type matches
        if ($validated['parent_id']) {
            $parent = FinancialCategory::find($validated['parent_id']);
            if ($parent->type !== $validated['type']) {
                return back()->withErrors([
                    'parent_id' => 'A categoria pai deve ser do mesmo tipo (receita/despesa).'
                ]);
            }
        }

        FinancialCategory::create($validated);

        return redirect()->route('categories.index')
                        ->with('success', 'Categoria criada com sucesso!');
    }

    /**
     * Display the specified category.
     */
    public function show(FinancialCategory $category)
    {
        $category->load(['parent', 'children', 'transactions' => function ($query) {
            $query->with('user')->orderBy('transaction_date', 'desc')->limit(10);
        }]);

        // Get category statistics
        $totalAmount = $category->getTotalAmount();
        $currentMonthAmount = $category->getTotalAmount(
            now()->startOfMonth(),
            now()->endOfMonth()
        );
        $transactionCount = $category->transactions()->count();

        return Inertia::render('Financial/Categories/Show', [
            'category' => $category,
            'stats' => [
                'totalAmount' => $totalAmount,
                'currentMonthAmount' => $currentMonthAmount,
                'transactionCount' => $transactionCount,
            ],
        ]);
    }

    /**
     * Show the form for editing the specified category.
     */
    public function edit(FinancialCategory $category)
    {
        $parentCategories = FinancialCategory::parents()
                                           ->active()
                                           ->where('id', '!=', $category->id)
                                           ->where('type', $category->type)
                                           ->orderBy('name')
                                           ->get();

        return Inertia::render('Financial/Categories/Edit', [
            'category' => $category,
            'parentCategories' => $parentCategories,
        ]);
    }

    /**
     * Update the specified category.
     */
    public function update(Request $request, FinancialCategory $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:financial_categories,name,' . $category->id,
            'description' => 'nullable|string|max:500',
            'type' => 'required|in:revenue,expense',
            'color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'icon' => 'nullable|string|max:50',
            'parent_id' => 'nullable|exists:financial_categories,id',
            'is_active' => 'boolean',
        ]);

        // Validate parent category type matches
        if ($validated['parent_id']) {
            $parent = FinancialCategory::find($validated['parent_id']);
            if ($parent->type !== $validated['type']) {
                return back()->withErrors([
                    'parent_id' => 'A categoria pai deve ser do mesmo tipo (receita/despesa).'
                ]);
            }

            // Prevent circular reference
            if ($validated['parent_id'] == $category->id) {
                return back()->withErrors([
                    'parent_id' => 'Uma categoria não pode ser pai de si mesma.'
                ]);
            }
        }

        $category->update($validated);

        return redirect()->route('categories.index')
                        ->with('success', 'Categoria atualizada com sucesso!');
    }

    /**
     * Remove the specified category.
     */
    public function destroy(FinancialCategory $category)
    {
        // Check if category has transactions
        if ($category->hasTransactions()) {
            return back()->withErrors([
                'category' => 'Não é possível excluir uma categoria que possui transações associadas.'
            ]);
        }

        // Check if category has child categories
        if ($category->children()->exists()) {
            return back()->withErrors([
                'category' => 'Não é possível excluir uma categoria que possui subcategorias.'
            ]);
        }

        $category->delete();

        return redirect()->route('categories.index')
                        ->with('success', 'Categoria excluída com sucesso!');
    }

    /**
     * Get categories by type (AJAX endpoint).
     */
    public function getByType(Request $request)
    {
        $type = $request->get('type');
        
        $categories = FinancialCategory::active()
                                     ->where('type', $type)
                                     ->orderBy('name')
                                     ->get(['id', 'name', 'color', 'icon']);

        return response()->json($categories);
    }
}