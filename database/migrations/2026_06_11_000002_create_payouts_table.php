<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payouts', function (Blueprint $table) {
            $table->id();
            // One payout per Adashi cycle record — the DB-level guard against
            // ever disbursing the same pot twice.
            $table->unsignedBigInteger('adashi_record_id')->unique();
            $table->unsignedBigInteger('recipient_user_id')->index();
            $table->unsignedBigInteger('amount');
            $table->string('currency', 8)->default('NGN');
            $table->string('provider', 32);
            $table->string('reference', 64)->unique();
            $table->string('transfer_code', 64)->nullable(); // provider's transfer handle
            $table->string('status', 16)->default('pending'); // pending | success | failed
            $table->string('failure_reason', 64)->nullable(); // e.g. no_bank_details
            $table->text('gateway_response')->nullable();
            $table->timestamp('disbursed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payouts');
    }
};
