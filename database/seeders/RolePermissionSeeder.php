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
            fn(PermissionName $permission): string =>
            $permission->value,
            PermissionName::cases()
        );

        Role::findByName(
            UserRole::SuperAdmin->value,
            'web'
        )->syncPermissions($allPermissions);

        /*
        |--------------------------------------------------------------------------
        | Operational Roles
        |--------------------------------------------------------------------------
        */

        $operationalReadPermissions = [
            PermissionName::DashboardView->value,
            PermissionName::ProjectsView->value,
            PermissionName::ProjectFinancialsView->value,
            PermissionName::ProjectBeneficiariesView->value,
            PermissionName::ProjectLivelihoodsView->value,
            PermissionName::ProjectBudgetItemsView->value,
            PermissionName::ProjectConvergenceView->value,
            PermissionName::ProjectWorkflowView->value,
            PermissionName::ProjectProcessingView->value,
            PermissionName::ProjectDocumentsView->value,
            PermissionName::ProjectMonitoringView->value,
            PermissionName::ReportsView->value,
            PermissionName::ReportsExport->value,
            PermissionName::FundTargetsView->value,
            PermissionName::BeneficiariesView->value,
        ];

        Role::findByName(
            UserRole::GIP->value,
            'web'
        )->syncPermissions($operationalReadPermissions);

        foreach ([
            UserRole::Focal,
            UserRole::DilpCoordinator,
        ] as $role) {
            Role::findByName(
                $role->value,
                'web'
            )->syncPermissions([
                ...$operationalReadPermissions,
                PermissionName::ProjectWorkflowUpdate->value,
                PermissionName::WorkQueuesView->value,
                PermissionName::ProjectProcessingUpdate->value,
                PermissionName::ProjectDocumentsUpdate->value,
                PermissionName::ProjectMonitoringUpdate->value,
            ]);
        }

        app(PermissionRegistrar::class)
            ->forgetCachedPermissions();
    }
}