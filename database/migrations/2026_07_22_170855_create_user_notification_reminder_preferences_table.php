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
        Schema::create('user_notification_reminder_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('section', 50);
            $table->unsignedInteger('minutes_before');
            $table->timestamps();

            $table->unique(['user_id', 'section', 'minutes_before'], 'notification_reminder_preference_unique');
            $table->index(['section', 'minutes_before'], 'notification_reminder_section_minutes_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_notification_reminder_preferences');
    }
};
