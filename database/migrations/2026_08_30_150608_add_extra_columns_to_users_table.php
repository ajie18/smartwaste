<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('resident')->after('password');
            $table->string('phone')->nullable()->after('role');
            $table->text('address')->nullable()->after('phone');
            $table->boolean('email_verified')->default(true)->after('address');
            $table->string('reset_pin')->nullable();
            $table->timestamp('reset_pin_expires')->nullable();
            $table->unsignedTinyInteger('reset_attempts')->default(0);
            $table->timestamp('last_login')->nullable();
            $table->string('last_ip', 45)->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamp('location_updated_at')->nullable();
            $table->timestamp('password_updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'role',
                'phone',
                'address',
                'email_verified',
                'reset_pin',
                'reset_pin_expires',
                'reset_attempts',
                'last_login',
                'last_ip',
                'locked_at',
                'latitude',
                'longitude',
                'location_updated_at',
                'password_updated_at'
            ]);
        });
    }
};
