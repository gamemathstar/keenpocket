<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('pockets') && !Schema::hasColumn('pockets', 'guarantor_required')) {
            Schema::table('pockets', function (Blueprint $table) {
                $table->boolean('guarantor_required')->default(false);
            });
        }

        if (!Schema::hasTable('pocket_guarantors')) {
            Schema::create('pocket_guarantors', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('pocket_id')->index();
                $table->unsignedBigInteger('slot_id')->nullable()->index(); // the pending pocket_slot
                $table->unsignedBigInteger('requester_id')->index();
                $table->unsignedBigInteger('guarantor_id')->index();
                $table->string('status', 12)->default('PENDING'); // PENDING | RECOMMENDED | DECLINED
                $table->string('note')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pocket_guarantors');
    }
};
