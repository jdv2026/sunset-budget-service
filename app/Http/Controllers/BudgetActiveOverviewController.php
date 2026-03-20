<?php

namespace App\Http\Controllers;

use App\Services\BudgetActiveReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BudgetActiveOverviewController extends BaseController
{
    public function __construct(private BudgetActiveReportService $service) {}

    public function index(Request $request): JsonResponse
    {
        $userId = $request->attributes->get('jwt_payload')->sub;
        $month  = $request->query('month', 'all');
        $year   = (int) $request->query('year', now()->year);

        $stats = $this->service->fetchStats($userId, $month, $year);
        $week  = $this->service->fetchWeekTotals($userId);

        return $this->success([
            'total_income'      => $stats['total_income'],
            'total_expense'     => $stats['total_expense'],
            'savings_rate'      => $stats['savings_rate'],
            'unassigned_money'  => $stats['unassigned_money'],
            'this_week_income'  => $week['this_week_income'],
            'this_week_expense' => $week['this_week_expense'],
            'transactions'      => $this->service->fetchTransactions($userId, $month, $year),
        ]);
    }
}
