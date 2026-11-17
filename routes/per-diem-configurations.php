<?php
use App\Http\Controllers\PerDiemConfigurations\PerDiemConfigurationController;
use Illuminate\Support\Facades\Route;
Route::middleware(['auth','verified'])->prefix('per-diem-configurations')->name('per-diem-configurations.')->group(function () {
    Route::get('/', [PerDiemConfigurationController::class,'index'])->middleware('permission:per-diem-configurations.view')->name('index');
    Route::get('/create', [PerDiemConfigurationController::class,'create'])->middleware('permission:per-diem-configurations.create')->name('create');
    Route::post('/', [PerDiemConfigurationController::class,'store'])->middleware('permission:per-diem-configurations.create')->name('store');
    Route::get('/{perDiemConfiguration}/edit', [PerDiemConfigurationController::class,'edit'])->middleware('permission:per-diem-configurations.update')->name('edit');
    Route::put('/{perDiemConfiguration}', [PerDiemConfigurationController::class,'update'])->middleware('permission:per-diem-configurations.update')->name('update');
    Route::get('/{perDiemConfiguration}/delete', [PerDiemConfigurationController::class,'delete'])->middleware('permission:per-diem-configurations.delete')->name('delete');
    Route::delete('/{perDiemConfiguration}', [PerDiemConfigurationController::class,'destroy'])->middleware('permission:per-diem-configurations.delete')->name('destroy');
});
