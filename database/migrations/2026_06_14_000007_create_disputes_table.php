<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('disputes')) {
            return;
        }
        Schema::create('disputes', function (Blueprint $table) {
            $table->id();
            $table->string('context_type', 8);          // pocket | adashi
            $table->unsignedBigInteger('context_id');
            $table->unsignedBigInteger('raised_by')->index();
            $table->string('subject');
            $table->text('body');
            $table->string('status', 12)->default('OPEN'); // OPEN | RESOLVED | DISMISSED
            $table->text('resolution')->nullable();
            $table->unsignedBigInteger('resolved_by')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->index(['context_type', 'context_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disputes');
    }
};
