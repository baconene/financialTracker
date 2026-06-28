<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\SavingsGoal;
use App\Models\SavingsContribution;
use App\Models\Loan;
use App\Models\LoanPayment;
use App\Models\Bill;
use App\Models\Budget;
use App\Models\BudgetCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $user = User::create([
            'name' => 'Juan dela Cruz',
            'email' => 'juan@example.com',
            'password' => Hash::make('password'),
        ]);

        // --- Categories ---
        $categories = $this->createCategories($user);

        // --- Accounts ---
        $bdo = Account::create(['user_id' => $user->id, 'name' => 'BDO Savings', 'type' => 'bank', 'bank_name' => 'BDO', 'account_number' => '1234-5678-9012', 'balance' => 85000, 'color' => '#2563EB']);
        $gcash = Account::create(['user_id' => $user->id, 'name' => 'GCash', 'type' => 'e-wallet', 'bank_name' => 'Globe', 'balance' => 12500, 'color' => '#0EA5E9']);
        $bpi = Account::create(['user_id' => $user->id, 'name' => 'BPI Checking', 'type' => 'bank', 'bank_name' => 'BPI', 'account_number' => '9876-5432-1098', 'balance' => 45000, 'color' => '#7C3AED']);
        $cash = Account::create(['user_id' => $user->id, 'name' => 'Cash on Hand', 'type' => 'cash', 'balance' => 3500, 'color' => '#10B981']);

        // --- Transactions (50+) ---
        $this->createTransactions($user, $bdo, $gcash, $bpi, $cash, $categories);

        // --- Savings Goals ---
        $emergencyFund = SavingsGoal::create([
            'user_id' => $user->id,
            'name' => 'Emergency Fund',
            'description' => '6 months of living expenses',
            'target_amount' => 150000,
            'current_amount' => 75000,
            'target_date' => Carbon::now()->addMonths(8)->toDateString(),
            'color' => '#10B981',
            'icon' => 'shield-check',
            'image_url' => 'https://images.unsplash.com/photo-1554224155-6726b3ff858f?w=400',
            'status' => 'active',
        ]);

        $laptop = SavingsGoal::create([
            'user_id' => $user->id,
            'name' => 'MacBook Pro',
            'description' => 'New laptop for work',
            'target_amount' => 120000,
            'current_amount' => 48000,
            'target_date' => Carbon::now()->addMonths(5)->toDateString(),
            'color' => '#7C3AED',
            'icon' => 'computer-desktop',
            'image_url' => 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?w=400',
            'status' => 'active',
        ]);

        $vacation = SavingsGoal::create([
            'user_id' => $user->id,
            'name' => 'Japan Trip 2027',
            'description' => 'Family vacation to Japan',
            'target_amount' => 200000,
            'current_amount' => 32000,
            'target_date' => Carbon::parse('2027-03-01')->toDateString(),
            'color' => '#EF4444',
            'icon' => 'map',
            'image_url' => 'https://images.unsplash.com/photo-1490806843957-31f4c9a91c65?w=400',
            'status' => 'active',
        ]);

        // Savings contributions
        $contributions = [
            ['goal' => $emergencyFund, 'amount' => 10000, 'date' => Carbon::now()->subMonths(5)],
            ['goal' => $emergencyFund, 'amount' => 15000, 'date' => Carbon::now()->subMonths(4)],
            ['goal' => $emergencyFund, 'amount' => 10000, 'date' => Carbon::now()->subMonths(3)],
            ['goal' => $emergencyFund, 'amount' => 20000, 'date' => Carbon::now()->subMonths(2)],
            ['goal' => $emergencyFund, 'amount' => 10000, 'date' => Carbon::now()->subMonths(1)],
            ['goal' => $emergencyFund, 'amount' => 10000, 'date' => Carbon::now()],
            ['goal' => $laptop, 'amount' => 16000, 'date' => Carbon::now()->subMonths(3)],
            ['goal' => $laptop, 'amount' => 16000, 'date' => Carbon::now()->subMonths(2)],
            ['goal' => $laptop, 'amount' => 16000, 'date' => Carbon::now()->subMonths(1)],
            ['goal' => $vacation, 'amount' => 8000, 'date' => Carbon::now()->subMonths(4)],
            ['goal' => $vacation, 'amount' => 8000, 'date' => Carbon::now()->subMonths(3)],
            ['goal' => $vacation, 'amount' => 8000, 'date' => Carbon::now()->subMonths(2)],
            ['goal' => $vacation, 'amount' => 8000, 'date' => Carbon::now()->subMonths(1)],
        ];

        foreach ($contributions as $c) {
            SavingsContribution::create([
                'savings_goal_id' => $c['goal']->id,
                'user_id' => $user->id,
                'amount' => $c['amount'],
                'contribution_date' => $c['date']->toDateString(),
            ]);
        }

        // --- Loans ---
        $carLoan = Loan::create([
            'user_id' => $user->id,
            'name' => 'Toyota Vios Car Loan',
            'lender' => 'BDO Auto Loan',
            'principal_amount' => 750000,
            'remaining_balance' => 485000,
            'interest_rate' => 6.5,
            'interest_type' => 'compound',
            'payment_frequency' => 'monthly',
            'monthly_payment' => 14500,
            'term_months' => 60,
            'start_date' => Carbon::now()->subMonths(18)->toDateString(),
            'end_date' => Carbon::now()->addMonths(42)->toDateString(),
            'next_payment_date' => Carbon::now()->addDays(5)->toDateString(),
            'status' => 'active',
        ]);

        $personalLoan = Loan::create([
            'user_id' => $user->id,
            'name' => 'SSS Salary Loan',
            'lender' => 'SSS',
            'principal_amount' => 36000,
            'remaining_balance' => 18000,
            'interest_rate' => 10.0,
            'interest_type' => 'simple',
            'payment_frequency' => 'monthly',
            'monthly_payment' => 3000,
            'term_months' => 12,
            'start_date' => Carbon::now()->subMonths(6)->toDateString(),
            'end_date' => Carbon::now()->addMonths(6)->toDateString(),
            'next_payment_date' => Carbon::now()->addDays(12)->toDateString(),
            'status' => 'active',
        ]);

        // Loan payments
        for ($i = 6; $i >= 1; $i--) {
            LoanPayment::create([
                'loan_id' => $carLoan->id,
                'user_id' => $user->id,
                'amount' => 14500,
                'principal_portion' => 11300,
                'interest_portion' => 3200,
                'payment_date' => Carbon::now()->subMonths($i)->toDateString(),
            ]);
        }

        for ($i = 6; $i >= 1; $i--) {
            LoanPayment::create([
                'loan_id' => $personalLoan->id,
                'user_id' => $user->id,
                'amount' => 3000,
                'principal_portion' => 2700,
                'interest_portion' => 300,
                'payment_date' => Carbon::now()->subMonths($i)->toDateString(),
            ]);
        }

        // --- Bills ---
        $bills = [
            ['name' => 'PLDT Fiber Home', 'amount' => 2799, 'frequency' => 'monthly', 'due_day' => 10, 'next_due_date' => Carbon::now()->day(10)->addMonth()->toDateString(), 'category' => 'Internet', 'payee' => 'PLDT', 'color' => '#7C3AED', 'icon' => 'wifi'],
            ['name' => 'Meralco Electric Bill', 'amount' => 4500, 'frequency' => 'monthly', 'due_day' => 15, 'next_due_date' => Carbon::now()->day(15)->addMonth()->toDateString(), 'category' => 'Utilities', 'payee' => 'Meralco', 'color' => '#F59E0B', 'icon' => 'bolt'],
            ['name' => 'Globe Postpaid', 'amount' => 1499, 'frequency' => 'monthly', 'due_day' => 20, 'next_due_date' => Carbon::now()->day(20)->addMonth()->toDateString(), 'category' => 'Phone', 'payee' => 'Globe', 'color' => '#0EA5E9', 'icon' => 'device-phone-mobile'],
            ['name' => 'Netflix Subscription', 'amount' => 549, 'frequency' => 'monthly', 'due_day' => 5, 'next_due_date' => Carbon::now()->day(5)->addMonth()->toDateString(), 'category' => 'Entertainment', 'payee' => 'Netflix', 'color' => '#EF4444', 'icon' => 'tv'],
            ['name' => 'Condo Monthly Dues', 'amount' => 3200, 'frequency' => 'monthly', 'due_day' => 1, 'next_due_date' => Carbon::now()->day(1)->addMonth()->toDateString(), 'category' => 'Housing', 'payee' => 'SMDC', 'color' => '#6366F1', 'icon' => 'building-office'],
            ['name' => 'PhilHealth Premium', 'amount' => 900, 'frequency' => 'monthly', 'due_day' => 25, 'next_due_date' => Carbon::now()->day(25)->addMonth()->toDateString(), 'category' => 'Insurance', 'payee' => 'PhilHealth', 'color' => '#10B981', 'icon' => 'heart'],
        ];

        foreach ($bills as $bill) {
            Bill::create(array_merge($bill, ['user_id' => $user->id]));
        }

        // --- Budget ---
        $budget = Budget::create([
            'user_id' => $user->id,
            'name' => Carbon::now()->format('F Y') . ' Budget',
            'month' => Carbon::now()->month,
            'year' => Carbon::now()->year,
            'total_budget' => 65000,
        ]);

        $budgetAllocations = [
            'Food & Dining' => 12000,
            'Transportation' => 8000,
            'Utilities' => 10000,
            'Entertainment' => 3000,
            'Healthcare' => 2500,
            'Shopping' => 5000,
            'Personal Care' => 2000,
        ];

        foreach ($budgetAllocations as $catName => $amount) {
            $cat = Category::where('user_id', $user->id)->where('name', $catName)->first();
            if ($cat) {
                BudgetCategory::create([
                    'budget_id' => $budget->id,
                    'category_id' => $cat->id,
                    'allocated_amount' => $amount,
                ]);
            }
        }
    }

    private function createCategories(User $user): array
    {
        $defs = [
            ['name' => 'Salary', 'icon' => 'banknotes', 'color' => '#10B981', 'type' => 'income'],
            ['name' => 'Freelance', 'icon' => 'computer-desktop', 'color' => '#0EA5E9', 'type' => 'income'],
            ['name' => 'Business', 'icon' => 'building-storefront', 'color' => '#7C3AED', 'type' => 'income'],
            ['name' => 'Investment', 'icon' => 'chart-bar', 'color' => '#F59E0B', 'type' => 'income'],
            ['name' => 'Food & Dining', 'icon' => 'cake', 'color' => '#EF4444', 'type' => 'expense'],
            ['name' => 'Transportation', 'icon' => 'truck', 'color' => '#F59E0B', 'type' => 'expense'],
            ['name' => 'Utilities', 'icon' => 'bolt', 'color' => '#6366F1', 'type' => 'expense'],
            ['name' => 'Housing', 'icon' => 'home', 'color' => '#8B5CF6', 'type' => 'expense'],
            ['name' => 'Healthcare', 'icon' => 'heart', 'color' => '#EC4899', 'type' => 'expense'],
            ['name' => 'Shopping', 'icon' => 'shopping-bag', 'color' => '#F97316', 'type' => 'expense'],
            ['name' => 'Entertainment', 'icon' => 'film', 'color' => '#14B8A6', 'type' => 'expense'],
            ['name' => 'Education', 'icon' => 'academic-cap', 'color' => '#3B82F6', 'type' => 'expense'],
            ['name' => 'Personal Care', 'icon' => 'user', 'color' => '#D946EF', 'type' => 'expense'],
            ['name' => 'Savings', 'icon' => 'archive-box', 'color' => '#22C55E', 'type' => 'expense'],
            ['name' => 'Others', 'icon' => 'ellipsis-horizontal', 'color' => '#6B7280', 'type' => 'both'],
        ];

        $result = [];
        foreach ($defs as $def) {
            $cat = Category::create(array_merge($def, ['user_id' => $user->id, 'is_system' => true]));
            $result[$cat->name] = $cat;
        }
        return $result;
    }

    private function createTransactions(User $user, Account $bdo, Account $gcash, Account $bpi, Account $cash, array $categories): void
    {
        $transactions = [];
        $now = Carbon::now();

        // Last 3 months of transactions
        for ($monthOffset = 2; $monthOffset >= 0; $monthOffset--) {
            $month = $now->copy()->subMonths($monthOffset);

            // Monthly salary
            $transactions[] = ['account' => $bdo, 'type' => 'income', 'amount' => 55000, 'description' => 'Monthly Salary - Accenture Philippines', 'category' => 'Salary', 'date' => $month->copy()->day(5)->toDateString()];

            // Freelance
            if ($monthOffset !== 1) {
                $transactions[] = ['account' => $gcash, 'type' => 'income', 'amount' => rand(8000, 15000), 'description' => 'Freelance Web Development Project', 'category' => 'Freelance', 'date' => $month->copy()->day(rand(10, 20))->toDateString()];
            }

            // Food
            $transactions[] = ['account' => $gcash, 'type' => 'expense', 'amount' => 350, 'description' => 'Jollibee - Ortigas', 'category' => 'Food & Dining', 'date' => $month->copy()->day(3)->toDateString()];
            $transactions[] = ['account' => $gcash, 'type' => 'expense', 'amount' => 480, 'description' => 'Mang Inasal - BGC', 'category' => 'Food & Dining', 'date' => $month->copy()->day(6)->toDateString()];
            $transactions[] = ['account' => $gcash, 'type' => 'expense', 'amount' => 1200, 'description' => 'Puregold Grocery Shopping', 'category' => 'Food & Dining', 'date' => $month->copy()->day(8)->toDateString()];
            $transactions[] = ['account' => $cash, 'type' => 'expense', 'amount' => 250, 'description' => 'Carinderia - Lunch', 'category' => 'Food & Dining', 'date' => $month->copy()->day(12)->toDateString()];
            $transactions[] = ['account' => $gcash, 'type' => 'expense', 'amount' => 890, 'description' => 'SM Supermarket Grocery', 'category' => 'Food & Dining', 'date' => $month->copy()->day(15)->toDateString()];
            $transactions[] = ['account' => $gcash, 'type' => 'expense', 'amount' => 650, 'description' => 'Starbucks - Meeting with client', 'category' => 'Food & Dining', 'date' => $month->copy()->day(18)->toDateString()];
            $transactions[] = ['account' => $gcash, 'type' => 'expense', 'amount' => 2100, 'description' => 'Family dinner - Dampa Seaside', 'category' => 'Food & Dining', 'date' => $month->copy()->day(22)->toDateString()];
            $transactions[] = ['account' => $gcash, 'type' => 'expense', 'amount' => 750, 'description' => 'Ministop convenience store', 'category' => 'Food & Dining', 'date' => $month->copy()->day(25)->toDateString()];

            // Transport
            $transactions[] = ['account' => $gcash, 'type' => 'expense', 'amount' => 500, 'description' => 'Shell Gas Station - Fuel', 'category' => 'Transportation', 'date' => $month->copy()->day(4)->toDateString()];
            $transactions[] = ['account' => $gcash, 'type' => 'expense', 'amount' => 450, 'description' => 'Grab - Office commute', 'category' => 'Transportation', 'date' => $month->copy()->day(9)->toDateString()];
            $transactions[] = ['account' => $cash, 'type' => 'expense', 'amount' => 180, 'description' => 'Jeepney & MRT fare', 'category' => 'Transportation', 'date' => $month->copy()->day(14)->toDateString()];
            $transactions[] = ['account' => $gcash, 'type' => 'expense', 'amount' => 500, 'description' => 'Petron Gas Station', 'category' => 'Transportation', 'date' => $month->copy()->day(20)->toDateString()];

            // Utilities
            $transactions[] = ['account' => $bdo, 'type' => 'expense', 'amount' => 4500, 'description' => 'Meralco Electric Bill', 'category' => 'Utilities', 'date' => $month->copy()->day(15)->toDateString()];
            $transactions[] = ['account' => $bdo, 'type' => 'expense', 'amount' => 2799, 'description' => 'PLDT Home Fiber Internet', 'category' => 'Utilities', 'date' => $month->copy()->day(10)->toDateString()];
            $transactions[] = ['account' => $gcash, 'type' => 'expense', 'amount' => 1499, 'description' => 'Globe Postpaid Bill', 'category' => 'Utilities', 'date' => $month->copy()->day(20)->toDateString()];

            // Entertainment
            $transactions[] = ['account' => $gcash, 'type' => 'expense', 'amount' => 549, 'description' => 'Netflix Subscription', 'category' => 'Entertainment', 'date' => $month->copy()->day(5)->toDateString()];
            $transactions[] = ['account' => $gcash, 'type' => 'expense', 'amount' => 800, 'description' => 'SM Cinema - Movie tickets', 'category' => 'Entertainment', 'date' => $month->copy()->day(16)->toDateString()];

            // Shopping
            $transactions[] = ['account' => $bdo, 'type' => 'expense', 'amount' => rand(2000, 5000), 'description' => 'Lazada Online Shopping', 'category' => 'Shopping', 'date' => $month->copy()->day(rand(7, 28))->toDateString()];

            // Healthcare
            if ($monthOffset === 0) {
                $transactions[] = ['account' => $bdo, 'type' => 'expense', 'amount' => 1500, 'description' => 'Checkup - Asian Hospital', 'category' => 'Healthcare', 'date' => $month->copy()->day(11)->toDateString()];
                $transactions[] = ['account' => $gcash, 'type' => 'expense', 'amount' => 650, 'description' => 'Mercury Drug - Medicines', 'category' => 'Healthcare', 'date' => $month->copy()->day(12)->toDateString()];
            }

            // Housing
            $transactions[] = ['account' => $bdo, 'type' => 'expense', 'amount' => 3200, 'description' => 'SMDC Monthly Condo Dues', 'category' => 'Housing', 'date' => $month->copy()->day(1)->toDateString()];

            // Personal Care
            $transactions[] = ['account' => $cash, 'type' => 'expense', 'amount' => 300, 'description' => 'Salon - Haircut', 'category' => 'Personal Care', 'date' => $month->copy()->day(rand(10, 25))->toDateString()];
        }

        foreach ($transactions as $t) {
            $catModel = $categories[$t['category']] ?? null;
            Transaction::create([
                'user_id' => $user->id,
                'account_id' => $t['account']->id,
                'category_id' => $catModel?->id,
                'type' => $t['type'],
                'amount' => $t['amount'],
                'description' => $t['description'],
                'transaction_date' => $t['date'],
            ]);
        }
    }
}

