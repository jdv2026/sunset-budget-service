<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\JwtAuthenticate;

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



});
