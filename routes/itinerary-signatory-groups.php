<?php

use App\Http\Controllers\ItinerarySignatoryGroups\ItinerarySignatoryGroupController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'portal:admin', 'permission:administration.view'])
    ->prefix('admin/itinerary-signatory-groups')
    ->name('itinerary-signatory-groups.')
    ->group(function () {
        Route::get('/', [ItinerarySignatoryGroupController::class, 'index'])
            ->middleware('permission:itinerary-signatory-groups.view')
            ->name('index');
        Route::post('/', [ItinerarySignatoryGroupController::class, 'store'])
            ->middleware('permission:itinerary-signatory-groups.create')
            ->name('store');
        Route::put('/{itinerarySignatoryGroup}', [ItinerarySignatoryGroupController::class, 'update'])
            ->middleware('permission:itinerary-signatory-groups.update')
            ->name('update');
        Route::delete('/{itinerarySignatoryGroup}', [ItinerarySignatoryGroupController::class, 'destroy'])
            ->middleware('permission:itinerary-signatory-groups.delete')
            ->name('destroy');
    });
