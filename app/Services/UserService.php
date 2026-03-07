<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UserService
{
    public function createUser(array $data): User
    {
        Log::info('Creating user', ['username' => $data['username']]);

        $user = User::create([
            'first_name' => $data['first_name'],
            'last_name'  => $data['last_name'],
            'username'   => $data['username'],
            'password'   => Hash::make($data['password']),
        ]);

        Log::info('User created successfully', ['user_id' => $user->id, 'username' => $user->username]);

        return $user;
    }
}
