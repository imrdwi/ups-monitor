<?php

namespace App\Http\Controllers;

use App\Http\Requests\MaintenanceReportRequest;
use App\Models\MaintenanceReport;
use App\Models\UpsUnit;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MaintenanceReportController extends Controller
{
    public function create(UpsUnit $unit): Response
    {
        return Inertia::render('reports/Form', [
            'unit' => $unit->only('id', 'name'),
            'report' => null,
            'suggested' => $unit->latestReading?->only('load_percent', 'temperature_celsius'),
        ]);
    }

    public function store(MaintenanceReportRequest $request, UpsUnit $unit): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $report = new MaintenanceReport($request->safe()->except('file'));
        $report->upsUnit()->associate($unit);
        $report->reporter()->associate($user);
        $this->attachFile($request, $report);
        $report->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Maintenance report saved.')]);

        return to_route('reports.show', $report);
    }

    public function show(MaintenanceReport $report): Response
    {
        $report->load('upsUnit.site.region', 'reporter:id,name');

        return Inertia::render('reports/Show', [
            'report' => $report,
            'canEdit' => $this->canEdit($report),
        ]);
    }

    public function edit(MaintenanceReport $report): Response
    {
        abort_unless($this->canEdit($report), 403);

        return Inertia::render('reports/Form', [
            'unit' => $report->upsUnit?->only('id', 'name'),
            'report' => $report,
            'suggested' => null,
        ]);
    }

    public function update(MaintenanceReportRequest $request, MaintenanceReport $report): RedirectResponse
    {
        abort_unless($this->canEdit($report), 403);

        $report->fill($request->safe()->except('file'));
        $this->attachFile($request, $report);
        $report->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Maintenance report updated.')]);

        return to_route('reports.show', $report);
    }

    public function destroy(MaintenanceReport $report): RedirectResponse
    {
        Gate::authorize('admin');

        if ($report->file_path) {
            Storage::disk('local')->delete($report->file_path);
        }

        $unitId = $report->ups_unit_id;
        $report->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Maintenance report deleted.')]);

        return to_route('units.show', $unitId);
    }

    public function download(MaintenanceReport $report): StreamedResponse
    {
        abort_unless($report->file_path !== null, 404);

        return Storage::disk('local')->download($report->file_path, $report->file_name);
    }

    /**
     * Technicians correct their own reports; admins any.
     */
    private function canEdit(MaintenanceReport $report): bool
    {
        $user = request()->user();

        return $user instanceof User && ($user->isAdmin() || $report->reporter_id === $user->id);
    }

    private function attachFile(Request $request, MaintenanceReport $report): void
    {
        $file = $request->file('file');

        if ($file === null) {
            return;
        }

        if ($report->file_path) {
            Storage::disk('local')->delete($report->file_path);
        }

        $report->forceFill([
            'file_path' => $file->store('maintenance-reports', 'local') ?: null,
            'file_name' => $file->getClientOriginalName(),
        ]);
    }
}
