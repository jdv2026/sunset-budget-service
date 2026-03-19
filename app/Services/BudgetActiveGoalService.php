<?php

namespace App\Services;

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

        $goal->update([
            'category_id' => $data['category_id'],
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'amount'      => $data['amount'],
            'deadline'    => $data['deadline'] ?? null,
        ]);

        return $goal;
    }

    public function store(string $userId, array $data): BudgetActiveGoal
    {
        return BudgetActiveGoal::create([
            'user_id'     => $userId,
            'category_id' => $data['category_id'],
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'amount'      => $data['amount'],
            'saved'       => 0,
            'deadline'    => $data['deadline'] ?? null,
        ]);
    }
}
