<?php

declare(strict_types=1);

namespace Fereydooni\LaravelUserManagement;

use Fereydooni\LaravelUserManagement\Attributes\UserField;
use Fereydooni\LaravelUserManagement\Attributes\UserRole;
use Fereydooni\LaravelUserManagement\Exceptions\FieldNotFoundException;
use Fereydooni\LaravelUserManagement\Exceptions\FieldValidationException;
use Fereydooni\LaravelUserManagement\Exceptions\PermissionDeniedException;
use Fereydooni\LaravelUserManagement\Exceptions\PermissionNotFoundException;
use Fereydooni\LaravelUserManagement\Exceptions\RoleNotFoundException;
use Fereydooni\LaravelUserManagement\Exceptions\UserFieldValidationException;
use Fereydooni\LaravelUserManagement\Exceptions\UserNotFoundException;
use Fereydooni\LaravelUserManagement\Contracts\UserManagerInterface;
use Fereydooni\LaravelUserManagement\Models\Role;
use Fereydooni\LaravelUserManagement\Models\Permission;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Schema;
use ReflectionClass;
use ReflectionProperty;
use Spatie\Permission\Models\Role as SpatieRole;
use Spatie\Permission\Models\Permission as SpatiePermission;
use Illuminate\Support\Facades\Hash;

class UserManager implements UserManagerInterface
{
    /**
     * @var string
     */
    protected string $userModel;

    /**
     * @var array<string,array>
     */
    protected array $dynamicFields = [];

    /**
     * @var array<string,array>
     */
    protected array $userRoles = [];

    /**
     * Create a new UserManager instance.
     *
     * @param Application $app
     */
    public function __construct(protected Application $app)
    {
        $this->userModel = config('user-management.user_model');
        $this->loadAttributes();
    }

    /**
     * Load user attributes from the user model.
     *
     * @return void
     */
    protected function loadAttributes(): void
    {
        try {
            $reflectionClass = new ReflectionClass($this->userModel);

            // Process UserField attributes
            $this->dynamicFields = $this->extractUserFieldAttributes($reflectionClass);

            // Process UserRole attributes
            $this->userRoles = $this->extractUserRoleAttributes($reflectionClass);
        } catch (\ReflectionException $e) {
            Log::error('Failed to load user attributes: ' . $e->getMessage());
        }
    }

    /**
     * Extract UserField attributes from the user model.
     *
     * @param ReflectionClass $reflectionClass
     * @return array<string,array>
     */
    protected function extractUserFieldAttributes(ReflectionClass $reflectionClass): array
    {
        $fields = [];
        $attributes = $reflectionClass->getAttributes(\Fereydooni\LaravelUserManagement\Attributes\UserField::class);

        foreach ($attributes as $attribute) {
            $field = $attribute->newInstance();
            $fields[$field->name] = [
                'type' => $field->type,
                'required' => $field->required,
                'unique' => $field->unique,
            ];
        }

        return $fields;
    }

    /**
     * Extract UserRole attributes from the user model.
     *
     * @param ReflectionClass $reflectionClass
     * @return array<string,array>
     */
    protected function extractUserRoleAttributes(ReflectionClass $reflectionClass): array
    {
        $roles = [];
        $attributes = $reflectionClass->getAttributes(\Fereydooni\LaravelUserManagement\Attributes\UserRole::class);

        foreach ($attributes as $attribute) {
            $role = $attribute->newInstance();
            $roles[$role->name] = [
                'permissions' => $role->permissions,
            ];
        }

        return $roles;
    }

    /**
     * Create a new user with the provided data.
     *
     * @param array<string, mixed> $data
     * @return Model
     * @throws UserFieldValidationException
     */
    public function create(array $data): Model
    {
        // Validate dynamic fields
        $this->validateDynamicFields($data);

        // Extract standard user fields
        $standardFields = $this->extractStandardFields($data);
        
        // Extract dynamic fields
        $dynamicFields = $this->extractDynamicFields($data);

        // Create user
        $user = $this->userModel::create($standardFields);

        // Store dynamic fields
        if (!empty($dynamicFields)) {
            $this->saveDynamicFields($user, $dynamicFields);
        }

        return $user;
    }

    /**
     * Update an existing user with the provided data.
     *
     * @param Model $user
     * @param array<string, mixed> $data
     * @return Model
     * @throws UserFieldValidationException
     */
    public function update(Model $user, array $data): Model
    {
        // Validate dynamic fields
        $this->validateDynamicFields($data, $user);

        // Extract standard user fields
        $standardFields = $this->extractStandardFields($data);
        
        // Extract dynamic fields
        $dynamicFields = $this->extractDynamicFields($data);

        // Update user
        $user->update($standardFields);

        // Update dynamic fields
        if (!empty($dynamicFields)) {
            $this->saveDynamicFields($user, $dynamicFields);
        }

        return $user;
    }

