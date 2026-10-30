<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MaintenanceReportController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\UpsUnitController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::resource('units', UpsUnitController::class);
    Route::post('units/{unit}/poll', [UpsUnitController::class, 'poll'])->middleware('throttle:10,1')->name('units.poll');

    Route::get('units/{unit}/reports/create', [MaintenanceReportController::class, 'create'])->name('units.reports.create');
    Route::post('units/{unit}/reports', [MaintenanceReportController::class, 'store'])->name('units.reports.store');
    Route::resource('reports', MaintenanceReportController::class)->only(['show', 'edit', 'update', 'destroy']);
    Route::get('reports/{report}/file', [MaintenanceReportController::class, 'download'])->name('reports.download');

    Route::middleware('can:admin')->group(function () {
        Route::get('sites', [SiteController::class, 'index'])->name('sites.index');
        Route::post('sites', [SiteController::class, 'store'])->name('sites.store');
        Route::put('sites/{site}', [SiteController::class, 'update'])->name('sites.update');
        Route::delete('sites/{site}', [SiteController::class, 'destroy'])->name('sites.destroy');
        Route::post('regions', [SiteController::class, 'storeRegion'])->name('regions.store');
        Route::delete('regions/{region}', [SiteController::class, 'destroyRegion'])->name('regions.destroy');
        Route::resource('staff', StaffController::class)->except('show')->parameters(['staff' => 'user']);
    });
});

require __DIR__.'/settings.php';
