<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recovery_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('two_factor_recovery_codes');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recovery_codes');

        Schema::table('users', function (Blueprint $table) {
            $table->text('two_factor_recovery_codes')->nullable();
        });
    }
};
