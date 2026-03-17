<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_active_bills', function (Blueprint $table) {
            $table->id();
            $table->string('user_id');
            $table->foreignId('category_id')->constrained('budget_active_categories')->cascadeOnDelete();
            $table->string('name', 20);
            $table->string('description', 500)->nullable();
            $table->decimal('amount', 15, 2);
            $table->date('due_date');
            $table->enum('frequency', ['monthly', 'weekly', 'yearly']);
            $table->enum('status', ['upcoming', 'paid', 'overdue'])->default('upcoming');
            $table->timestamps();

            $table->unique(['user_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_active_bills');
    }
};
