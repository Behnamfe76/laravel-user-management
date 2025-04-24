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
        Schema::table(config('user-management.tables.users', 'users'), function (Blueprint $table) {
            // Add JSON column for dynamic fields
            $table->json('dynamic_fields')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table(config('user-management.tables.users', 'users'), function (Blueprint $table) {
            $table->dropColumn('dynamic_fields');
        });
    }
}; 