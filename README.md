# Laravel User Management

A comprehensive user management package for Laravel with Spatie's role-permission integration and attribute-based authorization.

## Features

- User management with authentication
- Role and permission management using Spatie's package
- Attribute-based authorization with support for:
  - Permission-based authorization
  - Role-based authorization
  - User type-based authorization
- Easy to use and configure
- Extensible architecture

## Installation

```bash
composer require fereydooni/laravel-user-management
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag=user-management-config
```

## Configuration

The package configuration can be found in `config/user-management.php`. Here you can customize:

- Default roles
- Default permissions
- User model
- Attribute-based authorization settings
- Default user type

## Usage

### Basic User Management

```php
use Fereydooni\LaravelUserManagement\Traits\HasPermissions;

class User extends Authenticatable
{
    use HasPermissions;

    // Set custom user type (optional)
    protected string $userType = 'admin';
}
```

### Role and Permission Management

```php
// Create a role
$role = Role::create(['name' => 'writer']);

// Create a permission
$permission = Permission::create(['name' => 'edit articles']);

// Assign permission to role
$role->givePermissionTo($permission);

// Assign role to user
$user->assignRole('writer');
```

### Attribute-Based Authorization

You can use attributes to protect your controller methods with various combinations of permissions, roles, and user types:

```php
use Fereydooni\LaravelUserManagement\Attributes\Authorize;

class ArticleController extends Controller
{
    // Permission-based authorization
    #[Authorize(permission: 'view-articles')]
    public function index()
    {
        // Only users with 'view-articles' permission can access this
    }

    // Role-based authorization
    #[Authorize(role: 'editor')]
    public function create()
    {
        // Only users with 'editor' role can access this
    }

    // User type-based authorization
    #[Authorize(userType: 'manager')]
    public function manage()
    {
        // Only users of type 'manager' can access this
    }

    // Combined authorization
    #[Authorize(permission: 'edit-articles', role: 'editor', userType: 'manager')]
    public function edit()
    {
        // Only manager-type users with editor role and edit-articles permission can access this
    }
}
```

### Middleware

Register the middleware in your `app/Http/Kernel.php`:

```php
protected $routeMiddleware = [
    // ...
    'authorize' => \Fereydooni\LaravelUserManagement\Middleware\AuthorizeAttribute::class,
];
```

Then use it in your routes:

```php
Route::middleware('authorize')->group(function () {
    Route::get('/articles', [ArticleController::class, 'index']);
    Route::post('/articles', [ArticleController::class, 'store']);
});
```

## Examples

### Checking Permissions

```php
// Check if user has permission
if ($user->hasPermissionTo('edit articles')) {
    // User can edit articles
}

// Check if user has specific role
if ($user->hasRole('editor')) {
    // User has editor role
}

// Check if user is of specific type
if ($user->getUserType() === 'manager') {
    // User is of type manager
}

// Check combined authorization
if ($user->hasPermissionThroughAttribute('edit-articles', 'editor', 'manager')) {
    // User has all required permissions, roles, and type
}
```

### Registering Attribute Gates

```php
// Register gates for attribute-based authorization
User::registerAttributeGates();
```

## Security

If you discover any security related issues, please email behnamfe76@gmail.com instead of using the issue tracker.

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information. 