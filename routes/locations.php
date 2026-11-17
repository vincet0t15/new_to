<?php
use App\Http\Controllers\Locations\LocationController;
use Illuminate\Support\Facades\Route;
Route::middleware(['auth','verified'])->prefix('locations')->name('locations.')->group(function () {
    Route::get('/', [LocationController::class,'index'])->middleware('permission:locations.view')->name('index');
    Route::get('/create', [LocationController::class,'create'])->middleware('permission:locations.create')->name('create');
    Route::post('/', [LocationController::class,'store'])->middleware('permission:locations.create')->name('store');
    Route::get('/{location}/edit', [LocationController::class,'edit'])->middleware('permission:locations.update')->name('edit');
    Route::put('/{location}', [LocationController::class,'update'])->middleware('permission:locations.update')->name('update');
    Route::get('/{location}/delete', [LocationController::class,'delete'])->middleware('permission:locations.delete')->name('delete');
    Route::delete('/{location}', [LocationController::class,'destroy'])->middleware('permission:locations.delete')->name('destroy');
});
