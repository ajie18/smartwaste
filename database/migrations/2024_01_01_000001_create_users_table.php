<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->enum('role', ['admin', 'driver', 'resident'])->default('resident');
            $table->string('phone', 20)->nullable();
            $table->string('address', 500)->nullable();
            $table->boolean('email_verified')->default(false);
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamp('location_updated_at')->nullable();
            $table->timestamp('last_login')->nullable();
            $table->string('last_ip', 45)->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->string('reset_pin', 6)->nullable();
            $table->timestamp('reset_pin_expires')->nullable();
            $table->unsignedTinyInteger('reset_attempts')->default(0);
            $table->timestamp('password_updated_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
