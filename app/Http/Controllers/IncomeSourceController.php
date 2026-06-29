<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\IncomeSource;
use App\Models\Category;
use Inertia\Inertia;
use Inertia\Response;

class IncomeSourceController extends Controller
{
    public function index(): Response
    {
        $sources = IncomeSource::where('user_id', Auth::id())
            ->with('category')
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get()
            ->map(fn ($s) => array_merge($s->toArray(), ['monthly_amount' => $s->monthly_amount]));

        $categories = Category::where(function ($q) {
            $q->where('user_id', Auth::id())->orWhereNull('user_id');
        })->whereIn('type', ['income', 'both'])->orderBy('name')->get();

        $totalMonthly = IncomeSource::where('user_id', Auth::id())
            ->where('is_active', true)
            ->get()
            ->sum('monthly_amount');

        return Inertia::render('IncomeSources/Index', [
            'sources'      => $sources,
            'categories'   => $categories,
            'totalMonthly' => round($totalMonthly, 2),
        ]);
    }

    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'amount'      => 'required|numeric|min:0',
            'frequency'   => 'required|in:weekly,biweekly,monthly,quarterly,annually',
            'description' => 'nullable|string|max:500',
            'color'       => 'nullable|string',
            'category_id' => 'nullable|exists:categories,id',
            'is_active'   => 'boolean',
        ]);
        $validated['user_id'] = Auth::id();
        IncomeSource::create($validated);
        return back()->with('success', 'Income source added!');
    }

    public function update(Request $request, IncomeSource $incomeSource): \Illuminate\Http\RedirectResponse
    {
        abort_if($incomeSource->user_id !== Auth::id(), 403);
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'amount'      => 'required|numeric|min:0',
            'frequency'   => 'required|in:weekly,biweekly,monthly,quarterly,annually',
            'description' => 'nullable|string|max:500',
            'color'       => 'nullable|string',
            'category_id' => 'nullable|exists:categories,id',
            'is_active'   => 'boolean',
        ]);
        $incomeSource->update($validated);
        return back()->with('success', 'Income source updated!');
    }

    public function destroy(IncomeSource $incomeSource): \Illuminate\Http\RedirectResponse
    {
        abort_if($incomeSource->user_id !== Auth::id(), 403);
        $incomeSource->delete();
        return back()->with('success', 'Income source removed!');
    }
}
