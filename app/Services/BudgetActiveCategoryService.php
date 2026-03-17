<?php

namespace App\Services;

use App\Models\BudgetActiveCategory;

class BudgetActiveCategoryService
{
    public function fetchAll(string $userId): \Illuminate\Database\Eloquent\Collection
    {
        return BudgetActiveCategory::where('user_id', $userId)->orderBy('name')->get();
    }

    public function delete(string $userId, int $id): bool
    {
        return BudgetActiveCategory::where('id', $id)->where('user_id', $userId)->delete() > 0;
    }

    public function update(string $userId, int $id, array $data): ?BudgetActiveCategory
    {
        $category = BudgetActiveCategory::where('id', $id)->where('user_id', $userId)->first();

        if (!$category) {
            return null;
        }

        $category->update([
            'name'        => $data['name'],
            'icon'        => $data['icon'],
            'color'       => $data['color'],
            'description' => $data['description'] ?? null,
        ]);

        return $category;
    }

    public function store(string $userId, array $data): BudgetActiveCategory
    {
        return BudgetActiveCategory::create([
            'user_id' => $userId,
            'name'    => $data['name'],
            'icon'    => $data['icon'],
            'color'   => $data['color'],
            'description' => $data['description'] ?? null,
        ]);
    }
}
