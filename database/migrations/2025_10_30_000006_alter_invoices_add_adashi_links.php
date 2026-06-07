<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('adashi_record_id')->nullable()->after('paid_through')->constrained('adashi_records')->nullOnDelete();
            $table->foreignId('adashi_member_id')->nullable()->after('adashi_record_id')->constrained('adashi_members')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('adashi_member_id');
            $table->dropConstrainedForeignId('adashi_record_id');
        });
    }
};

