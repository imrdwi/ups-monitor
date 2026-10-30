<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpsUnitRequest;
use App\Jobs\PollUpsUnit;
use App\Models\Site;
use App\Models\UpsReading;
use App\Models\UpsUnit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class UpsUnitController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('units/Index', [
            'units' => UpsUnit::query()
                ->with('site:id,name,region_id', 'site.region:id,name')
                ->when($request->string('search')->toString(), fn ($query, string $search) => $query->where(fn ($matches) => $matches
                    ->where('name', 'like', "%{$search}%")->orWhere('serial_number', 'like', "%{$search}%")))
                ->orderBy('name')
                ->paginate(15)
                ->withQueryString(),
            'filters' => $request->only('search'),
            'canManage' => Gate::allows('admin'),
        ]);
    }

    public function show(UpsUnit $unit): Response
    {
        $unit->load('site.region', 'latestReading');

        return Inertia::render('units/Show', [
            'unit' => $unit,
            'stale' => $unit->isStale(),
            'history' => $unit->readings()
                ->where('recorded_at', '>=', now()->subDay())
                ->orderBy('recorded_at')
                ->get(['recorded_at', 'load_percent', 'temperature_celsius'])
                ->map(fn (UpsReading $reading) => [
                    'at' => $reading->recorded_at,
                    'load' => $reading->load_percent,
                    'temperature' => $reading->temperature_celsius,
                ]),
            'reports' => $unit->maintenanceReports()->with('reporter:id,name')->limit(12)->get(),
            'canManage' => Gate::allows('admin'),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('admin');

        return Inertia::render('units/Form', ['unit' => null, 'sites' => $this->sites()]);
    }

    public function store(UpsUnitRequest $request): RedirectResponse
    {
        Gate::authorize('admin');

        $unit = new UpsUnit($request->safe()->except('site_id'));
        $unit->site()->associate($request->integer('site_id'));
        $unit->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('UPS added.')]);

        return to_route('units.show', $unit);
    }

    public function edit(UpsUnit $unit): Response
    {
        Gate::authorize('admin');

        return Inertia::render('units/Form', ['unit' => $unit, 'sites' => $this->sites()]);
    }

    public function update(UpsUnitRequest $request, UpsUnit $unit): RedirectResponse
    {
        Gate::authorize('admin');

        $unit->fill($request->safe()->except('site_id'));
        $unit->site()->associate($request->integer('site_id'));
        $unit->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('UPS updated.')]);

        return to_route('units.show', $unit);
    }

    public function destroy(UpsUnit $unit): RedirectResponse
    {
        Gate::authorize('admin');

        $unit->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('UPS removed with its readings and reports.')]);

        return to_route('units.index');
    }

    /**
     * Poll one unit immediately instead of waiting for the next scheduled run.
     */
    public function poll(UpsUnit $unit): RedirectResponse
    {
        PollUpsUnit::dispatchSync($unit);

        $unit->refresh();

        Inertia::flash('toast', $unit->consecutive_failures > 0
            ? ['type' => 'error', 'message' => __('The UPS did not answer: :error', ['error' => $unit->last_error])]
            : ['type' => 'success', 'message' => __('Fresh reading stored.')]);

        return to_route('units.show', $unit);
    }

    /**
     * @return Collection<int, array{id: int, label: string}>
     */
    private function sites(): Collection
    {
        return Site::query()->with('region:id,name')->orderBy('name')->get()
            ->map(fn (Site $site) => ['id' => $site->id, 'label' => "{$site->name} ({$site->region?->name})"]);
    }
}
