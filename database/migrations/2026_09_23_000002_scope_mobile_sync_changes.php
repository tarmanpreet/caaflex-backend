<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mobile_sync_changes', function (Blueprint $table) {
            $table->unsignedBigInteger('branch_id')->nullable()->after('resource_id');
            $table->unsignedBigInteger('owner_user_id')->nullable()->after('branch_id');
        });
    }

    public function down(): void
    {
        Schema::table('mobile_sync_changes', function (Blueprint $table) {
            $table->dropColumn(['branch_id', 'owner_user_id']);
        });
    }
};
