<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('practices', function (Blueprint $table) {
            $table->foreignId('procedure_id')->nullable()->constrained('procedures')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('practices', function (Blueprint $table) {
            $table->dropForeign(['procedure_id']);
            $table->dropColumn('procedure_id');
        });
    }
};
