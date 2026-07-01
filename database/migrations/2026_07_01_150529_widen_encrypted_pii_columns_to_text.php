<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Widen all columns that store encrypted PII from VARCHAR to TEXT.
     * Encrypted ciphertext is significantly longer than the original string,
     * so VARCHAR(255) is too narrow once encryption is applied.
     */
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->text('description')->nullable()->change();
            $table->text('notes')->nullable()->change();
            $table->text('reference_number')->nullable()->change();
        });

        Schema::table('accounts', function (Blueprint $table) {
            $table->text('name')->nullable()->change();
            $table->text('bank_name')->nullable()->change();
            $table->text('account_number')->nullable()->change();
        });

        Schema::table('income_sources', function (Blueprint $table) {
            $table->text('name')->nullable()->change();
            $table->text('description')->nullable()->change();
        });

        Schema::table('savings_goals', function (Blueprint $table) {
            $table->text('name')->nullable()->change();
            $table->text('description')->nullable()->change();
        });

        Schema::table('loans', function (Blueprint $table) {
            $table->text('name')->nullable()->change();
            $table->text('lender')->nullable()->change();
            $table->text('notes')->nullable()->change();
        });

        Schema::table('bills', function (Blueprint $table) {
            $table->text('name')->nullable()->change();
            $table->text('payee')->nullable()->change();
            $table->text('notes')->nullable()->change();
        });

        Schema::table('budgets', function (Blueprint $table) {
            $table->text('name')->nullable()->change();
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->text('name')->nullable()->change();
        });

        Schema::table('savings_contributions', function (Blueprint $table) {
            $table->text('notes')->nullable()->change();
        });

        Schema::table('loan_payments', function (Blueprint $table) {
            $table->text('reference_number')->nullable()->change();
            $table->text('notes')->nullable()->change();
        });

        Schema::table('bill_payments', function (Blueprint $table) {
            $table->text('reference_number')->nullable()->change();
            $table->text('notes')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('description')->nullable()->change();
            $table->string('notes')->nullable()->change();
            $table->string('reference_number')->nullable()->change();
        });

        Schema::table('accounts', function (Blueprint $table) {
            $table->string('name')->nullable()->change();
            $table->string('bank_name')->nullable()->change();
            $table->string('account_number')->nullable()->change();
        });

        Schema::table('income_sources', function (Blueprint $table) {
            $table->string('name')->nullable()->change();
            $table->string('description')->nullable()->change();
        });

        Schema::table('savings_goals', function (Blueprint $table) {
            $table->string('name')->nullable()->change();
            $table->string('description')->nullable()->change();
        });

        Schema::table('loans', function (Blueprint $table) {
            $table->string('name')->nullable()->change();
            $table->string('lender')->nullable()->change();
            $table->string('notes')->nullable()->change();
        });

        Schema::table('bills', function (Blueprint $table) {
            $table->string('name')->nullable()->change();
            $table->string('payee')->nullable()->change();
            $table->string('notes')->nullable()->change();
        });

        Schema::table('budgets', function (Blueprint $table) {
            $table->string('name')->nullable()->change();
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->string('name')->nullable()->change();
        });

        Schema::table('savings_contributions', function (Blueprint $table) {
            $table->string('notes')->nullable()->change();
        });

        Schema::table('loan_payments', function (Blueprint $table) {
            $table->string('reference_number')->nullable()->change();
            $table->string('notes')->nullable()->change();
        });

        Schema::table('bill_payments', function (Blueprint $table) {
            $table->string('reference_number')->nullable()->change();
            $table->string('notes')->nullable()->change();
        });
    }
};
