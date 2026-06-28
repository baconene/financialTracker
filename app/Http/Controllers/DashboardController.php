<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Transaction;
use App\Models\Account;
use App\Models\SavingsGoal;
use App\Models\Loan;
use App\Models\Bill;
use Carbon\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = Auth::user();
        $now = Carbon::now();
        $currentMonth = $now->month;
        $currentYear = $now->year;

        // Total balance across all accounts
        $totalBalance = Account::where('user_id', $user->id)
            ->where('is_active', true)
            ->sum('balance');

        // Current month income & expenses
        $monthlyIncome = Transaction::where('user_id', $user->id)
            ->where('type', 'income')
            ->whereMonth('transaction_date', $currentMonth)
            ->whereYear('transaction_date', $currentYear)
            ->sum('amount');

        $monthlyExpenses = Transaction::where('user_id', $user->id)
            ->where('type', 'expense')
            ->whereMonth('transaction_date', $currentMonth)
            ->whereYear('transaction_date', $currentYear)
            ->sum('amount');

        // Total savings
        $totalSavings = SavingsGoal::where('user_id', $user->id)
            ->where('status', 'active')
            ->sum('current_amount');

        // Accounts
        $accounts = Account::where('user_id', $user->id)
            ->where('is_active', true)
            ->orderBy('balance', 'desc')
            ->get();

        // Savings goals
        $savingsGoals = SavingsGoal::where('user_id', $user->id)
            ->where('status', 'active')
            ->orderBy('created_at', 'desc')
            ->take(4)
            ->get()
            ->map(function ($goal) {
                return array_merge($goal->toArray(), [
                    'progress_percentage' => $goal->progress_percentage,
                ]);
            });

        // Upcoming bills
        $upcomingBills = Bill::where('user_id', $user->id)
            ->where('is_active', true)
            ->where('next_due_date', '>=', $now->toDateString())
            ->orderBy('next_due_date')
            ->take(5)
            ->get();

        // Active loans
        $loans = Loan::where('user_id', $user->id)
            ->where('status', 'active')
            ->orderBy('next_payment_date')
            ->take(3)
            ->get();

        // Recent transactions
        $recentTransactions = Transaction::where('user_id', $user->id)
            ->with(['category', 'account'])
            ->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc')
            ->take(10)
            ->get();

        // Cash flow data (last 6 months)
        $cashFlowData = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = $now->copy()->subMonths($i);
            $income = Transaction::where('user_id', $user->id)
                ->where('type', 'income')
                ->whereMonth('transaction_date', $date->month)
                ->whereYear('transaction_date', $date->year)
                ->sum('amount');
            $expenses = Transaction::where('user_id', $user->id)
                ->where('type', 'expense')
                ->whereMonth('transaction_date', $date->month)
                ->whereYear('transaction_date', $date->year)
                ->sum('amount');
            $cashFlowData[] = [
                'month' => $date->format('M Y'),
                'income' => (float) $income,
                'expenses' => (float) $expenses,
            ];
        }

        return Inertia::render('Dashboard', [
            'stats' => [
                'totalBalance' => (float) $totalBalance,
                'monthlyIncome' => (float) $monthlyIncome,
                'monthlyExpenses' => (float) $monthlyExpenses,
                'totalSavings' => (float) $totalSavings,
                'netWorth' => (float) $totalBalance + (float) $totalSavings,
            ],
            'accounts' => $accounts,
            'savingsGoals' => $savingsGoals,
            'upcomingBills' => $upcomingBills,
            'loans' => $loans,
            'recentTransactions' => $recentTransactions,
            'cashFlowData' => $cashFlowData,
        ]);
    }
}

