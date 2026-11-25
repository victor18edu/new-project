<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Run the migrations */
    public function up(): void
    {
        Schema::create('whatsapp_attendances', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('contact_id')->constrained('contacts')->cascadeOnDelete();
            $table->foreignId('operator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('open');
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->text('outcome_notes')->nullable();
            $table->timestamps();

            $table->index(['contact_id', 'status']);
            $table->index(['operator_id', 'started_at']);
        });
    }

    /** Reverse the migrations */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_attendances');
    }
};
