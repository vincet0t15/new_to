<?php

use App\Http\Controllers\ExpenseTypes\ExpenseTypeController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('expense-types')->name('expense-types.')->group(function () {
    Route::get('/', [ExpenseTypeController::class, 'index'])->middleware('permission:expense-types.view')->name('index');
    Route::post('/', [ExpenseTypeController::class, 'store'])->middleware('permission:expense-types.create')->name('store');
    Route::put('/{expenseType}', [ExpenseTypeController::class, 'update'])->middleware('permission:expense-types.update')->name('update');
    Route::delete('/{expenseType}', [ExpenseTypeController::class, 'destroy'])->middleware('permission:expense-types.delete')->name('destroy');
});
