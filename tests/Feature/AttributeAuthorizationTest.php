<?php

namespace Fereydooni\LaravelUserManagement\Tests\Feature;

use Fereydooni\LaravelUserManagement\Tests\TestCase;
use Fereydooni\LaravelUserManagement\Tests\Models\User;
use Fereydooni\LaravelUserManagement\Attributes\Authorize;
use Illuminate\Support\Facades\Gate;

class AttributeAuthorizationTest extends TestCase
{
    public function test_can_check_permission_through_attribute()
    {
        $user = User::factory()->create();
        $permission = \Spatie\Permission\Models\Permission::create(['name' => 'edit-users']);
        $user->givePermissionTo($permission);

        $this->assertTrue($user->hasPermissionThroughAttribute('edit-users'));
    }

    public function test_can_check_role_through_attribute()
    {
        $user = User::factory()->create();
        $role = \Spatie\Permission\Models\Role::create(['name' => 'admin']);
        $user->assignRole($role);

        $this->assertTrue($user->hasPermissionThroughAttribute(null, 'admin'));
    }

    public function test_can_check_user_type_through_attribute()
    {
        $user = User::factory()->create();
        $user->setUserType('manager');

        $this->assertTrue($user->hasPermissionThroughAttribute(null, null, 'manager'));
    }

    public function test_can_check_combined_authorization()
    {
        $user = User::factory()->create();
        $role = \Spatie\Permission\Models\Role::create(['name' => 'admin']);
        $permission = \Spatie\Permission\Models\Permission::create(['name' => 'edit-users']);
        
        $user->assignRole($role);
        $user->givePermissionTo($permission);
        $user->setUserType('manager');

        $this->assertTrue($user->hasPermissionThroughAttribute('edit-users', 'admin', 'manager'));
    }

    public function test_authorization_fails_with_incorrect_permission()
    {
        $user = User::factory()->create();
        $editPermission = \Spatie\Permission\Models\Permission::create(['name' => 'edit-users']);
        $deletePermission = \Spatie\Permission\Models\Permission::create(['name' => 'delete-users']);
        $user->givePermissionTo($editPermission);

        $this->assertFalse($user->hasPermissionThroughAttribute('delete-users'));
    }

    public function test_authorization_fails_with_incorrect_role()
    {
        $user = User::factory()->create();
        $role = \Spatie\Permission\Models\Role::create(['name' => 'admin']);
        $user->assignRole($role);

        $this->assertFalse($user->hasPermissionThroughAttribute(null, 'editor'));
    }

    public function test_authorization_fails_with_incorrect_user_type()
    {
        $user = User::factory()->create();
        $user->setUserType('manager');

        $this->assertFalse($user->hasPermissionThroughAttribute(null, null, 'admin'));
    }

    public function test_gates_are_registered_correctly()
    {
        $user = User::factory()->create();
        $permission = \Spatie\Permission\Models\Permission::create(['name' => 'edit-users']);
        $user->givePermissionTo($permission);

        // Register gates using the trait's method
        User::registerAttributeGates();

        // Test direct permission check
        $this->assertTrue($user->hasPermissionThroughAttribute('edit-users'));

        // Test gate check
        $this->assertTrue(Gate::forUser($user)->allows('edit-users'));
    }
} 