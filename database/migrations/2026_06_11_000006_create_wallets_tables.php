<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->unsignedBigInteger('balance')->default(0); // minor-unit-free: whole naira
            $table->string('currency', 8)->default('NGN');
            $table->timestamps();
        });

        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('wallet_id')->index();
            $table->string('type', 8);                 // credit | debit
            $table->unsignedBigInteger('amount');
            $table->unsignedBigInteger('balance_after'); // running balance for audit
            $table->string('reason', 64);
            // Idempotency key for externally-driven credits (e.g. a gateway ref).
            $table->string('reference', 64)->nullable()->unique();
            $table->text('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('wallets');
    }
};
