<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Pocket: hide other members' hand counts by default (admin can reveal).
        if (Schema::hasTable('pockets') && !Schema::hasColumn('pockets', 'members_visible')) {
            Schema::table('pockets', function (Blueprint $table) {
                $table->boolean('members_visible')->default(false);
            });
        }

        // Adashi: hide who receives which payout by default (admin can reveal).
        if (Schema::hasTable('adashis') && !Schema::hasColumn('adashis', 'payout_visible')) {
            Schema::table('adashis', function (Blueprint $table) {
                $table->boolean('payout_visible')->default(false);
            });
        }
    }

    public function down(): void
    {
        // No-op: additive.
    }
};
