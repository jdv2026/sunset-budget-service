<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $colors = [
            // Reds & Pinks
            ['color_name' => 'Rose',        'hex_code' => '#f43f5e'],
            ['color_name' => 'Pink',        'hex_code' => '#ec4899'],
            ['color_name' => 'Red',         'hex_code' => '#ef4444'],
            ['color_name' => 'Red Dark',    'hex_code' => '#dc2626'],
            ['color_name' => 'Red Deeper',  'hex_code' => '#b91c1c'],

            // Oranges & Yellows
            ['color_name' => 'Orange',      'hex_code' => '#f97316'],
            ['color_name' => 'Amber',       'hex_code' => '#f59e0b'],
            ['color_name' => 'Yellow',      'hex_code' => '#eab308'],
            ['color_name' => 'Orange Dark', 'hex_code' => '#ea580c'],
            ['color_name' => 'Amber Dark',  'hex_code' => '#d97706'],

            // Greens
            ['color_name' => 'Lime',        'hex_code' => '#84cc16'],
            ['color_name' => 'Green',       'hex_code' => '#22c55e'],
            ['color_name' => 'Emerald',     'hex_code' => '#10b981'],
            ['color_name' => 'Teal',        'hex_code' => '#14b8a6'],
            ['color_name' => 'Green Dark',  'hex_code' => '#16a34a'],
            ['color_name' => 'Emerald Dark','hex_code' => '#059669'],

            // Blues
            ['color_name' => 'Cyan',        'hex_code' => '#06b6d4'],
            ['color_name' => 'Sky',         'hex_code' => '#0ea5e9'],
            ['color_name' => 'Blue',        'hex_code' => '#3b82f6'],
            ['color_name' => 'Blue Dark',   'hex_code' => '#2563eb'],
            ['color_name' => 'Blue Deeper', 'hex_code' => '#1d4ed8'],
            ['color_name' => 'Cyan Dark',   'hex_code' => '#0891b2'],

            // Purples & Violets
            ['color_name' => 'Indigo',      'hex_code' => '#6366f1'],
            ['color_name' => 'Violet',      'hex_code' => '#8b5cf6'],
            ['color_name' => 'Purple',      'hex_code' => '#a855f7'],
            ['color_name' => 'Fuchsia',     'hex_code' => '#d946ef'],
            ['color_name' => 'Indigo Dark', 'hex_code' => '#4f46e5'],
            ['color_name' => 'Violet Dark', 'hex_code' => '#7c3aed'],

            // Neutrals
            ['color_name' => 'Slate',       'hex_code' => '#64748b'],
            ['color_name' => 'Slate Dark',  'hex_code' => '#475569'],
            ['color_name' => 'Gray',        'hex_code' => '#6b7280'],
            ['color_name' => 'Zinc',        'hex_code' => '#71717a'],
            ['color_name' => 'Stone',       'hex_code' => '#78716c'],
        ];

        $rows = array_map(fn($color) => [
            'color_name' => $color['color_name'],
            'hex_code'   => $color['hex_code'],
            'created_at' => $now,
            'updated_at' => $now,
        ], $colors);

        DB::table('budget_color_categories')->insertOrIgnore($rows);
    }

    public function down(): void
    {
        DB::table('budget_color_categories')->truncate();
    }
};
