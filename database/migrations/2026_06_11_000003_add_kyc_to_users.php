<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * KYC state on users. Guarded + additive.
 *
 * Privacy: we deliberately do NOT store the full BVN/NIN — only the last 4
 * digits (for user reference) and the provider's verification reference.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'kyc_status')) {
                $table->string('kyc_status', 16)->default('none'); // none | pending | verified | failed
            }
            if (!Schema::hasColumn('users', 'kyc_type')) {
                $table->string('kyc_type', 8)->nullable(); // BVN | NIN
            }
            if (!Schema::hasColumn('users', 'kyc_id_last4')) {
                $table->string('kyc_id_last4', 4)->nullable();
            }
            if (!Schema::hasColumn('users', 'kyc_reference')) {
                $table->string('kyc_reference')->nullable();
            }
            if (!Schema::hasColumn('users', 'kyc_verified_at')) {
                $table->timestamp('kyc_verified_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        // No-op: additive.
    }
};
