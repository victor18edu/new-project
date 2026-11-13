<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Run the migrations */
    public function up(): void
    {
        Schema::create('proposals', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('proposal_number', 30)->unique();
            $table->foreignId('opportunity_id')->constrained('opportunities')->onDelete('restrict');
            $table->foreignId('company_id')->constrained('companies')->onDelete('restrict');
            $table->foreignId('contact_id')->nullable()->constrained('contacts')->onDelete('set null');
            $table->foreignId('owner_id')->constrained('users')->onDelete('restrict');
            $table->enum('status', ['draft', 'sent', 'accepted', 'rejected', 'expired'])->default('draft');
            $table->decimal('total_one_off', 12, 2)->default(0.00);
            $table->decimal('total_recurring', 12, 2)->default(0.00);
            $table->text('payment_terms')->nullable();
            $table->date('valid_until');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->string('pdf_path', 255)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
        });
    }

    /** Reverse the migrations */
    public function down(): void
    {
        Schema::dropIfExists('proposals');
    }
};
