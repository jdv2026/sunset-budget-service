<?php

namespace App\Services;

use App\Models\BudgetActiveTransaction;
use Illuminate\Database\Eloquent\Builder;

class BudgetActiveReportService
{
    public function fetchReport(string $userId, string $month, int $year): array
    {
        $base = BudgetActiveTransaction::where('user_id', $userId)
            ->whereIn('type', ['expense', 'income'])
            ->when($month !== 'all', fn($q) => $q->whereMonth('date', $month))
            ->whereYear('date', $year);

        [$totalIncome, $totalExpense] = $this->getTotals($base);

        $balance     = $totalIncome - $totalExpense;
        $savingsRate = $totalIncome > 0 ? round(($balance / $totalIncome) * 100, 2) : 0;

        return [
            'total_income'               => $totalIncome,
            'total_expense'              => $totalExpense,
            'balance'                    => $balance,
            'savings_rate'               => $savingsRate,
            'transactions_income_expense' => $this->getTransactions($base),
        ];
    }

    private function getTotals(Builder $base): array
    {
        $row = (clone $base)->selectRaw('
            COALESCE(SUM(CASE WHEN type = ? THEN wallet END), 0) as total_income,
            COALESCE(SUM(CASE WHEN type = ? THEN bill END), 0)   as total_expense
        ', ['income', 'expense'])->first();

        return [(float) $row->total_income, (float) $row->total_expense];
    }

    private function getTransactions(Builder $base): \Illuminate\Support\Collection
    {
        return (clone $base)
            ->with([
                'wallet:id,name,category_id', 'wallet.category:id,color',
                'goal:id,name,category_id',   'goal.category:id,color',
                'bill:id,name,category_id',   'bill.category:id,color',
            ])
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($t) => [
                'id'          => $t->id,
                'type'        => $t->type,
                'description' => $t->description,
                'date'        => $t->date,
                'wallet'       => $t->wallet,
                'wallet_name'  => $t->getRelation('wallet')?->name,
                'wallet_color' => $t->getRelation('wallet')?->category?->color,
                'goal'         => $t->goal,
                'goal_name'    => $t->getRelation('goal')?->name,
                'goal_color'   => $t->getRelation('goal')?->category?->color,
                'bill'         => $t->bill,
                'bill_name'    => $t->getRelation('bill')?->name,
                'bill_color'   => $t->getRelation('bill')?->category?->color,
                'created_at'  => $t->created_at,
                'updated_at'  => $t->updated_at,
            ]);
    }
}
