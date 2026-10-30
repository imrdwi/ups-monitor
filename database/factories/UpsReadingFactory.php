<?php

namespace Database\Factories;

use App\Models\UpsReading;
use App\Models\UpsUnit;
use App\UpsStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UpsReading>
 */
class UpsReadingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ups_unit_id' => UpsUnit::factory(),
            'status' => UpsStatus::Online,
            'temperature_celsius' => fake()->randomFloat(2, 22, 32),
            'load_percent' => fake()->randomFloat(2, 20, 70),
            'battery_capacity_percent' => 100,
            'battery_voltage' => fake()->randomFloat(2, 270, 274),
            'battery_runtime_minutes' => fake()->numberBetween(40, 90),
            'input_voltage' => fake()->randomFloat(2, 218, 224),
            'output_voltage' => fake()->randomFloat(2, 219, 221),
            'output_current' => fake()->randomFloat(2, 10, 60),
            'frequency_hz' => 50,
            'raw' => [],
            'recorded_at' => now(),
        ];
    }
}
