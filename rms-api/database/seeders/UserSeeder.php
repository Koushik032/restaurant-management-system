<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::query()
            ->where('name', 'admin')
            ->firstOrFail();

        $managerRole = Role::query()
            ->where('name', 'manager')
            ->firstOrFail();

        $chefRole = Role::query()
            ->where('name', 'chef')
            ->firstOrFail();

        User::query()->updateOrCreate(
            ['email' => 'admin@rms.test'],
            [
                'role_id' => $adminRole->id,
                'name' => 'System Admin',
                'username' => 'admin',
                'password' => 'Admin@12345',
                'is_active' => true,
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'manager@rms.test'],
            [
                'role_id' => $managerRole->id,
                'name' => 'Restaurant Manager',
                'username' => 'manager',
                'password' => 'Manager@12345',
                'is_active' => true,
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'chef@rms.test'],
            [
                'role_id' => $chefRole->id,
                'name' => 'Kitchen Chef',
                'username' => 'chef',
                'password' => 'Chef@12345',
                'is_active' => true,
            ]
        );
    }
}