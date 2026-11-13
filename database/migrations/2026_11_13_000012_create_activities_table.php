<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Run the migrations */
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('activity_type_id')->constrained('activity_types')->onDelete('restrict');
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
            $table->foreignId('contact_id')->nullable()->constrained('contacts')->onDelete('set null');
            $table->foreignId('opportunity_id')->nullable()->constrained('opportunities')->onDelete('cascade');
            $table->foreignId('assigned_to')->constrained('users')->onDelete('restrict');
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->dateTime('due_date');
            $table->dateTime('completed_at')->nullable();
            $table->enum('status', ['pending', 'completed', 'cancelled'])->default('pending');
            $table->text('outcome_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('due_date');
            $table->index('status');
            $table->index(['assigned_to', 'status', 'due_date']);
        });
    }

    /** Reverse the migrations */
    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
