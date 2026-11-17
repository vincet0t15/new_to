<?php

use App\Http\Controllers\TransportationModes\TransportationModeController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('transportation-modes')->name('transportation-modes.')->group(function () {
    Route::get('/', [TransportationModeController::class, 'index'])->middleware('permission:transportation-modes.view')->name('index');
    Route::get('/create', [TransportationModeController::class, 'create'])->middleware('permission:transportation-modes.create')->name('create');
    Route::post('/', [TransportationModeController::class, 'store'])->middleware('permission:transportation-modes.create')->name('store');
    Route::get('/{transportationMode}/edit', [TransportationModeController::class, 'edit'])->middleware('permission:transportation-modes.update')->name('edit');
    Route::put('/{transportationMode}', [TransportationModeController::class, 'update'])->middleware('permission:transportation-modes.update')->name('update');
    Route::get('/{transportationMode}/delete', [TransportationModeController::class, 'delete'])->middleware('permission:transportation-modes.delete')->name('delete');
    Route::delete('/{transportationMode}', [TransportationModeController::class, 'destroy'])->middleware('permission:transportation-modes.delete')->name('destroy');
});
