<?php

namespace App\Providers;

use App\Repositories\Contracts\EmployeeRepository;
use App\Repositories\Contracts\EmploymentTypeRepository;
use App\Repositories\Contracts\OfficeDesignationRepository;
use App\Repositories\Contracts\OfficeRepository;
use App\Repositories\Contracts\PositionRepository;
use App\Repositories\Contracts\WorkflowRepository;
use App\Repositories\Contracts\WorkflowStepTypeRepository;
use App\Repositories\Eloquent\EloquentEmployeeRepository;
use App\Repositories\Eloquent\EloquentEmploymentTypeRepository;
use App\Repositories\Eloquent\EloquentOfficeDesignationRepository;
use App\Repositories\Eloquent\EloquentOfficeRepository;
use App\Repositories\Eloquent\EloquentPositionRepository;
use App\Repositories\Eloquent\EloquentWorkflowRepository;
use App\Repositories\Eloquent\EloquentWorkflowStepTypeRepository;
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
        $this->app->bind(WorkflowRepository::class, EloquentWorkflowRepository::class);
        $this->app->bind(WorkflowStepTypeRepository::class, EloquentWorkflowStepTypeRepository::class);
        $this->app->bind(OfficeDesignationRepository::class, EloquentOfficeDesignationRepository::class);
    }
}
