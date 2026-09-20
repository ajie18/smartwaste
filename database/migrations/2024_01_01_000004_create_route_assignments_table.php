<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('route_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('truck_id')->constrained('trucks')->cascadeOnDelete();
            $table->foreignId('route_id')->constrained('routes')->cascadeOnDelete();
            $table->date('assigned_date');
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->string('pickup_type', 50)->default('regular');
            $table->json('progress_log')->nullable();
            $table->timestamps();

            $table->index(['assigned_date', 'status']);
            $table->index(['driver_id', 'assigned_date']);
            $table->index(['truck_id', 'assigned_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('route_assignments');
    }
};
