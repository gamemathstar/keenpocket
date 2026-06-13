<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('pockets')) {
            return;
        }
        Schema::table('pockets', function (Blueprint $table) {
            // Admin opt-in: charity collection is off until the admin enables it.
            if (!Schema::hasColumn('pockets', 'charity_enabled')) {
                $table->boolean('charity_enabled')->default(false);
            }
            // fi-sabilillah: donor identities/amounts are hidden from members by
            // default. The admin may flip this to publish a donor honour-roll.
            if (!Schema::hasColumn('pockets', 'charity_donors_visible')) {
                $table->boolean('charity_donors_visible')->default(false);
            }
        });
    }

    public function down(): void
    {
        // No-op: additive.
    }
};
