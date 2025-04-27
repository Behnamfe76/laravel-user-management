<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create(config('user-management.tables.role_has_permissions', 'role_has_permissions'), function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');

            $table->foreign('permission_id')
                ->references('id')
                ->on(config('user-management.tables.permissions', 'permissions'))
                ->onDelete('cascade');

            $table->foreign('role_id')
                ->references('id')
                ->on(config('user-management.tables.roles', 'roles'))
                ->onDelete('cascade');

            $table->primary(['permission_id', 'role_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(config('user-management.tables.role_has_permissions', 'role_has_permissions'));
    }
}; 