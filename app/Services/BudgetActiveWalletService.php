<?php

namespace App\Services;

use App\Models\BudgetActiveWallet;

class BudgetActiveWalletService
{
    public function fetchAll(string $userId): \Illuminate\Database\Eloquent\Collection
    {
        return BudgetActiveWallet::where('user_id', $userId)->orderBy('name')->get();
    }

    public function delete(string $userId, int $id): bool
    {
        return BudgetActiveWallet::where('id', $id)->where('user_id', $userId)->delete() > 0;
    }

    public function update(string $userId, int $id, array $data): ?BudgetActiveWallet
    {
        $wallet = BudgetActiveWallet::where('id', $id)->where('user_id', $userId)->first();

        if (!$wallet) {
            return null;
        }

        $wallet->update([
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'category_id' => $data['category_id'] ?? null,
        ]);

        return $wallet;
    }

    public function store(string $userId, array $data): BudgetActiveWallet
    {
        return BudgetActiveWallet::create([
            'user_id'     => $userId,
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'category_id' => $data['category_id'],
        ]);
    }
	
}
