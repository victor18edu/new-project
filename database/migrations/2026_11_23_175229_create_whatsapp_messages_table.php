<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Run the migrations */
    public function up(): void
    {
        Schema::create('whatsapp_messages', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('contact_id')->constrained('contacts')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('direction', 10);
            $table->string('status', 12)->default('queued');
            $table->string('provider_message_id', 191)->nullable()->unique();
            $table->text('body');
            $table->timestamp('sent_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['contact_id', 'created_at']);
        });
    }

    /** Reverse the migrations */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_messages');
    }
};
