<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_active_categories', function (Blueprint $table) {
            $table->id();
            $table->string('user_id');
            $table->string('name', 20)->unique();
            $table->string('icon', 20);
            $table->string('color', 20);
            $table->enum('type', ['income', 'expense', 'goals']);
            $table->string('description', 500)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_active_categories');
    }
};
