<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auto_confirm_slots', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('day_of_week');
            $table->time('time_from');
            $table->time('time_to');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auto_confirm_slots');
    }
};
