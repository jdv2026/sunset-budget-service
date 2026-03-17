<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_active_goals', function (Blueprint $table) {
            $table->id();
            $table->string('user_id');
            $table->foreignId('category_id')->constrained('budget_active_categories')->cascadeOnDelete();
            $table->string('name', 20);
            $table->string('description', 500)->nullable();
            $table->decimal('target', 15, 2)->default(0);
            $table->decimal('saved', 15, 2)->default(0);
            $table->date('deadline')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_active_goals');
    }
};
