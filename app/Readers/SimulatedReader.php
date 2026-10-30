<?php

namespace App\Readers;

use App\Models\UpsUnit;
use App\UpsStatus;
use Carbon\CarbonImmutable;

/**
 * Produces plausible readings without hardware, for demos and local development.
 *
 * Values follow a daily cycle offset per serial number, so each unit has its
 * own curve and the same unit and time always give the same reading.
 */
class SimulatedReader implements UpsReader
{
    public function read(UpsUnit $unit): ReadingData
    {
        return $this->readAt($unit, now()->toImmutable());
    }

    public function readAt(UpsUnit $unit, CarbonImmutable $at): ReadingData
    {
        $offset = (crc32($unit->serial_number) % 1440) / 1440 * 2 * M_PI;
        $dayFraction = ($at->hour * 60 + $at->minute) / 1440;
        $wave = sin(2 * M_PI * $dayFraction + $offset);

        $load = round(45 + 25 * $wave, 2);
        $outputVoltage = round(220 + 2 * $wave, 2);

        return new ReadingData(
            status: UpsStatus::Online,
            recordedAt: $at,
            temperatureCelsius: round(25 + 4 * $wave, 2),
            loadPercent: $load,
            batteryCapacityPercent: 100.0,
            batteryVoltage: round(272 + 1.5 * $wave, 2),
            batteryRuntimeMinutes: (int) round(90 - 0.6 * $load),
            inputVoltage: round(221 + 3 * $wave, 2),
            outputVoltage: $outputVoltage,
            outputCurrent: round($unit->rated_power_va * $load / 100 / $outputVoltage / max($unit->phases, 1), 2),
            frequencyHz: 50.0,
            raw: ['driver' => 'simulated'],
        );
    }
}
