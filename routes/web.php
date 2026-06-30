<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\SavingsGoalController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\BillController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\FinancialSettingController;
use App\Http\Controllers\IncomeSourceController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\Admin;

// Redirect root to dashboard
Route::get('/', fn() => redirect('/dashboard'));

// Admin routes
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [Admin\AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [Admin\AuthController::class, 'login']);
    Route::middleware(['auth', 'admin'])->group(function () {
        Route::get('/dashboard', [Admin\DashboardController::class, 'index'])->name('dashboard');
        Route::post('/logout', [Admin\AuthController::class, 'logout'])->name('logout');
    });
});

// Auth routes (guest)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

// Auth routes (authenticated)
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Accounts
    Route::resource('accounts', AccountController::class);

    // Transactions
    Route::resource('transactions', TransactionController::class);
    Route::post('/transactions/scan-receipt', [ReceiptController::class, 'scan'])->name('transactions.scan-receipt');

    // Savings Goals
    Route::resource('savings-goals', SavingsGoalController::class);
    Route::post('/savings-goals/{savingsGoal}/contribute', [SavingsGoalController::class, 'contribute'])->name('savings-goals.contribute');

    // Loans
    Route::resource('loans', LoanController::class);
    Route::post('/loans/{loan}/payments', [LoanController::class, 'addPayment'])->name('loans.payments');

    // Bills
    Route::resource('bills', BillController::class);
    Route::post('/bills/{bill}/mark-paid', [BillController::class, 'markPaid'])->name('bills.mark-paid');

    // Budgets
    Route::resource('budgets', BudgetController::class);

    // Reports
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');

    // Income Sources
    Route::resource('income-sources', IncomeSourceController::class)->except(['show', 'create', 'edit']);

    // Onboarding
    Route::get('/onboarding', [OnboardingController::class, 'show'])->name('onboarding');
    Route::post('/onboarding/complete', [OnboardingController::class, 'complete'])->name('onboarding.complete');

    // Financial Settings
    Route::get('/settings/financial', [FinancialSettingController::class, 'show'])->name('settings.financial');
    Route::put('/settings/financial', [FinancialSettingController::class, 'update'])->name('settings.financial.update');
});
