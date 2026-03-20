<?php

namespace App\Services;

use App\Models\BudgetActiveBill;
use App\Models\BudgetActiveGoal;
use App\Models\BudgetActiveTransaction;
use App\Models\BudgetActiveWallet;

class BudgetActiveTransactionService
{
    public function fetchAll(string $userId, string $month, int $year, int $perPage): array
    {
        $base = BudgetActiveTransaction::where('user_id', $userId)
            ->when($month !== 'all', fn($q) => $q->whereMonth('date', $month))
            ->whereYear('date', $year);

        $totals = (clone $base)->selectRaw('
            COALESCE(SUM(CASE WHEN type = ? THEN wallet_amount END), 0) as total_income,
            COALESCE(SUM(CASE WHEN type = ? THEN bill_paid END), 0)     as total_expense
        ', ['income', 'expense'])->first();

        $transactions = (clone $base)
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return [
            'total_income'  => (float) $totals->total_income,
            'total_expense' => (float) $totals->total_expense,
            'balance'       => (float) $totals->total_income - (float) $totals->total_expense,
            'transactions'  => $transactions,
        ];
    }

    public function fetchPayTransferOptions(string $userId): array
    {
        return [
            'wallets' => BudgetActiveWallet::where('user_id', $userId)->orderBy('name')->get(),
            'goals'   => BudgetActiveGoal::where('user_id', $userId)->orderBy('name')->get(),
            'bills'   => BudgetActiveBill::where('user_id', $userId)->orderBy('name')->get(),
        ];
    }

    public function storeIncome(string $userId, array $data): BudgetActiveTransaction
    {
        $wallet = $this->resolveEntity($userId, 'wallet', $data['wallet_id']);

        $this->incrementWalletAmount($userId, $data['wallet_id'], $data['amount']);

        return BudgetActiveTransaction::create(array_merge(
            [
                'user_id'     => $userId,
                'type'        => 'income',
                'description' => 'cash in to ' . $wallet->name,
                'date'        => now()->toDateString(),
            ],
            $this->walletSnapshot($wallet, $data['amount'])
        ));
    }

    public function delete(string $userId, int $id): bool
    {
        return BudgetActiveTransaction::where('id', $id)->where('user_id', $userId)->delete() > 0;
    }

    public function payBills(string $userId, int $walletId, int $billId, float $amount): BudgetActiveTransaction
    {
        $wallet = $this->resolveEntity($userId, 'wallet', $walletId);
        $bill   = $this->resolveEntity($userId, 'bill', $billId);

        $this->validateFromBalance('wallet', $wallet, $amount);
        $this->validateFromBalance('bill', $bill, $amount);

        $this->decrementWalletAmount($userId, $walletId, $amount);
        $this->incrementBillPaid($userId, $billId, $amount);

        return BudgetActiveTransaction::create(array_merge(
            [
                'user_id'     => $userId,
                'type'        => 'expense',
                'description' => 'pay bill ' . $bill->name,
                'date'        => now()->toDateString(),
            ],
            $this->walletSnapshot($wallet, -$amount),
            $this->billSnapshot($bill, $amount)
        ));
    }

    public function transferFunds(string $userId, string $from, string $to, float $amount): BudgetActiveTransaction
    {
        [$fromType, $fromId] = explode(':', $from);
        [$toType,   $toId]   = explode(':', $to);

        $fromId = (int) $fromId;
        $toId   = (int) $toId;

        $fromEntity = $this->resolveEntity($userId, $fromType, $fromId);
        $toEntity   = $this->resolveEntity($userId, $toType, $toId);

        $this->validateFromBalance($fromType, $fromEntity, $amount);

        $fromName = $fromEntity->name;
        $toName   = $toEntity->name;

        $this->decrementEntity($userId, $fromType, $fromId, $amount);
        $this->incrementEntity($userId, $toType, $toId, $amount);

        $description = 'transfer funds from ' . $fromName . ' to ' . $toName;
        $base = ['user_id' => $userId, 'type' => 'transfer', 'description' => $description, 'date' => now()->toDateString()];

        if ($fromType === $toType) {
            BudgetActiveTransaction::create(array_merge($base, $this->entitySnapshot($fromType, $fromEntity, -$amount)));

            return BudgetActiveTransaction::create(array_merge($base, $this->entitySnapshot($toType, $toEntity, $amount)));
        }

        return BudgetActiveTransaction::create(array_merge(
            $base,
            $this->entitySnapshot($fromType, $fromEntity, -$amount),
            $this->entitySnapshot($toType, $toEntity, $amount)
        ));
    }

    private function entitySnapshot(string $type, BudgetActiveWallet|BudgetActiveBill|BudgetActiveGoal $entity, float $amount): array
    {
        return match ($type) {
            'wallet' => $this->walletSnapshot($entity, $amount),
            'goal'   => $this->goalSnapshot($entity, $amount),
            'bill'   => $this->billSnapshot($entity, $amount),
        };
    }

    private function walletSnapshot(BudgetActiveWallet $wallet, float $amount): array
    {
        return [
            'wallet_name'           => $wallet->name,
            'wallet_description'    => $wallet->description,
            'wallet_amount'         => $amount,
            'wallet_category_name'  => $wallet->category_name,
            'wallet_category_icon'  => $wallet->category_icon,
            'wallet_category_color' => $wallet->category_color,
            'wallet_category_type'  => $wallet->category_type,
        ];
    }

    private function goalSnapshot(BudgetActiveGoal $goal, float $amount): array
    {
        return [
            'goal_name'           => $goal->name,
            'goal_description'    => $goal->description,
            'goal_amount'         => $goal->amount,
            'goal_saved'          => $amount,
            'goal_deadline'       => $goal->deadline,
            'goal_category_name'  => $goal->category_name,
            'goal_category_icon'  => $goal->category_icon,
            'goal_category_color' => $goal->category_color,
            'goal_category_type'  => $goal->category_type,
        ];
    }

    private function billSnapshot(BudgetActiveBill $bill, float $amount): array
    {
        return [
            'bill_name'           => $bill->name,
            'bill_description'    => $bill->description,
            'bill_paid'           => $amount,
            'bill_amount'         => $bill->amount,
            'bill_due_date'       => $bill->due_date,
            'bill_frequency'      => $bill->frequency,
            'bill_status'         => $bill->status,
            'bill_category_name'  => $bill->category_name,
            'bill_category_icon'  => $bill->category_icon,
            'bill_category_color' => $bill->category_color,
            'bill_category_type'  => $bill->category_type,
        ];
    }

    private function resolveEntity(string $userId, string $type, int $id): BudgetActiveWallet|BudgetActiveBill|BudgetActiveGoal
    {
        return match ($type) {
            'wallet' => BudgetActiveWallet::where('id', $id)->where('user_id', $userId)->firstOrFail(),
            'bill'   => BudgetActiveBill::where('id', $id)->where('user_id', $userId)->firstOrFail(),
            'goal'   => BudgetActiveGoal::where('id', $id)->where('user_id', $userId)->firstOrFail(),
            default  => throw new \InvalidArgumentException("Unsupported entity type: {$type}"),
        };
    }

    private function validateFromBalance(string $type, BudgetActiveWallet|BudgetActiveBill|BudgetActiveGoal $entity, float $amount): void
    {
        $sufficient = match ($type) {
            'wallet' => $entity->amount >= $amount,
            'bill'   => ($entity->amount - $entity->paid) >= $amount,
            'goal'   => $entity->saved >= $amount,
            default  => throw new \InvalidArgumentException("Unsupported entity type: {$type}"),
        };

        if (!$sufficient) {
            throw new \InvalidArgumentException("Insufficient {$type} balance.");
        }
    }

    private function decrementEntity(string $userId, string $type, int $id, float $amount): void
    {
        match ($type) {
            'wallet' => $this->decrementWalletAmount($userId, $id, $amount),
            'bill'   => BudgetActiveBill::where('id', $id)->where('user_id', $userId)->decrement('paid', $amount),
            'goal'   => BudgetActiveGoal::where('id', $id)->where('user_id', $userId)->decrement('saved', $amount),
            default  => throw new \InvalidArgumentException("Unsupported entity type: {$type}"),
        };
    }

    private function incrementEntity(string $userId, string $type, int $id, float $amount): void
    {
        match ($type) {
            'wallet' => $this->incrementWalletAmount($userId, $id, $amount),
            'bill'   => BudgetActiveBill::where('id', $id)->where('user_id', $userId)->increment('paid', $amount),
            'goal'   => BudgetActiveGoal::where('id', $id)->where('user_id', $userId)->increment('saved', $amount),
            default  => throw new \InvalidArgumentException("Unsupported entity type: {$type}"),
        };
    }

    private function incrementWalletAmount(string $userId, int $walletId, float $amount): void
    {
        BudgetActiveWallet::where('id', $walletId)->where('user_id', $userId)->increment('amount', $amount);
    }

    private function decrementWalletAmount(string $userId, int $walletId, float $amount): void
    {
        BudgetActiveWallet::where('id', $walletId)->where('user_id', $userId)->decrement('amount', $amount);
    }

    private function incrementBillPaid(string $userId, int $billId, float $amount): void
    {
        BudgetActiveBill::where('id', $billId)->where('user_id', $userId)->increment('paid', $amount);
    }
}
