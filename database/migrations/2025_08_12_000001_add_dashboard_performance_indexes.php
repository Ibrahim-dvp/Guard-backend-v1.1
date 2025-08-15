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
        Schema::table('leads', function (Blueprint $table) {
            // Composite indexes for dashboard queries
            $table->index(['status', 'created_at'], 'idx_leads_status_created');
            $table->index(['status', 'updated_at'], 'idx_leads_status_updated');
            $table->index(['assigned_to_id', 'status'], 'idx_leads_assigned_status');
            $table->index(['organization_id', 'status'], 'idx_leads_org_status');
            $table->index(['assigned_by_id', 'status'], 'idx_leads_assigned_by_status');
            $table->index(['source', 'status'], 'idx_leads_source_status');
            
            // Revenue calculations
            $table->index(['status', 'revenue'], 'idx_leads_status_revenue');
        });

        Schema::table('appointments', function (Blueprint $table) {
            // Appointment performance indexes
            $table->index(['scheduled_by', 'status'], 'idx_appointments_scheduled_status');
            $table->index(['status', 'created_at'], 'idx_appointments_status_created');
        });

        Schema::table('users', function (Blueprint $table) {
            // User performance indexes
            $table->index(['organization_id', 'created_by'], 'idx_users_org_created_by');
            $table->index(['is_active', 'updated_at'], 'idx_users_active_updated');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropIndex('idx_leads_status_created');
            $table->dropIndex('idx_leads_status_updated');
            $table->dropIndex('idx_leads_assigned_status');
            $table->dropIndex('idx_leads_org_status');
            $table->dropIndex('idx_leads_assigned_by_status');
            $table->dropIndex('idx_leads_source_status');
            $table->dropIndex('idx_leads_status_revenue');
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->dropIndex('idx_appointments_scheduled_status');
            $table->dropIndex('idx_appointments_status_created');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_org_created_by');
            $table->dropIndex('idx_users_active_updated');
        });
    }
};
