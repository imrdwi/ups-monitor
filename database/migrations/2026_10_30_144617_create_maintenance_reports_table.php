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
        Schema::create('maintenance_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ups_unit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reporter_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('reported_on');
            $table->string('work_permit_number')->nullable();
            $table->decimal('room_temperature_celsius', 5, 2);
            $table->unsignedInteger('backup_minutes');
            $table->decimal('load_percent', 5, 2);
            $table->decimal('battery_voltage_positive', 6, 2);
            $table->decimal('battery_voltage_negative', 6, 2);
            $table->unsignedInteger('battery_blocks');
            $table->unsignedInteger('blocks_ok');
            $table->unsignedInteger('blocks_low');
            $table->unsignedInteger('blocks_bad');
            $table->text('summary');
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->timestamps();

            $table->index(['ups_unit_id', 'reported_on']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_reports');
    }
};
