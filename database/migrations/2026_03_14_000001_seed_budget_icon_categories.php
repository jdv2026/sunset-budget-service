<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $icons = [
            // Food & Dining
            'restaurant', 'local_cafe', 'fastfood', 'local_bar', 'bakery_dining',
            // Transport
            'directions_car', 'directions_bus', 'local_taxi', 'two_wheeler', 'train',
            // Utilities
            'bolt', 'water_drop', 'wifi', 'phone', 'gas_meter',
            // Entertainment
            'movie', 'music_note', 'sports_esports', 'casino', 'theater_comedy',
            // Health & Wellness
            'favorite', 'local_hospital', 'fitness_center', 'spa', 'medication',
            // Finance
            'payments', 'savings', 'account_balance', 'credit_card', 'trending_up',
            // Shopping
            'shopping_cart', 'storefront', 'checkroom', 'diamond', 'redeem',
            // Home
            'home', 'chair', 'cleaning_services', 'handyman', 'security',
            // Travel
            'flight', 'hotel', 'luggage', 'beach_access', 'map',
            // Education
            'school', 'menu_book', 'science', 'computer', 'calculate',
            // Work
            'work', 'business_center', 'print', 'inventory', 'badge',
            // Pets
            'pets', 'veterinary',
        ];

        $rows = array_map(fn($icon) => [
            'icon_name'  => $icon,
            'created_at' => $now,
            'updated_at' => $now,
        ], $icons);

        DB::table('budget_icon_categories')->insertOrIgnore($rows);
    }

    public function down(): void
    {
        DB::table('budget_icon_categories')->truncate();
    }
};
