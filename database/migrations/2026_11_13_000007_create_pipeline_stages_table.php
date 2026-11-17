<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Run the migrations */
    public function up(): void
    {
        Schema::create('pipeline_stages', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('pipeline_id')->constrained('pipelines')->onDelete('cascade');
            $table->string('name', 100);
            $table->string('slug', 120);
            $table->integer('order_index')->default(0);
            $table->unsignedTinyInteger('probability_percent')->default(0);
            $table->boolean('is_won_stage')->default(false);
            $table->boolean('is_lost_stage')->default(false);
            $table->string('color_hex', 7)->default('#6c757d');
            $table->timestamps();

            $table->unique(['pipeline_id', 'slug']);
        });
    }

    /** Reverse the migrations */
    public function down(): void
    {
        Schema::dropIfExists('pipeline_stages');
    }
};
