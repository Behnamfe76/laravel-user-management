<?php

declare(strict_types=1);

namespace Fereydooni\LaravelUserManagement;

use Fereydooni\LaravelUserManagement\Contracts\UserManagerInterface;
use Illuminate\Support\ServiceProvider;

class UserManagementServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/user-management.php', 'user-management'
        );

        $this->app->bind(UserManagerInterface::class, function ($app) {
            return new UserManager($app);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Publish configuration file
        $this->publishes([
            __DIR__ . '/../config/user-management.php' => config_path('user-management.php'),
        ], 'user-management-config');

        // Publish migrations
        $this->publishes([
            __DIR__ . '/../database/migrations/' => database_path('migrations')
        ], 'user-management-migrations');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Register command to scan for attributes
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Fereydooni\LaravelUserManagement\Console\Commands\ScanAttributesCommand::class,
            ]);
        }
    }
} 