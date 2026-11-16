<?php

use App\Http\Controllers\Workflows\OfficeDesignationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'portal:admin', 'permission:designations.manage'])
    ->prefix('admin/designations')
    ->name('designations.')
    ->group(function () {
        Route::get('/', [OfficeDesignationController::class, 'index'])->name('index');
        Route::post('/', [OfficeDesignationController::class, 'store'])->name('store');
        Route::put('/{designation}', [OfficeDesignationController::class, 'update'])->name('update');
        Route::delete('/{designation}', [OfficeDesignationController::class, 'destroy'])->name('destroy');
    });
