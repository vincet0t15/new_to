<?php

use App\Http\Controllers\Offices\OfficeController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'portal:admin', 'permission:administration.view'])
    ->prefix('admin/offices')
    ->name('offices.')
    ->group(function () {
        Route::get('/', [OfficeController::class, 'index'])
            ->middleware('permission:offices.view')
            ->name('index');
        Route::post('/', [OfficeController::class, 'store'])
            ->middleware('permission:offices.create')
            ->name('store');
        Route::put('/{office}', [OfficeController::class, 'update'])
            ->middleware('permission:offices.update')
            ->name('update');
        Route::delete('/{office}', [OfficeController::class, 'destroy'])
            ->middleware('permission:offices.delete')
            ->name('destroy');
    });
