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
        Schema::table('ups_units', function (Blueprint $table) {
            $table->boolean('is_modular')->default(false)->after('phases');
            $table->string('battery_brand')->nullable()->after('is_modular');
            $table->string('battery_type')->nullable()->after('battery_brand');
            $table->string('battery_capacity')->nullable()->after('battery_type');
            $table->date('installed_on')->nullable()->after('battery_capacity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ups_units', function (Blueprint $table) {
            $table->dropColumn(['is_modular', 'battery_brand', 'battery_type', 'battery_capacity', 'installed_on']);
        });
    }
};
