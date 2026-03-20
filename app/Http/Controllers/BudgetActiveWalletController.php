<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBudgetActiveWalletRequest;
use App\Http\Requests\UpdateBudgetActiveWalletRequest;
use App\Services\BudgetActiveWalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BudgetActiveWalletController extends BaseController
{
    public function __construct(private BudgetActiveWalletService $service) {}

    public function index(Request $request): JsonResponse
    {
        $userId  = $request->attributes->get('jwt_payload')->sub;
        $wallets = $this->service->fetchAll($userId);

        return $this->success($wallets);
    }

    public function store(StoreBudgetActiveWalletRequest $request): JsonResponse
    {
        $userId = $request->attributes->get('jwt_payload')->sub;
        $wallet = $this->service->store($userId, $request->validated());

        return $this->success($wallet, 'Wallet saved successfully', 201);
    }

    public function update(UpdateBudgetActiveWalletRequest $request, int $id): JsonResponse
    {
        $userId = $request->attributes->get('jwt_payload')->sub;
        $wallet = $this->service->update($userId, $id, $request->validated());

        if (!$wallet) {
            return $this->fail('Wallet not found', 404);
        }

        return $this->success($wallet, 'Wallet updated successfully');
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $userId  = $request->attributes->get('jwt_payload')->sub;
        $deleted = $this->service->delete($userId, $id);

        if (!$deleted) {
            return $this->fail('Wallet not found', 404);
        }

        return $this->success(message: 'Wallet deleted successfully');
    }
}
