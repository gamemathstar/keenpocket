<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The core `invoices` table is provisioned outside Laravel migrations
        // (legacy schema). Guard so this is a no-op where it is absent (e.g. a
        // fresh sqlite test database) and idempotent elsewhere.
        if (!Schema::hasTable('invoices')) {
            return;
        }

        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'adashi_record_id')) {
                $table->foreignId('adashi_record_id')->nullable()->after('paid_through')->constrained('adashi_records')->nullOnDelete();
            }
            if (!Schema::hasColumn('invoices', 'adashi_member_id')) {
                $table->foreignId('adashi_member_id')->nullable()->after('adashi_record_id')->constrained('adashi_members')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('invoices')) {
            return;
        }

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('adashi_member_id');
            $table->dropConstrainedForeignId('adashi_record_id');
        });
    }
};

