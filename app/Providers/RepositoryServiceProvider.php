<?php

namespace App\Providers;

use App\Repositories\Contracts\OfficeRepository;
use App\Repositories\Eloquent\EloquentOfficeRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register the application's repository bindings.
     */
    public function register(): void
    {
        $this->app->bind(OfficeRepository::class, EloquentOfficeRepository::class);
    }
}
