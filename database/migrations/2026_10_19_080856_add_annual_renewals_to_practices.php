<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('practices', function (Blueprint $table) {
            $table->foreignId('renewal_of_id')->nullable()->constrained('practices')->nullOnDelete();
            $table->unique(['renewal_of_id', 'reference_year']);
        });
    }

    public function down(): void
    {
        Schema::table('practices', function (Blueprint $table) {
            $table->dropUnique(['renewal_of_id', 'reference_year']);
            $table->dropConstrainedForeignId('renewal_of_id');
        });
    }
};
