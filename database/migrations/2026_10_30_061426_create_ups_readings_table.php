<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ups_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ups_unit_id')->constrained()->cascadeOnDelete();
            $table->string('status');
            $table->decimal('temperature_celsius', 5, 2)->nullable();
            $table->decimal('load_percent', 5, 2)->nullable();
            $table->decimal('battery_capacity_percent', 5, 2)->nullable();
            $table->decimal('battery_voltage', 6, 2)->nullable();
            $table->unsignedInteger('battery_runtime_minutes')->nullable();
            $table->decimal('input_voltage', 6, 2)->nullable();
            $table->decimal('output_voltage', 6, 2)->nullable();
            $table->decimal('output_current', 7, 2)->nullable();
            $table->decimal('frequency_hz', 5, 2)->nullable();
            $table->json('raw')->nullable();
            $table->timestamp('recorded_at');
            $table->timestamps();

            $table->index(['ups_unit_id', 'recorded_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ups_readings');
    }
};
