<?php

namespace App\Providers;

use App\Repositories\Contracts\EmployeeRepository;
use App\Repositories\Contracts\EmploymentTypeRepository;
use App\Repositories\Contracts\OfficeDesignationRepository;
use App\Repositories\Contracts\OfficeRepository;
use App\Repositories\Contracts\PositionRepository;
use App\Repositories\Contracts\TransportationModeRepository;
use App\Repositories\Contracts\ExpenseTypeRepository;
use App\Repositories\Contracts\FundSourceRepository;
use App\Repositories\Contracts\LocationRepository;
use App\Repositories\Contracts\PerDiemConfigurationRepository;
use App\Repositories\Contracts\TravelClassificationRepository;
use App\Repositories\Contracts\TravelOrderRepository;
use App\Repositories\Contracts\WorkflowRepository;
use App\Repositories\Contracts\WorkflowStepTypeRepository;
use App\Repositories\Eloquent\EloquentEmployeeRepository;
use App\Repositories\Eloquent\EloquentEmploymentTypeRepository;
use App\Repositories\Eloquent\EloquentOfficeDesignationRepository;
use App\Repositories\Eloquent\EloquentOfficeRepository;
use App\Repositories\Eloquent\EloquentPositionRepository;
use App\Repositories\Eloquent\EloquentTransportationModeRepository;
use App\Repositories\Eloquent\EloquentExpenseTypeRepository;
use App\Repositories\Eloquent\EloquentFundSourceRepository;
use App\Repositories\Eloquent\EloquentLocationRepository;
use App\Repositories\Eloquent\EloquentPerDiemConfigurationRepository;
use App\Repositories\Eloquent\EloquentTravelClassificationRepository;
use App\Repositories\Eloquent\EloquentTravelOrderRepository;
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
        $this->app->bind(TravelOrderRepository::class, EloquentTravelOrderRepository::class);
        $this->app->bind(TransportationModeRepository::class, EloquentTransportationModeRepository::class);
        $this->app->bind(ExpenseTypeRepository::class, EloquentExpenseTypeRepository::class);
        $this->app->bind(FundSourceRepository::class, EloquentFundSourceRepository::class);
        $this->app->bind(LocationRepository::class, EloquentLocationRepository::class);
        $this->app->bind(TravelClassificationRepository::class, EloquentTravelClassificationRepository::class);
        $this->app->bind(PerDiemConfigurationRepository::class, EloquentPerDiemConfigurationRepository::class);
        $this->app->bind(WorkflowRepository::class, EloquentWorkflowRepository::class);
        $this->app->bind(WorkflowStepTypeRepository::class, EloquentWorkflowStepTypeRepository::class);
        $this->app->bind(OfficeDesignationRepository::class, EloquentOfficeDesignationRepository::class);
    }
}
