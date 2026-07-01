<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Transaction;
use App\Models\Category;
use App\Models\Loan;
use App\Models\Bill;
use App\Models\SavingsGoal;
use App\Models\Account;
use App\Models\FinancialSetting;
use Carbon\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function index(Request $request): Response
    {
        $user  = Auth::user();
        $year  = (int) $request->get('year', Carbon::now()->year);
        $now   = Carbon::now();
        $month = $now->month;

        // Load all transactions for the year once (amount is encrypted; PHP-side aggregation)
        $yearTransactions = Transaction::where('user_id', $user->id)
            ->whereYear('transaction_date', $year)
            ->with('category:id,name,color')
            ->get(['type', 'amount', 'transaction_date', 'category_id']);

        // Monthly income vs expenses for the selected year
        $monthlyData = [];
        for ($m = 1; $m <= 12; $m++) {
            $monthly  = $yearTransactions->filter(fn ($t) => $t->transaction_date->month === $m);
            $income   = (float) $monthly->where('type', 'income')->sum('amount');
            $expenses = (float) $monthly->where('type', 'expense')->sum('amount');
            $monthlyData[] = [
                'month'    => Carbon::createFromDate($year, $m, 1)->format('M'),
                'income'   => $income,
                'expenses' => $expenses,
                'net'      => $income - $expenses,
            ];
        }

        // Category breakdown (selected year expenses) — PHP-side groupBy + sum
        $categoryBreakdown = $yearTransactions
            ->where('type', 'expense')
            ->filter(fn ($t) => $t->category_id !== null)
            ->groupBy('category_id')
            ->map(function ($group, $catId) {
                $first = $group->first();
                return [
                    'category_id' => $catId,
                    'category'    => $first->category?->name ?? 'Uncategorized',
                    'color'       => $first->category?->color ?? '#6B7280',
                    'amount'      => (float) $group->sum('amount'),
                ];
            })
            ->sortByDesc('amount')
            ->values();

        // Savings trend (monthly contributions) — PHP-side sum per month
        $yearContributions = \App\Models\SavingsContribution::where('user_id', $user->id)
            ->whereYear('contribution_date', $year)
            ->get(['amount', 'contribution_date']);

        $savingsTrend = [];
        for ($m = 1; $m <= 12; $m++) {
            $saved = (float) $yearContributions
                ->filter(fn ($c) => $c->contribution_date->month === $m)
                ->sum('amount');
            $savingsTrend[] = ['month' => Carbon::createFromDate($year, $m, 1)->format('M'), 'amount' => $saved];
        }

        // ── Current-month financial health metrics ───────────────────────────
        $currentMonthTxns = Transaction::where('user_id', $user->id)
            ->whereMonth('transaction_date', $month)
            ->whereYear('transaction_date', $now->year)
            ->get(['type', 'amount']);

        $currentIncome   = (float) $currentMonthTxns->where('type', 'income')->sum('amount');
        $currentExpenses = (float) $currentMonthTxns->where('type', 'expense')->sum('amount');

        // Debt-to-income: sum of active loan monthly payments / income × 100
        $activeLoans = Loan::where('user_id', $user->id)->where('status', 'active')->get(['monthly_payment', 'remaining_balance']);
        $monthlyLoanPayments = (float) $activeLoans->sum('monthly_payment');
        $debtToIncome = $currentIncome > 0 ? ($monthlyLoanPayments / $currentIncome) * 100 : 0;

        // Bills stress: (monthly bills + loan payments) / income × 100
        $monthlyBillsTotal = (float) Bill::where('user_id', $user->id)
            ->where('is_active', true)
            ->where('frequency', 'monthly')
            ->get(['amount'])
            ->sum('amount');
        $billsStressScore = $currentIncome > 0 ? (($monthlyBillsTotal + $monthlyLoanPayments) / $currentIncome) * 100 : 0;

        // Savings rate (current month): net / income × 100
        $currentSavingsRate = $currentIncome > 0 ? (($currentIncome - $currentExpenses) / $currentIncome) * 100 : 0;

        // Emergency fund: total savings / avg monthly expenses (last 3 months)
        $totalSavings = (float) SavingsGoal::where('user_id', $user->id)
            ->where('status', 'active')
            ->get(['current_amount'])
            ->sum('current_amount');
        $avgMonthlyExpenses = (float) Transaction::where('user_id', $user->id)
            ->where('type', 'expense')
            ->where('transaction_date', '>=', $now->copy()->subMonths(3)->startOfMonth())
            ->get(['amount'])
            ->sum('amount') / 3;
        $emergencyFundCoverage = $avgMonthlyExpenses > 0 ? $totalSavings / $avgMonthlyExpenses : 0;

        // Total outstanding loans
        $outstandingLoans = (float) $activeLoans->sum('remaining_balance');

        // Financial health score (0-100)
        $healthScore = $this->calculateHealthScore($currentSavingsRate, $debtToIncome, $billsStressScore, $emergencyFundCoverage);

        // User settings
        $settings = FinancialSetting::where('user_id', $user->id)->first();

        // Insights
        $insights = $this->generateInsights(
            $currentIncome, $currentExpenses, $currentSavingsRate,
            $debtToIncome, $billsStressScore, $emergencyFundCoverage,
            $categoryBreakdown->toArray(), $settings,
            $totalSavings, $outstandingLoans
        );

        return Inertia::render('Reports/Index', [
            'monthlyData'           => $monthlyData,
            'categoryBreakdown'     => $categoryBreakdown,
            'savingsTrend'          => $savingsTrend,
            'year'                  => $year,
            'availableYears'        => range(Carbon::now()->year, Carbon::now()->year - 4),
            'healthMetrics' => [
                'currentIncome'          => $currentIncome,
                'currentExpenses'        => $currentExpenses,
                'disposableIncome'       => $currentIncome - $currentExpenses,
                'debtToIncome'           => round($debtToIncome, 1),
                'billsStressScore'       => round($billsStressScore, 1),
                'savingsRate'            => round($currentSavingsRate, 1),
                'emergencyFundCoverage'  => round($emergencyFundCoverage, 1),
                'totalSavings'           => $totalSavings,
                'outstandingLoans'       => $outstandingLoans,
                'monthlyLoanPayments'    => $monthlyLoanPayments,
                'healthScore'            => $healthScore,
            ],
            'insights' => $insights,
            'settings' => $settings,
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
            $emergencyFund >= 6  => 100,
            $emergencyFund >= 3  => 50 + (($emergencyFund - 3) / 3) * 50,
            $emergencyFund >= 1  => 20 + (($emergencyFund - 1) / 2) * 30,
            $emergencyFund > 0   => $emergencyFund * 20,
            default => 0,
        };

        return (int) round(
            $savingsScore * 0.30 +
            $debtScore    * 0.25 +
            $billsScore   * 0.25 +
            $emergencyScore * 0.20
        );
    }

    private function generateInsights(
        float $income, float $expenses, float $savingsRate,
        float $debtToIncome, float $billsStress, float $emergencyFund,
        array $categoryBreakdown, ?FinancialSetting $settings,
        float $totalSavings, float $outstandingLoans
    ): array {
        $insights = [];

        $minRate = $settings?->min_savings_rate ?? 10;
        if ($income > 0 && $savingsRate < $minRate) {
            $shortfall = $income * ($minRate - $savingsRate) / 100;
            $insights[] = ['type' => 'warning', 'icon' => '📉', 'message' => "Your savings rate is " . number_format($savingsRate, 1) . "%, below your " . $minRate . "% target. Save an additional ₱" . number_format($shortfall, 2) . " this month to reach it."];
        }

        $maxDTI = $settings?->max_debt_to_income ?? 35;
        if ($debtToIncome > $maxDTI) {
            $insights[] = ['type' => 'danger', 'icon' => '🏦', 'message' => "Your debt payments consume " . number_format($debtToIncome, 1) . "% of your income — above your " . $maxDTI . "% limit. Consider paying down high-interest loans."];
        } elseif ($debtToIncome > $maxDTI * 0.8) {
            $insights[] = ['type' => 'warning', 'icon' => '🏦', 'message' => "Your debt-to-income ratio (" . number_format($debtToIncome, 1) . "%) is approaching your " . $maxDTI . "% configured limit."];
        }

        $maxBills = $settings?->max_bills_stress_score ?? 50;
        if ($billsStress > $maxBills) {
            $insights[] = ['type' => 'danger', 'icon' => '📋', 'message' => "Fixed expenses & loan payments consume " . number_format($billsStress, 1) . "% of your income. Review subscriptions to reduce your financial stress."];
        }

        $targetMonths = $settings?->emergency_fund_months ?? 6;
        if ($emergencyFund < $targetMonths && $emergencyFund < 3) {
            $insights[] = ['type' => 'danger', 'icon' => '🛡️', 'message' => "You have only " . number_format($emergencyFund, 1) . " months of emergency coverage. Build your fund to " . $targetMonths . " months for financial security."];
        } elseif ($emergencyFund < $targetMonths) {
            $insights[] = ['type' => 'warning', 'icon' => '🛡️', 'message' => "Your emergency fund covers " . number_format($emergencyFund, 1) . " months of expenses. Your target is " . $targetMonths . " months."];
        } else {
            $insights[] = ['type' => 'success', 'icon' => '🛡️', 'message' => "Your emergency fund covers " . number_format($emergencyFund, 1) . " months of expenses — you're well protected."];
        }

        if ($settings?->category_limits) {
            foreach ($categoryBreakdown as $cat) {
                $limit = $settings->category_limits[$cat['category_id']] ?? null;
                if ($limit && $cat['amount'] > $limit) {
                    $pct = (($cat['amount'] - $limit) / $limit) * 100;
                    $insights[] = ['type' => 'warning', 'icon' => '🏷️', 'message' => "You exceeded your " . $cat['category'] . " budget by " . number_format($pct, 0) . "% (₱" . number_format($cat['amount'] - $limit, 2) . " over the ₱" . number_format($limit, 2) . " limit)."];
                }
            }
        }

        if ($settings?->max_monthly_spending && $expenses > $settings->max_monthly_spending) {
            $insights[] = ['type' => 'danger', 'icon' => '💸', 'message' => "Monthly spending of ₱" . number_format($expenses, 2) . " exceeds your ₱" . number_format($settings->max_monthly_spending, 2) . " limit by ₱" . number_format($expenses - $settings->max_monthly_spending, 2) . "."];
        }

        $netFlow = $income - $expenses;
        if ($settings?->desired_net_cash_flow && $netFlow < $settings->desired_net_cash_flow) {
            $insights[] = ['type' => 'warning', 'icon' => '💰', 'message' => "Your net cash flow this month is ₱" . number_format($netFlow, 2) . ", below your desired ₱" . number_format($settings->desired_net_cash_flow, 2) . "."];
        }

        if (empty($insights) || count(array_filter($insights, fn($i) => in_array($i['type'], ['warning', 'danger']))) === 0) {
            $insights[] = ['type' => 'success', 'icon' => '🌟', 'message' => "Your finances look healthy this month! Keep up the great work."];
        }

        return $insights;
    }
}
