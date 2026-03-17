<?php

namespace App\Http\Controllers;

use App\Services\BudgetIconCategoryService;
use Illuminate\Http\JsonResponse;

class BudgetIconCategoryController extends BaseController
{
    public function __construct(private BudgetIconCategoryService $service) {}

    public function index(): JsonResponse
    {
        return $this->success($this->service->fetchAll());
    }
}
