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

    case ProjectBeneficiariesView = 'project-beneficiaries.view';
    case ProjectBeneficiariesUpdate = 'project-beneficiaries.update';

    case ProjectLivelihoodsView = 'project-livelihoods.view';
    case ProjectLivelihoodsUpdate = 'project-livelihoods.update';

    case ProjectBudgetItemsView = 'project-budget-items.view';
    case ProjectBudgetItemsUpdate = 'project-budget-items.update';

    case ProjectConvergenceView = 'project-convergence.view';
    case ProjectConvergenceUpdate = 'project-convergence.update';

    case ProjectWorkflowView = 'project-workflow.view';
    case ProjectWorkflowUpdate = 'project-workflow.update';
    case WorkQueuesView = 'work-queues.view';

    case ProjectProcessingView = 'project-processing.view';
    case ProjectProcessingUpdate = 'project-processing.update';

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

            self::ProjectBeneficiariesView => 'View Project Beneficiaries',
            self::ProjectBeneficiariesUpdate => 'Update Project Beneficiaries',

            self::ProjectLivelihoodsView => 'View Project Livelihood Details',
            self::ProjectLivelihoodsUpdate => 'Update Project Livelihood Details',

            self::ProjectBudgetItemsView => 'View Project Budget Items',
            self::ProjectBudgetItemsUpdate => 'Update Project Budget Items',

            self::ProjectConvergenceView => 'View Project Convergence Details',
            self::ProjectConvergenceUpdate => 'Update Project Convergence Details',

            self::ProjectWorkflowView => 'View Project Workflow',
            self::ProjectWorkflowUpdate => 'Update Project Workflow',
            self::WorkQueuesView => 'View Work Queues',

            self::ProjectProcessingView => 'View Project Financial & Implementation Processing',
            self::ProjectProcessingUpdate => 'Update Project Financial & Implementation Processing',
        };
    }
}