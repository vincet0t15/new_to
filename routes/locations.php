<?php
use App\Http\Controllers\Locations\LocationController;
use Illuminate\Support\Facades\Route;
Route::middleware(['auth','verified'])->prefix('locations')->name('locations.')->group(function () {
    Route::get('/', [LocationController::class,'index'])->middleware('permission:locations.view')->name('index');
    Route::post('/', [LocationController::class,'store'])->middleware('permission:locations.create')->name('store');
    Route::put('/{location}', [LocationController::class,'update'])->middleware('permission:locations.update')->name('update');
    Route::delete('/{location}', [LocationController::class,'destroy'])->middleware('permission:locations.delete')->name('destroy');
});
