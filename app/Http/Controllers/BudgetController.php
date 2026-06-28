<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Budget;
use App\Models\BudgetCategory;
use App\Models\Category;
use App\Models\Transaction;
use Carbon\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class BudgetController extends Controller
{
    public function index(Request $request): Response
    {
        $user = Auth::user();
        $now = Carbon::now();
        $month = $request->get('month', $now->month);
        $year = $request->get('year', $now->year);

        $budget = Budget::where('user_id', $user->id)
            ->where('month', $month)
            ->where('year', $year)
            ->with(['budgetCategories.category'])
            ->first();

        // Get spending per category for this month
        $categorySpending = Transaction::where('user_id', $user->id)
            ->where('type', 'expense')
            ->whereMonth('transaction_date', $month)
            ->whereYear('transaction_date', $year)
            ->whereNotNull('category_id')
            ->selectRaw('category_id, SUM(amount) as total')
            ->groupBy('category_id')
            ->pluck('total', 'category_id');

        $categories = Category::where('user_id', $user->id)
            ->where('type', '!=', 'income')
            ->get();

        return Inertia::render('Budgets/Index', [
            'budget' => $budget,
            'categories' => $categories,
            'categorySpending' => $categorySpending,
            'month' => (int) $month,
            'year' => (int) $year,
        ]);
    }

    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020',
            'total_budget' => 'required|numeric|min:0',
            'categories' => 'nullable|array',
            'categories.*.category_id' => 'exists:categories,id',
            'categories.*.allocated_amount' => 'numeric|min:0',
        ]);

        $budget = Budget::updateOrCreate(
            ['user_id' => Auth::id(), 'month' => $validated['month'], 'year' => $validated['year']],
            ['name' => $validated['name'], 'total_budget' => $validated['total_budget']]
        );

        if (!empty($validated['categories'])) {
            foreach ($validated['categories'] as $cat) {
                BudgetCategory::updateOrCreate(
                    ['budget_id' => $budget->id, 'category_id' => $cat['category_id']],
                    ['allocated_amount' => $cat['allocated_amount']]
                );
            }
        }

        return back()->with('success', 'Budget saved!');
    }

    public function update(Request $request, Budget $budget): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('update', $budget);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'total_budget' => 'required|numeric|min:0',
        ]);

        $budget->update($validated);
        return back()->with('success', 'Budget updated!');
    }

    public function destroy(Budget $budget): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('delete', $budget);
        $budget->delete();
        return back()->with('success', 'Budget deleted!');
    }

    public function create(): Response
    {
        return Inertia::render('Budgets/Create', [
            'categories' => Category::where('user_id', Auth::id())->get(),
        ]);
    }

    public function show(Budget $budget): Response
    {
        return Inertia::render('Budgets/Show', ['budget' => $budget->load('budgetCategories.category')]);
    }

    public function edit(Budget $budget): Response
    {
        return Inertia::render('Budgets/Edit', [
            'budget' => $budget->load('budgetCategories'),
            'categories' => Category::where('user_id', Auth::id())->get(),
        ]);
    }
}
