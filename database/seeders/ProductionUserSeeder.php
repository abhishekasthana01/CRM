<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Webkul\User\Models\User;
use Webkul\User\Models\Role;

class ProductionUserSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure admin role exists
        $role = Role::firstOrCreate(
            ['id' => 1],
            [
                'name'            => 'Administrator',
                'description'     => 'Administrator',
                'permission_type' => 'all',
            ]
        );

        // Upsert the production admin user
        User::updateOrCreate(
            ['email' => 'test@beamwallet.com'],
            [
                'name'     => 'Abhishek Asthana',
                // Hash of "test@beamwallet" — same as local
                'password' => '$2y$10$JxBHmV26KKLPzkWKWqZf8.9qBliKy8VjhWApUrqAM5J9AEcrscvi6',
                'role_id'  => $role->id,
                'status'   => 1,
            ]
        );
    }
}
