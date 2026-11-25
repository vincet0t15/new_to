<?php

use App\Http\Controllers\Workflows\OfficeDesignationController;
use App\Http\Controllers\Workflows\WorkflowController;
use App\Http\Controllers\Workflows\WorkflowStepController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'portal:admin', 'permission:workflow.manage'])
    ->prefix('admin/workflows')
    ->name('workflows.')
    ->group(function () {
        Route::get('/', [WorkflowController::class, 'index'])->name('index');
        Route::get('/preview', [WorkflowController::class, 'preview'])->name('preview');
        Route::post('/', [WorkflowController::class, 'store'])->name('store');
        Route::get('/{workflow}/edit', [WorkflowController::class, 'edit'])->name('edit');
        Route::put('/{workflow}', [WorkflowController::class, 'update'])->name('update');
        Route::post('/{workflow}/duplicate', [WorkflowController::class, 'duplicate'])->name('duplicate');
        Route::delete('/{workflow}', [WorkflowController::class, 'destroy'])->name('destroy');

        Route::get('/{workflow}/steps/{step}/designees', [OfficeDesignationController::class, 'forStep'])
            ->middleware('permission:designations.manage')
            ->name('steps.designees');
        Route::post('/{workflow}/steps', [WorkflowStepController::class, 'store'])->name('steps.store');
        Route::put('/{workflow}/steps/{step}', [WorkflowStepController::class, 'update'])->name('steps.update');
        Route::post('/{workflow}/steps/{step}/move', [WorkflowStepController::class, 'move'])->name('steps.move');
        Route::delete('/{workflow}/steps/{step}', [WorkflowStepController::class, 'destroy'])->name('steps.destroy');
    });
