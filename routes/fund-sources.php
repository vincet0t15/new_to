<?php
use App\Http\Controllers\FundSources\FundSourceController;
use Illuminate\Support\Facades\Route;
Route::middleware(['auth','verified'])->prefix('fund-sources')->name('fund-sources.')->group(function () {
    Route::get('/', [FundSourceController::class,'index'])->middleware('permission:fund-sources.view')->name('index');
    Route::get('/create', [FundSourceController::class,'create'])->middleware('permission:fund-sources.create')->name('create');
    Route::post('/', [FundSourceController::class,'store'])->middleware('permission:fund-sources.create')->name('store');
    Route::get('/{fundSource}/edit', [FundSourceController::class,'edit'])->middleware('permission:fund-sources.update')->name('edit');
    Route::put('/{fundSource}', [FundSourceController::class,'update'])->middleware('permission:fund-sources.update')->name('update');
    Route::get('/{fundSource}/delete', [FundSourceController::class,'delete'])->middleware('permission:fund-sources.delete')->name('delete');
    Route::delete('/{fundSource}', [FundSourceController::class,'destroy'])->middleware('permission:fund-sources.delete')->name('destroy');
});
