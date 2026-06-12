<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('adashi_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('adashi_id')->index();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('action', 48);
            $table->text('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adashi_audit_logs');
    }
};
