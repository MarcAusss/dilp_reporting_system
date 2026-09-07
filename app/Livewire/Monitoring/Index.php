<?php

namespace App\Livewire\Monitoring;

use App\Enums\ComplianceReportStatus;
use App\Enums\MonitoringFindingStatus;
use App\Enums\PermissionName;
use App\Enums\ProjectWorkflowStage;
use App\Enums\ProjectWorkflowStatus;
use App\Models\Project;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';
    public string $attention = 'all';

    public function mount(): void
    {
        Gate::authorize(PermissionName::ProjectMonitoringView->value);
    }

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedAttention(): void { $this->resetPage(); }

    public function render(): View
    {
        Gate::authorize(PermissionName::ProjectMonitoringView->value);

        $openStatuses = [
            MonitoringFindingStatus::Open->value,
            MonitoringFindingStatus::InProgress->value,
        ];

        $completeCompliance = [
            ComplianceReportStatus::Submitted->value,
            ComplianceReportStatus::Verified->value,
            ComplianceReportStatus::Waived->value,
        ];

        $approvedScope = fn (Builder $query) => $query
            ->where('stage', ProjectWorkflowStage::Completed->value)
            ->where('status', ProjectWorkflowStatus::Completed->value);

        $base = Project::query()
            ->whereHas('workflowState', $approvedScope)
            ->with(['proponent', 'office', 'implementation'])
            ->withCount('monitoringVisits')
            ->withCount([
                'monitoringFindings as open_findings_count' => fn (Builder $query) => $query->whereIn('status', $openStatuses),
                'complianceReports as overdue_reports_count' => fn (Builder $query) => $query
                    ->whereDate('due_date', '<', today())
                    ->whereNotIn('status', $completeCompliance),
            ])
            ->withMin('monitoringVisits as next_monitoring_date', 'next_monitoring_date');

        $summaryBase = Project::query()->whereHas('workflowState', $approvedScope);

        $summary = [
            'projects' => (clone $summaryBase)->count(),
            'with_open_findings' => (clone $summaryBase)->whereHas(
                'monitoringFindings',
                fn (Builder $query) => $query->whereIn('status', $openStatuses)
            )->count(),
            'with_overdue_reports' => (clone $summaryBase)->whereHas(
                'complianceReports',
                fn (Builder $query) => $query
                    ->whereDate('due_date', '<', today())
                    ->whereNotIn('status', $completeCompliance)
            )->count(),
            'due_for_monitoring' => (clone $summaryBase)->whereHas(
                'monitoringVisits',
                fn (Builder $query) => $query
                    ->whereNotNull('next_monitoring_date')
                    ->whereDate('next_monitoring_date', '<=', today()->addDays(30))
            )->count(),
        ];

        $projects = $base
            ->when(filled($this->search), function (Builder $query): void {
                $search = trim($this->search);
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('registry_number', 'like', "%{$search}%")
                        ->orWhere('project_code', 'like', "%{$search}%")
                        ->orWhere('title', 'like', "%{$search}%")
                        ->orWhereHas('proponent', fn (Builder $query) => $query->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($this->attention === 'findings', fn (Builder $query) => $query->whereHas(
                'monitoringFindings',
                fn (Builder $query) => $query->whereIn('status', $openStatuses)
            ))
            ->when($this->attention === 'overdue', fn (Builder $query) => $query->whereHas(
                'complianceReports',
                fn (Builder $query) => $query
                    ->whereDate('due_date', '<', today())
                    ->whereNotIn('status', $completeCompliance)
            ))
            ->when($this->attention === 'monitoring_due', fn (Builder $query) => $query->whereHas(
                'monitoringVisits',
                fn (Builder $query) => $query
                    ->whereNotNull('next_monitoring_date')
                    ->whereDate('next_monitoring_date', '<=', today()->addDays(30))
            ))
            ->latest('updated_at')
            ->paginate(15);

        return view('livewire.monitoring.index', [
            'projects' => $projects,
            'summary' => $summary,
        ]);
    }
}
