<?php

declare(strict_types=1);

namespace Fereydooni\LaravelUserManagement\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface UserManagerInterface
{
    /**
     * Create a new user with the provided data.
     *
     * @param array<string, mixed> $data
     * @return Model
     */
    public function create(array $data): Model;

    /**
     * Update an existing user with the provided data.
     *
     * @param Model $user
     * @param array<string, mixed> $data
     * @return Model
     */
    public function update(Model $user, array $data): Model;

    /**
     * Delete a user (soft delete if enabled).
     *
     * @param Model $user
     * @return bool
     */
    public function delete(Model $user): bool;

    /**
     * Force delete a user (bypassing soft deletes).
     *
     * @param Model $user
     * @return bool
     */
    public function forceDelete(Model $user): bool;

    /**
     * Restore a soft-deleted user.
     *
     * @param Model $user
     * @return bool
     */
    public function restore(Model $user): bool;

    /**
     * Assign a role to a user.
     *
     * @param Model $user
     * @param string $roleName
     * @return bool
     */
    public function assignRole(Model $user, string $roleName): bool;

    /**
     * Remove a role from a user.
     *
     * @param Model $user
     * @param string $roleName
     * @return bool
     */
    public function removeRole(Model $user, string $roleName): bool;

    /**
     * Check if a user has a specific role.
     *
     * @param Model $user
     * @param string $roleName
     * @return bool
     */
    public function hasRole(Model $user, string $roleName): bool;

    /**
     * Check if a user has a specific permission.
     *
     * @param Model $user
     * @param string $permissionName
     * @return bool
     */
    public function hasPermission(Model $user, string $permissionName): bool;

    /**
     * Get all users.
     *
     * @return Collection
     */
    public function all(): Collection;

    /**
     * Find a user by ID.
     *
     * @param int|string $id
     * @return Model|null
     */
    public function find(int|string $id): ?Model;

    /**
     * Query users by a dynamic field.
     *
     * @param string $fieldName
     * @param mixed $value
     * @return Builder
     */
    public function whereField(string $fieldName, mixed $value): Builder;
} 