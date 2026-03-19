<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_active_wallets', function (Blueprint $table) {
            $table->id();
            $table->string('user_id');
            $table->string('name', 20);
            $table->string('description', 500)->nullable();
            $table->decimal('amount', 15, 2)->default(0);
            $table->unique(['user_id', 'name']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_active_wallets');
    }
};
