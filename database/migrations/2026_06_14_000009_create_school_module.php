<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasColumn('users', 'is_super_admin')) {
                    $table->boolean('is_super_admin')->default(false);
                }
                if (!Schema::hasColumn('users', 'can_create_school')) {
                    $table->boolean('can_create_school')->default(false);
                }
            });
        }

        if (!Schema::hasTable('schools')) {
            Schema::create('schools', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('owner_id')->index();
                $table->string('name');
                $table->string('address')->nullable();
                $table->string('contact')->nullable();
                $table->string('logo')->nullable();
                $table->string('background_image')->nullable();
                $table->string('bank')->nullable();
                $table->string('nuban', 32)->nullable();
                $table->string('account_name')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('school_classes')) {
            Schema::create('school_classes', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('school_id')->index();
                $table->string('name');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('fee_items')) {
            Schema::create('fee_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('school_id')->index();
                $table->unsignedBigInteger('school_class_id')->index();
                $table->unsignedTinyInteger('term'); // 1 | 2 | 3
                $table->string('name');
                $table->unsignedBigInteger('amount')->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('students')) {
            Schema::create('students', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('school_id')->index();
                $table->unsignedBigInteger('school_class_id')->nullable()->index();
                $table->unsignedBigInteger('parent_id')->index(); // a User
                $table->string('name');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('payment_plans')) {
            Schema::create('payment_plans', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('school_id')->index();
                $table->unsignedBigInteger('student_id')->index();
                $table->string('mode', 12)->default('percent');   // percent | min_monthly
                $table->unsignedTinyInteger('percent')->nullable(); // 100 | 50 | 30
                $table->unsignedBigInteger('min_monthly')->nullable();
                $table->string('note')->nullable();
                $table->string('status', 12)->default('ACTIVE');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('school_payments')) {
            Schema::create('school_payments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('school_id')->index();
                $table->unsignedBigInteger('student_id')->index();
                $table->unsignedTinyInteger('term');
                $table->unsignedBigInteger('amount');
                $table->string('note')->nullable();
                $table->unsignedBigInteger('recorded_by')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('school_payments');
        Schema::dropIfExists('payment_plans');
        Schema::dropIfExists('students');
        Schema::dropIfExists('fee_items');
        Schema::dropIfExists('school_classes');
        Schema::dropIfExists('schools');
    }
};
