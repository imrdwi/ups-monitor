<?php

namespace Database\Factories;

use App\Models\Region;
use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Site>
 */
class SiteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'region_id' => Region::factory(),
            'code' => fake()->unique()->bothify('STE-###'),
            'name' => fake()->city().' Exchange',
            'address' => fake()->address(),
        ];
    }
}
