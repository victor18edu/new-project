<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Run the migrations */
    public function up(): void
    {
        Schema::create('activity_types', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('name', 80);
            $table->string('slug', 80)->unique();
            $table->string('icon', 50)->default('bi-check-circle');
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /** Reverse the migrations */
    public function down(): void
    {
        Schema::dropIfExists('activity_types');
    }
};
