<?php
use App\Http\Controllers\FundSources\FundSourceController;
use Illuminate\Support\Facades\Route;
Route::middleware(['auth','verified'])->prefix('fund-sources')->name('fund-sources.')->group(function () {
    Route::get('/', [FundSourceController::class,'index'])->middleware('permission:fund-sources.view')->name('index');
    Route::post('/', [FundSourceController::class,'store'])->middleware('permission:fund-sources.create')->name('store');
    Route::put('/{fundSource}', [FundSourceController::class,'update'])->middleware('permission:fund-sources.update')->name('update');
    Route::delete('/{fundSource}', [FundSourceController::class,'destroy'])->middleware('permission:fund-sources.delete')->name('destroy');
});
