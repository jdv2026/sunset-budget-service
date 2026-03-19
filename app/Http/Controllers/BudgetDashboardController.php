<?php

namespace App\Http\Controllers;

use App\Services\BudgetActiveBillService;
use App\Services\BudgetActiveGoalService;
use App\Services\BudgetActiveWalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BudgetDashboardController extends BaseController
{
    public function __construct(
        private BudgetActiveWalletService $walletService,
        private BudgetActiveGoalService $goalService,
        private BudgetActiveBillService $billService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $userId = $request->attributes->get('jwt_payload')->sub;

        return $this->success([
            'wallets' => $this->walletService->fetchAll($userId),
            'goals'   => $this->goalService->fetchAll($userId),
            'bills'   => $this->billService->fetchAll($userId),
        ]);
    }
}
