<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('adashis', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('amount_per_cycle');
            $table->integer('total_members');
            $table->date('start_date');
            $table->integer('cycle_duration_days');
            $table->integer('current_cycle_number')->default(1);
            $table->foreignId('admin_id')->constrained('users');
            $table->enum('rotation_mode', ['AUTO','MANUAL'])->default('AUTO');
            $table->enum('status', ['ACTIVE','PAUSED','COMPLETED'])->default('ACTIVE');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adashis');
    }
};


