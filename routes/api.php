<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\JwtAuthenticate;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\JwksController;
use App\Http\Controllers\MetaController;

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

Route::get('jwks', [JwksController::class, 'jwks']);

Route::post('web/meta/data', [MetaController::class, 'meta']);
Route::post('web/user/login', [AuthController::class, 'userLogin']);
Route::post('web/guest/login', [AuthController::class, 'guestLogin']);
Route::post('web/firsttime/2fa/verify', [AuthController::class, 'verifyFirstTime2fa']);
Route::post('web/2fa/recovery', [AuthController::class, 'useRecoveryCode']);
Route::post('web/2fa/verify', [AuthController::class, 'verify2fa']);

Route::middleware([JwtAuthenticate::class])->group(function () {

    Route::post('web/2fa/enable',  [AuthController::class, 'enable2fa']);
    Route::post('web/2fa/disable', [AuthController::class, 'disable2fa']);
	Route::post('web/user', [AuthController::class, 'fetchAuthUser']);

	Route::post('web/nav', [MetaController::class, 'getNav']);

});
