<?php

namespace App\Services;

use App\Models\BudgetIconCategory;
use Illuminate\Database\Eloquent\Collection;

class BudgetIconCategoryService
{
    public function fetchAll(): Collection
    {
        return BudgetIconCategory::orderBy('icon_name')->get();
    }
}
