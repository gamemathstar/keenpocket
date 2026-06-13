<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('pockets') && !Schema::hasColumn('pockets', 'account_name')) {
            Schema::table('pockets', function (Blueprint $table) {
                $table->string('account_name')->nullable(); // name on the account (bank/nuban already exist)
            });
        }

        if (Schema::hasTable('adashis')) {
            Schema::table('adashis', function (Blueprint $table) {
                if (!Schema::hasColumn('adashis', 'bank')) {
                    $table->string('bank')->nullable();
                }
                if (!Schema::hasColumn('adashis', 'nuban')) {
                    $table->string('nuban')->nullable();
                }
                if (!Schema::hasColumn('adashis', 'account_name')) {
                    $table->string('account_name')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        // No-op: additive.
    }
};
