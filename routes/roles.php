<?php

use App\Http\Controllers\Roles\RoleController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'portal:admin'])
    ->prefix('admin/roles')
    ->name('roles.')
    ->group(function () {
        Route::get('/', [RoleController::class, 'index'])
            ->middleware('permission:roles.view')
            ->name('index');
        Route::post('/', [RoleController::class, 'store'])
            ->middleware('permission:roles.create')
            ->name('store');
        Route::put('/{role}', [RoleController::class, 'update'])
            ->middleware('permission:roles.update')
            ->name('update');
        Route::delete('/{role}', [RoleController::class, 'destroy'])
            ->middleware('permission:roles.delete')
            ->name('destroy');
    });
