<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Run the migrations */
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('corporate_name', 200)->nullable();
            $table->string('trade_name', 200);
            $table->string('tax_id', 20)->nullable();
            $table->foreignId('segment_id')->constrained('segments')->onDelete('restrict');
            $table->foreignId('lead_source_id')->nullable()->constrained('lead_sources')->onDelete('set null');
            $table->foreignId('owner_id')->constrained('users')->onDelete('restrict');
            $table->string('website_url', 255)->nullable();
            $table->string('instagram_handle', 100)->nullable();
            $table->text('google_maps_url')->nullable();
            $table->enum('website_status', ['none', 'outdated', 'broken', 'modern'])->default('none');
            $table->boolean('is_mobile_responsive')->default(false);
            $table->boolean('has_whatsapp_button')->default(false);
            $table->boolean('has_active_ads')->default(false);
            $table->text('pain_points_notes')->nullable();
            $table->string('address', 200)->nullable();
            $table->string('city', 100);
            $table->char('state', 2);
            $table->string('postal_code', 10)->nullable();
            $table->enum('status', ['prospecting', 'contacted', 'qualified', 'customer', 'churned', 'unviable'])->default('prospecting');
            $table->enum('temperature', ['cold', 'warm', 'hot'])->default('cold');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['segment_id', 'city']);
            $table->index(['owner_id', 'status']);
            $table->index('temperature');
            $table->index('tax_id');
            $table->index('city');
            $table->index('state');
            $table->index('status');
        });
    }

    /** Reverse the migrations */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
