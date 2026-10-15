<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('watchlist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // stock = Yahoo Finance symbol (stocks, ETFs, indices, forex); crypto = CoinGecko coin id
            $table->enum('asset_type', ['stock', 'crypto']);
            $table->string('symbol', 64);
            $table->string('ticker', 32)->nullable();
            $table->string('name')->nullable();
            // Holdings are personal financial data — stored encrypted per user
            $table->text('quantity')->nullable();
            $table->text('avg_cost')->nullable();
            $table->decimal('target_above', 20, 8)->nullable();
            $table->decimal('target_below', 20, 8)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'asset_type', 'symbol']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('watchlist_items');
    }
};
