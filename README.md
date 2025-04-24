# Dynamic User Management for Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/fereydooni/laravel-user-management.svg)](https://packagist.org/packages/fereydooni/laravel-user-management)
[![Total Downloads](https://img.shields.io/packagist/dt/fereydooni/laravel-user-management.svg)](https://packagist.org/packages/fereydooni/laravel-user-management)
[![PHP Version](https://img.shields.io/packagist/php-v/fereydooni/laravel-user-management.svg)](https://packagist.org/packages/fereydooni/laravel-user-management)
[![License](https://img.shields.io/github/license/fereydooni/laravel-user-management.svg)](LICENSE.md)

A powerful, attribute-driven user management package for Laravel. Define user fields, roles, and permissions using PHP 8.1+ attributes.

## Features

- Dynamic user field management via PHP attributes
- Role and permission management
- Support for custom fields stored in JSON or pivot tables
- Configurable integration with existing applications
- CLI command for scanning attributes in your models
- Compatible with Laravel 10.x and PHP 8.1+

## Requirements

- PHP 8.1 or higher
- Laravel 10.x
- Composer

## Installation

You can install the package via composer:

```bash
composer require fereydooni/laravel-user-management
```

Publish the configuration file and migrations:

```bash
php artisan vendor:publish --provider="Fereydooni\LaravelUserManagement\UserManagementServiceProvider"
```

Run the migrations:

```bash
php artisan migrate
```

## Configuration

After publishing the configuration, you can find it at `config/user-management.php`. Key options include:

- `enable_dynamic_fields`: Enable/disable dynamic field functionality
- `user_model`: Your application's user model
- `role_integration`: Choose between 'custom' or 'spatie' for role management
- `tables`: Configure custom table names if needed
- `soft_deletes`: Enable/disable soft deletes for users
- `cache`: Configure caching settings for roles and permissions

## Usage

### Defining User Fields and Roles

Use PHP attributes to define custom fields and roles on your User model:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Fereydooni\LaravelUserManagement\Attributes\UserField;
use Fereydooni\LaravelUserManagement\Attributes\UserRole;

#[UserField(name: 'phone_number', type: 'string', required: true, unique: true)]
#[UserField(name: 'address', type: 'string')]
#[UserField(name: 'age', type: 'integer')]
#[UserField(name: 'is_verified', type: 'boolean', default: false)]
#[UserRole(name: 'admin', permissions: ['view_dashboard', 'manage_users', 'manage_content'])]
#[UserRole(name: 'editor', permissions: ['view_dashboard', 'edit_content'])]
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    // ...
}
```

### Managing Users

Inject the UserManager to manage users in your controllers:

```php
<?php

namespace App\Http\Controllers;

use Fereydooni\LaravelUserManagement\Contracts\UserManagerInterface;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(
        protected UserManagerInterface $userManager
    ) {}

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8',
            'phone_number' => 'required|string|unique:users',
            'age' => 'nullable|integer',
            // other fields...
        ]);
        
        $user = $this->userManager->create($validated);
        
        return response()->json($user, 201);
    }

    public function update(Request $request, $id)
    {
        $user = $this->userManager->find($id);
        
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }
        
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'phone_number' => 'sometimes|string|unique:users,phone_number,' . $id,
            // other fields...
        ]);
        
        $user = $this->userManager->update($user, $validated);
        
        return response()->json($user);
    }
    
    public function assignRole($userId, $role)
    {
        $user = $this->userManager->find($userId);
        
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }
        
        $this->userManager->assignRole($user, $role);
        
        return response()->json(['message' => "Role {$role} assigned successfully"]);
    }
}
```

### Querying by Dynamic Fields

Use the `whereField` method to query users by their dynamic fields:

```php
$users = $userManager->whereField('phone_number', '1234567890')->get();

// Or for multiple conditions
$activeAdults = $userManager->whereField('is_verified', true)
    ->whereField('age', '>=', 18)
    ->get();
```

### Scanning for Attributes

You can scan your models for UserField and UserRole attributes using the included command:

```bash
# Scan the default user model
php artisan user-management:scan-attributes

# Scan a specific model
php artisan user-management:scan-attributes --model="App\Models\Admin"

# Scan all models in a directory
php artisan user-management:scan-attributes --path="app/Models"
```

## Testing

This package uses Pest PHP for testing. Run the tests with:

```bash
composer test
```

or directly with Pest:

```bash
./vendor/bin/pest
```

Group specific tests:

```bash
./vendor/bin/pest --group=user-manager
```

## Changelog

Please see [CHANGELOG.md](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email the author instead of using the issue tracker.

## Credits

- [Behnam Fereydooni](https://github.com/Behnamfe76)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information. 