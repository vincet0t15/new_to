<?php

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\FuelAllocationController;
use App\Http\Controllers\Admin\ReportsController;
use App\Http\Controllers\Admin\TravelOrderActionController as AdminTravelOrderActionController;
use App\Http\Controllers\Admin\TravelOrderRegisterController;
use App\Http\Controllers\Approvals\ApprovalInboxController;
use App\Http\Controllers\Approvals\FuelMonitoringController;
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
            Route::post('travel-orders/{travelOrder}/earmark', [TravelOrderActionController::class, 'earmark'])
                ->middleware('permission:travel-orders.earmark')
                ->name('travel-orders.earmark');
            Route::post('travel-orders/{travelOrder}/assign-fund-source', [TravelOrderActionController::class, 'assignFundSource'])
                ->middleware('permission:travel-orders.assign-fund-source')
                ->name('travel-orders.assign-fund-source');
            Route::post('travel-orders/{travelOrder}/record', [TravelOrderActionController::class, 'record'])
                ->middleware('permission:travel-orders.record')
                ->name('travel-orders.record');
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
            Route::get('fuel-monitoring', [FuelMonitoringController::class, 'index'])
                ->name('fuel-monitoring.index');
            Route::get('fuel-monitoring/export', [FuelMonitoringController::class, 'export'])
                ->name('fuel-monitoring.export');
            Route::middleware('permission:fuel-allocations.view')->group(function () {
                Route::get('fuel-allocations', [FuelAllocationController::class, 'index'])
                    ->name('fuel-allocations.index');
                Route::post('fuel-allocations', [FuelAllocationController::class, 'store'])
                    ->middleware('permission:fuel-allocations.manage')
                    ->name('fuel-allocations.store');
                Route::put('fuel-allocations/{fuelAllocation}', [FuelAllocationController::class, 'update'])
                    ->middleware('permission:fuel-allocations.manage')
                    ->name('fuel-allocations.update');
                Route::post('fuel-allocations/{fuelAllocation}/void', [FuelAllocationController::class, 'void'])
                    ->middleware('permission:fuel-allocations.manage')
                    ->name('fuel-allocations.void');
            });
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
            Route::post('travel-orders/{travelOrder}/earmark', [TravelOrderActionController::class, 'earmark'])
                ->middleware('permission:travel-orders.earmark')
                ->name('travel-orders.earmark');
            Route::post('travel-orders/{travelOrder}/assign-fund-source', [TravelOrderActionController::class, 'assignFundSource'])
                ->middleware('permission:travel-orders.assign-fund-source')
                ->name('travel-orders.assign-fund-source');
            Route::post('travel-orders/{travelOrder}/record', [TravelOrderActionController::class, 'record'])
                ->middleware('permission:travel-orders.record')
                ->name('travel-orders.record');
        });
    });

Route::middleware(['auth', 'verified', 'portal:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('dashboard', AdminDashboardController::class)
            ->name('dashboard');

        Route::middleware('permission:travel-orders.view-all')->group(function () {
            Route::get('travel-orders', [TravelOrderRegisterController::class, 'index'])
                ->name('travel-orders.index');
            Route::post('travel-orders/reassign-bulk', [AdminTravelOrderActionController::class, 'reassignBulk'])
                ->middleware('permission:travel-orders.reassign')
                ->name('travel-orders.reassign-bulk');
            Route::post('travel-orders/{travelOrder}/reassign', [AdminTravelOrderActionController::class, 'reassign'])
                ->middleware('permission:travel-orders.reassign')
                ->name('travel-orders.reassign');
            Route::get('reports', [ReportsController::class, 'index'])
                ->name('reports.index');
            Route::get('reports/export', [ReportsController::class, 'export'])
                ->name('reports.export');
            Route::get('fuel-monitoring', [FuelMonitoringController::class, 'index'])
                ->name('fuel-monitoring.index');
            Route::get('fuel-monitoring/export', [FuelMonitoringController::class, 'export'])
                ->name('fuel-monitoring.export');
            Route::middleware('permission:fuel-allocations.view')->group(function () {
                Route::get('fuel-allocations', [FuelAllocationController::class, 'index'])
                    ->name('fuel-allocations.index');
                Route::post('fuel-allocations', [FuelAllocationController::class, 'store'])
                    ->middleware('permission:fuel-allocations.manage')
                    ->name('fuel-allocations.store');
                Route::put('fuel-allocations/{fuelAllocation}', [FuelAllocationController::class, 'update'])
                    ->middleware('permission:fuel-allocations.manage')
                    ->name('fuel-allocations.update');
                Route::post('fuel-allocations/{fuelAllocation}/void', [FuelAllocationController::class, 'void'])
                    ->middleware('permission:fuel-allocations.manage')
                    ->name('fuel-allocations.void');
            });
        });

        Route::middleware('permission:audit.view')->group(function () {
            Route::get('audit', [AuditLogController::class, 'index'])
                ->name('audit.index');
        });
    });
