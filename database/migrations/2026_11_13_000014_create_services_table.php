<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Run the migrations */
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('category_id')->constrained('service_categories')->onDelete('restrict');
            $table->string('name', 150);
            $table->string('slug', 160)->unique();
            $table->text('description')->nullable();
            $table->decimal('base_price', 12, 2)->default(0.00);
            $table->enum('pricing_type', ['one_off', 'monthly', 'hourly'])->default('one_off');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /** Reverse the migrations */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
