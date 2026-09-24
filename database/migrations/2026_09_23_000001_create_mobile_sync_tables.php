<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mobile_sync_operations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->uuid('operation_id');
            $table->unsignedSmallInteger('http_status');
            $table->longText('response_body');
            $table->timestamps();
            $table->unique(['user_id', 'operation_id']);
        });

        Schema::create('mobile_sync_changes', function (Blueprint $table) {
            $table->id();
            $table->string('resource', 64);
            $table->unsignedBigInteger('resource_id');
            $table->boolean('deleted')->default(false);
            $table->timestamp('record_updated_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['resource', 'resource_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mobile_sync_changes');
        Schema::dropIfExists('mobile_sync_operations');
    }
};
