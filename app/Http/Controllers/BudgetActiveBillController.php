<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBudgetActiveBillRequest;
use App\Http\Requests\UpdateBudgetActiveBillRequest;
use App\Services\BudgetActiveBillService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BudgetActiveBillController extends BaseController
{
    public function __construct(private BudgetActiveBillService $service) {}

    public function index(Request $request): JsonResponse
    {
        $userId = $request->attributes->get('jwt_payload')->sub;
        $bills  = $this->service->fetchAll($userId);

        return $this->success($bills);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $userId  = $request->attributes->get('jwt_payload')->sub;
        $deleted = $this->service->delete($userId, $id);

        if (!$deleted) {
            return $this->fail('Bill not found', 404);
        }

        return $this->success(message: 'Bill deleted successfully');
    }

    public function update(UpdateBudgetActiveBillRequest $request, int $id): JsonResponse
    {
        $userId = $request->attributes->get('jwt_payload')->sub;
        $bill   = $this->service->update($userId, $id, $request->validated());

        if (!$bill) {
            return $this->fail('Bill not found', 404);
        }

        return $this->success($bill, 'Bill updated successfully');
    }

    public function store(StoreBudgetActiveBillRequest $request): JsonResponse
    {
        $userId = $request->attributes->get('jwt_payload')->sub;
        $bill   = $this->service->store($userId, $request->validated());

        return $this->success($bill, 'Bill saved successfully', 201);
    }
}
