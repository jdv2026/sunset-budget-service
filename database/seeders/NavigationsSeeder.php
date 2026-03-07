<?php

namespace Database\Seeders;

use App\Contracts\UserType;
use App\Models\Navigation;
use Illuminate\Database\Seeder;

class NavigationsSeeder extends Seeder {
    public function run(): void {
        Navigation::truncate();
        $now = now();
        $navs = [
            [
                'logo' => 'mat:insights',
                'name' => 'Analytics',
                'link' => '/dashboard/analytics',
                'header' => 'Dashboard',
            ],
            [
                'logo' => 'mat:group',
                'name' => 'Members',
                'link' => '/dashboard/members',
                'header' => 'Membership',
            ],
            [
                'logo' => 'mat:person',
                'name' => 'Admins',
                'link' => '/dashboard/admins/all',
                'header' => 'Membership',
            ],
			[
                'logo' => 'mat:person_add',
                'name' => 'Create User',
                'link' => '/dashboard/create/member',
                'header' => 'Administration',
            ],
            [
                'logo' => 'mat:list',
                'name' => 'Event Logs',
                'link' => '/dashboard/logs',
                'header' => 'Logs',
            ],
            [
                'logo' => 'mat:settings',
                'name' => 'Configuration',
                'link' => '/dashboard/configuration',
                'header' => 'Administration',
			],
        ];

        foreach ($navs as &$nav) {
            $nav['created_at'] = $now;
            $nav['updated_at'] = $now;
        }

        Navigation::insert($navs);
    }
}
