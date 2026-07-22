<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->foreignId('parent_id')->nullable()->after('id')->constrained('branches')->restrictOnDelete();
            $table->index(['parent_id', 'is_active']);
        });

        Schema::table('client_profiles', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('id')->constrained('branches')->restrictOnDelete();
        });

        $mainBranchId = DB::table('branches')->min('id');

        if ($mainBranchId !== null) {
            DB::table('branches')->whereNull('parent_id')->where('id', '!=', $mainBranchId)->update(['parent_id' => $mainBranchId]);
            DB::table('client_profiles')->whereNull('branch_id')->update(['branch_id' => $mainBranchId]);
            DB::table('practices')->whereNull('branch_id')->update(['branch_id' => $mainBranchId]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('client_profiles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('branch_id');
        });

        Schema::table('branches', function (Blueprint $table) {
            $table->dropIndex(['parent_id', 'is_active']);
            $table->dropConstrainedForeignId('parent_id');
        });
    }
};
