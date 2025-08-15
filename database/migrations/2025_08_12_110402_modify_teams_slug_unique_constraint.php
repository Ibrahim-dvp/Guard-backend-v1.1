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
        Schema::table('teams', function (Blueprint $table) {
            // Drop the existing unique constraint on slug
            $table->dropUnique(['slug']);
            
            // Add composite unique constraint on slug and organization_id
            $table->unique(['slug', 'organization_id'], 'teams_slug_organization_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            // Drop the composite unique constraint
            $table->dropUnique('teams_slug_organization_unique');
            
            // Add back the simple unique constraint on slug
            $table->unique('slug');
        });
    }
};
