<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('practice_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('practice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('document_id')->nullable()->constrained('practice_documents')->nullOnDelete();
            $table->text('body');
            $table->string('visibility', 16)->default('public');
            $table->timestamps();
            $table->index(['practice_id', 'visibility', 'id']);
        });
        Schema::create('practice_document_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('practice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->text('instructions')->nullable();
            $table->date('due_on')->nullable();
            $table->string('status', 16)->default('requested');
            $table->text('review_reason')->nullable();
            $table->timestamps();
            $table->index(['practice_id', 'status']);
        });
        Schema::table('practice_documents', function (Blueprint $table) {
            $table->foreignId('document_request_id')->nullable()->constrained('practice_document_requests')->nullOnDelete();
            $table->string('review_status', 16)->default('pending');
            $table->text('review_reason')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
        });
        Schema::create('activity_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('practice_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('client_profile_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 80);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['practice_id', 'id']);
            $table->index(['client_profile_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_entries');
        Schema::table('practice_documents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('document_request_id');
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn(['review_status', 'review_reason', 'reviewed_at']);
        });
        Schema::dropIfExists('practice_document_requests');
        Schema::dropIfExists('practice_messages');
    }
};
