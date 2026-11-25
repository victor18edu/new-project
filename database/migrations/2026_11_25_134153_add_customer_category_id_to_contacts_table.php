<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Run the migrations */
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table): void {
            $table->foreignId('customer_category_id')
                ->nullable()
                ->after('company_id')
                ->constrained('customer_categories')
                ->nullOnDelete();
        });
    }

    /** Reverse the migrations */
    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('customer_category_id');
        });
    }
};
