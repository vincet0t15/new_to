<?php

use App\Http\Controllers\Vehicles\VehicleController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('vehicles')->name('vehicles.')->group(function () {
    Route::get('/', [VehicleController::class, 'index'])->middleware('permission:vehicles.view')->name('index');
    Route::post('/', [VehicleController::class, 'store'])->middleware('permission:vehicles.create')->name('store');
    Route::put('/{vehicle}', [VehicleController::class, 'update'])->middleware('permission:vehicles.update')->name('update');
    Route::delete('/{vehicle}', [VehicleController::class, 'destroy'])->middleware('permission:vehicles.delete')->name('destroy');
});
