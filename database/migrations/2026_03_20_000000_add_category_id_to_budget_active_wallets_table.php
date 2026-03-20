<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('budget_active_wallets', function (Blueprint $table) {
            $table->foreignId('category_id')->after('updated_at')
                ->constrained('budget_active_categories')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('budget_active_wallets', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn('category_id');
        });
    }
};
