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
            $table->string('description', 500);
            $table->date('date');
            $table->foreignId('wallet_id')->nullable()->constrained('budget_active_wallets')->cascadeOnDelete();
			$table->foreignId('goal_id')->nullable()->constrained('budget_active_goals')->cascadeOnDelete();
            $table->foreignId('bill_id')->nullable()->constrained('budget_active_bills')->cascadeOnDelete();
            $table->decimal('wallet', 15, 2)->nullable();
            $table->decimal('goal', 15, 2)->nullable();
            $table->decimal('bill', 15, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_active_transactions');
    }
};
