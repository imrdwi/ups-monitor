<?php

namespace App\Http\Controllers;

use App\Models\Region;
use App\Models\Site;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Regions and the sites in them, on one page.
 */
class SiteController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('sites/Index', [
            'regions' => Region::query()
                ->with(['sites' => fn ($query) => $query->withCount('upsUnits')->orderBy('name')])
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);
        Region::query()->findOrFail($validated['region_id'])->sites()->create($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Site added.')]);

        return to_route('sites.index');
    }

    public function update(Request $request, Site $site): RedirectResponse
    {
        $validated = $this->validated($request, $site);
        $site->region()->associate($validated['region_id']);
        $site->update($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Site updated.')]);

        return to_route('sites.index');
    }

    public function destroy(Site $site): RedirectResponse
    {
        abort_if($site->upsUnits()->exists(), 422, __('Move or remove the UPS units at this site first.'));

        $site->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Site removed.')]);

        return to_route('sites.index');
    }

    public function storeRegion(Request $request): RedirectResponse
    {
        Region::query()->create($request->validate(['name' => ['required', 'string', 'max:100', 'unique:regions,name']]));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Region added.')]);

        return to_route('sites.index');
    }

    public function destroyRegion(Region $region): RedirectResponse
    {
        abort_if($region->sites()->exists(), 422, __('Remove the sites in this region first.'));

        $region->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Region removed.')]);

        return to_route('sites.index');
    }

    /**
     * @return array{region_id: int, code: string, name: string, address?: string|null}
     */
    private function validated(Request $request, ?Site $site = null): array
    {
        /** @var array{region_id: int|string, code: string, name: string, address?: string|null} $validated */
        $validated = $request->validate([
            'region_id' => ['required', 'integer', 'exists:regions,id'],
            'code' => ['required', 'string', 'max:30', Rule::unique('sites')->ignore($site?->id)],
            'name' => ['required', 'string', 'max:150'],
            'address' => ['nullable', 'string', 'max:500'],
        ]);

        return ['region_id' => (int) $validated['region_id']] + $validated;
    }
}
