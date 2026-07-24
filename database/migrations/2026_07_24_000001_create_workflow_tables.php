<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('practice_type_id')->constrained('practice_types')->cascadeOnDelete();
            $table->string('name');
            $table->string('code');
            $table->unsignedSmallInteger('version')->default(1);
            $table->boolean('is_draft')->default(true);
            $table->boolean('is_active')->default(false);
            $table->string('legacy_status_code', 30)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('workflow_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained('workflows')->cascadeOnDelete();
            $table->string('name');
            $table->string('code');
            $table->string('color', 7)->default('#3B82F6');
            $table->boolean('is_initial')->default(false);
            $table->boolean('is_terminal')->default(false);
            $table->integer('order')->default(0);
            $table->string('icon')->nullable();
            $table->timestamps();
        });

        Schema::create('workflow_transitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained('workflows')->cascadeOnDelete();
            $table->foreignId('from_state_id')->constrained('workflow_states')->cascadeOnDelete();
            $table->foreignId('to_state_id')->constrained('workflow_states')->cascadeOnDelete();
            $table->string('name');
            $table->string('code');
            $table->json('conditions')->nullable();
            $table->integer('order')->default(0);
            $table->timestamps();
        });

        Schema::create('workflow_state_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained('workflows')->cascadeOnDelete();
            $table->foreignId('practices_id')->constrained('practices')->cascadeOnDelete();
            $table->foreignId('from_state_id')->nullable()->constrained('workflow_states')->nullOnDelete();
            $table->foreignId('to_state_id')->nullable()->constrained('workflow_states')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->index(['practices_id', 'workflow_id']);
            $table->index(['practices_id', 'to_state_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_state_logs');
        Schema::dropIfExists('workflow_transitions');
        Schema::dropIfExists('workflow_states');
        Schema::dropIfExists('workflows');
    }
};
