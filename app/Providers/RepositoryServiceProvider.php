<?php

namespace App\Providers;

use App\Repositories\Contracts\EmployeeRepository;
use App\Repositories\Contracts\EmploymentTypeRepository;
use App\Repositories\Contracts\OfficeRepository;
use App\Repositories\Contracts\PositionRepository;
use App\Repositories\Eloquent\EloquentEmployeeRepository;
use App\Repositories\Eloquent\EloquentEmploymentTypeRepository;
use App\Repositories\Eloquent\EloquentOfficeRepository;
use App\Repositories\Eloquent\EloquentPositionRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register the application's repository bindings.
     */
    public function register(): void
    {
        $this->app->bind(OfficeRepository::class, EloquentOfficeRepository::class);
        $this->app->bind(EmploymentTypeRepository::class, EloquentEmploymentTypeRepository::class);
        $this->app->bind(PositionRepository::class, EloquentPositionRepository::class);
        $this->app->bind(EmployeeRepository::class, EloquentEmployeeRepository::class);
    }
}
