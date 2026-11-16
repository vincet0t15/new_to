<?php

use App\Http\Controllers\Employees\EmployeeController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'portal:admin', 'permission:administration.view'])
    ->prefix('admin/employees')
    ->name('employees.')
    ->group(function () {
        Route::get('/', [EmployeeController::class, 'index'])
            ->middleware('permission:employees.view')
            ->name('index');
        Route::get('/create', [EmployeeController::class, 'create'])
            ->middleware('permission:employees.create')
            ->name('create');
        Route::post('/', [EmployeeController::class, 'store'])
            ->middleware('permission:employees.create')
            ->name('store');
        Route::get('/{employee}/edit', [EmployeeController::class, 'edit'])
            ->middleware('permission:employees.update')
            ->name('edit');
        Route::put('/{employee}', [EmployeeController::class, 'update'])
            ->middleware('permission:employees.update')
            ->name('update');
        Route::delete('/{employee}', [EmployeeController::class, 'destroy'])
            ->middleware('permission:employees.delete')
            ->name('destroy');
    });
