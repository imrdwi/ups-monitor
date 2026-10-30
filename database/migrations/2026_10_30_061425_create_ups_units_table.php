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
        Schema::create('ups_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('serial_number')->unique();
            $table->string('manufacturer')->nullable();
            $table->string('model')->nullable();
            $table->unsignedInteger('rated_power_va');
            $table->unsignedTinyInteger('phases')->default(1);
            $table->string('driver')->default('simulated');
            $table->string('endpoint')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_polled_at')->nullable();
            $table->timestamp('last_reading_at')->nullable();
            $table->unsignedInteger('consecutive_failures')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ups_units');
    }
};
