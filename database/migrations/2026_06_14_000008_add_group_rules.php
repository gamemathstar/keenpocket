<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('pockets') && !Schema::hasColumn('pockets', 'rules')) {
            Schema::table('pockets', function (Blueprint $table) {
                $table->text('rules')->nullable();
            });
        }
        if (Schema::hasTable('adashis') && !Schema::hasColumn('adashis', 'rules')) {
            Schema::table('adashis', function (Blueprint $table) {
                $table->text('rules')->nullable();
            });
        }
    }

    public function down(): void
    {
        // No-op: additive.
    }
};
