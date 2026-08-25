<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class SuperAdminUserSeeder extends Seeder
{
    /**
     * Seed the initial Super Admin account.
     */
    public function run(): void
    {
        $name = config('dilp.super_admin.name');
        $email = config('dilp.super_admin.email');
        $password = config('dilp.super_admin.password');

        if (blank($email)) {
            throw new RuntimeException(
                'DILP_SUPER_ADMIN_EMAIL is not configured in the .env file.'
            );
        }

        if (blank($password)) {
            throw new RuntimeException(
                'DILP_SUPER_ADMIN_PASSWORD is not configured in the .env file.'
            );
        }

        $user = User::firstOrCreate(
            [
                'email' => strtolower(trim($email)),
            ],
            [
                'name' => $name,
                'password' => $password,
                'is_active' => true,
            ]
        );

        $user->syncRoles([
            UserRole::SuperAdmin->value,
        ]);
    }
}