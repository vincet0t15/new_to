<?php

use App\Http\Controllers\Workflows\WorkflowStepTypeController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'portal:admin', 'permission:workflow.manage'])
    ->prefix('admin/workflow-step-types')
    ->name('workflow-step-types.')
    ->group(function () {
        Route::get('/', [WorkflowStepTypeController::class, 'index'])->name('index');
        Route::post('/', [WorkflowStepTypeController::class, 'store'])->name('store');
        Route::put('/{stepType}', [WorkflowStepTypeController::class, 'update'])->name('update');
        Route::delete('/{stepType}', [WorkflowStepTypeController::class, 'destroy'])->name('destroy');
    });
