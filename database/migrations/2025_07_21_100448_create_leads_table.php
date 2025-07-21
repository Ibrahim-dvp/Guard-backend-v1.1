<?php

declare(strict_types=1);

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
        Schema::create('leads', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('referral_id')->constrained('users')->onDelete('cascade');
            $table->foreignUuid('organization_id')->constrained('organizations')->onDelete('cascade');
            $table->json('client_info');
            $table->string('status');
            $table->foreignUuid('assigned_to_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('source')->default('wordpress_form');
            $table->decimal('revenue', 10, 2)->default(0.00);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
