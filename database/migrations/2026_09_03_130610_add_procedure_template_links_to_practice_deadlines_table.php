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
        Schema::table('practice_deadlines', function (Blueprint $table) {
            $table->string('kind')->default('manual')->after('practice_id');
            $table->foreignId('parent_deadline_id')->nullable()->after('kind')->constrained('practice_deadlines')->nullOnDelete();
            $table->foreignId('procedure_deadline_template_id')->nullable()->after('parent_deadline_id')->constrained()->nullOnDelete();
            $table->unsignedInteger('advance_minutes')->nullable()->after('procedure_deadline_template_id');

            $table->unique(['parent_deadline_id', 'procedure_deadline_template_id'], 'practice_deadline_parent_template_unique');
            $table->index(['practice_id', 'kind']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('practice_deadlines', function (Blueprint $table) {
            $table->dropUnique('practice_deadline_parent_template_unique');
            $table->dropIndex(['practice_id', 'kind']);
            $table->dropConstrainedForeignId('procedure_deadline_template_id');
            $table->dropConstrainedForeignId('parent_deadline_id');
            $table->dropColumn(['kind', 'advance_minutes']);
        });
    }
};
