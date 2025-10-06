<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Stancl\Tenancy\Events\TenantCreated;
use Stancl\Tenancy\Events\TenantDeleted;
use Stancl\Tenancy\Listeners\BootstrapTenancy;
use Stancl\Tenancy\Listeners\RevertToCentralContext;

class TenancyServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->bootTenancyEvents();
    }

    protected function bootTenancyEvents(): void
    {
        // Automatically create database and run migrations when tenant is created
        Event::listen(TenantCreated::class, function (TenantCreated $event) {
            $event->tenant->createDatabase();
            $event->tenant->run(function () {
                \Artisan::call('migrate', [
                    '--database' => 'tenant',
                    '--path' => 'database/migrations/tenant',
                    '--force' => true,
                ]);

                // Seed basic roles and permissions
                \Artisan::call('db:seed', [
                    '--class' => 'TenantSeeder',
                    '--force' => true,
                ]);
            });
        });

        // Clean up when tenant is deleted
        Event::listen(TenantDeleted::class, function (TenantDeleted $event) {
            $event->tenant->deleteDatabase();
        });
    }
}