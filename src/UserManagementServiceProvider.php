<?php

declare(strict_types=1);

namespace Fereydooni\LaravelUserManagement;

use Fereydooni\LaravelUserManagement\Contracts\UserManagerInterface;
use Fereydooni\LaravelUserManagement\Middleware\AuthorizeAttribute;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\PermissionServiceProvider;

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

        $this->app->register(PermissionServiceProvider::class);
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

        // Register middleware globally
        $this->app['router']->aliasMiddleware('authorize', AuthorizeAttribute::class);
        
        // Apply middleware to all routes
        $this->app['router']->pushMiddlewareToGroup('web', AuthorizeAttribute::class);
        $this->app['router']->pushMiddlewareToGroup('api', AuthorizeAttribute::class);

        // Register command to scan for attributes
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Fereydooni\LaravelUserManagement\Console\Commands\ScanAttributesCommand::class,
            ]);
        }
    }
} 