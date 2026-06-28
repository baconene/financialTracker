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
        Schema::create('bills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->decimal('amount', 15, 2);
            $table->enum('frequency', ['weekly', 'biweekly', 'monthly', 'quarterly', 'annually', 'one-time'])->default('monthly');
            $table->integer('due_day')->nullable();
            $table->date('next_due_date');
            $table->string('category')->nullable();
            $table->string('payee')->nullable();
            $table->string('color')->default('#7C3AED');
            $table->string('icon')->default('bill');
            $table->boolean('auto_pay')->default(false);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bills');
    }
};
