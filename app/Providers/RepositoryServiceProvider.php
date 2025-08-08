<?php

namespace App\Providers;

use App\Interfaces\AppointmentRepositoryInterface;
use App\Interfaces\LeadRepositoryInterface;
use App\Interfaces\OrganizationRepositoryInterface;
use App\Interfaces\TeamRepositoryInterface;
use App\Interfaces\UserRepositoryInterface;
use App\Repositories\EloquentAppointmentRepository;
use App\Repositories\EloquentLeadRepository;
use App\Repositories\EloquentOrganizationRepository;
use App\Repositories\EloquentTeamRepository;
use App\Repositories\EloquentUserRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, EloquentUserRepository::class);
        $this->app->bind(TeamRepositoryInterface::class, EloquentTeamRepository::class);
        $this->app->bind(LeadRepositoryInterface::class, EloquentLeadRepository::class);
        $this->app->bind(OrganizationRepositoryInterface::class, EloquentOrganizationRepository::class);
        $this->app->bind(AppointmentRepositoryInterface::class, EloquentAppointmentRepository::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
