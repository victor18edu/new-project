<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Run the migrations */
    public function up(): void
    {
        Schema::create('opportunities', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('title', 200);
            $table->foreignId('company_id')->constrained('companies')->onDelete('restrict');
            $table->foreignId('primary_contact_id')->nullable()->constrained('contacts')->onDelete('set null');
            $table->foreignId('pipeline_id')->constrained('pipelines')->onDelete('restrict');
            $table->foreignId('stage_id')->constrained('pipeline_stages')->onDelete('restrict');
            $table->foreignId('owner_id')->constrained('users')->onDelete('restrict');
            $table->decimal('estimated_one_off_value', 12, 2)->default(0.00);
            $table->decimal('estimated_recurring_value', 12, 2)->default(0.00);
            $table->char('currency', 3)->default('BRL');
            $table->date('expected_close_date')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->enum('status', ['open', 'won', 'lost', 'abandoned'])->default('open');
            $table->foreignId('lost_reason_id')->nullable()->constrained('lost_reasons')->onDelete('set null');
            $table->text('lost_reason_notes')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['pipeline_id', 'stage_id', 'status']);
            $table->index(['owner_id', 'status', 'expected_close_date']);
        });
    }

    /** Reverse the migrations */
    public function down(): void
    {
        Schema::dropIfExists('opportunities');
    }
};
