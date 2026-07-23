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
        Schema::create('scheduled_notification_occurrences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('event_key', 100);
            $table->string('subject_type');
            $table->unsignedBigInteger('subject_id');
            $table->unsignedInteger('minutes_before');
            $table->timestamp('subject_scheduled_at');
            $table->timestamp('scheduled_for');
            $table->timestamp('expires_at');
            $table->string('status', 20)->default('pending');
            $table->string('deduplication_key', 64)->unique();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'scheduled_for']);
            $table->index(['subject_type', 'subject_id']);
            $table->index(['user_id', 'event_key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scheduled_notification_occurrences');
    }
};
