<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('navigations', function (Blueprint $table) {
            $table->unique('link');
        });

        $now = now();

        DB::table('navigations')->upsert(
            [
                ['logo' => 'mat:home',         'name' => 'Home',         'link' => '/dashboard/home',                 'header' => 'Main',   'created_at' => $now, 'updated_at' => $now],
                ['logo' => 'mat:dashboard',    'name' => 'Overview',     'link' => '/dashboard/budget/overview',      'header' => 'Budget', 'created_at' => $now, 'updated_at' => $now],
                ['logo' => 'mat:receipt_long', 'name' => 'Transactions', 'link' => '/dashboard/budget/transactions',  'header' => 'Budget', 'created_at' => $now, 'updated_at' => $now],
                ['logo' => 'mat:category',     'name' => 'Categories',   'link' => '/dashboard/budget/categories',    'header' => 'Budget', 'created_at' => $now, 'updated_at' => $now],
                ['logo' => 'mat:bar_chart',    'name' => 'Reports',      'link' => '/dashboard/budget/reports',       'header' => 'Budget', 'created_at' => $now, 'updated_at' => $now],
                ['logo' => 'mat:savings',      'name' => 'Goals',        'link' => '/dashboard/budget/goals',         'header' => 'Budget', 'created_at' => $now, 'updated_at' => $now],
                ['logo' => 'mat:event_repeat', 'name' => 'Bills',        'link' => '/dashboard/budget/bills',         'header' => 'Budget', 'created_at' => $now, 'updated_at' => $now],
            ],
            ['link'],
            ['logo', 'name', 'header', 'updated_at']
        );
    }

    public function down(): void
    {
        DB::table('navigations')->whereIn('link', [
            '/dashboard/home',
            '/dashboard/budget/overview',
            '/dashboard/budget/transactions',
            '/dashboard/budget/categories',
            '/dashboard/budget/reports',
            '/dashboard/budget/goals',
            '/dashboard/budget/bills',
        ])->delete();

        Schema::table('navigations', function (Blueprint $table) {
            $table->dropUnique(['link']);
        });
    }
};
