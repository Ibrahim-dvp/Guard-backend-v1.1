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
        Schema::table('leads', function (Blueprint $table) {
            // Add new client info fields
            $table->string('client_first_name')->nullable();
            $table->string('client_last_name')->nullable();
            $table->string('client_email')->nullable();
            $table->string('client_phone')->nullable();
            $table->string('client_company')->nullable();
        });

        // Migrate existing data from JSON client_info to separate fields
        $leads = DB::table('leads')->whereNotNull('client_info')->get();
        
        foreach ($leads as $lead) {
            if ($lead->client_info) {
                $clientInfo = json_decode($lead->client_info, true);
                
                if ($clientInfo) {
                    DB::table('leads')
                        ->where('id', $lead->id)
                        ->update([
                            'client_first_name' => $clientInfo['firstName'] ?? null,
                            'client_last_name' => $clientInfo['lastName'] ?? null,
                            'client_email' => $clientInfo['email'] ?? null,
                            'client_phone' => $clientInfo['phone'] ?? null,
                            'client_company' => $clientInfo['company'] ?? null,
                        ]);
                }
            }
        }

        // Remove the old client_info column
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn('client_info');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            // Add back the client_info JSON column
            $table->json('client_info')->nullable();
        });

        // Migrate data back to JSON format
        $leads = DB::table('leads')->get();
        
        foreach ($leads as $lead) {
            $clientInfo = [];
            
            if ($lead->client_first_name) $clientInfo['firstName'] = $lead->client_first_name;
            if ($lead->client_last_name) $clientInfo['lastName'] = $lead->client_last_name;
            if ($lead->client_email) $clientInfo['email'] = $lead->client_email;
            if ($lead->client_phone) $clientInfo['phone'] = $lead->client_phone;
            if ($lead->client_company) $clientInfo['company'] = $lead->client_company;
            
            if (!empty($clientInfo)) {
                DB::table('leads')
                    ->where('id', $lead->id)
                    ->update(['client_info' => json_encode($clientInfo)]);
            }
        }

        // Remove the separate fields
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn([
                'client_first_name',
                'client_last_name', 
                'client_email',
                'client_phone',
                'client_company'
            ]);
        });
    }
};
