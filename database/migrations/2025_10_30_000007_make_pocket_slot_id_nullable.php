<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL-specific column modify on the legacy `invoices` table. No-op when
        // the table is absent (sqlite test DB) or on non-MySQL drivers.
        if (Schema::hasTable('invoices') && DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE invoices MODIFY pocket_slot_id BIGINT UNSIGNED NULL');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('invoices') && DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE invoices MODIFY pocket_slot_id BIGINT UNSIGNED NOT NULL');
        }
    }
};

