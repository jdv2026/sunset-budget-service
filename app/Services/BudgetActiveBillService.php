<?php

namespace App\Services;

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

        $bill->update([
            'category_id' => $data['category_id'],
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'amount'      => $data['amount'],
            'due_date'    => $data['due_date'],
            'frequency'   => $data['frequency'],
        ]);

        return $bill;
    }

    public function store(string $userId, array $data): BudgetActiveBill
    {
        return BudgetActiveBill::create([
            'user_id'     => $userId,
            'category_id' => $data['category_id'],
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'amount'      => $data['amount'],
            'due_date'    => $data['due_date'],
            'frequency'   => $data['frequency'],
            'status'      => $this->resolveStatus($data['due_date']),
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
