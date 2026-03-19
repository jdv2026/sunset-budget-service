<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\JwtAuthenticate;
use App\Http\Controllers\BudgetActiveCategoryController;
use App\Http\Controllers\BudgetColorCategoryController;
use App\Http\Controllers\BudgetActiveGoalController;
use App\Http\Controllers\BudgetIconCategoryController;
use App\Http\Controllers\BudgetActiveBillController;
use App\Http\Controllers\BudgetActiveWalletController;
use App\Http\Controllers\BudgetActiveTransactionController;
use App\Http\Controllers\BudgetDashboardController;

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
    Route::get('/web/budget-active-categories/wallets', [BudgetActiveCategoryController::class, 'forWallets']);
    Route::get('/web/budget-active-categories/bills', [BudgetActiveCategoryController::class, 'forBills']);
    Route::get('/web/budget-active-categories/goals', [BudgetActiveCategoryController::class, 'forGoals']);
    Route::put('/web/budget-active-categories/{id}', [BudgetActiveCategoryController::class, 'update']);
    Route::delete('/web/budget-active-categories/{id}', [BudgetActiveCategoryController::class, 'destroy']);
    Route::post('/web/store/budget-active-categories', [BudgetActiveCategoryController::class, 'store']);

    Route::get('/web/budget-active-goals', [BudgetActiveGoalController::class, 'index']);
    Route::put('/web/budget-active-goals/{id}', [BudgetActiveGoalController::class, 'update']);
    Route::delete('/web/budget-active-goals/{id}', [BudgetActiveGoalController::class, 'destroy']);
    Route::post('/web/store/budget-active-goals', [BudgetActiveGoalController::class, 'store']);

    Route::get('/web/budget-active-bills', [BudgetActiveBillController::class, 'index']);
    Route::put('/web/budget-active-bills/{id}', [BudgetActiveBillController::class, 'update']);
    Route::delete('/web/budget-active-bills/{id}', [BudgetActiveBillController::class, 'destroy']);
    Route::post('/web/store/budget-active-bills', [BudgetActiveBillController::class, 'store']);

    Route::get('/web/budget-active-wallets', [BudgetActiveWalletController::class, 'index']);
    Route::put('/web/budget-active-wallets/{id}', [BudgetActiveWalletController::class, 'update']);
    Route::delete('/web/budget-active-wallets/{id}', [BudgetActiveWalletController::class, 'destroy']);
    Route::post('/web/store/budget-active-wallets', [BudgetActiveWalletController::class, 'store']);

    Route::get('/web/budget-active-transactions', [BudgetActiveTransactionController::class, 'index']);
    Route::post('/web/store/income/budget-active-transactions', [BudgetActiveTransactionController::class, 'storeIncome']);
    Route::post('/web/store/pay-bills/budget-active-transactions', [BudgetActiveTransactionController::class, 'payBills']);
    Route::post('/web/store/transfer-funds/budget-active-transactions', [BudgetActiveTransactionController::class, 'transferFunds']);

    Route::get('/web/budget-dashboard', [BudgetDashboardController::class, 'index']);

});
