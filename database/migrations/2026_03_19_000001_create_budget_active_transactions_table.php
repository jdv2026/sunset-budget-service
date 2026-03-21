<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_active_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('user_id');
            $table->enum('type', ['income', 'expense', 'transfer']);
            $table->string('description', 500)->nullable();
            $table->date('date');

            $table->string('wallet_name', 20)->nullable();
            $table->string('wallet_description', 500)->nullable();
            $table->decimal('wallet_amount', 15, 2)->nullable();
            $table->string('wallet_category_name', 20)->nullable();
            $table->string('wallet_category_icon', 20)->nullable();
            $table->string('wallet_category_color', 20)->nullable();
            $table->enum('wallet_category_type', ['income', 'expense', 'goals'])->nullable();

            $table->string('goal_name', 20)->nullable();
            $table->string('goal_description', 500)->nullable();
            $table->decimal('goal_amount', 15, 2)->nullable();
            $table->decimal('goal_saved', 15, 2)->nullable();
            $table->date('goal_deadline')->nullable();
            $table->string('goal_category_name', 20)->nullable();
            $table->string('goal_category_icon', 20)->nullable();
            $table->string('goal_category_color', 20)->nullable();
            $table->enum('goal_category_type', ['income', 'expense', 'goals'])->nullable();

            $table->string('bill_name', 20)->nullable();
            $table->string('bill_description', 500)->nullable();
            $table->decimal('bill_paid', 15, 2)->nullable();
            $table->decimal('bill_amount', 15, 2)->nullable();
            $table->date('bill_due_date')->nullable();
            $table->enum('bill_frequency', ['monthly', 'weekly', 'yearly'])->nullable();
            $table->enum('bill_status', ['upcoming', 'paid', 'overdue'])->nullable();
            $table->string('bill_category_name', 20)->nullable();
            $table->string('bill_category_icon', 20)->nullable();
            $table->string('bill_category_color', 20)->nullable();
            $table->enum('bill_category_type', ['income', 'expense', 'goals'])->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_active_transactions');
    }
};
