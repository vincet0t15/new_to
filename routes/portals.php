<?php

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\ReportsController;
use App\Http\Controllers\Admin\TravelOrderRegisterController;
use App\Http\Controllers\Approvals\ApprovalInboxController;
use App\Http\Controllers\Approvals\TravelOrderActionController;
use App\Http\Controllers\Portals\PortalDashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Portals
|--------------------------------------------------------------------------
|
| One application, four workspaces. Each portal is a route prefix with its
| own landing page, guarded server-side by the `portal` middleware. The
| employee workspace owns filing, the other two share the approval screen
| because the holder of a step decides what may be done, not the prefix.
|
*/

Route::middleware(['auth', 'verified', 'portal:employee'])
    ->prefix('employee')
    ->name('employee.')
    ->group(function () {
        Route::get('dashboard', [PortalDashboardController::class, 'employee'])
            ->name('dashboard');
    });

Route::middleware(['auth', 'verified', 'portal:department-head'])
    ->prefix('department-head')
    ->name('department-head.')
    ->group(function () {
        Route::get('dashboard', [PortalDashboardController::class, 'departmentHead'])
            ->name('dashboard');

        Route::middleware('permission:travel-orders.view')->group(function () {
            Route::get('inbox', [ApprovalInboxController::class, 'index'])
                ->name('inbox');
            Route::get('history', [ApprovalInboxController::class, 'history'])
                ->name('history');
            Route::get('travel-orders/{travelOrder}', [ApprovalInboxController::class, 'show'])
                ->name('travel-orders.show');

            Route::post('travel-orders/{travelOrder}/receive', [TravelOrderActionController::class, 'receive'])
                ->middleware('permission:inbox.receive')
                ->name('travel-orders.receive');
            Route::post('travel-orders/{travelOrder}/approve', [TravelOrderActionController::class, 'approve'])
                ->middleware('permission:travel-orders.approve')
                ->name('travel-orders.approve');
            Route::post('travel-orders/{travelOrder}/acknowledge', [TravelOrderActionController::class, 'acknowledge'])
                ->middleware('permission:inbox.receive')
                ->name('travel-orders.acknowledge');
            Route::post('travel-orders/{travelOrder}/return', [TravelOrderActionController::class, 'returnTo'])
                ->middleware('permission:travel-orders.return')
                ->name('travel-orders.return');
            Route::post('travel-orders/{travelOrder}/forward', [TravelOrderActionController::class, 'forward'])
                ->middleware('permission:travel-orders.forward')
                ->name('travel-orders.forward');
        });
    });

Route::middleware(['auth', 'verified', 'portal:approvals'])
    ->prefix('approvals')
    ->name('approvals.')
    ->group(function () {
        Route::middleware('permission:travel-orders.view')->group(function () {
            Route::get('inbox', [ApprovalInboxController::class, 'index'])
                ->name('inbox');
            Route::get('history', [ApprovalInboxController::class, 'history'])
                ->name('history');
            Route::get('travel-orders/{travelOrder}', [ApprovalInboxController::class, 'show'])
                ->name('travel-orders.show');

            Route::post('travel-orders/{travelOrder}/receive', [TravelOrderActionController::class, 'receive'])
                ->middleware('permission:inbox.receive')
                ->name('travel-orders.receive');
            Route::post('travel-orders/{travelOrder}/approve', [TravelOrderActionController::class, 'approve'])
                ->middleware('permission:travel-orders.approve')
                ->name('travel-orders.approve');
            Route::post('travel-orders/{travelOrder}/acknowledge', [TravelOrderActionController::class, 'acknowledge'])
                ->middleware('permission:inbox.receive')
                ->name('travel-orders.acknowledge');
            Route::post('travel-orders/{travelOrder}/return', [TravelOrderActionController::class, 'returnTo'])
                ->middleware('permission:travel-orders.return')
                ->name('travel-orders.return');
            Route::post('travel-orders/{travelOrder}/forward', [TravelOrderActionController::class, 'forward'])
                ->middleware('permission:travel-orders.forward')
                ->name('travel-orders.forward');
        });
    });

Route::middleware(['auth', 'verified', 'portal:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::inertia('dashboard', 'portals/admin/dashboard')
            ->name('dashboard');

        Route::middleware('permission:travel-orders.view-all')->group(function () {
            Route::get('travel-orders', [TravelOrderRegisterController::class, 'index'])
                ->name('travel-orders.index');
            Route::get('reports', [ReportsController::class, 'index'])
                ->name('reports.index');
            Route::get('reports/export', [ReportsController::class, 'export'])
                ->name('reports.export');
        });

        Route::middleware('permission:audit.view')->group(function () {
            Route::get('audit', [AuditLogController::class, 'index'])
                ->name('audit.index');
        });
    });
