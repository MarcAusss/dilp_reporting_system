<?php

namespace App\Services;

use App\Enums\ComplianceReportStatus;
use App\Enums\ProjectDocumentStatus;
use App\Enums\ProjectWorkflowStage;
use App\Enums\ProjectWorkflowStatus;
use App\Models\Project;
use Illuminate\Database\Eloquent\Builder;

class DataQualityService
{
    public function checks(): array
    {
        return [
            'missing_location' => [
                'label' => 'Missing Primary Location',
                'severity' => 'danger',
                'description' => 'Projects without a primary province/location cannot be grouped reliably in geographic reports.',
                'query' => fn (): Builder => Project::query()->whereDoesntHave('primaryLocation'),
            ],
            'missing_fund' => [
                'label' => 'Missing Fund Source',
                'severity' => 'warning',
                'description' => 'Projects without a fund source will be excluded from fund-based reporting groups.',
                'query' => fn (): Builder => Project::query()->whereNull('fund_source_id'),
            ],
            'missing_office' => [
                'label' => 'Missing Office',
                'severity' => 'warning',
                'description' => 'Projects without an office cannot be attributed correctly in Per PO reports.',
                'query' => fn (): Builder => Project::query()->whereNull('office_id'),
            ],
            'missing_financials' => [
                'label' => 'Missing Financial Summary',
                'severity' => 'warning',
                'description' => 'Projects without financial details cannot contribute complete financial utilization figures.',
                'query' => fn (): Builder => Project::query()->whereDoesntHave('financial'),
            ],
            'missing_beneficiaries' => [
                'label' => 'No Beneficiary Records',
                'severity' => 'warning',
                'description' => 'Projects with no individual beneficiary records will show zero actual beneficiaries in reports.',
                'query' => fn (): Builder => Project::query()->whereDoesntHave('beneficiaries'),
            ],
            'approved_missing_implementation' => [
                'label' => 'Approved Without Implementation Record',
                'severity' => 'danger',
                'description' => 'Approved projects should have an implementation tracking record.',
                'query' => fn (): Builder => Project::query()
                    ->whereHas('workflowState', fn (Builder $query) => $query
                        ->where('stage', ProjectWorkflowStage::Completed->value)
                        ->where('status', ProjectWorkflowStatus::Completed->value))
                    ->whereDoesntHave('implementation'),
            ],
            'overdue_documents' => [
                'label' => 'Overdue Documentary Requirements',
                'severity' => 'danger',
                'description' => 'Projects have document requirements past due that are not submitted or verified.',
                'query' => fn (): Builder => Project::query()->whereHas('documents', fn (Builder $query) => $query
                    ->whereDate('due_date', '<', today())
                    ->whereNotIn('status', [ProjectDocumentStatus::Submitted->value, ProjectDocumentStatus::Verified->value])),
            ],
            'overdue_reports' => [
                'label' => 'Overdue Compliance Reports',
                'severity' => 'danger',
                'description' => 'Projects have required reports past due that have not been submitted, verified, or waived.',
                'query' => fn (): Builder => Project::query()->whereHas('complianceReports', fn (Builder $query) => $query
                    ->whereDate('due_date', '<', today())
                    ->whereNotIn('status', [
                        ComplianceReportStatus::Submitted->value,
                        ComplianceReportStatus::Verified->value,
                        ComplianceReportStatus::Waived->value,
                    ])),
            ],
        ];
    }

    public function summary(): array
    {
        $result = [];

        foreach ($this->checks() as $key => $check) {
            $result[$key] = [
                ...$check,
                'count' => $check['query']()->count(),
            ];
            unset($result[$key]['query']);
        }

        return $result;
    }

    public function projectsFor(string $checkKey, int $limit = 50)
    {
        $checks = $this->checks();
        abort_unless(isset($checks[$checkKey]), 404);

        return $checks[$checkKey]['query']()
            ->with(['proponent', 'office', 'fundSource'])
            ->latest('id')
            ->limit($limit)
            ->get();
    }
}
