<?php

namespace Fereydooni\LaravelUserManagement\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Fereydooni\LaravelUserManagement\UserManagementServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up database structure
        $this->setUpDatabase();
    }

    protected function getPackageProviders($app)
    {
        return [
            UserManagementServiceProvider::class,
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
        
        // Setup user management config
        $app['config']->set('user-management.user_model', \stdClass::class);
        $app['config']->set('user-management.enable_dynamic_fields', true);
        $app['config']->set('user-management.tables.users', 'users');
        $app['config']->set('user-management.tables.roles', 'roles');
        $app['config']->set('user-management.tables.permissions', 'permissions');
        $app['config']->set('user-management.tables.user_roles', 'user_roles');
        $app['config']->set('user-management.tables.role_permissions', 'role_permissions');
        $app['config']->set('user-management.tables.user_fields', 'user_fields');
    }
    
    protected function setUpDatabase()
    {
        // First create users table
        $this->createUsersTable();
        
        // Then run package migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->artisan('migrate')->run();
    }

    protected function createUsersTable()
    {
        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->rememberToken();
                $table->timestamps();
            });
        }
    }
}
