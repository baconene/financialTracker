<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Transaction;
use App\Models\Category;
use Carbon\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function index(Request $request): Response
    {
        $user = Auth::user();
        $year = $request->get('year', Carbon::now()->year);

        // Monthly income vs expenses
        $monthlyData = [];
        for ($m = 1; $m <= 12; $m++) {
            $income = Transaction::where('user_id', $user->id)
                ->where('type', 'income')
                ->whereMonth('transaction_date', $m)
                ->whereYear('transaction_date', $year)
                ->sum('amount');
            $expenses = Transaction::where('user_id', $user->id)
                ->where('type', 'expense')
                ->whereMonth('transaction_date', $m)
                ->whereYear('transaction_date', $year)
                ->sum('amount');
            $monthlyData[] = [
                'month' => Carbon::createFromDate($year, $m, 1)->format('M'),
                'income' => (float) $income,
                'expenses' => (float) $expenses,
                'net' => (float) $income - (float) $expenses,
            ];
        }

        // Category breakdown (current year expenses)
        $categoryBreakdown = Transaction::where('user_id', $user->id)
            ->where('type', 'expense')
            ->whereYear('transaction_date', $year)
            ->whereNotNull('category_id')
            ->selectRaw('category_id, SUM(amount) as total')
            ->groupBy('category_id')
            ->with('category')
            ->get()
            ->map(function ($item) {
                return [
                    'category' => $item->category?->name ?? 'Uncategorized',
                    'color' => $item->category?->color ?? '#6B7280',
                    'amount' => (float) $item->total,
                ];
            })
            ->sortByDesc('amount')
            ->values();

        // Savings trend (monthly savings goal contributions)
        $savingsTrend = [];
        for ($m = 1; $m <= 12; $m++) {
            $saved = \App\Models\SavingsContribution::where('user_id', $user->id)
                ->whereMonth('contribution_date', $m)
                ->whereYear('contribution_date', $year)
                ->sum('amount');
            $savingsTrend[] = [
                'month' => Carbon::createFromDate($year, $m, 1)->format('M'),
                'amount' => (float) $saved,
            ];
        }

        return Inertia::render('Reports/Index', [
            'monthlyData' => $monthlyData,
            'categoryBreakdown' => $categoryBreakdown,
            'savingsTrend' => $savingsTrend,
            'year' => (int) $year,
            'availableYears' => range(Carbon::now()->year, Carbon::now()->year - 4),
        ]);
    }
}
