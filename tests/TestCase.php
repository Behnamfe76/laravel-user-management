<?php

namespace Fereydooni\LaravelUserManagement\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\Permission\PermissionServiceProvider;
use Fereydooni\LaravelUserManagement\UserManagementServiceProvider;

class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function getPackageProviders($app)
    {
        return [
            UserManagementServiceProvider::class,
            PermissionServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        // Setup user model
        $app['config']->set('user-management.user_model', \Fereydooni\LaravelUserManagement\Tests\Models\User::class);
        $app['config']->set('user-management.default_roles', ['admin', 'user']);
        $app['config']->set('user-management.default_permissions', ['view-users', 'create-users']);
        $app['config']->set('user-management.attribute_based_authorization', true);

        // Setup auth configuration
        $app['config']->set('auth.defaults.guard', 'web');
        $app['config']->set('auth.guards.web', [
            'driver' => 'session',
            'provider' => 'users',
        ]);
        $app['config']->set('auth.providers.users', [
            'driver' => 'eloquent',
            'model' => \Fereydooni\LaravelUserManagement\Tests\Models\User::class,
        ]);

        // Setup permission configuration
        $app['config']->set('permission.models.permission', \Spatie\Permission\Models\Permission::class);
        $app['config']->set('permission.models.role', \Spatie\Permission\Models\Role::class);
        $app['config']->set('permission.table_names.roles', 'roles');
        $app['config']->set('permission.table_names.permissions', 'permissions');
        $app['config']->set('permission.table_names.model_has_permissions', 'model_has_permissions');
        $app['config']->set('permission.table_names.model_has_roles', 'model_has_roles');
        $app['config']->set('permission.table_names.role_has_permissions', 'role_has_permissions');
    }

    protected function defineDatabaseMigrations()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->loadMigrationsFrom(__DIR__ . '/Database/migrations');
        $this->loadMigrationsFrom(__DIR__ . '/../vendor/spatie/laravel-permission/database/migrations');
    }
}
