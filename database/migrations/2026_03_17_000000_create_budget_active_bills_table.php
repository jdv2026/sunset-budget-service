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
            $table->string('name', 20);
            $table->string('description', 500)->nullable();
			$table->decimal('paid', 15, 2)->default(0);
            $table->decimal('amount', 15, 2);
            $table->date('due_date');
            $table->enum('frequency', ['monthly', 'weekly', 'yearly']);
            $table->enum('status', ['upcoming', 'paid', 'overdue'])->default('upcoming');
            $table->string('category_name', 20);
            $table->string('category_icon', 20);
            $table->string('category_color', 20);
            $table->enum('category_type', ['income', 'expense', 'goals']);
            $table->unique(['user_id', 'name']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_active_bills');
    }
};
