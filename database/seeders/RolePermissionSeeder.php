<?php

namespace Database\Seeders;

use App\Enums\PermissionName;
use App\Enums\UserRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)
            ->forgetCachedPermissions();

        foreach (PermissionName::cases() as $permission) {
            Permission::findOrCreate(
                $permission->value,
                'web'
            );
        }

        foreach (UserRole::cases() as $role) {
            Role::findOrCreate(
                $role->value,
                'web'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Super Admin
        |--------------------------------------------------------------------------
        */

        $allPermissions = array_map(
            fn(PermissionName $permission): string => $permission->value,
            PermissionName::cases()
        );

        Role::findByName(
            UserRole::SuperAdmin->value,
            'web'
        )->syncPermissions($allPermissions);

        /*
        |--------------------------------------------------------------------------
        | Operational Accounts
        |--------------------------------------------------------------------------
        |
        | Workflow-specific create/update/approve permissions will be assigned
        | in later phases once the responsibilities of each role are defined.
        |
        */

        $operationalPermissions = [
            PermissionName::DashboardView->value,
            PermissionName::ProjectsView->value,
        ];

        $operationalRoles = [
            UserRole::GIP,
            UserRole::Focal,
            UserRole::DilpCoordinator,
        ];

        foreach ($operationalRoles as $role) {
            Role::findByName(
                $role->value,
                'web'
            )->syncPermissions(
                    $operationalPermissions
                );
        }

        app(PermissionRegistrar::class)
            ->forgetCachedPermissions();
    }
}