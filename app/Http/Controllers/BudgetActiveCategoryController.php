<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBudgetActiveCategoryRequest;
use App\Http\Requests\UpdateBudgetActiveCategoryRequest;
use App\Services\BudgetActiveCategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BudgetActiveCategoryController extends BaseController
{
    public function __construct(private BudgetActiveCategoryService $service) {}

    public function index(Request $request): JsonResponse
    {
        $userId     = $request->attributes->get('jwt_payload')->sub;
        $categories = $this->service->fetchAll($userId);

        return $this->success($categories);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $userId  = $request->attributes->get('jwt_payload')->sub;
        $deleted = $this->service->delete($userId, $id);

        if (!$deleted) {
            return $this->fail('Category not found', 404);
        }

        return $this->success(message: 'Category deleted successfully');
    }

    public function update(UpdateBudgetActiveCategoryRequest $request, int $id): JsonResponse
    {
        $userId   = $request->attributes->get('jwt_payload')->sub;
        $category = $this->service->update($userId, $id, $request->validated());

        if (!$category) {
            return $this->fail('Category not found', 404);
        }

        return $this->success($category, 'Category updated successfully');
    }

    public function store(StoreBudgetActiveCategoryRequest $request): JsonResponse
    {
        $userId   = $request->attributes->get('jwt_payload')->sub;
        $category = $this->service->store($userId, $request->validated());

        return $this->success($category, 'Category saved successfully', 201);
    }
}
