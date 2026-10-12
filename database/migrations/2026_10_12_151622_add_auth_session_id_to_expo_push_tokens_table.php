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
        Schema::table('expo_push_tokens', function (Blueprint $table) {
            $table->uuid('auth_session_id')->nullable()->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expo_push_tokens', function (Blueprint $table) {
            $table->dropColumn('auth_session_id');
        });
    }
};
