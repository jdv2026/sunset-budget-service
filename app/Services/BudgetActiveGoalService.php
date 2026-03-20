<?php

namespace App\Services;

use App\Models\BudgetActiveCategory;
use App\Models\BudgetActiveGoal;

class BudgetActiveGoalService
{
    public function fetchAll(string $userId): \Illuminate\Database\Eloquent\Collection
    {
        return BudgetActiveGoal::where('user_id', $userId)->orderBy('created_at')->get();
    }

    public function delete(string $userId, int $id): bool
    {
        return BudgetActiveGoal::where('id', $id)->where('user_id', $userId)->delete() > 0;
    }

    public function update(string $userId, int $id, array $data): ?BudgetActiveGoal
    {
        $goal = BudgetActiveGoal::where('id', $id)->where('user_id', $userId)->first();

        if (!$goal) {
            return null;
        }

        $category = BudgetActiveCategory::find($data['category_id']);

        $goal->update([
            'name'           => $data['name'],
            'description'    => $data['description'] ?? null,
            'category_name'  => $category->name,
            'category_icon'  => $category->icon,
            'category_color' => $category->color,
            'category_type'  => $category->type,
            'amount'         => $data['amount'],
            'deadline'       => $data['deadline'] ?? null,
        ]);

        return $goal;
    }

    public function store(string $userId, array $data): BudgetActiveGoal
    {
        $category = BudgetActiveCategory::find($data['category_id']);

        return BudgetActiveGoal::create([
            'user_id'        => $userId,
            'name'           => $data['name'],
            'description'    => $data['description'] ?? null,
            'category_name'  => $category->name,
            'category_icon'  => $category->icon,
            'category_color' => $category->color,
            'category_type'  => $category->type,
            'amount'         => $data['amount'],
            'saved'          => 0,
            'deadline'       => $data['deadline'] ?? null,
        ]);
    }
}
