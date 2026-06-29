<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Transaction;
use App\Models\Account;
use App\Models\SavingsGoal;
use App\Models\Loan;
use App\Models\Bill;
use App\Models\FinancialSetting;
use App\Models\IncomeSource;
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

        // Cash flow date range (filterable via query params)
        $cfFromParam = $request->filled('cf_from') ? $request->cf_from : null;
        $cfToParam   = $request->filled('cf_to')   ? $request->cf_to   : null;
        $cfFrom = $cfFromParam
            ? Carbon::parse($cfFromParam . '-01')->startOfMonth()
            : $now->copy()->subMonths(5)->startOfMonth();
        $cfTo   = $cfToParam
            ? Carbon::parse($cfToParam . '-01')->startOfMonth()
            : $now->copy()->startOfMonth();
        // Cap to current month so future months don't appear as "actual"
        if ($cfTo->gt($now->copy()->startOfMonth())) {
            $cfTo = $now->copy()->startOfMonth();
        }

        $cashFlowData = [];
        $cursor = $cfFrom->copy();
        while ($cursor->lte($cfTo)) {
            $income = Transaction::where('user_id', $user->id)
                ->where('type', 'income')
                ->whereMonth('transaction_date', $cursor->month)
                ->whereYear('transaction_date', $cursor->year)
                ->sum('amount');
            $expenses = Transaction::where('user_id', $user->id)
                ->where('type', 'expense')
                ->whereMonth('transaction_date', $cursor->month)
                ->whereYear('transaction_date', $cursor->year)
                ->sum('amount');
            $cashFlowData[] = [
                'month'    => $cursor->format('M Y'),
                'income'   => (float) $income,
                'expenses' => (float) $expenses,
            ];
            $cursor->addMonth();
        }

        // Project next 6 months — bills stay flat, loans decline as they're paid off
        $billsMonthly = 0;
        foreach (Bill::where('user_id', $user->id)->where('is_active', true)->get() as $bill) {
            $billsMonthly += match ($bill->frequency) {
                'weekly'    => $bill->amount * 4.33,
                'biweekly'  => $bill->amount * 2.17,
                'monthly'   => $bill->amount,
                'quarterly' => $bill->amount / 3,
                'annually'  => $bill->amount / 12,
                default     => 0,
            };
        }

        $activeLoans = Loan::where('user_id', $user->id)->where('status', 'active')->get();

        // Prefer user-defined income sources; fall back to 3-month rolling average
        $incomeSourcesMonthly = IncomeSource::where('user_id', $user->id)
            ->where('is_active', true)
            ->get()
            ->sum('monthly_amount');

        if ($incomeSourcesMonthly > 0) {
            $projectedIncome = round($incomeSourcesMonthly, 2);
            $incomeProjectionBasis = 'sources';
        } else {
            $recentIncomeSum = (float) Transaction::where('user_id', $user->id)
                ->where('type', 'income')
                ->where('transaction_date', '>=', $now->copy()->subMonths(3)->startOfMonth())
                ->sum('amount');
            $projectedIncome = round($recentIncomeSum / 3, 2);
            $incomeProjectionBasis = 'average';
        }

        $cashFlowProjection = [];
        for ($i = 1; $i <= 6; $i++) {
            $date = $now->copy()->addMonths($i);
            // Only include a loan's payment if it hasn't been fully paid off by month $i
            $loanPaymentsThisMonth = 0.0;
            foreach ($activeLoans as $loan) {
                if ($loan->monthly_payment <= 0) continue;
                $monthsLeft = (int) ceil($loan->remaining_balance / $loan->monthly_payment);
                if ($i <= $monthsLeft) {
                    $loanPaymentsThisMonth += $loan->monthly_payment;
                }
            }
            $cashFlowProjection[] = [
                'month'    => $date->format('M Y'),
                'income'   => $projectedIncome,
                'expenses' => round($billsMonthly + $loanPaymentsThisMonth, 2),
            ];
        }

        $monthlyIncomeFlt   = (float) $monthlyIncome;
        $monthlyExpensesFlt = (float) $monthlyExpenses;

        // Financial health score
        $monthlyLoanPayments = (float) $activeLoans->sum('monthly_payment');
        $debtToIncome        = $monthlyIncomeFlt > 0 ? ($monthlyLoanPayments / $monthlyIncomeFlt) * 100 : 0;
        $monthlyBillsTotal   = (float) Bill::where('user_id', $user->id)->where('is_active', true)->where('frequency', 'monthly')->sum('amount');
        $billsStress         = $monthlyIncomeFlt > 0 ? (($monthlyBillsTotal + $monthlyLoanPayments) / $monthlyIncomeFlt) * 100 : 0;
        $savingsRate         = $monthlyIncomeFlt > 0 ? (($monthlyIncomeFlt - $monthlyExpensesFlt) / $monthlyIncomeFlt) * 100 : 0;
        $avgMonthlyExpenses  = (float) Transaction::where('user_id', $user->id)->where('type', 'expense')
            ->where('transaction_date', '>=', $now->copy()->subMonths(3)->startOfMonth())
            ->sum('amount') / 3;
        $emergencyFund = $avgMonthlyExpenses > 0 ? (float) $totalSavings / $avgMonthlyExpenses : 0;
        $healthScore   = $this->calculateHealthScore($savingsRate, $debtToIncome, $billsStress, $emergencyFund);

        // Quick insights (top 3)
        $settings = FinancialSetting::where('user_id', $user->id)->first();
        $quickInsights = $this->quickInsights($monthlyIncomeFlt, $monthlyExpensesFlt, $savingsRate, $debtToIncome, $billsStress, $emergencyFund, $settings);

        return Inertia::render('Dashboard', [
            'stats' => [
                'totalBalance'      => (float) $totalBalance,
                'monthlyIncome'     => $monthlyIncomeFlt,
                'monthlyExpenses'   => $monthlyExpensesFlt,
                'totalSavings'      => (float) $totalSavings,
                'netWorth'          => (float) $totalBalance + (float) $totalSavings,
                'disposableIncome'  => $monthlyIncomeFlt - $monthlyExpensesFlt,
                'outstandingLoans'  => (float) Loan::where('user_id', $user->id)->where('status', 'active')->sum('remaining_balance'),
                'upcomingBillsTotal'=> (float) Bill::where('user_id', $user->id)->where('is_active', true)->where('next_due_date', '>=', $now->toDateString())->where('next_due_date', '<=', $now->copy()->addDays(30)->toDateString())->sum('amount'),
                'healthScore'       => $healthScore,
                'savingsRate'       => round($savingsRate, 1),
                'debtToIncome'      => round($debtToIncome, 1),
                'emergencyFundMonths' => round($emergencyFund, 1),
            ],
            'accounts'           => $accounts,
            'savingsGoals'       => $savingsGoals,
            'upcomingBills'      => $upcomingBills,
            'loans'              => $loans,
            'recentTransactions' => $recentTransactions,
            'cashFlowData'            => $cashFlowData,
            'cashFlowProjection'      => $cashFlowProjection,
            'cashFlowRange'           => ['from' => $cfFrom->format('Y-m'), 'to' => $cfTo->format('Y-m')],
            'incomeProjectionBasis'   => $incomeProjectionBasis,
            'incomeProjectionMonthly' => $projectedIncome,
            'quickInsights'      => $quickInsights,
        ]);
    }

    private function calculateHealthScore(float $savingsRate, float $debtToIncome, float $billsStress, float $emergencyFund): int
    {
        $savingsScore = match (true) {
            $savingsRate >= 20 => 100,
            $savingsRate >= 10 => 60 + ($savingsRate - 10) * 4,
            $savingsRate >= 5  => 30 + ($savingsRate - 5) * 6,
            $savingsRate > 0   => $savingsRate * 6,
            default => 0,
        };
        $debtScore = match (true) {
            $debtToIncome <= 15 => 100,
            $debtToIncome <= 35 => 100 - (($debtToIncome - 15) / 20) * 40,
            $debtToIncome <= 50 => 60 - (($debtToIncome - 35) / 15) * 40,
            default => max(0, 20 - ($debtToIncome - 50)),
        };
        $billsScore = match (true) {
            $billsStress <= 30 => 100,
            $billsStress <= 50 => 100 - (($billsStress - 30) / 20) * 40,
            $billsStress <= 70 => 60 - (($billsStress - 50) / 20) * 40,
            default => max(0, 20 - ($billsStress - 70)),
        };
        $emergencyScore = match (true) {
            $emergencyFund >= 6 => 100,
            $emergencyFund >= 3 => 50 + (($emergencyFund - 3) / 3) * 50,
            $emergencyFund >= 1 => 20 + (($emergencyFund - 1) / 2) * 30,
            $emergencyFund > 0  => $emergencyFund * 20,
            default => 0,
        };
        return (int) round($savingsScore * 0.30 + $debtScore * 0.25 + $billsScore * 0.25 + $emergencyScore * 0.20);
    }

    private function quickInsights(float $income, float $expenses, float $savingsRate, float $debtToIncome, float $billsStress, float $emergencyFund, ?FinancialSetting $settings): array
    {
        $insights = [];
        $minRate  = $settings?->min_savings_rate ?? 10;
        $maxDTI   = $settings?->max_debt_to_income ?? 35;
        $maxBills = $settings?->max_bills_stress_score ?? 50;
        $targetEF = $settings?->emergency_fund_months ?? 6;

        if ($income > 0 && $savingsRate < $minRate) {
            $insights[] = ['type' => 'warning', 'icon' => '📉', 'message' => "Savings rate " . number_format($savingsRate, 1) . "% is below your " . $minRate . "% target."];
        }
        if ($debtToIncome > $maxDTI) {
            $insights[] = ['type' => 'danger', 'icon' => '🏦', 'message' => "Debt-to-income " . number_format($debtToIncome, 1) . "% exceeds your " . $maxDTI . "% limit."];
        }
        if ($billsStress > $maxBills) {
            $insights[] = ['type' => 'danger', 'icon' => '📋', 'message' => "Bills stress " . number_format($billsStress, 1) . "% exceeds your " . $maxBills . "% limit."];
        }
        if ($emergencyFund < 3) {
            $insights[] = ['type' => 'danger', 'icon' => '🛡️', 'message' => "Only " . number_format($emergencyFund, 1) . " months emergency coverage. Target: " . $targetEF . " months."];
        }
        if (empty($insights)) {
            $insights[] = ['type' => 'success', 'icon' => '🌟', 'message' => "All financial metrics look healthy this month!"];
        }
        return array_slice($insights, 0, 3);
    }
}

