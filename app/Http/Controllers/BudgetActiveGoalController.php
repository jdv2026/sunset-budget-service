<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBudgetActiveGoalRequest;
use App\Http\Requests\UpdateBudgetActiveGoalRequest;
use App\Services\BudgetActiveGoalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BudgetActiveGoalController extends BaseController
{
    public function __construct(private BudgetActiveGoalService $service) {}

    public function index(Request $request): JsonResponse
    {
        $userId = $request->attributes->get('jwt_payload')->sub;
        $goals  = $this->service->fetchAll($userId);

        return $this->success($goals);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $userId  = $request->attributes->get('jwt_payload')->sub;
        $deleted = $this->service->delete($userId, $id);

        if (!$deleted) {
            return $this->fail('Goal not found', 404);
        }

        return $this->success(message: 'Goal deleted successfully');
    }

    public function update(UpdateBudgetActiveGoalRequest $request, int $id): JsonResponse
    {
        $userId = $request->attributes->get('jwt_payload')->sub;
        $goal   = $this->service->update($userId, $id, $request->validated());

        if (!$goal) {
            return $this->fail('Goal not found', 404);
        }

        return $this->success($goal, 'Goal updated successfully');
    }

    public function store(StoreBudgetActiveGoalRequest $request): JsonResponse
    {
        $userId = $request->attributes->get('jwt_payload')->sub;
        $goal   = $this->service->store($userId, $request->validated());

        return $this->success($goal, 'Goal saved successfully', 201);
    }
}
