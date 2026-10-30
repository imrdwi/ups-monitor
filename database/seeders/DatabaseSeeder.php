<?php

namespace Database\Seeders;

use App\Models\MaintenanceReport;
use App\Models\Region;
use App\Models\Site;
use App\Models\UpsUnit;
use App\Models\User;
use App\Readers\SimulatedReader;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed a demo fleet of simulated units with the last 24 hours of readings.
     */
    public function run(): void
    {
        User::factory()->admin()->create(['name' => 'Admin', 'email' => 'admin@example.com']);
        $technician = User::factory()->create(['name' => 'Field Technician', 'email' => 'technician@example.com']);

        $units = Region::factory()
            ->count(2)
            ->has(Site::factory()->count(3)->has(UpsUnit::factory()->count(2)))
            ->create()
            ->flatMap(fn (Region $region) => $region->upsUnits()->get());

        $reader = new SimulatedReader;
        $start = now()->toImmutable()->subDay()->startOfHour();

        // One unit with a degraded battery bank and one whose card stopped answering, for the dashboard.
        foreach ($units->values() as $index => $unit) {
            MaintenanceReport::factory()
                ->for($unit)
                ->state(['battery_blocks' => 32, 'reporter_id' => $technician->id])
                ->failing(low: $index === 1 ? 2 : ($index === 2 ? 6 : 0))
                ->create();
        }

        $unreachable = $units->last();

        foreach ($units as $unit) {
            $until = $unit->is($unreachable) ? now()->subMinutes(42) : now();

            for ($at = $start; $at->lte($until); $at = $at->addMinutes(5)) {
                $unit->recordReading($reader->readAt($unit, $at));
            }
        }

        $unreachable?->recordFailure('cURL error 28: Connection timed out after 10001 milliseconds');
    }
}
