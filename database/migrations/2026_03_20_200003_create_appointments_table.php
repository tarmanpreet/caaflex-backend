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
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_profile_id')->constrained('client_profiles')->cascadeOnDelete();
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('practice_type_id')->nullable()->constrained('practice_types')->nullOnDelete();
            $table->foreignId('practice_id')->nullable()->constrained('practices')->nullOnDelete();
            $table->datetime('scheduled_at');
            $table->unsignedSmallInteger('duration_minutes');
            $table->string('status', 30)->default('da_confermare');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
