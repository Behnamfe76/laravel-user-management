<?php

namespace Fereydooni\LaravelUserManagement\Tests\Unit;

use Fereydooni\LaravelUserManagement\Tests\TestCase;
use Fereydooni\LaravelUserManagement\UserManagementServiceProvider;
use Spatie\Permission\PermissionServiceProvider;

class ServiceProviderTest extends TestCase
{
    public function test_service_provider_is_registered()
    {
        $this->assertTrue($this->app->providerIsLoaded(UserManagementServiceProvider::class));
    }

    public function test_permission_service_provider_is_registered()
    {
        $this->assertTrue($this->app->providerIsLoaded(PermissionServiceProvider::class));
    }

    public function test_config_is_published()
    {
        $this->assertTrue($this->app['config']->has('user-management'));
        $this->assertTrue($this->app['config']->has('user-management.user_model'));
        $this->assertTrue($this->app['config']->has('user-management.default_roles'));
        $this->assertTrue($this->app['config']->has('user-management.default_permissions'));
        $this->assertTrue($this->app['config']->has('user-management.attribute_based_authorization'));
    }

    public function test_user_manager_is_bound()
    {
        $this->assertTrue($this->app->bound(\Fereydooni\LaravelUserManagement\Contracts\UserManagerInterface::class));
    }
} 