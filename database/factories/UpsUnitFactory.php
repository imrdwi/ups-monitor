<?php

namespace Database\Factories;

use App\Models\Site;
use App\Models\UpsUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UpsUnit>
 */
class UpsUnitFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'site_id' => Site::factory(),
            'name' => 'UPS '.fake()->unique()->numberBetween(1, 999),
            'serial_number' => fake()->unique()->bothify('SN-####-????'),
            'manufacturer' => fake()->randomElement(['Acme Power', 'Volta Systems', 'Northgrid']),
            'model' => fake()->bothify('X-###'),
            'rated_power_va' => fake()->randomElement([10_000, 20_000, 40_000]),
            'phases' => 1,
            'is_modular' => false,
            'battery_brand' => fake()->randomElement(['Voltcell', 'Northstar', 'Ampra']),
            'battery_type' => 'VRLA 12V',
            'battery_capacity' => fake()->randomElement(['65 Ah', '100 Ah', '150 Ah']),
            'installed_on' => fake()->dateTimeBetween('-6 years', '-1 year')->format('Y-m-d'),
            'driver' => 'simulated',
            'endpoint' => null,
            'is_active' => true,
        ];
    }

    /**
     * A unit read through its network card's HTML status page.
     */
    public function html(string $endpoint = 'http://ups.example.test/status.htm'): static
    {
        return $this->state(fn (array $attributes) => [
            'driver' => 'html',
            'endpoint' => $endpoint,
        ]);
    }

    public function threePhase(): static
    {
        return $this->state(fn (array $attributes) => [
            'phases' => 3,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
