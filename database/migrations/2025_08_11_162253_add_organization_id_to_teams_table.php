<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            // Add the organization_id column as nullable first
            $table->uuid('organization_id')->nullable()->after('creator_id');
        });
        
        // Update existing teams to have an organization_id based on their creator's organization
        DB::statement('
            UPDATE teams 
            SET organization_id = (
                SELECT users.organization_id 
                FROM users 
                WHERE users.id = teams.creator_id
            ) 
            WHERE organization_id IS NULL
        ');
        
        // Now add the foreign key constraint and make it not nullable
        Schema::table('teams', function (Blueprint $table) {
            // Make the column not nullable now that all teams have an organization
            $table->uuid('organization_id')->nullable(false)->change();
            
            // Add the foreign key constraint
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            
            // Add index for performance
            $table->index('organization_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropForeign(['organization_id']);
            $table->dropIndex(['organization_id']);
            $table->dropColumn('organization_id');
        });
    }
};
