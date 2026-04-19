<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * All repository bindings.
     * Add new bindings here when creating a new microservice.
     *
     * Format: Interface::class => Implementation::class
     */
    protected array $repositories = [
        // \App\Repositories\Contracts\ExampleRepositoryInterface::class => \App\Repositories\ExampleRepository::class,
        \App\Repositories\Contracts\TaskRepositoryInterface::class => \App\Repositories\TaskRepository::class,
        \App\Repositories\Contracts\ProjectRepositoryInterface::class => \App\Repositories\ProjectRepository::class,
        \App\Repositories\Contracts\EmployeeRepositoryInterface::class => \App\Repositories\EmployeeRepository::class,
        \App\Repositories\Contracts\WarehouseRepositoryInterface::class => \App\Repositories\WarehouseRepository::class,
        \App\Repositories\Contracts\ProductRepositoryInterface::class => \App\Repositories\ProductRepository::class,
        \App\Repositories\Contracts\PaymentRepositoryInterface::class => \App\Repositories\PaymentRepository::class,
        \App\Repositories\Contracts\AccountRepositoryInterface::class => \App\Repositories\AccountRepository::class,
        \App\Repositories\Contracts\InvoiceRepositoryInterface::class => \App\Repositories\InvoiceRepository::class,
        \App\Repositories\Contracts\PartyRepositoryInterface::class => \App\Repositories\PartyRepository::class,
        \App\Repositories\Contracts\BranchRepositoryInterface::class => \App\Repositories\BranchRepository::class,
        \App\Repositories\Contracts\OrganizationRepositoryInterface::class => \App\Repositories\OrganizationRepository::class,
    ];

    public function register(): void
    {
        foreach ($this->repositories as $interface => $implementation) {
            $this->app->bind($interface, $implementation);
        }
    }

    public function boot(): void
    {
        //
    }
}
