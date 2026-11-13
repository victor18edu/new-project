<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Run the migrations */
    public function up(): void
    {
        Schema::create('proposal_items', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('proposal_id')->constrained('proposals')->onDelete('cascade');
            $table->foreignId('service_id')->nullable()->constrained('services')->onDelete('set null');
            $table->string('item_name', 150);
            $table->text('description')->nullable();
            $table->decimal('quantity', 8, 2)->default(1.00);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('discount_amount', 12, 2)->default(0.00);
            $table->decimal('total_price', 12, 2);
            $table->boolean('is_recurring')->default(false);
            $table->timestamps();
        });
    }

    /** Reverse the migrations */
    public function down(): void
    {
        Schema::dropIfExists('proposal_items');
    }
};
