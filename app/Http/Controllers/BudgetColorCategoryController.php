<?php

namespace App\Http\Controllers;

use App\Services\BudgetColorCategoryService;
use Illuminate\Http\JsonResponse;

class BudgetColorCategoryController extends BaseController
{
    public function __construct(private BudgetColorCategoryService $service) {}

    public function index(): JsonResponse
    {
        return $this->success($this->service->fetchAll());
    }
}
