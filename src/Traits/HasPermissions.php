<?php

namespace Fereydooni\LaravelUserManagement\Traits;

use Spatie\Permission\Traits\HasRoles;
use Fereydooni\LaravelUserManagement\Attributes\Authorize;
use Illuminate\Support\Facades\Gate;

trait HasPermissions
{
    use HasRoles;

    /**
     * The user type of the model.
     */
    protected string $userType = 'user';

    /**
     * Get the user type of the model.
     */
    public function getUserType(): string
    {
        return $this->userType;
    }

    /**
     * Set the user type of the model.
     */
    public function setUserType(string $type): void
    {
        $this->userType = $type;
    }

    /**
     * Check if the user has permission through attributes
     */
    public function hasPermissionThroughAttribute(string $permission, ?string $role = null, ?string $userType = null): bool
    {
        if (!config('user-management.attribute_based_authorization')) {
            return $this->hasPermissionTo($permission);
        }

        // Check user type first
        if ($userType !== null && $this->getUserType() !== $userType) {
            return false;
        }

        // Check role if specified
        if ($role !== null && !$this->hasRole($role)) {
            return false;
        }

        // Check permission if specified
        if ($permission !== null && !$this->hasPermissionTo($permission)) {
            return false;
        }

        return true;
    }

    /**
     * Register attribute-based authorization gates
     */
    public static function registerAttributeGates(): void
    {
        if (!config('user-management.attribute_based_authorization')) {
            return;
        }

        $reflection = new \ReflectionClass(static::class);
        $methods = $reflection->getMethods();

        foreach ($methods as $method) {
            $attributes = $method->getAttributes(Authorize::class);
            foreach ($attributes as $attribute) {
                $instance = $attribute->newInstance();
                
                // Create a unique gate name based on all authorization parameters
                $gateName = implode('|', array_filter([
                    $instance->permission,
                    $instance->role,
                    $instance->userType
                ]));

                Gate::define($gateName, function ($user) use ($instance) {
                    return $user->hasPermissionThroughAttribute(
                        $instance->permission,
                        $instance->role,
                        $instance->userType
                    );
                });
            }
        }
    }
} 