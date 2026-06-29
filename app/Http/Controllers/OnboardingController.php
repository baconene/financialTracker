<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\IncomeSource;
use App\Models\Account;
use App\Models\Loan;
use App\Models\Bill;
use App\Models\FinancialSetting;
use App\Models\Transaction;
use App\Models\Category;
use Inertia\Inertia;
use Inertia\Response;

class OnboardingController extends Controller
{
    public function show(): Response
    {
        $user = Auth::user();
        return Inertia::render('Onboarding/Index', [
            'categories' => Category::where('user_id', $user->id)->get(),
            'user'       => $user,
        ]);
    }

    public function complete(Request $request): \Illuminate\Http\RedirectResponse
    {
        $user = Auth::user();

        // Income sources
        foreach ($request->input('income_sources', []) as $src) {
            if (empty($src['name']) || !isset($src['amount'])) continue;
            IncomeSource::create([
                'user_id'   => $user->id,
                'name'      => $src['name'],
                'amount'    => (float) $src['amount'],
                'frequency' => $src['frequency'] ?? 'monthly',
                'color'     => $src['color'] ?? '#10B981',
                'is_active' => true,
            ]);
        }

        // Accounts with opening balance transactions
        foreach ($request->input('accounts', []) as $acc) {
            if (empty($acc['name'])) continue;
            $balance = (float) ($acc['balance'] ?? 0);
            $account = Account::create([
                'user_id'   => $user->id,
                'name'      => $acc['name'],
                'type'      => $acc['type'] ?? 'bank',
                'bank_name' => $acc['bank_name'] ?? null,
                'balance'   => $balance,
                'color'     => $acc['color'] ?? '#7C3AED',
                'currency'  => 'PHP',
            ]);
            if ($balance != 0) {
                Transaction::create([
                    'user_id'          => $user->id,
                    'account_id'       => $account->id,
                    'type'             => $balance > 0 ? 'income' : 'expense',
                    'amount'           => abs($balance),
                    'description'      => 'Opening Balance',
                    'transaction_date' => now()->toDateString(),
                ]);
            }
        }

        // Loans
        foreach ($request->input('loans', []) as $loan) {
            if (empty($loan['name'])) continue;
            $principal = (float) ($loan['principal_amount'] ?? 0);
            $remaining = (float) ($loan['remaining_balance'] ?? $principal);
            Loan::create([
                'user_id'           => $user->id,
                'name'              => $loan['name'],
                'lender'            => $loan['lender'] ?? 'Unknown',
                'principal_amount'  => $principal,
                'remaining_balance' => $remaining,
                'interest_rate'     => (float) ($loan['interest_rate'] ?? 0),
                'interest_type'     => $loan['interest_type'] ?? 'compound',
                'payment_frequency' => $loan['payment_frequency'] ?? 'monthly',
                'monthly_payment'   => (float) ($loan['monthly_payment'] ?? 0),
                'term_months'       => (int) ($loan['term_months'] ?? 12),
                'start_date'        => $loan['start_date'] ?? now()->toDateString(),
                'end_date'          => $loan['end_date'] ?? now()->addYear()->toDateString(),
                'next_payment_date' => $loan['next_payment_date'] ?? null,
                'status'            => 'active',
            ]);
        }

        // Bills
        foreach ($request->input('bills', []) as $bill) {
            if (empty($bill['name'])) continue;
            Bill::create([
                'user_id'       => $user->id,
                'name'          => $bill['name'],
                'amount'        => (float) ($bill['amount'] ?? 0),
                'frequency'     => $bill['frequency'] ?? 'monthly',
                'next_due_date' => $bill['next_due_date'] ?? now()->addMonth()->toDateString(),
                'color'         => $bill['color'] ?? '#7C3AED',
                'icon'          => 'bill',
                'is_active'     => true,
            ]);
        }

        // Financial settings
        $s = $request->input('settings', []);
        FinancialSetting::updateOrCreate(
            ['user_id' => $user->id],
            [
                'emergency_fund_months'      => (float) ($s['emergency_fund_months'] ?? 6),
                'min_savings_rate'           => (float) ($s['min_savings_rate'] ?? 10),
                'max_debt_to_income'         => (float) ($s['max_debt_to_income'] ?? 35),
                'spending_warning_threshold' => (float) ($s['spending_warning_threshold'] ?? 80),
                'max_bills_stress_score'     => (float) ($s['max_bills_stress_score'] ?? 50),
            ]
        );

        return redirect('/dashboard')->with('success', 'Welcome! Your financial profile is all set up.');
    }
}
