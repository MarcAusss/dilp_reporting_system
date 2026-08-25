<?php

namespace App\Enums;

enum PermissionName: string
{
    case DashboardView = 'dashboard.view';

    case UsersView = 'users.view';
    case UsersCreate = 'users.create';
    case UsersUpdate = 'users.update';
    case UsersDeactivate = 'users.deactivate';

    case MasterDataView = 'master-data.view';
    case MasterDataCreate = 'master-data.create';
    case MasterDataUpdate = 'master-data.update';
    case MasterDataToggle = 'master-data.toggle';

    case ProjectsView = 'projects.view';
    case ProjectsCreate = 'projects.create';
    case ProjectsUpdate = 'projects.update';
    case ProjectsArchive = 'projects.archive';

    case ProjectFinancialsView = 'project-financials.view';
    case ProjectFinancialsUpdate = 'project-financials.update';

    public function label(): string
    {
        return match ($this) {
            self::DashboardView => 'View Dashboard',

            self::UsersView => 'View Users',
            self::UsersCreate => 'Create Users',
            self::UsersUpdate => 'Update Users',
            self::UsersDeactivate => 'Deactivate Users',

            self::MasterDataView => 'View Master Data',
            self::MasterDataCreate => 'Create Master Data',
            self::MasterDataUpdate => 'Update Master Data',
            self::MasterDataToggle => 'Activate / Deactivate Master Data',

            self::ProjectsView => 'View Projects',
            self::ProjectsCreate => 'Create Projects',
            self::ProjectsUpdate => 'Update Projects',
            self::ProjectsArchive => 'Archive Projects',

            self::ProjectFinancialsView => 'View Project Financial Details',
            self::ProjectFinancialsUpdate => 'Update Project Financial Details',
        };
    }
}