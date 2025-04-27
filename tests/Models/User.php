<?php

namespace Fereydooni\LaravelUserManagement\Tests\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Fereydooni\LaravelUserManagement\Traits\HasPermissions;
use Fereydooni\LaravelUserManagement\Tests\Factories\UserFactory;
use Fereydooni\LaravelUserManagement\Attributes\Authorize;

class User extends Authenticatable
{
    use HasFactory;
    use HasPermissions;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    protected static function newFactory()
    {
        return UserFactory::new();
    }

    #[Authorize(permission: 'edit-users')]
    public function canEditUsers(): bool
    {
        return true;
    }
} 