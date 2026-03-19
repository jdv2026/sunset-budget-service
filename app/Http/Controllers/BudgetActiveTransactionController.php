<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBudgetActiveIncomeRequest;
use App\Http\Requests\UpdateBudgetActiveTransactionRequest;
use App\Services\BudgetActiveTransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BudgetActiveTransactionController extends BaseController
{
    public function __construct(private BudgetActiveTransactionService $service) {}

    public function index(Request $request): JsonResponse
    {
        $userId       = $request->attributes->get('jwt_payload')->sub;
        $month        = $request->input('month', 'all');
        $year         = $request->integer('year');
        $perPage      = $request->integer('per_page', 10);
        $transactions = $this->service->fetchAll($userId, $month, $year, $perPage);

        return $this->success($transactions);
    }

    public function storeIncome(StoreBudgetActiveIncomeRequest $request): JsonResponse
    {
        $userId      = $request->attributes->get('jwt_payload')->sub;
        $transaction = $this->service->storeIncome($userId, $request->validated());

        return $this->success($transaction, 'Income saved successfully', 201);
    }

    public function payBills(Request $request): JsonResponse
    {
        $userId   = $request->attributes->get('jwt_payload')->sub;
        $amount   = $request->input('amount');
        $from     = $request->input('from'); // e.g. "wallet:1"
        $to       = $request->input('to');   // e.g. "bill:1"

        $walletId = (int) explode(':', $from)[1];
        $billId   = (int) explode(':', $to)[1];

        try {
            $transaction = $this->service->payBills($userId, $walletId, $billId, $amount);
        } catch (\InvalidArgumentException $e) {
            return $this->fail($e->getMessage(), 422);
        }

        return $this->success($transaction, 'Bill paid successfully', 201);
    }

    public function transferFunds(Request $request): JsonResponse
    {
        $userId = $request->attributes->get('jwt_payload')->sub;
        $amount = $request->input('amount');
        $from   = $request->input('from'); // e.g. "wallet:1"
        $to     = $request->input('to');   // e.g. "wallet:2"

        try {
            $transaction = $this->service->transferFunds($userId, $from, $to, $amount);
        } catch (\InvalidArgumentException $e) {
            return $this->fail($e->getMessage(), 422);
        }

        return $this->success($transaction, 'Funds transferred successfully', 201);
    }

}
