<?php

use App\Http\Controllers\EmploymentTypes\EmploymentTypeController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'portal:admin', 'permission:administration.view'])
    ->prefix('admin/employment-types')
    ->name('employment-types.')
    ->group(function () {
        Route::get('/', [EmploymentTypeController::class, 'index'])
            ->middleware('permission:employment-types.view')
            ->name('index');
        Route::post('/', [EmploymentTypeController::class, 'store'])
            ->middleware('permission:employment-types.create')
            ->name('store');
        Route::put('/{employmentType}', [EmploymentTypeController::class, 'update'])
            ->middleware('permission:employment-types.update')
            ->name('update');
        Route::delete('/{employmentType}', [EmploymentTypeController::class, 'destroy'])
            ->middleware('permission:employment-types.delete')
            ->name('destroy');
    });
