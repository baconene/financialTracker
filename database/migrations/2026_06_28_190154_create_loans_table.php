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
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('lender');
            $table->decimal('principal_amount', 15, 2);
            $table->decimal('remaining_balance', 15, 2);
            $table->decimal('interest_rate', 5, 2);
            $table->enum('interest_type', ['simple', 'compound'])->default('compound');
            $table->enum('payment_frequency', ['weekly', 'biweekly', 'monthly', 'quarterly'])->default('monthly');
            $table->decimal('monthly_payment', 15, 2);
            $table->integer('term_months');
            $table->date('start_date');
            $table->date('end_date');
            $table->date('next_payment_date')->nullable();
            $table->enum('status', ['active', 'paid', 'defaulted'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
