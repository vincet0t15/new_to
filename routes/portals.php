<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Portals
|--------------------------------------------------------------------------
|
| One application, four workspaces. Each portal is a route prefix with its
| own landing page, guarded server-side by the `portal` middleware. The
| pages are placeholders until the Travel Order screens arrive.
|
*/

Route::middleware(['auth', 'verified', 'portal:employee'])
    ->prefix('employee')
    ->name('employee.')
    ->group(function () {
        Route::inertia('dashboard', 'portals/employee/dashboard')
            ->name('dashboard');
    });

Route::middleware(['auth', 'verified', 'portal:department-head'])
    ->prefix('department-head')
    ->name('department-head.')
    ->group(function () {
        Route::inertia('dashboard', 'portals/department-head/dashboard')
            ->name('dashboard');
    });

Route::middleware(['auth', 'verified', 'portal:approvals'])
    ->prefix('approvals')
    ->name('approvals.')
    ->group(function () {
        Route::inertia('inbox', 'portals/approvals/inbox')
            ->name('inbox');
    });

Route::middleware(['auth', 'verified', 'portal:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::inertia('dashboard', 'portals/admin/dashboard')
            ->name('dashboard');
    });
