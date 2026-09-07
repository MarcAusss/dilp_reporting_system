<?php

namespace App\Services\Reports;

use App\Enums\ComplianceReportStatus;
use App\Enums\DisbursementStatus;
use App\Enums\MonitoringFindingStatus;
use App\Enums\ObligationStatus;
use App\Enums\ProjectDocumentStatus;
use App\Enums\ProjectWorkflowStage;
use App\Enums\ProjectWorkflowStatus;
use App\Enums\ReportType;
use App\Models\FundSource;
use App\Models\Office;
use App\Models\Project;
use App\Models\Province;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class DilpReportService
{
    public function filterOptions(): array
    {
        $years = Project::query()
            ->select('fiscal_year')
            ->distinct()
            ->orderByDesc('fiscal_year')
            ->pluck('fiscal_year')
            ->map(fn ($year) => (int) $year)
            ->values();

        if ($years->isEmpty()) {
            $years = collect([(int) now()->year]);
        }

        return [
            'years' => $years,
            'offices' => Office::query()->ordered()->get(['id', 'name']),
            'fundSources' => FundSource::query()->ordered()->get(['id', 'name']),
            'provinces' => Province::query()->ordered()->get(['id', 'name']),
            'quarters' => collect([
                1 => '1st Quarter',
                2 => '2nd Quarter',
                3 => '3rd Quarter',
                4 => '4th Quarter',
            ]),
        ];
    }

    public function normalizedFilters(array $input): array
    {
        return [
            'fiscal_year' => filled($input['fiscal_year'] ?? null)
                ? (int) $input['fiscal_year']
                : (int) now()->year,
            'quarter' => filled($input['quarter'] ?? null)
                ? max(1, min(4, (int) $input['quarter']))
                : null,
            'office_id' => filled($input['office_id'] ?? null)
                ? (int) $input['office_id']
                : null,
            'fund_source_id' => filled($input['fund_source_id'] ?? null)
                ? (int) $input['fund_source_id']
                : null,
            'province_id' => filled($input['province_id'] ?? null)
                ? (int) $input['province_id']
                : null,
        ];
    }

    public function build(ReportType $type, array $filters): array
    {
        $filters = $this->normalizedFilters($filters);
        $projects = $this->projectCollection($filters);

        $payload = match ($type) {
            ReportType::Summary => $this->summaryReport($projects),
            ReportType::PerFund => $this->perFundReport($projects),
            ReportType::PerPo => $this->perPoReport($projects),
            ReportType::OnProcessRo => $this->onProcessReport($projects),
            ReportType::Sprs => $this->sprsReport($projects),
            ReportType::Cqpr => $this->cqprReport($projects),
            ReportType::Pcl => $this->pclReport($projects),
            ReportType::Raod => $this->raodReport($projects),
        };

        return [
            'type' => $type,
            'title' => $type->label(),
            'description' => $type->description(),
            'filters' => $filters,
            'filterLabels' => $this->filterLabels($filters),
            'generatedAt' => now(),
            ...$payload,
        ];
    }

    private function projectCollection(array $filters): Collection
    {
        return $this->baseQuery($filters)
            ->with([
                'proponent',
                'office',
                'fundSource',
                'projectType',
                'projectPurpose',
                'implementationMode',
                'primaryLocation.province',
                'primaryLocation.municipality',
                'financial',
                'workflowState',
                'implementation',
                'obligations',
                'disbursements',
                'documents',
                'complianceReports',
                'monitoringFindings',
            ])
            ->withCount([
                'beneficiaries as active_beneficiaries_count' => fn (Builder $query) => $query->where('is_active', true),
                'monitoringVisits',
            ])
            ->orderBy('registry_number')
            ->get();
    }

    private function baseQuery(array $filters): Builder
    {
        $query = Project::query()
            ->where('fiscal_year', $filters['fiscal_year']);

        if ($filters['office_id']) {
            $query->where('office_id', $filters['office_id']);
        }

        if ($filters['fund_source_id']) {
            $query->where('fund_source_id', $filters['fund_source_id']);
        }

        if ($filters['province_id']) {
            $query->whereHas('primaryLocation', fn (Builder $query) => $query
                ->where('province_id', $filters['province_id']));
        }

        if ($filters['quarter']) {
            [$start, $end] = $this->quarterRange(
                $filters['fiscal_year'],
                $filters['quarter']
            );

            $query->where(function (Builder $query) use ($start, $end): void {
                $query->whereBetween('date_received', [$start, $end])
                    ->orWhere(function (Builder $query) use ($start, $end): void {
                        $query->whereNull('date_received')
                            ->whereBetween('created_at', [
                                $start->copy()->startOfDay(),
                                $end->copy()->endOfDay(),
                            ]);
                    });
            });
        }

        return $query;
    }

    private function summaryReport(Collection $projects): array
    {
        $rows = $projects->map(fn (Project $project) => [
            'registry' => $project->registry_number ?? '—',
            'project' => $project->title,
            'proponent' => $project->proponent?->name ?? '—',
            'office' => $project->office?->name ?? '—',
            'location' => $this->location($project),
            'fund' => $project->fundSource?->name ?? '—',
            'beneficiaries' => (int) $project->active_beneficiaries_count,
            'dole_share' => $this->doleShare($project),
            'obligated' => $this->obligated($project),
            'disbursed' => $this->disbursed($project),
            'workflow' => $project->workflowState?->stage?->label() ?? 'Not Started',
            'implementation' => $project->implementation?->status?->label() ?? 'Not Started',
            'accomplishment' => (int) ($project->implementation?->accomplishment_percentage ?? 0),
        ])->values();

        return [
            'columns' => [
                ['key' => 'registry', 'label' => 'Registry No.'],
                ['key' => 'project', 'label' => 'Project'],
                ['key' => 'proponent', 'label' => 'Proponent'],
                ['key' => 'office', 'label' => 'PO / Office'],
                ['key' => 'location', 'label' => 'Location'],
                ['key' => 'fund', 'label' => 'Fund Source'],
                ['key' => 'beneficiaries', 'label' => 'Beneficiaries', 'type' => 'integer'],
                ['key' => 'dole_share', 'label' => 'DOLE Share', 'type' => 'money'],
                ['key' => 'obligated', 'label' => 'Obligated', 'type' => 'money'],
                ['key' => 'disbursed', 'label' => 'Disbursed', 'type' => 'money'],
                ['key' => 'workflow', 'label' => 'Workflow'],
                ['key' => 'implementation', 'label' => 'Implementation'],
                ['key' => 'accomplishment', 'label' => 'Accomplishment', 'type' => 'percent'],
            ],
            'rows' => $rows,
            'summary' => $this->projectSummary($projects),
        ];
    }

    private function perFundReport(Collection $projects): array
    {
        $rows = $projects
            ->groupBy(fn (Project $project) => $project->fundSource?->name ?? 'Unspecified Fund Source')
            ->map(function (Collection $group, string $fund): array {
                $dole = $group->sum(fn (Project $project) => $this->doleShare($project));
                $obligated = $group->sum(fn (Project $project) => $this->obligated($project));
                $disbursed = $group->sum(fn (Project $project) => $this->disbursed($project));

                return [
                    'fund' => $fund,
                    'projects' => $group->count(),
                    'beneficiaries' => $group->sum('active_beneficiaries_count'),
                    'dole_share' => round($dole, 2),
                    'obligated' => round($obligated, 2),
                    'disbursed' => round($disbursed, 2),
                    'balance' => round(max(0, $dole - $disbursed), 2),
                    'utilization' => $dole > 0 ? round(($disbursed / $dole) * 100, 2) : 0,
                ];
            })
            ->sortBy('fund')
            ->values();

        return [
            'columns' => [
                ['key' => 'fund', 'label' => 'Fund Source'],
                ['key' => 'projects', 'label' => 'Projects', 'type' => 'integer'],
                ['key' => 'beneficiaries', 'label' => 'Beneficiaries', 'type' => 'integer'],
                ['key' => 'dole_share', 'label' => 'DOLE Share', 'type' => 'money'],
                ['key' => 'obligated', 'label' => 'Obligated', 'type' => 'money'],
                ['key' => 'disbursed', 'label' => 'Disbursed', 'type' => 'money'],
                ['key' => 'balance', 'label' => 'Balance', 'type' => 'money'],
                ['key' => 'utilization', 'label' => 'Utilization', 'type' => 'percent'],
            ],
            'rows' => $rows,
            'summary' => $this->projectSummary($projects),
        ];
    }

    private function perPoReport(Collection $projects): array
    {
        $rows = $projects
            ->groupBy(fn (Project $project) => $project->office?->name ?? 'Unassigned Office')
            ->map(function (Collection $group, string $office): array {
                $dole = $group->sum(fn (Project $project) => $this->doleShare($project));
                $disbursed = $group->sum(fn (Project $project) => $this->disbursed($project));
                $approved = $group->filter(fn (Project $project) => $this->isApproved($project))->count();
                $average = $group->avg(fn (Project $project) => (int) ($project->implementation?->accomplishment_percentage ?? 0));

                return [
                    'office' => $office,
                    'projects' => $group->count(),
                    'approved' => $approved,
                    'beneficiaries' => $group->sum('active_beneficiaries_count'),
                    'dole_share' => round($dole, 2),
                    'disbursed' => round($disbursed, 2),
                    'utilization' => $dole > 0 ? round(($disbursed / $dole) * 100, 2) : 0,
                    'accomplishment' => round((float) ($average ?? 0), 2),
                ];
            })
            ->sortBy('office')
            ->values();

        return [
            'columns' => [
                ['key' => 'office', 'label' => 'Provincial / Field Office'],
                ['key' => 'projects', 'label' => 'Projects', 'type' => 'integer'],
                ['key' => 'approved', 'label' => 'Approved', 'type' => 'integer'],
                ['key' => 'beneficiaries', 'label' => 'Beneficiaries', 'type' => 'integer'],
                ['key' => 'dole_share', 'label' => 'DOLE Share', 'type' => 'money'],
                ['key' => 'disbursed', 'label' => 'Disbursed', 'type' => 'money'],
                ['key' => 'utilization', 'label' => 'Utilization', 'type' => 'percent'],
                ['key' => 'accomplishment', 'label' => 'Avg. Accomplishment', 'type' => 'percent'],
            ],
            'rows' => $rows,
            'summary' => $this->projectSummary($projects),
        ];
    }

    private function onProcessReport(Collection $projects): array
    {
        $projects = $projects->filter(function (Project $project): bool {
            $state = $project->workflowState;

            return $state
                && ! in_array($state->status, [
                    ProjectWorkflowStatus::Completed,
                    ProjectWorkflowStatus::Rejected,
                ], true);
        })->values();

        $rows = $projects->map(fn (Project $project) => [
            'registry' => $project->registry_number ?? '—',
            'date_received' => $project->date_received?->format('M d, Y') ?? '—',
            'project' => $project->title,
            'proponent' => $project->proponent?->name ?? '—',
            'office' => $project->office?->name ?? '—',
            'fund' => $project->fundSource?->name ?? '—',
            'stage' => $project->workflowState?->stage?->label() ?? '—',
            'status' => $project->workflowState?->status?->label() ?? '—',
            'assigned' => $this->roleLabel($project->workflowState?->assigned_role),
            'last_action' => $project->workflowState?->last_action_at?->format('M d, Y h:i A') ?? '—',
        ]);

        return [
            'columns' => [
                ['key' => 'registry', 'label' => 'Registry No.'],
                ['key' => 'date_received', 'label' => 'Date Received'],
                ['key' => 'project', 'label' => 'Project'],
                ['key' => 'proponent', 'label' => 'Proponent'],
                ['key' => 'office', 'label' => 'PO / Office'],
                ['key' => 'fund', 'label' => 'Fund Source'],
                ['key' => 'stage', 'label' => 'Current Stage'],
                ['key' => 'status', 'label' => 'Status'],
                ['key' => 'assigned', 'label' => 'Assigned To'],
                ['key' => 'last_action', 'label' => 'Last Action'],
            ],
            'rows' => $rows,
            'summary' => [
                ['label' => 'Projects on Process', 'value' => $projects->count(), 'type' => 'integer'],
                ['label' => 'Evaluation / Endorsement', 'value' => $projects->filter(fn (Project $p) => in_array($p->workflowState?->stage, [ProjectWorkflowStage::Evaluation, ProjectWorkflowStage::Endorsement], true))->count(), 'type' => 'integer'],
                ['label' => 'Validation', 'value' => $projects->filter(fn (Project $p) => $p->workflowState?->stage === ProjectWorkflowStage::Validation)->count(), 'type' => 'integer'],
                ['label' => 'Approval', 'value' => $projects->filter(fn (Project $p) => $p->workflowState?->stage === ProjectWorkflowStage::Approval)->count(), 'type' => 'integer'],
            ],
        ];
    }

    private function sprsReport(Collection $projects): array
    {
        $rows = $projects->map(fn (Project $project) => [
            'registry' => $project->registry_number ?? '—',
            'project' => $project->title,
            'office' => $project->office?->name ?? '—',
            'location' => $this->location($project),
            'project_type' => $project->projectType?->name ?? '—',
            'beneficiaries' => (int) $project->active_beneficiaries_count,
            'workflow' => $project->workflowState?->stage?->label() ?? '—',
            'workflow_status' => $project->workflowState?->status?->label() ?? '—',
            'implementation' => $project->implementation?->status?->label() ?? 'Not Started',
            'accomplishment' => (int) ($project->implementation?->accomplishment_percentage ?? 0),
            'start_date' => $project->implementation?->start_date?->format('M d, Y') ?? '—',
            'target_completion' => $project->implementation?->target_completion_date?->format('M d, Y') ?? '—',
        ]);

        return [
            'columns' => [
                ['key' => 'registry', 'label' => 'Registry No.'],
                ['key' => 'project', 'label' => 'Project'],
                ['key' => 'office', 'label' => 'PO / Office'],
                ['key' => 'location', 'label' => 'Location'],
                ['key' => 'project_type', 'label' => 'Project Type'],
                ['key' => 'beneficiaries', 'label' => 'Beneficiaries', 'type' => 'integer'],
                ['key' => 'workflow', 'label' => 'Workflow'],
                ['key' => 'workflow_status', 'label' => 'Workflow Status'],
                ['key' => 'implementation', 'label' => 'Implementation'],
                ['key' => 'accomplishment', 'label' => 'Accomplishment', 'type' => 'percent'],
                ['key' => 'start_date', 'label' => 'Start Date'],
                ['key' => 'target_completion', 'label' => 'Target Completion'],
            ],
            'rows' => $rows,
            'summary' => $this->projectSummary($projects),
        ];
    }

    private function cqprReport(Collection $projects): array
    {
        $rows = $projects
            ->groupBy(fn (Project $project) => $project->office?->name ?? 'Unassigned Office')
            ->map(function (Collection $group, string $office): array {
                $dole = $group->sum(fn (Project $project) => $this->doleShare($project));
                $obligated = $group->sum(fn (Project $project) => $this->obligated($project));
                $disbursed = $group->sum(fn (Project $project) => $this->disbursed($project));
                $completed = $group->filter(fn (Project $project) => $project->implementation?->status?->value === 'completed')->count();
                $average = $group->avg(fn (Project $project) => (int) ($project->implementation?->accomplishment_percentage ?? 0));

                return [
                    'office' => $office,
                    'projects' => $group->count(),
                    'completed' => $completed,
                    'beneficiaries' => $group->sum('active_beneficiaries_count'),
                    'dole_share' => round($dole, 2),
                    'obligated' => round($obligated, 2),
                    'disbursed' => round($disbursed, 2),
                    'utilization' => $dole > 0 ? round(($disbursed / $dole) * 100, 2) : 0,
                    'accomplishment' => round((float) ($average ?? 0), 2),
                ];
            })
            ->sortBy('office')
            ->values();

        return [
            'columns' => [
                ['key' => 'office', 'label' => 'Provincial / Field Office'],
                ['key' => 'projects', 'label' => 'Projects', 'type' => 'integer'],
                ['key' => 'completed', 'label' => 'Completed', 'type' => 'integer'],
                ['key' => 'beneficiaries', 'label' => 'Beneficiaries', 'type' => 'integer'],
                ['key' => 'dole_share', 'label' => 'DOLE Share', 'type' => 'money'],
                ['key' => 'obligated', 'label' => 'Obligated', 'type' => 'money'],
                ['key' => 'disbursed', 'label' => 'Disbursed', 'type' => 'money'],
                ['key' => 'utilization', 'label' => 'Utilization', 'type' => 'percent'],
                ['key' => 'accomplishment', 'label' => 'Avg. Accomplishment', 'type' => 'percent'],
            ],
            'rows' => $rows,
            'summary' => $this->projectSummary($projects),
        ];
    }

    private function pclReport(Collection $projects): array
    {
        $rows = $projects->map(function (Project $project): array {
            $documents = $project->documents;
            $verifiedDocuments = $documents->filter(fn ($document) => $document->status === ProjectDocumentStatus::Verified)->count();
            $overdueDocuments = $documents->filter(fn ($document) => $document->isOverdue())->count();
            $overdueReports = $project->complianceReports->filter(fn ($report) => $report->isOverdue())->count();
            $openFindings = $project->monitoringFindings->filter(fn ($finding) => in_array($finding->status, [MonitoringFindingStatus::Open, MonitoringFindingStatus::InProgress], true))->count();
            $submittedReports = $project->complianceReports->filter(fn ($report) => in_array($report->status, [ComplianceReportStatus::Submitted, ComplianceReportStatus::Verified, ComplianceReportStatus::Waived], true))->count();

            return [
                'registry' => $project->registry_number ?? '—',
                'project' => $project->title,
                'office' => $project->office?->name ?? '—',
                'documents' => $documents->count(),
                'verified_documents' => $verifiedDocuments,
                'overdue_documents' => $overdueDocuments,
                'reports' => $project->complianceReports->count(),
                'submitted_reports' => $submittedReports,
                'overdue_reports' => $overdueReports,
                'open_findings' => $openFindings,
                'compliance' => ($overdueDocuments + $overdueReports + $openFindings) === 0 ? 'Compliant' : 'Needs Attention',
            ];
        });

        $attention = $rows->where('compliance', 'Needs Attention')->count();

        return [
            'columns' => [
                ['key' => 'registry', 'label' => 'Registry No.'],
                ['key' => 'project', 'label' => 'Project'],
                ['key' => 'office', 'label' => 'PO / Office'],
                ['key' => 'documents', 'label' => 'Requirements', 'type' => 'integer'],
                ['key' => 'verified_documents', 'label' => 'Verified Docs', 'type' => 'integer'],
                ['key' => 'overdue_documents', 'label' => 'Overdue Docs', 'type' => 'integer'],
                ['key' => 'reports', 'label' => 'Reports', 'type' => 'integer'],
                ['key' => 'submitted_reports', 'label' => 'Submitted / Verified', 'type' => 'integer'],
                ['key' => 'overdue_reports', 'label' => 'Overdue Reports', 'type' => 'integer'],
                ['key' => 'open_findings', 'label' => 'Open Findings', 'type' => 'integer'],
                ['key' => 'compliance', 'label' => 'Compliance'],
            ],
            'rows' => $rows,
            'summary' => [
                ['label' => 'Projects', 'value' => $projects->count(), 'type' => 'integer'],
                ['label' => 'Compliant', 'value' => $projects->count() - $attention, 'type' => 'integer'],
                ['label' => 'Needs Attention', 'value' => $attention, 'type' => 'integer'],
                ['label' => 'Open Findings', 'value' => $rows->sum('open_findings'), 'type' => 'integer'],
            ],
        ];
    }

    private function raodReport(Collection $projects): array
    {
        $rows = $projects->map(function (Project $project): array {
            $dole = $this->doleShare($project);
            $obligated = $this->obligated($project);
            $disbursed = $this->disbursed($project);

            return [
                'registry' => $project->registry_number ?? '—',
                'office' => $project->office?->name ?? '—',
                'fund' => $project->fundSource?->name ?? '—',
                'project' => $project->title,
                'proponent' => $project->proponent?->name ?? '—',
                'dole_share' => $dole,
                'obligated' => $obligated,
                'unobligated' => round(max(0, $dole - $obligated), 2),
                'disbursed' => $disbursed,
                'undisbursed' => round(max(0, $obligated - $disbursed), 2),
                'balance' => round(max(0, $dole - $disbursed), 2),
                'utilization' => $dole > 0 ? round(($disbursed / $dole) * 100, 2) : 0,
            ];
        });

        $dole = $rows->sum('dole_share');
        $obligated = $rows->sum('obligated');
        $disbursed = $rows->sum('disbursed');

        return [
            'columns' => [
                ['key' => 'registry', 'label' => 'Registry No.'],
                ['key' => 'office', 'label' => 'PO / Office'],
                ['key' => 'fund', 'label' => 'Fund Source'],
                ['key' => 'project', 'label' => 'Project'],
                ['key' => 'proponent', 'label' => 'Proponent'],
                ['key' => 'dole_share', 'label' => 'DOLE Share', 'type' => 'money'],
                ['key' => 'obligated', 'label' => 'Obligated', 'type' => 'money'],
                ['key' => 'unobligated', 'label' => 'Unobligated', 'type' => 'money'],
                ['key' => 'disbursed', 'label' => 'Disbursed', 'type' => 'money'],
                ['key' => 'undisbursed', 'label' => 'Undisbursed', 'type' => 'money'],
                ['key' => 'balance', 'label' => 'Balance', 'type' => 'money'],
                ['key' => 'utilization', 'label' => 'Utilization', 'type' => 'percent'],
            ],
            'rows' => $rows,
            'summary' => [
                ['label' => 'DOLE Share', 'value' => round($dole, 2), 'type' => 'money'],
                ['label' => 'Obligated', 'value' => round($obligated, 2), 'type' => 'money'],
                ['label' => 'Disbursed', 'value' => round($disbursed, 2), 'type' => 'money'],
                ['label' => 'Utilization', 'value' => $dole > 0 ? round(($disbursed / $dole) * 100, 2) : 0, 'type' => 'percent'],
            ],
        ];
    }

    private function projectSummary(Collection $projects): array
    {
        $dole = $projects->sum(fn (Project $project) => $this->doleShare($project));
        $disbursed = $projects->sum(fn (Project $project) => $this->disbursed($project));

        return [
            ['label' => 'Projects', 'value' => $projects->count(), 'type' => 'integer'],
            ['label' => 'Beneficiaries', 'value' => $projects->sum('active_beneficiaries_count'), 'type' => 'integer'],
            ['label' => 'DOLE Share', 'value' => round($dole, 2), 'type' => 'money'],
            ['label' => 'Utilization', 'value' => $dole > 0 ? round(($disbursed / $dole) * 100, 2) : 0, 'type' => 'percent'],
        ];
    }

    private function doleShare(Project $project): float
    {
        return round($project->financial?->doleShare() ?? 0, 2);
    }

    private function obligated(Project $project): float
    {
        return round($project->obligations
            ->filter(fn ($record) => $record->status === ObligationStatus::Obligated)
            ->sum(fn ($record) => (float) $record->amount), 2);
    }

    private function disbursed(Project $project): float
    {
        return round($project->disbursements
            ->filter(fn ($record) => $record->status === DisbursementStatus::Paid)
            ->sum(fn ($record) => (float) $record->amount), 2);
    }

    private function isApproved(Project $project): bool
    {
        return $project->workflowState?->stage === ProjectWorkflowStage::Completed
            && $project->workflowState?->status === ProjectWorkflowStatus::Completed;
    }

    private function location(Project $project): string
    {
        return collect([
            $project->primaryLocation?->municipality?->name,
            $project->primaryLocation?->province?->name,
        ])->filter()->implode(', ') ?: '—';
    }

    private function roleLabel(?string $role): string
    {
        if (blank($role)) {
            return '—';
        }

        return Str::of($role)->replace('_', ' ')->title()->toString();
    }

    private function filterLabels(array $filters): array
    {
        $labels = ['Fiscal Year' => (string) $filters['fiscal_year']];

        if ($filters['quarter']) {
            $labels['Quarter'] = match ($filters['quarter']) {
                1 => '1st Quarter',
                2 => '2nd Quarter',
                3 => '3rd Quarter',
                4 => '4th Quarter',
            };
        }

        if ($filters['office_id']) {
            $labels['Office'] = Office::query()->find($filters['office_id'])?->name ?? 'Unknown';
        }

        if ($filters['fund_source_id']) {
            $labels['Fund Source'] = FundSource::query()->find($filters['fund_source_id'])?->name ?? 'Unknown';
        }

        if ($filters['province_id']) {
            $labels['Province'] = Province::query()->find($filters['province_id'])?->name ?? 'Unknown';
        }

        return $labels;
    }

    private function quarterRange(int $year, int $quarter): array
    {
        $month = (($quarter - 1) * 3) + 1;
        $start = now()->setDate($year, $month, 1)->startOfDay();
        $end = $start->copy()->addMonths(3)->subDay()->endOfDay();

        return [$start, $end];
    }
}
