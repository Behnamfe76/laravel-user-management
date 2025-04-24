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
        Schema::create(config('user-management.tables.role_permissions', 'role_permissions'), function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('permission_id');
            $table->timestamps();

            $table->unique(['role_id', 'permission_id']);
            
            $table->foreign('role_id')
                ->references('id')
                ->on(config('user-management.tables.roles', 'roles'))
                ->onDelete('cascade');
                
            $table->foreign('permission_id')
                ->references('id')
                ->on(config('user-management.tables.permissions', 'permissions'))
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(config('user-management.tables.role_permissions', 'role_permissions'));
    }
}; 