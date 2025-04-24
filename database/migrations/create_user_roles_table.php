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
        Schema::create(config('user-management.tables.user_roles', 'user_roles'), function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('role_id');
            $table->timestamps();

            $table->unique(['user_id', 'role_id']);
            
            $table->foreign('user_id')
                ->references('id')
                ->on(config('user-management.tables.users', 'users'))
                ->onDelete('cascade');
                
            $table->foreign('role_id')
                ->references('id')
                ->on(config('user-management.tables.roles', 'roles'))
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(config('user-management.tables.user_roles', 'user_roles'));
    }
}; 