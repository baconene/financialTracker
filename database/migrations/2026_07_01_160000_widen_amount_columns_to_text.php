<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('name')->nullable()->change();
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->text('amount')->nullable()->change();
        });

        Schema::table('accounts', function (Blueprint $table) {
            $table->text('balance')->nullable()->change();
        });

        Schema::table('savings_goals', function (Blueprint $table) {
            $table->text('target_amount')->nullable()->change();
            $table->text('current_amount')->nullable()->change();
        });

        Schema::table('loans', function (Blueprint $table) {
            $table->text('principal_amount')->nullable()->change();
            $table->text('remaining_balance')->nullable()->change();
            $table->text('monthly_payment')->nullable()->change();
        });

        Schema::table('bills', function (Blueprint $table) {
            $table->text('amount')->nullable()->change();
        });

        Schema::table('income_sources', function (Blueprint $table) {
            $table->text('amount')->nullable()->change();
        });

        Schema::table('savings_contributions', function (Blueprint $table) {
            $table->text('amount')->nullable()->change();
        });

        Schema::table('loan_payments', function (Blueprint $table) {
            $table->text('amount')->nullable()->change();
            $table->text('principal_portion')->nullable()->change();
            $table->text('interest_portion')->nullable()->change();
        });

        Schema::table('bill_payments', function (Blueprint $table) {
            $table->text('amount')->nullable()->change();
        });

        Schema::table('budgets', function (Blueprint $table) {
            $table->text('total_budget')->nullable()->change();
        });

        Schema::table('budget_categories', function (Blueprint $table) {
            $table->text('allocated_amount')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('name')->nullable(false)->change();
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->decimal('amount', 15, 2)->nullable(false)->change();
        });

        Schema::table('accounts', function (Blueprint $table) {
            $table->decimal('balance', 15, 2)->default(0)->nullable(false)->change();
        });

        Schema::table('savings_goals', function (Blueprint $table) {
            $table->decimal('target_amount', 15, 2)->nullable(false)->change();
            $table->decimal('current_amount', 15, 2)->default(0)->nullable(false)->change();
        });

        Schema::table('loans', function (Blueprint $table) {
            $table->decimal('principal_amount', 15, 2)->nullable(false)->change();
            $table->decimal('remaining_balance', 15, 2)->nullable(false)->change();
            $table->decimal('monthly_payment', 15, 2)->nullable(false)->change();
        });

        Schema::table('bills', function (Blueprint $table) {
            $table->decimal('amount', 15, 2)->nullable(false)->change();
        });

        Schema::table('income_sources', function (Blueprint $table) {
            $table->decimal('amount', 12, 2)->nullable(false)->change();
        });

        Schema::table('savings_contributions', function (Blueprint $table) {
            $table->decimal('amount', 15, 2)->nullable(false)->change();
        });

        Schema::table('loan_payments', function (Blueprint $table) {
            $table->decimal('amount', 15, 2)->nullable(false)->change();
            $table->decimal('principal_portion', 15, 2)->default(0)->nullable(false)->change();
            $table->decimal('interest_portion', 15, 2)->default(0)->nullable(false)->change();
        });

        Schema::table('bill_payments', function (Blueprint $table) {
            $table->decimal('amount', 15, 2)->nullable(false)->change();
        });

        Schema::table('budgets', function (Blueprint $table) {
            $table->decimal('total_budget', 15, 2)->nullable(false)->change();
        });

        Schema::table('budget_categories', function (Blueprint $table) {
            $table->decimal('allocated_amount', 15, 2)->nullable(false)->change();
        });
    }
};
