<?php

use App\Http\Controllers\FuelTypes\FuelTypeController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('fuel-types')->name('fuel-types.')->group(function () {
    Route::get('/', [FuelTypeController::class, 'index'])->middleware('permission:fuel-types.view')->name('index');
    Route::post('/', [FuelTypeController::class, 'store'])->middleware('permission:fuel-types.create')->name('store');
    Route::put('/{fuelType}', [FuelTypeController::class, 'update'])->middleware('permission:fuel-types.update')->name('update');
    Route::delete('/{fuelType}', [FuelTypeController::class, 'destroy'])->middleware('permission:fuel-types.delete')->name('destroy');
});
