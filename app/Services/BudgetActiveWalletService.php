<?php

namespace App\Services;

use App\Models\BudgetActiveCategory;
use App\Models\BudgetActiveWallet;

class BudgetActiveWalletService
{
    public function fetchAll(string $userId): \Illuminate\Database\Eloquent\Collection
    {
        return BudgetActiveWallet::where('user_id', $userId)->orderBy('name')->get();
    }

    public function delete(string $userId, int $id): bool
    {
        $wallet = BudgetActiveWallet::where('id', $id)->where('user_id', $userId)->first();

        if (!$wallet) {
            return false;
        }

        if ($wallet->amount != 0) {
            throw new \InvalidArgumentException('Wallet balance must be 0 before deleting.');
        }

        $wallet->delete();

        return true;
    }

    public function update(string $userId, int $id, array $data): ?BudgetActiveWallet
    {
        $wallet = BudgetActiveWallet::where('id', $id)->where('user_id', $userId)->first();

        if (!$wallet) {
            return null;
        }

        $payload = [
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
        ];

        if (!empty($data['category_id'])) {
            $category = BudgetActiveCategory::find($data['category_id']);
            $payload['category_name']  = $category->name;
            $payload['category_icon']  = $category->icon;
            $payload['category_color'] = $category->color;
            $payload['category_type']  = $category->type;
        }

        $wallet->update($payload);

        return $wallet;
    }

    public function store(string $userId, array $data): BudgetActiveWallet
    {
        $category = BudgetActiveCategory::find($data['category_id']);

        return BudgetActiveWallet::create([
            'user_id'        => $userId,
            'name'           => $data['name'],
            'description'    => $data['description'] ?? null,
            'category_name'  => $category->name,
            'category_icon'  => $category->icon,
            'category_color' => $category->color,
            'category_type'  => $category->type,
        ]);
    }
	
}
