<?php

namespace App\Http\Controllers;

use App\Services\BudgetActiveReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BudgetActiveReportController extends BaseController
{
    public function __construct(private BudgetActiveReportService $service) {}

    public function index(Request $request): JsonResponse
    {
        $userId = $request->attributes->get('jwt_payload')->sub;
        $month  = $request->query('month', 'all');
        $year   = (int) $request->query('year', now()->year);

        $report = $this->service->fetchReport($userId, $month, $year);

        return $this->success($report);
    }
}
