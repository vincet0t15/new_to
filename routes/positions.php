<?php

use App\Http\Controllers\Positions\PositionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'portal:admin', 'permission:administration.view'])
    ->prefix('admin/positions')
    ->name('positions.')
    ->group(function () {
        Route::get('/', [PositionController::class, 'index'])
            ->middleware('permission:positions.view')
            ->name('index');
        Route::post('/', [PositionController::class, 'store'])
            ->middleware('permission:positions.create')
            ->name('store');
        Route::put('/{position}', [PositionController::class, 'update'])
            ->middleware('permission:positions.update')
            ->name('update');
        Route::delete('/{position}', [PositionController::class, 'destroy'])
            ->middleware('permission:positions.delete')
            ->name('destroy');
    });
