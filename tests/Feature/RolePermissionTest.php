<?php

namespace Fereydooni\LaravelUserManagement\Tests\Feature;

use Fereydooni\LaravelUserManagement\Tests\TestCase;
use Fereydooni\LaravelUserManagement\Tests\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionTest extends TestCase
{
    public function test_can_assign_role_to_user()
    {
        $user = User::factory()->create();
        $role = Role::create(['name' => 'admin']);

        $user->assignRole($role);

        $this->assertTrue($user->hasRole('admin'));
    }

    public function test_can_assign_permission_to_role()
    {
        $role = Role::create(['name' => 'admin']);
        $permission = Permission::create(['name' => 'edit-users']);

        $role->givePermissionTo($permission);

        $this->assertTrue($role->hasPermissionTo('edit-users'));
    }

    public function test_user_inherits_role_permissions()
    {
        $user = User::factory()->create();
        $role = Role::create(['name' => 'admin']);
        $permission = Permission::create(['name' => 'edit-users']);

        $role->givePermissionTo($permission);
        $user->assignRole($role);

        $this->assertTrue($user->hasPermissionTo('edit-users'));
    }

    public function test_can_assign_permission_directly_to_user()
    {
        $user = User::factory()->create();
        $permission = Permission::create(['name' => 'edit-users']);

        $user->givePermissionTo($permission);

        $this->assertTrue($user->hasPermissionTo('edit-users'));
    }

    public function test_can_remove_role_from_user()
    {
        $user = User::factory()->create();
        $role = Role::create(['name' => 'admin']);

        $user->assignRole($role);
        $this->assertTrue($user->hasRole('admin'));

        $user->removeRole($role);
        $this->assertFalse($user->hasRole('admin'));
    }

    public function test_can_remove_permission_from_role()
    {
        $role = Role::create(['name' => 'admin']);
        $permission = Permission::create(['name' => 'edit-users']);

        $role->givePermissionTo($permission);
        $this->assertTrue($role->hasPermissionTo('edit-users'));

        $role->revokePermissionTo($permission);
        $this->assertFalse($role->hasPermissionTo('edit-users'));
    }

    public function test_can_remove_permission_from_user()
    {
        $user = User::factory()->create();
        $permission = Permission::create(['name' => 'edit-users']);

        $user->givePermissionTo($permission);
        $this->assertTrue($user->hasPermissionTo('edit-users'));

        $user->revokePermissionTo($permission);
        $this->assertFalse($user->hasPermissionTo('edit-users'));
    }
} 