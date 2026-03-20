<?php

namespace App\Services;

use App\Models\BudgetActiveGoal;
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

    public function fetchStats(string $userId, string $month, int $year): array
    {
        $base = BudgetActiveTransaction::where('user_id', $userId)
            ->whereIn('type', ['expense', 'income'])
            ->when($month !== 'all', fn($q) => $q->whereMonth('date', $month))
            ->whereYear('date', $year);

        [$totalIncome, $totalExpense] = $this->getTotals($base);

        $balance          = $totalIncome - $totalExpense;
        $savingsRate      = $totalIncome > 0 ? round(($balance / $totalIncome) * 100, 2) : 0;
        $goalsSaved       = (float) BudgetActiveGoal::where('user_id', $userId)->sum('saved');
        $unassignedMoney  = $totalIncome - $goalsSaved;

        return [
            'total_income'     => $totalIncome,
            'total_expense'    => $totalExpense,
            'savings_rate'     => $savingsRate,
            'unassigned_money' => $unassignedMoney,
        ];
    }

    public function fetchWeekTotals(string $userId): array
    {
        $row = BudgetActiveTransaction::where('user_id', $userId)
            ->whereIn('type', ['expense', 'income'])
            ->whereBetween('date', [now()->startOfWeek(), now()->endOfWeek()])
            ->selectRaw('
                COALESCE(SUM(CASE WHEN type = ? THEN wallet_amount END), 0) as this_week_income,
                COALESCE(SUM(CASE WHEN type = ? THEN bill_paid END), 0)     as this_week_expense
            ', ['income', 'expense'])
            ->first();

        return [
            'this_week_income'  => (float) $row->this_week_income,
            'this_week_expense' => (float) $row->this_week_expense,
        ];
    }

    public function fetchTransactions(string $userId, string $month, int $year): \Illuminate\Support\Collection
    {
        $base = BudgetActiveTransaction::where('user_id', $userId)
            ->whereIn('type', ['expense', 'income'])
            ->when($month !== 'all', fn($q) => $q->whereMonth('date', $month))
            ->whereYear('date', $year);

        return $this->getTransactions($base);
    }

    private function getTotals(Builder $base): array
    {
        $row = (clone $base)->selectRaw('
            COALESCE(SUM(CASE WHEN type = ? THEN wallet_amount END), 0) as total_income,
            COALESCE(SUM(CASE WHEN type = ? THEN bill_paid END), 0)    as total_expense
        ', ['income', 'expense'])->first();

        return [(float) $row->total_income, (float) $row->total_expense];
    }

    private function getTransactions(Builder $base): \Illuminate\Support\Collection
    {
        return (clone $base)
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($t) => [
                'id'           => $t->id,
                'type'         => $t->type,
                'description'  => $t->description,
                'date'         => $t->date,
                'wallet'       => $t->wallet_amount,
                'wallet_name'  => $t->wallet_name,
                'wallet_color' => $t->wallet_category_color,
                'goal'         => $t->goal_saved,
                'goal_name'    => $t->goal_name,
                'goal_color'   => $t->goal_category_color,
                'bill'         => $t->bill_paid,
                'bill_name'    => $t->bill_name,
                'bill_color'   => $t->bill_category_color,
                'created_at'   => $t->created_at,
                'updated_at'   => $t->updated_at,
            ]);
    }
}
