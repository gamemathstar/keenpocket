<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('otp_codes', function (Blueprint $table) {
            $table->id();
            $table->string('phone_number', 20)->index();
            $table->string('purpose', 32)->default('verify'); // verify | login | reset
            $table->string('code_hash');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->index(['phone_number', 'purpose']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('otp_codes');
    }
};
