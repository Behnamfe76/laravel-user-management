<?php

namespace Fereydooni\LaravelUserManagement\Examples;

use Fereydooni\LaravelUserManagement\Attributes\Authorize;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class UserController
{
    // Only users with 'view-users' permission can access this
    #[Authorize(permission: 'view-users')]
    public function index(): JsonResponse
    {
        return response()->json(['message' => 'List of users']);
    }

    // Only admin users with 'create-users' permission can access this
    #[Authorize(permission: 'create-users', role: 'admin')]
    public function store(Request $request): JsonResponse
    {
        return response()->json(['message' => 'User created']);
    }

    // Only super-admin users with 'edit-users' permission can access this
    #[Authorize(permission: 'edit-users', role: 'super-admin')]
    public function update(Request $request, int $id): JsonResponse
    {
        return response()->json(['message' => "User {$id} updated"]);
    }

    // Only users of type 'manager' with 'delete-users' permission can access this
    #[Authorize(permission: 'delete-users', userType: 'manager')]
    public function destroy(int $id): JsonResponse
    {
        return response()->json(['message' => "User {$id} deleted"]);
    }

    // Only admin users of type 'manager' can access this
    #[Authorize(role: 'admin', userType: 'manager')]
    public function manage(): JsonResponse
    {
        return response()->json(['message' => 'Management dashboard']);
    }
} 