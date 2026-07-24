<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workflows', function (Blueprint $table) {
            $table->index('code');
            $table->index('practice_type_id');
            $table->index(['is_draft', 'is_active']);
            $table->index('created_at');
            $table->unique(['practice_type_id', 'code']);
        });

        Schema::table('workflow_states', function (Blueprint $table) {
            $table->index('workflow_id');
            $table->index('is_initial');
            $table->index('is_terminal');
            $table->index('order');
            $table->index('created_at');
            $table->unique(['workflow_id', 'code']);
        });

        Schema::table('workflow_transitions', function (Blueprint $table) {
            $table->index('workflow_id');
            $table->index('from_state_id');
            $table->index('to_state_id');
            $table->index('order');
            $table->index('created_at');
            $table->unique(['workflow_id', 'from_state_id', 'to_state_id']);
        });

        Schema::table('workflow_state_logs', function (Blueprint $table) {
            $table->index('workflow_id');
            $table->index('user_id');
            $table->index('from_state_id');
            $table->index('to_state_id');
            $table->index('created_at');
            $table->index(['workflow_id', 'created_at']);
            $table->index(['practices_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('workflow_state_logs', function (Blueprint $table) {
            $table->dropIndex(['workflow_id', 'created_at']);
            $table->dropIndex(['practices_id', 'created_at']);
            $table->dropIndex('workflow_id');
            $table->dropIndex('user_id');
            $table->dropIndex('from_state_id');
            $table->dropIndex('to_state_id');
            $table->dropIndex('created_at');
        });

        Schema::table('workflow_transitions', function (Blueprint $table) {
            $table->dropUnique(['workflow_id', 'from_state_id', 'to_state_id']);
            $table->dropIndex('workflow_id');
            $table->dropIndex('from_state_id');
            $table->dropIndex('to_state_id');
            $table->dropIndex('order');
            $table->dropIndex('created_at');
        });

        Schema::table('workflow_states', function (Blueprint $table) {
            $table->dropUnique(['workflow_id', 'code']);
            $table->dropIndex('workflow_id');
            $table->dropIndex('is_initial');
            $table->dropIndex('is_terminal');
            $table->dropIndex('order');
            $table->dropIndex('created_at');
        });

        Schema::table('workflows', function (Blueprint $table) {
            $table->dropUnique(['practice_type_id', 'code']);
            $table->dropIndex('code');
            $table->dropIndex('practice_type_id');
            $table->dropIndex(['is_draft', 'is_active']);
            $table->dropIndex('created_at');
        });
    }
};
