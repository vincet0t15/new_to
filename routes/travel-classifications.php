<?php
use App\Http\Controllers\TravelClassifications\TravelClassificationController;
use Illuminate\Support\Facades\Route;
Route::middleware(['auth','verified'])->prefix('travel-classifications')->name('travel-classifications.')->group(function () {
    Route::get('/', [TravelClassificationController::class,'index'])->middleware('permission:travel-classifications.view')->name('index');
    Route::post('/', [TravelClassificationController::class,'store'])->middleware('permission:travel-classifications.create')->name('store');
    Route::put('/{travelClassification}', [TravelClassificationController::class,'update'])->middleware('permission:travel-classifications.update')->name('update');
    Route::delete('/{travelClassification}', [TravelClassificationController::class,'destroy'])->middleware('permission:travel-classifications.delete')->name('destroy');
});
