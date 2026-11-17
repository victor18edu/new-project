<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Run the migrations */
    public function up(): void
    {
        Schema::create('opportunity_stage_history', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('opportunity_id')->constrained('opportunities')->onDelete('cascade');
            $table->foreignId('from_stage_id')->nullable()->constrained('pipeline_stages')->onDelete('set null');
            $table->foreignId('to_stage_id')->constrained('pipeline_stages')->onDelete('restrict');
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict');
            $table->unsignedInteger('duration_in_seconds')->nullable();
            $table->timestamps();

            $table->index('created_at');
        });
    }

    /** Reverse the migrations */
    public function down(): void
    {
        Schema::dropIfExists('opportunity_stage_history');
    }
};
