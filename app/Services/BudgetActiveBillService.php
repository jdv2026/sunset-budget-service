<?php

namespace App\Services;

use App\Models\BudgetActiveCategory;
use App\Models\BudgetActiveBill;
use Carbon\Carbon;

class BudgetActiveBillService
{

    public function fetchAll(string $userId): \Illuminate\Database\Eloquent\Collection
    {
        return BudgetActiveBill::where('user_id', $userId)->orderBy('name')->get();
    }

    public function delete(string $userId, int $id): bool
    {
        return BudgetActiveBill::where('id', $id)->where('user_id', $userId)->delete() > 0;
    }

    public function update(string $userId, int $id, array $data): ?BudgetActiveBill
    {
        $bill = BudgetActiveBill::where('id', $id)->where('user_id', $userId)->first();

        if (!$bill) {
            return null;
        }

        $category = BudgetActiveCategory::find($data['category_id']);

        $bill->update([
            'name'           => $data['name'],
            'description'    => $data['description'] ?? null,
            'category_name'  => $category->name,
            'category_icon'  => $category->icon,
            'category_color' => $category->color,
            'category_type'  => $category->type,
            'amount'         => $data['amount'],
            'due_date'       => $data['due_date'],
            'frequency'      => $data['frequency'],
        ]);

        return $bill;
    }

    public function store(string $userId, array $data): BudgetActiveBill
    {
        $category = BudgetActiveCategory::find($data['category_id']);

        return BudgetActiveBill::create([
            'user_id'        => $userId,
            'name'           => $data['name'],
            'description'    => $data['description'] ?? null,
            'category_name'  => $category->name,
            'category_icon'  => $category->icon,
            'category_color' => $category->color,
            'category_type'  => $category->type,
            'amount'         => $data['amount'],
            'due_date'       => $data['due_date'],
            'frequency'      => $data['frequency'],
            'status'         => $this->resolveStatus($data['due_date']),
        ]);
    }

	private function resolveStatus(string $dueDate): string
    {
        $due = Carbon::parse($dueDate)->startOfDay();

        if ($due->isPast() && !$due->isToday()) {
            return 'overdue';
        }

        return 'upcoming';
    }
	
}
