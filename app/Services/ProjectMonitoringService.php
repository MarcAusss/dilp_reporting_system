<?php

namespace App\Services;

use App\Enums\ComplianceReportStatus;
use App\Enums\MonitoringFindingStatus;
use App\Enums\ProjectDocumentStatus;
use App\Models\Project;

class ProjectMonitoringService
{
    public function summary(Project $project): array
    {
        $project->loadMissing([
            'documents',
            'monitoringVisits',
            'monitoringFindings',
            'complianceReports',
            'implementation',
        ]);

        $verifiedDocuments = $project->documents
            ->filter(fn ($record): bool => $record->status === ProjectDocumentStatus::Verified)
            ->count();

        $overdueDocuments = $project->documents
            ->filter(fn ($record): bool => $record->isOverdue())
            ->count();

        $openFindings = $project->monitoringFindings
            ->filter(fn ($record): bool => $record->status->isOpen())
            ->count();

        $overdueFindings = $project->monitoringFindings
            ->filter(fn ($record): bool => $record->isOverdue())
            ->count();

        $overdueCompliance = $project->complianceReports
            ->filter(fn ($record): bool => $record->isOverdue())
            ->count();

        $verifiedCompliance = $project->complianceReports
            ->filter(fn ($record): bool => $record->status === ComplianceReportStatus::Verified)
            ->count();

        return [
            'documents_total' => $project->documents->count(),
            'documents_verified' => $verifiedDocuments,
            'documents_overdue' => $overdueDocuments,
            'visits_total' => $project->monitoringVisits->count(),
            'open_findings' => $openFindings,
            'overdue_findings' => $overdueFindings,
            'compliance_total' => $project->complianceReports->count(),
            'compliance_verified' => $verifiedCompliance,
            'compliance_overdue' => $overdueCompliance,
            'accomplishment_percentage' => (int) ($project->implementation?->accomplishment_percentage ?? 0),
        ];
    }
}
