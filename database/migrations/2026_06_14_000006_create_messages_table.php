<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('messages')) {
            return;
        }
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->string('context_type', 8);          // pocket | adashi
            $table->unsignedBigInteger('context_id');
            $table->unsignedBigInteger('user_id')->index();
            $table->text('body');
            $table->timestamps();
            $table->index(['context_type', 'context_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
