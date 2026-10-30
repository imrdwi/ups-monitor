<?php

namespace Tests\Feature;

use App\Models\UpsUnit;
use App\Readers\SimulatedReader;
use App\UpsStatus;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SimulatedReaderTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_same_unit_and_time_always_give_the_same_reading(): void
    {
        $unit = UpsUnit::factory()->create();
        $at = CarbonImmutable::parse('2026-01-01 08:30');

        $this->assertEquals((new SimulatedReader)->readAt($unit, $at), (new SimulatedReader)->readAt($unit, $at));
    }

    #[Test]
    public function readings_stay_within_plausible_ranges_across_a_day(): void
    {
        $unit = UpsUnit::factory()->threePhase()->create(['rated_power_va' => 40_000]);
        $reader = new SimulatedReader;

        for ($at = CarbonImmutable::parse('2026-01-01 00:00'); $at->isSameDay('2026-01-01'); $at = $at->addMinutes(30)) {
            $reading = $reader->readAt($unit, $at);

            $this->assertSame(UpsStatus::Online, $reading->status);
            $this->assertGreaterThanOrEqual(20, $reading->loadPercent);
            $this->assertLessThanOrEqual(70, $reading->loadPercent);
            $this->assertGreaterThanOrEqual(21, $reading->temperatureCelsius);
            $this->assertLessThanOrEqual(29, $reading->temperatureCelsius);
            $this->assertGreaterThan(0, $reading->batteryRuntimeMinutes);
        }
    }
}
