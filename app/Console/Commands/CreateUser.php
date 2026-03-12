<?php

namespace App\Console\Commands;

use App\Contracts\UserType;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateUser extends Command
{
    protected $signature = 'user:create';

    protected $description = 'Create a new user interactively';

    public function handle(): int
    {
        $this->info('Create a new user');
        $this->newLine();

        $username = $this->ask('Username');

        if (User::where('username', $username)->exists()) {
            $this->error("Username '{$username}' is already taken.");
            return self::FAILURE;
        }

        $password = $this->secret('Password');

        if (strlen($password) < 8) {
            $this->error('Password must be at least 8 characters.');
            return self::FAILURE;
        }

        $types    = UserType::values();
        $type     = $this->choice('Type', $types, 'User');

        User::create([
            'username' => $username,
            'password' => Hash::make($password),
            'type'     => $type,
        ]);

        $this->newLine();
        $this->info("✓ User '{$username}' created as {$type}.");

        return self::SUCCESS;
    }
}
