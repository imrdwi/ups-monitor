<?php

namespace Database\Factories;

use App\Models\MaintenanceReport;
use App\Models\UpsUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MaintenanceReport>
 */
class MaintenanceReportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $blocks = fake()->randomElement([20, 32, 40]);

        return [
            'ups_unit_id' => UpsUnit::factory(),
            'reporter_id' => null,
            'reported_on' => fake()->dateTimeBetween('-80 days', 'now')->format('Y-m-d'),
            'work_permit_number' => fake()->bothify('PTW-####-??'),
            'room_temperature_celsius' => fake()->randomFloat(1, 20, 28),
            'backup_minutes' => fake()->numberBetween(20, 90),
            'load_percent' => fake()->randomFloat(1, 20, 70),
            'battery_voltage_positive' => fake()->randomFloat(1, 270, 274),
            'battery_voltage_negative' => fake()->randomFloat(1, 270, 274),
            'battery_blocks' => $blocks,
            'blocks_ok' => $blocks,
            'blocks_low' => 0,
            'blocks_bad' => 0,
            'summary' => fake()->sentence(12),
        ];
    }

    /**
     * Some blocks failing, as a share of the bank.
     */
    public function failing(int $low, int $bad = 0): static
    {
        return $this->state(fn (array $attributes) => [
            'blocks_low' => $low,
            'blocks_bad' => $bad,
            'blocks_ok' => $attributes['battery_blocks'] - $low - $bad,
        ]);
    }
}
