<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateUserRequest;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class UserController extends BaseController
{
    public function __construct(private readonly UserService $userService)
    {
    }


}
