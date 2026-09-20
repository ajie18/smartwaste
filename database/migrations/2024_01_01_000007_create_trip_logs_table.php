<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('trip_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained('route_assignments')->cascadeOnDelete();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('fuel_used', 10, 2)->nullable();
            $table->timestamp('logged_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('end_time')->nullable();
            $table->string('status', 30)->nullable();

            $table->index('assignment_id');
            $table->index('logged_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trip_logs');
    }
};
