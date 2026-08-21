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
        Schema::table('client_documents', function (Blueprint $table) {
            $table->date('expires_on')->nullable()->index()->after('description');
        });

        Schema::table('practice_documents', function (Blueprint $table) {
            $table->date('expires_on')->nullable()->index()->after('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('client_documents', function (Blueprint $table) {
            $table->dropIndex(['expires_on']);
            $table->dropColumn('expires_on');
        });

        Schema::table('practice_documents', function (Blueprint $table) {
            $table->dropIndex(['expires_on']);
            $table->dropColumn('expires_on');
        });
    }
};
