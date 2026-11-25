<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Run the migrations */
    public function up(): void
    {
        Schema::create('whatsapp_message_revisions', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('message_id')->constrained('whatsapp_messages')->cascadeOnDelete();
            $table->foreignId('editor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('old_body');
            $table->text('new_body');
            $table->timestamps();

            $table->index(['message_id', 'created_at']);
        });
    }

    /** Reverse the migrations */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_message_revisions');
    }
};
