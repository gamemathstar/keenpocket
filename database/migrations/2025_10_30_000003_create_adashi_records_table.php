<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('adashi_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('adashi_id')->constrained('adashis')->cascadeOnDelete();
            $table->integer('cycle_number');
            $table->dateTime('due_at');
            $table->unsignedBigInteger('total_collected')->default(0);
            $table->foreignId('receiver_user_id')->constrained('users');
            $table->foreignId('receiver_member_id')->nullable()->constrained('adashi_members');
            $table->integer('paid_members_count')->default(0);
            $table->enum('status', ['PENDING','COLLECTING','PAID_OUT','DISPUTE'])->default('PENDING');
            $table->timestamps();
            $table->unique(['adashi_id','cycle_number']);
            $table->index('due_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adashi_records');
    }
};


