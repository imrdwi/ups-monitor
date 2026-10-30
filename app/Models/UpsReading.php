<?php

namespace App\Models;

use App\UpsStatus;
use Carbon\CarbonImmutable;
use Database\Factories\UpsReadingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $ups_unit_id
 * @property UpsStatus $status
 * @property float|null $temperature_celsius
 * @property float|null $load_percent
 * @property float|null $battery_capacity_percent
 * @property float|null $battery_voltage
 * @property int|null $battery_runtime_minutes
 * @property float|null $input_voltage
 * @property float|null $output_voltage
 * @property float|null $output_current
 * @property float|null $frequency_hz
 * @property array<string, mixed>|null $raw
 * @property CarbonImmutable $recorded_at
 */
#[Fillable([
    'status', 'temperature_celsius', 'load_percent', 'battery_capacity_percent',
    'battery_voltage', 'battery_runtime_minutes', 'input_voltage', 'output_voltage',
    'output_current', 'frequency_hz', 'raw', 'recorded_at',
])]
class UpsReading extends Model
{
    /** @use HasFactory<UpsReadingFactory> */
    use HasFactory, MassPrunable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => UpsStatus::class,
            'temperature_celsius' => 'float',
            'load_percent' => 'float',
            'battery_capacity_percent' => 'float',
            'battery_voltage' => 'float',
            'battery_runtime_minutes' => 'integer',
            'input_voltage' => 'float',
            'output_voltage' => 'float',
            'output_current' => 'float',
            'frequency_hz' => 'float',
            'raw' => 'array',
            'recorded_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<UpsUnit, $this>
     */
    public function upsUnit(): BelongsTo
    {
        return $this->belongsTo(UpsUnit::class);
    }

    /**
     * Readings older than the retention window are deleted by model:prune.
     *
     * @return Builder<static>
     */
    public function prunable(): Builder
    {
        return static::query()->where('recorded_at', '<', now()->subDays((int) config('ups.retention_days')));
    }
}
