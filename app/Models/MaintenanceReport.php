<?php

namespace App\Models;

use App\BatteryHealth;
use Carbon\CarbonImmutable;
use Database\Factories\MaintenanceReportFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A technician's on-site check of one UPS and its battery bank.
 *
 * @property int $id
 * @property int $ups_unit_id
 * @property int|null $reporter_id
 * @property CarbonImmutable $reported_on
 * @property string|null $work_permit_number
 * @property float $room_temperature_celsius
 * @property int $backup_minutes
 * @property float $load_percent
 * @property float $battery_voltage_positive
 * @property float $battery_voltage_negative
 * @property int $battery_blocks
 * @property int $blocks_ok
 * @property int $blocks_low
 * @property int $blocks_bad
 * @property string $summary
 * @property string|null $file_path
 * @property string|null $file_name
 * @property-read BatteryHealth $battery_health
 * @property-read string $period
 */
#[Fillable([
    'reported_on', 'work_permit_number', 'room_temperature_celsius', 'backup_minutes', 'load_percent',
    'battery_voltage_positive', 'battery_voltage_negative', 'battery_blocks', 'blocks_ok', 'blocks_low',
    'blocks_bad', 'summary',
])]
class MaintenanceReport extends Model
{
    /** @use HasFactory<MaintenanceReportFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $appends = ['battery_health', 'period'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'reported_on' => 'immutable_date',
            'room_temperature_celsius' => 'float',
            'backup_minutes' => 'integer',
            'load_percent' => 'float',
            'battery_voltage_positive' => 'float',
            'battery_voltage_negative' => 'float',
            'battery_blocks' => 'integer',
            'blocks_ok' => 'integer',
            'blocks_low' => 'integer',
            'blocks_bad' => 'integer',
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
     * @return BelongsTo<User, $this>
     */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    /**
     * @return Attribute<BatteryHealth, never>
     */
    protected function batteryHealth(): Attribute
    {
        return Attribute::get(fn (): BatteryHealth => BatteryHealth::fromBlocks($this->battery_blocks, $this->blocks_low, $this->blocks_bad));
    }

    /**
     * The maintenance quarter the check belongs to, e.g. "Q3 2026".
     *
     * @return Attribute<string, never>
     */
    protected function period(): Attribute
    {
        /** @var Attribute<string, never> $period */
        $period = Attribute::get(fn (): string => "Q{$this->reported_on->quarter} {$this->reported_on->year}");

        return $period;
    }
}
