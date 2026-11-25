<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Run the migrations */
    public function up(): void
    {
        Schema::table('whatsapp_messages', function (Blueprint $table): void {
            $table->foreignId('edited_by')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->after('edited_by')->constrained('users')->nullOnDelete();
            $table->timestamp('edited_at')->nullable()->after('metadata');
            $table->softDeletes();
        });
    }

    /** Reverse the migrations */
    public function down(): void
    {
        Schema::table('whatsapp_messages', function (Blueprint $table): void {
            $table->dropSoftDeletes();
            $table->dropColumn('edited_at');
            $table->dropConstrainedForeignId('deleted_by');
            $table->dropConstrainedForeignId('edited_by');
        });
    }
};
