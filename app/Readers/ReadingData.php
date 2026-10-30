<?php

namespace App\Readers;

use App\UpsStatus;
use Carbon\CarbonImmutable;

/**
 * One normalized reading, independent of which device or driver produced it.
 */
final readonly class ReadingData
{
    /**
     * @param  array<string, mixed>  $raw  The values as the device reported them, including per-phase figures.
     */
    public function __construct(
        public UpsStatus $status,
        public CarbonImmutable $recordedAt,
        public ?float $temperatureCelsius = null,
        public ?float $loadPercent = null,
        public ?float $batteryCapacityPercent = null,
        public ?float $batteryVoltage = null,
        public ?int $batteryRuntimeMinutes = null,
        public ?float $inputVoltage = null,
        public ?float $outputVoltage = null,
        public ?float $outputCurrent = null,
        public ?float $frequencyHz = null,
        public array $raw = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toAttributes(): array
    {
        return [
            'status' => $this->status,
            'recorded_at' => $this->recordedAt,
            'temperature_celsius' => $this->temperatureCelsius,
            'load_percent' => $this->loadPercent,
            'battery_capacity_percent' => $this->batteryCapacityPercent,
            'battery_voltage' => $this->batteryVoltage,
            'battery_runtime_minutes' => $this->batteryRuntimeMinutes,
            'input_voltage' => $this->inputVoltage,
            'output_voltage' => $this->outputVoltage,
            'output_current' => $this->outputCurrent,
            'frequency_hz' => $this->frequencyHz,
            'raw' => $this->raw,
        ];
    }
}
