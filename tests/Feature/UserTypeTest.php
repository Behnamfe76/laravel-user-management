<?php

namespace Fereydooni\LaravelUserManagement\Tests\Feature;

use Fereydooni\LaravelUserManagement\Tests\TestCase;
use Fereydooni\LaravelUserManagement\Tests\Models\User;
use Fereydooni\LaravelUserManagement\Contracts\UserManagerInterface;

class UserTypeTest extends TestCase
{
    private UserManagerInterface $userManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userManager = $this->app->make(UserManagerInterface::class);
    }

    public function test_can_set_user_type()
    {
        $user = User::factory()->create();
        $this->userManager->setUserType($user, 'admin');

        $this->assertEquals('admin', $user->user_type);
    }

    public function test_can_get_user_type()
    {
        $user = User::factory()->create();
        $this->userManager->setUserType($user, 'admin');

        $this->assertEquals('admin', $this->userManager->getUserType($user));
    }

    public function test_can_check_user_type()
    {
        $user = User::factory()->create();
        $this->userManager->setUserType($user, 'admin');

        $this->assertTrue($this->userManager->isUserType($user, 'admin'));
        $this->assertFalse($this->userManager->isUserType($user, 'user'));
    }

    public function test_can_remove_user_type()
    {
        $user = User::factory()->create();
        $this->userManager->setUserType($user, 'admin');
        $this->userManager->removeUserType($user);

        $this->assertNull($user->user_type);
    }

    public function test_can_check_multiple_user_types()
    {
        $user = User::factory()->create();
        $this->userManager->setUserType($user, 'admin');

        $this->assertTrue($this->userManager->isUserType($user, ['admin', 'moderator']));
        $this->assertFalse($this->userManager->isUserType($user, ['user', 'moderator']));
    }
} 