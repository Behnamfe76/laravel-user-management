<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Dynamic Fields
    |--------------------------------------------------------------------------
    |
    | This option determines whether dynamic user fields are enabled or not.
    | When disabled, the UserField attributes will be ignored.
    |
    */
    'enable_dynamic_fields' => true,

    /*
    |--------------------------------------------------------------------------
    | User Model
    |--------------------------------------------------------------------------
    |
    | The model that should be used for user management.
    |
    */
    'user_model' => App\Models\User::class,

    /*
    |--------------------------------------------------------------------------
    | Role Integration
    |--------------------------------------------------------------------------
    |
    | Specify which package to use for role/permission management.
    | Options: 'custom', 'spatie'
    |
    */
    'role_integration' => 'custom',

    /*
    |--------------------------------------------------------------------------
    | Database Tables
    |--------------------------------------------------------------------------
    |
    | These are the tables that will be created for storing users, roles, and
    | permissions. If you want to use different table names, you can specify
    | them here.
    |
    */
    'tables' => [
        'users' => 'users',
        'roles' => 'roles',
        'permissions' => 'permissions',
        'user_roles' => 'user_roles',
        'role_permissions' => 'role_permissions',
        'user_fields' => 'user_fields',
    ],

    /*
    |--------------------------------------------------------------------------
    | Soft Deletes
    |--------------------------------------------------------------------------
    |
    | This option determines whether soft deletes should be used for users.
    |
    */
    'soft_deletes' => true,

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    |
    | This option determines whether caching should be used for roles and
    | permissions to improve performance.
    |
    */
    'cache' => [
        'enabled' => true,
        'ttl' => 60 * 24, // 1 day in minutes
    ],
]; 