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
        Schema::create('client_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete()->unique();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('phone', 20);
            $table->date('date_of_birth');
            $table->string('fiscal_code', 16)->nullable()->unique();
            $table->string('email', 255)->nullable();
            $table->string('address', 255)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('province', 2)->nullable();
            $table->string('postal_code', 5)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_profiles');
    }
};
