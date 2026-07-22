<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('practices', function (Blueprint $table) {
            $table->char('tracking_code', 10)->nullable()->unique()->after('id');
        });

        DB::table('practices')
            ->select('id')
            ->whereNull('tracking_code')
            ->orderBy('id')
            ->chunkById(100, function ($practices): void {
                foreach ($practices as $practice) {
                    DB::table('practices')
                        ->where('id', $practice->id)
                        ->update(['tracking_code' => $this->uniqueTrackingCode()]);
                }
            });

        Schema::table('practices', function (Blueprint $table) {
            $table->char('tracking_code', 10)->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('practices', function (Blueprint $table) {
            $table->dropUnique(['tracking_code']);
            $table->dropColumn('tracking_code');
        });
    }

    private function uniqueTrackingCode(): string
    {
        do {
            $trackingCode = Str::upper(Str::random(10));
        } while (DB::table('practices')->where('tracking_code', $trackingCode)->exists());

        return $trackingCode;
    }
};
