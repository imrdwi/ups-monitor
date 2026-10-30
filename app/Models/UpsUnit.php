<?php

namespace App\Models;

use App\Readers\ReadingData;
use Carbon\CarbonImmutable;
use Database\Factories\UpsUnitFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int $site_id
 * @property string $name
 * @property string $serial_number
 * @property string|null $manufacturer
 * @property string|null $model
 * @property int $rated_power_va
 * @property int $phases
 * @property bool $is_modular
 * @property string|null $battery_brand
 * @property string|null $battery_type
 * @property string|null $battery_capacity
 * @property CarbonImmutable|null $installed_on
 * @property string $driver
 * @property string|null $endpoint
 * @property bool $is_active
 * @property CarbonImmutable|null $last_polled_at
 * @property CarbonImmutable|null $last_reading_at
 * @property int $consecutive_failures
 * @property string|null $last_error
 */
#[Fillable([
    'name', 'serial_number', 'manufacturer', 'model', 'rated_power_va',
    'phases', 'is_modular', 'battery_brand', 'battery_type', 'battery_capacity', 'installed_on',
    'driver', 'endpoint', 'is_active',
])]
class UpsUnit extends Model
{
    /** @use HasFactory<UpsUnitFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rated_power_va' => 'integer',
            'phases' => 'integer',
            'is_modular' => 'boolean',
            'installed_on' => 'immutable_date',
            'is_active' => 'boolean',
            'last_polled_at' => 'datetime',
            'last_reading_at' => 'datetime',
            'consecutive_failures' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Site, $this>
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * @return HasMany<UpsReading, $this>
     */
    public function readings(): HasMany
    {
        return $this->hasMany(UpsReading::class);
    }

    /**
     * @return HasOne<UpsReading, $this>
     */
    public function latestReading(): HasOne
    {
        return $this->hasOne(UpsReading::class)->latestOfMany('recorded_at');
    }

    /**
     * @return HasMany<MaintenanceReport, $this>
     */
    public function maintenanceReports(): HasMany
    {
        return $this->hasMany(MaintenanceReport::class)->latest('reported_on')->latest('id');
    }

    /**
     * @return HasOne<MaintenanceReport, $this>
     */
    public function latestMaintenanceReport(): HasOne
    {
        return $this->hasOne(MaintenanceReport::class)->ofMany(['reported_on' => 'max', 'id' => 'max']);
    }

    /**
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Store a successful poll and clear any failure streak.
     */
    public function recordReading(ReadingData $data): UpsReading
    {
        $reading = $this->readings()->create($data->toAttributes());

        $this->forceFill([
            'last_polled_at' => now(),
            'last_reading_at' => $reading->recorded_at,
            'consecutive_failures' => 0,
            'last_error' => null,
        ])->save();

        return $reading;
    }

    /**
     * Note a failed poll without touching the last good reading.
     */
    public function recordFailure(string $error): void
    {
        $this->forceFill([
            'last_polled_at' => now(),
            'consecutive_failures' => $this->consecutive_failures + 1,
            'last_error' => mb_substr($error, 0, 1000),
        ])->save();
    }

    /**
     * Whether the newest reading is too old to describe the unit's current state.
     */
    public function isStale(): bool
    {
        $threshold = (int) config('ups.stale_after_minutes');

        return $this->last_reading_at === null || $this->last_reading_at->lt(now()->subMinutes($threshold));
    }
}
