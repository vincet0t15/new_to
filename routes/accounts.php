<?php

use App\Http\Controllers\Accounts\UserAccountController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'portal:admin'])
    ->prefix('admin/accounts')
    ->name('accounts.')
    ->group(function () {
        Route::get('/', [UserAccountController::class, 'index'])
            ->middleware('permission:accounts.view')
            ->name('index');
        Route::post('/', [UserAccountController::class, 'store'])
            ->middleware('permission:accounts.edit')
            ->name('store');
        Route::put('/{user}/roles', [UserAccountController::class, 'updateRoles'])
            ->middleware('permission:accounts.edit')
            ->name('roles.update');
        Route::put('/{user}/employee', [UserAccountController::class, 'updateEmployee'])
            ->middleware('permission:accounts.edit')
            ->name('employee.update');
        Route::put('/{user}/status', [UserAccountController::class, 'updateStatus'])
            ->middleware('permission:accounts.edit')
            ->name('status.update');
    });
