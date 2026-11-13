<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Run the migrations */
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->string('name', 150);
            $table->string('role_title', 100)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('phone', 25)->nullable();
            $table->string('whatsapp', 25)->nullable();
            $table->boolean('is_decision_maker')->default(false);
            $table->boolean('is_primary')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('email');
            $table->index('whatsapp');
        });
    }

    /** Reverse the migrations */
    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
