<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('procedures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('procedure_type_id')->constrained('practice_types')->onDelete('restrict');
            $table->string('name');
            $table->text('default_notes')->nullable();
            $table->timestamps();
            $table->unique(['procedure_type_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('procedures');
    }
};
