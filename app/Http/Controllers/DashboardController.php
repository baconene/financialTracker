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

        // Load all active accounts once; sort by balance in PHP (balance is encrypted)
        $allActiveAccounts = Account::where('user_id', $user->id)
            ->where('is_active', true)
            ->get();

        $totalBalance = (float) $allActiveAccounts->sum('balance');
        $accounts = $allActiveAccounts->sortByDesc('balance')->values();

        // Current month income & expenses (PHP-side sum; amount is encrypted)
        $monthlyIncome = (float) Transaction::where('user_id', $user->id)
            ->where('type', 'income')
            ->whereMonth('transaction_date', $currentMonth)
            ->whereYear('transaction_date', $currentYear)
            ->get(['amount'])
            ->sum('amount');

        $monthlyExpenses = (float) Transaction::where('user_id', $user->id)
            ->where('type', 'expense')
            ->whereMonth('transaction_date', $currentMonth)
            ->whereYear('transaction_date', $currentYear)
            ->get(['amount'])
            ->sum('amount');

        // Total savings
        $totalSavings = (float) SavingsGoal::where('user_id', $user->id)
            ->where('status', 'active')
            ->get(['current_amount'])
            ->sum('current_amount');

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
        if ($cfTo->gt($now->copy()->startOfMonth())) {
            $cfTo = $now->copy()->startOfMonth();
        }

        // Load all transactions in the cash flow date range once
        $cfTransactions = Transaction::where('user_id', $user->id)
            ->whereBetween('transaction_date', [
                $cfFrom->toDateString(),
                $cfTo->copy()->endOfMonth()->toDateString(),
            ])
            ->get(['type', 'amount', 'transaction_date']);

        $cashFlowData = [];
        $cursor = $cfFrom->copy();
        while ($cursor->lte($cfTo)) {
            $monthly  = $cfTransactions->filter(
                fn ($t) => $t->transaction_date->month === $cursor->month
                        && $t->transaction_date->year  === $cursor->year
            );
            $income   = (float) $monthly->where('type', 'income')->sum('amount');
            $expenses = (float) $monthly->where('type', 'expense')->sum('amount');
            $cashFlowData[] = [
                'month'    => $cursor->format('M Y'),
                'income'   => $income,
                'expenses' => $expenses,
            ];
            $cursor->addMonth();
        }

        // ── Projection data ──────────────────────────────────────────────
        $activeBills = Bill::where('user_id', $user->id)->where('is_active', true)->get();
        $activeLoans = Loan::where('user_id', $user->id)->where('status', 'active')->get();

        // Income: prefer income sources, fall back to 3-month rolling average
        $activeIncomeSources  = IncomeSource::where('user_id', $user->id)->where('is_active', true)->get();
        $incomeSourcesMonthly = (float) $activeIncomeSources->sum('monthly_amount');

        if ($incomeSourcesMonthly > 0) {
            $projectedIncome       = round($incomeSourcesMonthly, 2);
            $incomeProjectionBasis = 'sources';
            $incomeSourcesMatrix   = $activeIncomeSources->map(fn($s) => [
                'name'      => $s->name,
                'color'     => $s->color,
                'frequency' => $s->frequency,
                'months'    => array_fill(0, 6, round((float) $s->monthly_amount, 2)),
            ])->values()->toArray();
        } else {
            $recentIncomeSum = (float) Transaction::where('user_id', $user->id)
                ->where('type', 'income')
                ->where('transaction_date', '>=', $now->copy()->subMonths(3)->startOfMonth())
                ->get(['amount'])
                ->sum('amount');
            $projectedIncome       = round($recentIncomeSum / 3, 2);
            $incomeProjectionBasis = 'average';
            $incomeSourcesMatrix   = [[
                'name'      => '3-month Average',
                'color'     => '#10B981',
                'frequency' => 'monthly',
                'months'    => array_fill(0, 6, $projectedIncome),
            ]];
        }

        // Bills: per-bill monthly amounts + row matrix for breakdown table
        $billPaymentsByMonth = array_fill(1, 6, 0.0);
        $billsMatrix = [];
        foreach ($activeBills as $bill) {
            $rowAmounts = [];
            for ($j = 1; $j <= 6; $j++) {
                $amt = $this->billAmountForMonth(
                    $now->copy()->addMonths($j)->startOfMonth(),
                    $now->copy()->addMonths($j)->endOfMonth(),
                    $bill
                );
                $rowAmounts[]            = round($amt, 2);
                $billPaymentsByMonth[$j] += $amt;
            }
            if (array_sum($rowAmounts) > 0) {
                $billsMatrix[] = [
                    'name'      => $bill->name,
                    'color'     => $bill->color,
                    'frequency' => $bill->frequency,
                    'months'    => $rowAmounts,
                ];
            }
        }

        // Loans: simulate schedule, collect per-loan amounts for breakdown table
        $loanPaymentsByMonth = array_fill(1, 6, 0.0);
        $loanMonthlyDetail   = [];
        foreach ($activeLoans as $loan) {
            $loanMonthlyDetail[$loan->id] = array_fill(0, 6, 0.0);
            if (!$loan->next_payment_date || $loan->monthly_payment <= 0) continue;
            $nextDate         = $loan->next_payment_date->copy();
            $remainingBalance = $loan->remaining_balance;
            $safetyCount      = 0;
            while ($remainingBalance > 0.01 && $safetyCount < 500) {
                $safetyCount++;
                $monthDiff = ($nextDate->year - $now->year) * 12 + ($nextDate->month - $now->month);
                if ($monthDiff > 6) break;
                if ($monthDiff >= 1) {
                    $payment = min($loan->monthly_payment, $remainingBalance);
                    $loanPaymentsByMonth[$monthDiff]               += $payment;
                    $loanMonthlyDetail[$loan->id][$monthDiff - 1] += $payment;
                }
                $remainingBalance -= $loan->monthly_payment;
                if ($remainingBalance < 0) $remainingBalance = 0;
                $nextDate = $this->advanceDateByFrequency($nextDate, $loan->payment_frequency);
            }
        }
        $loansMatrix = [];
        foreach ($activeLoans as $loan) {
            $rowAmounts = array_map(fn($a) => round($a, 2), $loanMonthlyDetail[$loan->id]);
            if (array_sum($rowAmounts) > 0) {
                $loansMatrix[] = [
                    'name'              => $loan->name,
                    'lender'            => $loan->lender,
                    'payment_frequency' => $loan->payment_frequency,
                    'months'            => $rowAmounts,
                ];
            }
        }

        // Build projection series + aggregated breakdown arrays
        $cashFlowProjection = [];
        $projectionMonths   = [];
        $totalBillsArr      = [];
        $totalLoansArr      = [];
        $totalExpensesArr   = [];
        $netSavingsArr      = [];
        for ($i = 1; $i <= 6; $i++) {
            $date     = $now->copy()->addMonths($i);
            $bills    = round($billPaymentsByMonth[$i] ?? 0, 2);
            $loansAmt = round($loanPaymentsByMonth[$i] ?? 0, 2);
            $expenses = round($bills + $loansAmt, 2);
            $cashFlowProjection[] = [
                'month'    => $date->format('M Y'),
                'income'   => $projectedIncome,
                'expenses' => $expenses,
            ];
            $projectionMonths[] = $date->format('M Y');
            $totalBillsArr[]    = $bills;
            $totalLoansArr[]    = $loansAmt;
            $totalExpensesArr[] = $expenses;
            $netSavingsArr[]    = round($projectedIncome - $expenses, 2);
        }

        $cashFlowBreakdown = [
            'months'         => $projectionMonths,
            'income_sources' => $incomeSourcesMatrix,
            'total_income'   => array_fill(0, 6, $projectedIncome),
            'bills'          => $billsMatrix,
            'total_bills'    => $totalBillsArr,
            'loans'          => $loansMatrix,
            'total_loans'    => $totalLoansArr,
            'total_expenses' => $totalExpensesArr,
            'net_savings'    => $netSavingsArr,
        ];

        $monthlyIncomeFlt   = $monthlyIncome;
        $monthlyExpensesFlt = $monthlyExpenses;

        // Financial health score (PHP-side sums; all amount fields are encrypted)
        $monthlyLoanPayments = (float) $activeLoans->sum('monthly_payment');
        $debtToIncome        = $monthlyIncomeFlt > 0 ? ($monthlyLoanPayments / $monthlyIncomeFlt) * 100 : 0;
        $monthlyBillsTotal   = (float) Bill::where('user_id', $user->id)
            ->where('is_active', true)
            ->where('frequency', 'monthly')
            ->get(['amount'])
            ->sum('amount');
        $billsStress  = $monthlyIncomeFlt > 0 ? (($monthlyBillsTotal + $monthlyLoanPayments) / $monthlyIncomeFlt) * 100 : 0;
        $savingsRate  = $monthlyIncomeFlt > 0 ? (($monthlyIncomeFlt - $monthlyExpensesFlt) / $monthlyIncomeFlt) * 100 : 0;
        $avgMonthlyExpenses = (float) Transaction::where('user_id', $user->id)
            ->where('type', 'expense')
            ->where('transaction_date', '>=', $now->copy()->subMonths(3)->startOfMonth())
            ->get(['amount'])
            ->sum('amount') / 3;
        $emergencyFund = $avgMonthlyExpenses > 0 ? (float) $totalSavings / $avgMonthlyExpenses : 0;
        $healthScore   = $this->calculateHealthScore($savingsRate, $debtToIncome, $billsStress, $emergencyFund);

        $outstandingLoans = (float) Loan::where('user_id', $user->id)
            ->where('status', 'active')
            ->get(['remaining_balance'])
            ->sum('remaining_balance');

        $upcomingBillsTotal = (float) Bill::where('user_id', $user->id)
            ->where('is_active', true)
            ->where('next_due_date', '>=', $now->toDateString())
            ->where('next_due_date', '<=', $now->copy()->addDays(30)->toDateString())
            ->get(['amount'])
            ->sum('amount');

        // Quick insights (top 3)
        $settings = FinancialSetting::where('user_id', $user->id)->first();
        $quickInsights = $this->quickInsights($monthlyIncomeFlt, $monthlyExpensesFlt, $savingsRate, $debtToIncome, $billsStress, $emergencyFund, $settings);

        return Inertia::render('Dashboard', [
            'stats' => [
                'totalBalance'        => $totalBalance,
                'monthlyIncome'       => $monthlyIncomeFlt,
                'monthlyExpenses'     => $monthlyExpensesFlt,
                'totalSavings'        => $totalSavings,
                'netWorth'            => $totalBalance + $totalSavings,
                'disposableIncome'    => $monthlyIncomeFlt - $monthlyExpensesFlt,
                'outstandingLoans'    => $outstandingLoans,
                'upcomingBillsTotal'  => $upcomingBillsTotal,
                'healthScore'         => $healthScore,
                'savingsRate'         => round($savingsRate, 1),
                'debtToIncome'        => round($debtToIncome, 1),
                'emergencyFundMonths' => round($emergencyFund, 1),
            ],
            'accounts'           => $accounts,
            'savingsGoals'       => $savingsGoals,
            'upcomingBills'      => $upcomingBills,
            'loans'              => $loans,
            'recentTransactions' => $recentTransactions,
            'cashFlowData'            => $cashFlowData,
            'cashFlowProjection'      => $cashFlowProjection,
            'cashFlowBreakdown'       => $cashFlowBreakdown,
            'cashFlowRange'           => ['from' => $cfFrom->format('Y-m'), 'to' => $cfTo->format('Y-m')],
            'incomeProjectionBasis'   => $incomeProjectionBasis,
            'incomeProjectionMonthly' => $projectedIncome,
            'quickInsights'      => $quickInsights,
        ]);
    }

    private function advanceDateByFrequency(Carbon $date, string $frequency): Carbon
    {
        return match ($frequency) {
            'weekly'    => $date->copy()->addWeek(),
            'biweekly'  => $date->copy()->addWeeks(2),
            'monthly'   => $date->copy()->addMonth(),
            'quarterly' => $date->copy()->addMonths(3),
            'annually'  => $date->copy()->addYear(),
            default     => $date->copy()->addMonth(),
        };
    }

    private function billAmountForMonth(Carbon $monthStart, Carbon $monthEnd, $bill): float
    {
        $freq     = $bill->frequency;
        $nextDate = $bill->next_due_date->copy();

        if ($freq === 'one-time') {
            return $nextDate->between($monthStart, $monthEnd) ? (float) $bill->amount : 0.0;
        }

        $safetyA = 0;
        while ($nextDate->lt($monthStart) && $safetyA < 1000) {
            $safetyA++;
            $nextDate = $this->advanceDateByFrequency($nextDate, $freq);
        }

        $total   = 0.0;
        $safetyB = 0;
        while ($nextDate->lte($monthEnd) && $safetyB < 10) {
            $safetyB++;
            $total   += (float) $bill->amount;
            $nextDate = $this->advanceDateByFrequency($nextDate, $freq);
        }

        return $total;
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
