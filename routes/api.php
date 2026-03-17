<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\JwtAuthenticate;
use App\Http\Controllers\BudgetActiveCategoryController;
use App\Http\Controllers\BudgetColorCategoryController;
use App\Http\Controllers\BudgetActiveGoalController;
use App\Http\Controllers\BudgetIconCategoryController;

Route::get('/', function () {
    $title = '403 - Forbidden';
    $description = 'You do not have permission to access this resource.';
    $status = 403;

    return response()->view(
        'errors.common',
        [
            'title' => $title,
            'description' => $description,
            'status' => $status,
        ],
        $status
    );
});

Route::middleware([JwtAuthenticate::class])->group(function () {

    Route::get('/web/budget-icon-categories', [BudgetIconCategoryController::class, 'index']);
    Route::get('/web/budget-color-categories', [BudgetColorCategoryController::class, 'index']);
    Route::get('/web/budget-active-categories', [BudgetActiveCategoryController::class, 'index']);
    Route::put('/web/budget-active-categories/{id}', [BudgetActiveCategoryController::class, 'update']);
    Route::delete('/web/budget-active-categories/{id}', [BudgetActiveCategoryController::class, 'destroy']);
    Route::post('/web/store/budget-active-categories', [BudgetActiveCategoryController::class, 'store']);

    Route::get('/web/budget-active-goals', [BudgetActiveGoalController::class, 'index']);
    Route::put('/web/budget-active-goals/{id}', [BudgetActiveGoalController::class, 'update']);
    Route::delete('/web/budget-active-goals/{id}', [BudgetActiveGoalController::class, 'destroy']);
    Route::post('/web/store/budget-active-goals', [BudgetActiveGoalController::class, 'store']);

});
