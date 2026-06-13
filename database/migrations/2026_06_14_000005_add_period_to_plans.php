<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('plans') && !Schema::hasColumn('plans', 'period_type')) {
            Schema::table('plans', function (Blueprint $table) {
                $table->string('period_type', 8)->default('month'); // month | year
            });
        }
    }

    public function down(): void
    {
        // No-op: additive.
    }
};
