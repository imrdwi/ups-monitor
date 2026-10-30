<?php

namespace App\Http\Controllers;

use App\BatteryHealth;
use App\Models\Region;
use App\Models\UpsUnit;
use App\UpsStatus;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The fleet at a glance: live state from polling, battery health from the last maintenance check.
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $units = UpsUnit::query()
            ->active()
            ->with('site:id,name,code,region_id', 'site.region:id,name', 'latestReading', 'latestMaintenanceReport')
            ->when($request->integer('region_id'), fn ($query, int $id) => $query->whereRelation('site', 'region_id', $id))
            ->orderBy('name')
            ->get()
            ->map(fn (UpsUnit $unit) => [
                'id' => $unit->id,
                'name' => $unit->name,
                'site' => $unit->site?->name,
                'region' => $unit->site?->region?->name,
                'condition' => $this->condition($unit),
                'reading' => $unit->latestReading?->only('load_percent', 'temperature_celsius', 'battery_capacity_percent', 'recorded_at'),
                'last_reading_at' => $unit->last_reading_at,
                'battery_health' => $unit->latestMaintenanceReport?->battery_health->value,
                'last_maintained_on' => $unit->latestMaintenanceReport?->reported_on,
            ]);

        $byCondition = $units->countBy('condition');
        $byHealth = $units->countBy('battery_health');

        return Inertia::render('Dashboard', [
            'units' => $units->sortBy(fn (array $unit) => array_search($unit['condition'], ['fault', 'not_reporting', 'on_battery', 'bypass', 'unknown', 'online'], true))->values(),
            'conditions' => collect(['online', 'on_battery', 'bypass', 'fault', 'not_reporting'])->map(fn (string $condition) => [
                'value' => $condition,
                'count' => (int) ($byCondition[$condition] ?? 0),
            ]),
            'health' => collect(BatteryHealth::cases())->map(fn (BatteryHealth $health) => [
                'value' => $health->value,
                'label' => $health->label(),
                'count' => (int) ($byHealth[$health->value] ?? 0),
            ]),
            'regions' => Region::query()->orderBy('name')->get(['id', 'name']),
            'filters' => $request->only('region_id'),
            'staleAfterMinutes' => (int) config('ups.stale_after_minutes'),
        ]);
    }

    /**
     * A stale unit is "not reporting" whatever its last status said.
     */
    private function condition(UpsUnit $unit): string
    {
        if ($unit->isStale()) {
            return 'not_reporting';
        }

        return ($unit->latestReading->status ?? UpsStatus::Unknown)->value;
    }
}
