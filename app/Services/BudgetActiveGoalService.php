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
        $goal = $this->findGoal($userId, $id);

        if (!$goal) {
            return false;
        }

        if ($goal->saved !== 0) {
            throw new \InvalidArgumentException('Goal has saved funds and cannot be deleted.');
        }

        $goal->delete();

        return true;
    }

    public function update(string $userId, int $id, array $data): ?BudgetActiveGoal
    {
        $goal = $this->findGoal($userId, $id);

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

    private function findGoal(string $userId, int $id): ?BudgetActiveGoal
    {
        return BudgetActiveGoal::where('id', $id)->where('user_id', $userId)->first();
    }
}