    /**
     * Delete a user (soft delete if enabled).
     *
     * @param Model $user
     * @return bool
     */
    public function delete(Model $user): bool
    {
        try {
            return (bool) $user->delete();
        } catch (\Exception $e) {
            Log::error('Failed to delete user: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Force delete a user (bypassing soft deletes).
     *
     * @param Model $user
     * @return bool
     */
    public function forceDelete(Model $user): bool
    {
        try {
            return (bool) $user->forceDelete();
        } catch (\Exception $e) {
            Log::error('Failed to force delete user: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Restore a soft-deleted user.
     *
     * @param Model $user
     * @return bool
     */
    public function restore(Model $user): bool
    {
        try {
            return (bool) $user->restore();
        } catch (\Exception $e) {
            Log::error('Failed to restore user: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Assign a role to a user.
     *
     * @param Model $user
     * @param string $roleName
     * @return bool
     * @throws RoleNotFoundException
     */
    public function assignRole(Model $user, string $roleName): bool
    {
        // Check if role exists
        if (!$this->roleExists($roleName)) {
            throw new RoleNotFoundException("Role '{$roleName}' does not exist.");
        }

        if (config('user-management.role_integration') === 'spatie' && class_exists('\\Spatie\\Permission\\Models\\Role')) {
            $roleClass = '\\Spatie\\Permission\\Models\\Role';
            $role = $roleClass::findByName($roleName);
            $user->assignRole($role);
            return true;
        }

        // Custom implementation
        $userRolesTable = config('user-management.tables.user_roles');
        $rolesTable = config('user-management.tables.roles');

        // Get role ID
        $role = $this->app->make('db')->table($rolesTable)
            ->where('name', $roleName)
            ->first();

        if (!$role) {
            // Create role if it doesn't exist
            $roleId = $this->app->make('db')->table($rolesTable)->insertGetId([
                'name' => $roleName,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $roleId = $role->id;
        }

        // Check if user already has this role
        $exists = $this->app->make('db')->table($userRolesTable)
            ->where('user_id', $user->getKey())
            ->where('role_id', $roleId)
            ->exists();

        if (!$exists) {
            $this->app->make('db')->table($userRolesTable)->insert([
                'user_id' => $user->getKey(),
                'role_id' => $roleId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return true;
    }

    /**
     * Remove a role from a user.
     *
     * @param Model $user
     * @param string $roleName
     * @return bool
     */
    public function removeRole(Model $user, string $roleName): bool
    {
        try {
            if (config('user-management.role_integration') === 'spatie' && class_exists('\\Spatie\\Permission\\Models\\Role')) {
                $roleClass = '\\Spatie\\Permission\\Models\\Role';
                $role = $roleClass::findByName($roleName);
                $user->removeRole($role);
                return true;
            }

            // Custom implementation
            $userRolesTable = config('user-management.tables.user_roles');
            $rolesTable = config('user-management.tables.roles');

            // Get role ID
            $role = $this->app->make('db')->table($rolesTable)
                ->where('name', $roleName)
                ->first();

            if (!$role) {
                return false;
            }

            // Remove role from user
            $this->app->make('db')->table($userRolesTable)
                ->where('user_id', $user->getKey())
                ->where('role_id', $role->id)
                ->delete();

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to remove role from user: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if a user has a specific role.
     *
     * @param Model $user
     * @param string $roleName
     * @return bool
     */
    public function hasRole(Model $user, string $roleName): bool
    {
        if (config('user-management.role_integration') === 'spatie') {
            return $user->hasRole($roleName);
        }

        // Custom implementation
        $userRolesTable = config('user-management.tables.user_roles');
        $rolesTable = config('user-management.tables.roles');

        return $this->app->make('db')->table($userRolesTable)
            ->join($rolesTable, "{$userRolesTable}.role_id", '=', "{$rolesTable}.id")
            ->where("{$userRolesTable}.user_id", $user->getKey())
            ->where("{$rolesTable}.name", $roleName)
            ->exists();
    }

    /**
     * Check if a user has a specific permission.
     *
     * @param Model $user
     * @param string $permissionName
     * @return bool
     * @throws \Fereydooni\LaravelUserManagement\Exceptions\PermissionNotFoundException
     */
    public function hasPermission(Model $user, string $permissionName): bool
    {
        // Check if permission exists
        if (!$this->permissionExists($permissionName)) {
            throw new \Fereydooni\LaravelUserManagement\Exceptions\PermissionNotFoundException("Permission '{$permissionName}' does not exist.");
        }

        if (config('user-management.role_integration') === 'spatie') {
            return $user->hasPermissionTo($permissionName);
        }

        // Custom implementation
        $userRolesTable = config('user-management.tables.user_roles');
        $rolesTable = config('user-management.tables.roles');
        $rolePermissionsTable = config('user-management.tables.role_permissions');
        $permissionsTable = config('user-management.tables.permissions');

        return $this->app->make('db')->table($userRolesTable)
            ->join($rolesTable, "{$userRolesTable}.role_id", '=', "{$rolesTable}.id")
            ->join($rolePermissionsTable, "{$rolesTable}.id", '=', "{$rolePermissionsTable}.role_id")
            ->join($permissionsTable, "{$rolePermissionsTable}.permission_id", '=', "{$permissionsTable}.id")
            ->where("{$userRolesTable}.user_id", $user->getKey())
            ->where("{$permissionsTable}.name", $permissionName)
            ->exists();
    }

    /**
     * Get all users.
     *
     * @return Collection
     */
    public function all(): Collection
    {
        return $this->userModel::all();
    }

    /**
     * Find a user by ID.
     *
     * @param int|string $id
     * @return Model|null
     */
    public function find(int|string $id): ?Model
    {
        return $this->userModel::find($id);
    }

    /**
     * Query users by a dynamic field.
     *
     * @param string $fieldName
     * @param mixed $value
     * @return Builder
     */
    public function whereField(string $fieldName, mixed $value): Builder
    {
        // If it's a standard field, use the standard query builder
        $standardFields = ['id', 'name', 'email', 'password', 'created_at', 'updated_at', 'deleted_at'];
        if (in_array($fieldName, $standardFields)) {
            return $this->userModel::where($fieldName, $value);
        }

        // Handle JSON fields if the user model has a 'dynamic_fields' column
        if (method_exists($this->userModel, 'hasDynamicFieldsColumn') && $this->userModel::hasDynamicFieldsColumn()) {
            return $this->userModel::where("dynamic_fields->{$fieldName}", $value);
        }

        // Otherwise, query via the pivot table
        $userFieldsTable = config('user-management.tables.user_fields');
        $usersTable = config('user-management.tables.users');

        return $this->userModel::join($userFieldsTable, "{$usersTable}.id", '=', "{$userFieldsTable}.user_id")
            ->where("{$userFieldsTable}.field_name", $fieldName)
            ->where("{$userFieldsTable}.field_value", $value)
            ->select("{$usersTable}.*");
    }

    /**
     * Validate dynamic fields.
     *
     * @param array<string, mixed> $data
     * @param Model|null $user
     * @return void
     * @throws UserFieldValidationException
     */
    protected function validateDynamicFields(array $data, ?Model $user = null): void
    {
        $errors = [];

        foreach ($this->dynamicFields as $fieldName => $field) {
            if ($field['required'] && !isset($data[$fieldName])) {
                $errors[] = "The {$fieldName} field is required.";
            }

            if (isset($data[$fieldName])) {
                $value = $data[$fieldName];
                $type = $field['type'];

                if (!$this->validateFieldType($value, $type)) {
                    $errors[] = "The {$fieldName} field must be of type {$type}.";
                }

                if ($field['unique'] && !$this->isFieldUnique($fieldName, $value, $user)) {
                    $errors[] = "The {$fieldName} field must be unique.";
                }
            }
        }

        if (!empty($errors)) {
            throw new UserFieldValidationException($errors);
        }
    }

    /**
     * Validate a field's type.
     *
     * @param mixed $value
     * @param string $type
     * @return bool
     */
    protected function validateFieldType(mixed $value, string $type): bool
    {
        return match ($type) {
            'string' => is_string($value),
            'integer' => is_numeric($value),
            'boolean' => is_bool($value) || in_array($value, [0, 1, '0', '1', true, false], true),
            'date' => strtotime($value) !== false,
            default => false,
        };
    }

    /**
     * Check if a field value is unique.
     *
     * @param string $fieldName
     * @param mixed $value
     * @param Model|null $user
     * @return bool
     */
    protected function isFieldUnique(string $fieldName, mixed $value, ?Model $user = null): bool
    {
        $query = $this->whereField($fieldName, $value);

        if ($user !== null) {
            $query->where('id', '!=', $user->getKey());
        }

        return !$query->exists();
    }

    /**
     * Extract standard fields from data.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function extractStandardFields(array $data): array
    {
        $standardFields = ['name', 'email', 'password', 'remember_token'];
        $result = [];

        foreach ($standardFields as $field) {
            if (isset($data[$field])) {
                $result[$field] = $data[$field];
            }
        }

        return $result;
    }

    /**
     * Extract dynamic fields from data.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function extractDynamicFields(array $data): array
    {
        $standardFields = ['id', 'name', 'email', 'password', 'remember_token', 'created_at', 'updated_at', 'deleted_at'];
        $result = [];

        foreach ($data as $key => $value) {
            if (!in_array($key, $standardFields) && isset($this->dynamicFields[$key])) {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * Save dynamic fields for a user.
     *
     * @param Model $user
     * @param array<string, mixed> $fields
     * @return void
     */
    protected function saveDynamicFields(Model $user, array $fields): void
    {
        // If the user model has a dynamic_fields column (JSON), use it
        if (method_exists($user, 'hasDynamicFieldsColumn') && $user::hasDynamicFieldsColumn()) {
            $currentFields = $user->dynamic_fields ?? [];
            $user->dynamic_fields = array_merge($currentFields, $fields);
            $user->save();
            return;
        }

        // Otherwise, use the pivot table
        $userFieldsTable = config('user-management.tables.user_fields');
        
        foreach ($fields as $fieldName => $fieldValue) {
            // Convert array/object values to JSON
            if (is_array($fieldValue) || is_object($fieldValue)) {
                $fieldValue = json_encode($fieldValue);
            }

            // Check if field already exists
            $exists = $this->app->make('db')->table($userFieldsTable)
                ->where('user_id', $user->getKey())
                ->where('field_name', $fieldName)
                ->exists();

            if ($exists) {
                // Update existing field
                $this->app->make('db')->table($userFieldsTable)
                    ->where('user_id', $user->getKey())
                    ->where('field_name', $fieldName)
                    ->update([
                        'field_value' => $fieldValue,
                        'updated_at' => now(),
                    ]);
            } else {
                // Create new field
                $this->app->make('db')->table($userFieldsTable)
                    ->insert([
                        'user_id' => $user->getKey(),
                        'field_name' => $fieldName,
                        'field_value' => $fieldValue,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
            }
        }
    }

    /**
     * Check if a role exists.
     *
     * @param string $roleName
     * @return bool
     */
    protected function roleExists(string $roleName): bool
    {
        if (isset($this->userRoles[$roleName])) {
            return true;
        }

        if (config('user-management.role_integration') === 'spatie' && class_exists('\\Spatie\\Permission\\Models\\Role')) {
            $roleClass = '\\Spatie\\Permission\\Models\\Role';
            return $roleClass::where('name', $roleName)->exists();
        }

        // Check the roles table
        $rolesTable = config('user-management.tables.roles');
        return $this->app->make('db')->table($rolesTable)
            ->where('name', $roleName)
            ->exists();
    }

    /**
     * Check if a permission exists.
     *
     * @param string $permissionName
     * @return bool
     */
    protected function permissionExists(string $permissionName): bool
    {
        if (config('user-management.role_integration') === 'spatie' && class_exists('\\Spatie\\Permission\\Models\\Permission')) {
            $permissionClass = '\\Spatie\\Permission\\Models\\Permission';
            return $permissionClass::where('name', $permissionName)->exists();
        }

        // Check the permissions table
        $permissionsTable = config('user-management.tables.permissions');
        return $this->app->make('db')->table($permissionsTable)
            ->where('name', $permissionName)
            ->exists();
    }

    public function createUser(array $data): Model
    {
        $data['password'] = Hash::make($data['password']);
        return $this->userModel::create($data);
    }

    public function updateUser(Model $user, array $data): Model
    {
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }
        $user->update($data);
        return $user;
    }

    public function deleteUser(Model $user): bool
    {
        return $user->delete();
    }

    public function setUserType(Model $user, string $type): Model
    {
        $user->user_type = $type;
        $user->save();
        return $user;
    }

    public function getUserType(Model $user): ?string
    {
        return $user->user_type;
    }

    public function removeUserType(Model $user): Model
    {
        $user->user_type = null;
        $user->save();
        return $user;
    }

    /**
     * Check if a user has a specific user type.
     *
     * @param Model $user
     * @param string|array $types
     * @return bool
     */
    public function isUserType(Model $user, string|array $types): bool
    {
        if (is_string($types)) {
            return $user->user_type === $types;
        }

        return in_array($user->user_type, $types);
    }
} 