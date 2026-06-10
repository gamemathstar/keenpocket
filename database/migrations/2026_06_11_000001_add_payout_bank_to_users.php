<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-user payout destination (where this member's Adashi pot is disbursed).
 * Guarded + additive — no-op if the columns already exist.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'payout_bank_name')) {
                $table->string('payout_bank_name')->nullable();
            }
            if (!Schema::hasColumn('users', 'payout_bank_code')) {
                $table->string('payout_bank_code', 16)->nullable();
            }
            if (!Schema::hasColumn('users', 'payout_account_number')) {
                $table->string('payout_account_number', 20)->nullable();
            }
        });
    }

    public function down(): void
    {
        // No-op: additive, safe to leave in place.
    }
};
