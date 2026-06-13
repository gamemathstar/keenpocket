<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('bank_accounts')) {
            Schema::create('bank_accounts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->index();
                $table->string('label')->nullable();      // e.g. "Salary", "Main"
                $table->string('account_name');
                $table->string('bank');
                $table->string('nuban', 32);
                $table->boolean('is_default')->default(false);
                $table->timestamps();
            });
        }

        // The account a member receives their payout / cashback into, per group.
        if (Schema::hasTable('adashi_members') && !Schema::hasColumn('adashi_members', 'bank_account_id')) {
            Schema::table('adashi_members', function (Blueprint $table) {
                $table->unsignedBigInteger('bank_account_id')->nullable();
            });
        }
        if (Schema::hasTable('pocket_slots') && !Schema::hasColumn('pocket_slots', 'bank_account_id')) {
            Schema::table('pocket_slots', function (Blueprint $table) {
                $table->unsignedBigInteger('bank_account_id')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_accounts');
    }
};
