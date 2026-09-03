<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('procedure_deadline_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('procedure_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('notes')->nullable();
            $table->unsignedInteger('offset_days')->default(0);
            $table->unsignedTinyInteger('offset_hours')->default(0);
            $table->unsignedTinyInteger('priority')->default(3);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['procedure_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('procedure_deadline_templates');
    }
};
