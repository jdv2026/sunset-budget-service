<?php

namespace Database\Seeders;

use App\Contracts\UserType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command->warn('AdminUserSeeder skipped in production.');
            return;
        }

        User::firstOrCreate(
            ['username' => 'admin'],
            [
                'first_name' => 'Admin',
                'last_name'  => 'User',
                'password'   => Hash::make('Admin@1234'),
                'type'       => UserType::Admin,
            ]
        );

        $this->command->info('Admin test user seeded: admin / Admin@1234');
    }
}