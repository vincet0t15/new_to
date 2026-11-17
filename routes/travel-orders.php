<?php

use App\Http\Controllers\TravelOrders\TravelOrderController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'portal:employee', 'permission:travel-orders.view'])
    ->prefix('employee/travel-orders')
    ->name('travel-orders.')
    ->group(function () {
        Route::get('/', [TravelOrderController::class, 'index'])->name('index');
        Route::get('/returned', [TravelOrderController::class, 'returned'])->name('returned')->middleware('permission:travel-orders.view');
        Route::get('/create', [TravelOrderController::class, 'create'])->name('create')->middleware('permission:travel-orders.create');
        Route::post('/', [TravelOrderController::class, 'store'])->name('store')->middleware('permission:travel-orders.create');
        Route::get('/{travelOrder}', [TravelOrderController::class, 'show'])->name('show');
        Route::get('/{travelOrder}/edit', [TravelOrderController::class, 'edit'])->name('edit')->middleware('permission:travel-orders.update');
        Route::put('/{travelOrder}', [TravelOrderController::class, 'update'])->name('update')->middleware('permission:travel-orders.update');
        Route::post('/{travelOrder}/submit', [TravelOrderController::class, 'submit'])->name('submit')->middleware('permission:travel-orders.send');
        Route::post('/{travelOrder}/resubmit', [TravelOrderController::class, 'resubmit'])->name('resubmit')->middleware('permission:travel-orders.resubmit');
        Route::delete('/{travelOrder}', [TravelOrderController::class, 'destroy'])->name('destroy')->middleware('permission:travel-orders.delete');
    });
