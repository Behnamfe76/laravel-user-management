<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint;

// Skip RefreshDatabase trait for this test
// uses(RefreshDatabase::class);

it('creates necessary tables and columns', function () {
    // Drop all existing tables first
    Schema::dropIfExists('user_roles');
    Schema::dropIfExists('user_fields');
    Schema::dropIfExists('role_permissions');
    Schema::dropIfExists('permissions');
    Schema::dropIfExists('roles');
    Schema::dropIfExists('users');
    
    // Create the users table first
    Schema::create('users', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('email')->unique();
        $table->timestamp('email_verified_at')->nullable();
        $table->string('password');
        $table->rememberToken();
        $table->timestamps();
    });
    
    // Run the package migrations
    $migrationFiles = [
        '2023_01_01_000001_create_roles_table.php',
        '2023_01_01_000002_create_permissions_table.php',
        '2023_01_01_000003_create_role_permissions_table.php',
        '2023_01_01_000004_create_user_fields_table.php',
        '2023_01_01_000005_create_user_roles_table.php',
        '2023_01_01_000006_add_dynamic_fields_to_users_table.php',
    ];
    
    $migrationPath = __DIR__ . '/../../database/migrations/';
    
    foreach ($migrationFiles as $file) {
        $migration = require $migrationPath . $file;
        $migration->up();
    }
    
    // Now verify all tables and columns exist
    expect(Schema::hasTable('roles'))->toBeTrue();
    expect(Schema::hasTable('permissions'))->toBeTrue();
    expect(Schema::hasTable('role_permissions'))->toBeTrue();
    expect(Schema::hasTable('user_fields'))->toBeTrue();
    expect(Schema::hasTable('user_roles'))->toBeTrue();
    
    // Verify dynamic_fields column in users table
    expect(Schema::hasColumn('users', 'dynamic_fields'))->toBeTrue();
    
    // Verify columns in roles table
    $columns = Schema::getColumnListing('roles');
    expect($columns)->toContain('id');
    expect($columns)->toContain('name');
    expect($columns)->toContain('description');
    expect($columns)->toContain('created_at');
    expect($columns)->toContain('updated_at');
    
    // Verify columns in permissions table
    $columns = Schema::getColumnListing('permissions');
    expect($columns)->toContain('id');
    expect($columns)->toContain('name');
    expect($columns)->toContain('description');
    expect($columns)->toContain('created_at');
    expect($columns)->toContain('updated_at');
    
    // Verify columns in role_permissions table
    $columns = Schema::getColumnListing('role_permissions');
    expect($columns)->toContain('id');
    expect($columns)->toContain('role_id');
    expect($columns)->toContain('permission_id');
    expect($columns)->toContain('created_at');
    expect($columns)->toContain('updated_at');
    
    // Verify columns in user_fields table
    $columns = Schema::getColumnListing('user_fields');
    expect($columns)->toContain('id');
    expect($columns)->toContain('user_id');
    expect($columns)->toContain('field_name');
    expect($columns)->toContain('field_value');
    expect($columns)->toContain('created_at');
    expect($columns)->toContain('updated_at');
    
    // Verify columns in user_roles table
    $columns = Schema::getColumnListing('user_roles');
    expect($columns)->toContain('id');
    expect($columns)->toContain('user_id');
    expect($columns)->toContain('role_id');
    expect($columns)->toContain('created_at');
    expect($columns)->toContain('updated_at');
}); 