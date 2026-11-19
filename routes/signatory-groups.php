<?php

use App\Http\Controllers\SignatoryGroups\SignatoryGroupController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'portal:admin', 'permission:administration.view'])
    ->prefix('admin/signatory-groups')
    ->name('signatory-groups.')
    ->group(function () {
        Route::get('/', [SignatoryGroupController::class, 'index'])
            ->middleware('permission:signatory-groups.view')
            ->name('index');
        Route::post('/', [SignatoryGroupController::class, 'store'])
            ->middleware('permission:signatory-groups.create')
            ->name('store');
        Route::put('/{signatoryGroup}', [SignatoryGroupController::class, 'update'])
            ->middleware('permission:signatory-groups.update')
            ->name('update');
        Route::delete('/{signatoryGroup}', [SignatoryGroupController::class, 'destroy'])
            ->middleware('permission:signatory-groups.delete')
            ->name('destroy');
    });
