<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deadline_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deadline_id')->constrained('practice_deadlines')->cascadeOnDelete();
            $table->enum('type', ['email', 'in_app']);
            $table->unsignedInteger('minutes_before');
            $table->boolean('sent')->default(false);
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['deadline_id', 'sent']);
            $table->index(['type', 'sent']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deadline_reminders');
    }
};
