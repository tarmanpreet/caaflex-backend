<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('practice_type_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('practice_type_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'practice_type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('practice_type_user');
    }
};
