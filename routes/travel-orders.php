<?php

use App\Http\Controllers\TravelOrders\TravelOrderController;
use App\Http\Controllers\TravelOrders\TravelOrderItineraryController;
use App\Http\Controllers\TravelOrders\TravelOrderTripTicketController;
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

        Route::post('/{travelOrder}/itineraries', [TravelOrderItineraryController::class, 'store'])
            ->middleware('permission:travel-orders.update')
            ->name('itineraries.store');
        Route::put('/{travelOrder}/itineraries/{itinerary}', [TravelOrderItineraryController::class, 'update'])
            ->middleware('permission:travel-orders.update')
            ->name('itineraries.update')
            ->scopeBindings();
        Route::delete('/{travelOrder}/itineraries/{itinerary}', [TravelOrderItineraryController::class, 'destroy'])
            ->middleware('permission:travel-orders.update')
            ->name('itineraries.destroy')
            ->scopeBindings();

        Route::get('/{travelOrder}/trip-tickets/create', [TravelOrderTripTicketController::class, 'create'])
            ->middleware('permission:travel-orders.update')
            ->name('trip-tickets.create');
        Route::post('/{travelOrder}/trip-tickets', [TravelOrderTripTicketController::class, 'store'])
            ->middleware('permission:travel-orders.update')
            ->name('trip-tickets.store');
        Route::get('/{travelOrder}/trip-tickets/{tripTicket}', [TravelOrderTripTicketController::class, 'show'])
            ->name('trip-tickets.show')
            ->scopeBindings();
        Route::get('/{travelOrder}/trip-tickets/{tripTicket}/edit', [TravelOrderTripTicketController::class, 'edit'])
            ->middleware('permission:travel-orders.update')
            ->name('trip-tickets.edit')
            ->scopeBindings();
        Route::put('/{travelOrder}/trip-tickets/{tripTicket}', [TravelOrderTripTicketController::class, 'update'])
            ->middleware('permission:travel-orders.update')
            ->name('trip-tickets.update')
            ->scopeBindings();
        Route::post('/{travelOrder}/trip-tickets/{tripTicket}/complete', [TravelOrderTripTicketController::class, 'complete'])
            ->middleware('permission:travel-orders.update')
            ->name('trip-tickets.complete')
            ->scopeBindings();
        Route::post('/{travelOrder}/trip-tickets/{tripTicket}/cancel', [TravelOrderTripTicketController::class, 'cancel'])
            ->middleware('permission:travel-orders.update')
            ->name('trip-tickets.cancel')
            ->scopeBindings();
        Route::delete('/{travelOrder}/trip-tickets/{tripTicket}', [TravelOrderTripTicketController::class, 'destroy'])
            ->middleware('permission:travel-orders.update')
            ->name('trip-tickets.destroy')
            ->scopeBindings();
        Route::get('/{travelOrder}/trip-tickets/{tripTicket}/print', [TravelOrderTripTicketController::class, 'print'])
            ->middleware('permission:travel-orders.print')
            ->name('trip-tickets.print')
            ->scopeBindings();
    });

Route::middleware(['auth', 'verified'])
    ->prefix('travel-orders/{travelOrder}/print')
    ->name('travel-orders.print.')
    ->group(function () {
        Route::get('/order', [TravelOrderController::class, 'printOrder'])
            ->middleware('permission:travel-orders.print')
            ->name('order');
        Route::get('/itinerary', [TravelOrderController::class, 'printItinerary'])
            ->middleware('permission:travel-orders.print')
            ->name('itinerary');
    });
