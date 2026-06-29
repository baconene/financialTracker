<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('financial_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Expense thresholds
            $table->decimal('max_monthly_spending', 15, 2)->nullable();
            $table->decimal('max_spending_percentage', 5, 2)->nullable();
            $table->json('category_limits')->nullable();
            $table->decimal('spending_warning_threshold', 5, 2)->default(80);

            // Savings thresholds
            $table->decimal('min_monthly_savings', 15, 2)->nullable();
            $table->decimal('desired_savings_rate', 5, 2)->nullable();
            $table->decimal('emergency_fund_months', 5, 2)->default(6);
            $table->decimal('min_remaining_balance', 15, 2)->nullable();

            // Financial health targets
            $table->decimal('max_debt_to_income', 5, 2)->nullable();
            $table->decimal('max_bills_stress_score', 5, 2)->nullable();
            $table->decimal('min_savings_rate', 5, 2)->nullable();
            $table->decimal('desired_net_cash_flow', 15, 2)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_settings');
    }
};
