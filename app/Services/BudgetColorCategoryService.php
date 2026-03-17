<?php

namespace App\Services;

use App\Models\BudgetColorCategory;
use Illuminate\Database\Eloquent\Collection;

class BudgetColorCategoryService
{
    public function fetchAll(): Collection
    {
        return BudgetColorCategory::orderBy('color_name')->get();
    }
}
