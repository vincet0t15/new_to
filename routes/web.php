<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Notifications\NotificationController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::get('notifications/{notification}', [NotificationController::class, 'open'])
        ->name('notifications.open');
    Route::post('notifications/read-all', [NotificationController::class, 'readAll'])
        ->name('notifications.read-all');
});

require __DIR__.'/portals.php';
require __DIR__.'/settings.php';
require __DIR__.'/offices.php';
require __DIR__.'/employment-types.php';
require __DIR__.'/positions.php';
require __DIR__.'/employees.php';
require __DIR__.'/workflows.php';
require __DIR__.'/workflow-step-types.php';
require __DIR__.'/designations.php';
require __DIR__.'/travel-orders.php';
require __DIR__.'/transportation-modes.php';
require __DIR__.'/expense-types.php';
require __DIR__.'/fund-sources.php';
require __DIR__.'/locations.php';
require __DIR__.'/travel-classifications.php';
require __DIR__.'/per-diem-configurations.php';
require __DIR__.'/roles.php';
require __DIR__.'/accounts.php';
