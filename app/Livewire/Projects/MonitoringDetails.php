<?php

namespace App\Livewire\Projects;

use App\Enums\ComplianceReportStatus;
use App\Enums\MonitoringFindingStatus;
use App\Enums\MonitoringVisitStatus;
use App\Enums\MonitoringVisitType;
use App\Enums\PermissionName;
use App\Models\Project;
use App\Models\ProjectComplianceReport;
use App\Models\ProjectMonitoringFinding;
use App\Models\ProjectMonitoringFollowUp;
use App\Models\ProjectMonitoringVisit;
use App\Services\ProjectMonitoringService;
use App\Services\ProjectProcessingService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class MonitoringDetails extends Component
{
    public int $projectId;

    public bool $showVisitForm = false;
    public ?int $editingVisitId = null;
    public string $visit_type = 'regular';
    public string $visit_date = '';
    public string $visit_status = 'scheduled';
    public string $conducted_by_name = '';
    public string $visit_location = '';
    public string $visit_accomplishment = '0';
    public string $observations = '';
    public string $recommendations = '';
    public string $next_monitoring_date = '';

    public bool $showFindingForm = false;
    public ?int $editingFindingId = null;
    public string $finding_visit_id = '';
    public string $finding_text = '';
    public string $corrective_action = '';
    public string $finding_due_date = '';
    public string $finding_status = 'open';
    public string $resolution_notes = '';

    public bool $showFollowUpForm = false;
    public ?int $followUpFindingId = null;
    public string $follow_up_date = '';
    public string $follow_up_notes = '';
    public string $next_follow_up_date = '';
    public string $follow_up_status = '';

    public bool $showComplianceForm = false;
    public ?int $editingComplianceId = null;
    public string $report_type = '';
    public string $period_label = '';
    public string $period_start = '';
    public string $period_end = '';
    public string $compliance_due_date = '';
    public string $compliance_status = 'pending';
    public string $compliance_accomplishment = '';
    public string $linked_document_id = '';
    public string $compliance_remarks = '';

    public function mount(int $projectId): void
    {
        Gate::authorize(PermissionName::ProjectMonitoringView->value);
        Project::query()->findOrFail($projectId);
        $this->projectId = $projectId;
    }

    public function startVisit(): void
    {
        $this->authorizeUpdate();
        $this->resetVisitForm();
        $this->visit_date = now()->format('Y-m-d');
        $this->showVisitForm = true;
    }

    public function editVisit(int $id): void
    {
        $this->authorizeUpdate();
        $record = ProjectMonitoringVisit::query()->where('project_id', $this->projectId)->findOrFail($id);

        $this->editingVisitId = $record->id;
        $this->visit_type = $record->visit_type->value;
        $this->visit_date = $record->visit_date->format('Y-m-d');
        $this->visit_status = $record->status->value;
        $this->conducted_by_name = $record->conducted_by_name ?? '';
        $this->visit_location = $record->location ?? '';
        $this->visit_accomplishment = (string) $record->accomplishment_percentage;
        $this->observations = $record->observations ?? '';
        $this->recommendations = $record->recommendations ?? '';
        $this->next_monitoring_date = $record->next_monitoring_date?->format('Y-m-d') ?? '';
        $this->showVisitForm = true;
    }

    public function saveVisit(): void
    {
        $this->authorizeUpdate();

        $validated = $this->validate([
            'visit_type' => ['required', Rule::enum(MonitoringVisitType::class)],
            'visit_date' => ['required', 'date'],
            'visit_status' => ['required', Rule::enum(MonitoringVisitStatus::class)],
            'conducted_by_name' => ['nullable', 'string', 'max:255'],
            'visit_location' => ['nullable', 'string', 'max:255'],
            'visit_accomplishment' => ['required', 'integer', 'min:0', 'max:100'],
            'observations' => ['nullable', 'string', 'max:5000'],
            'recommendations' => ['nullable', 'string', 'max:5000'],
            'next_monitoring_date' => ['nullable', 'date', 'after_or_equal:visit_date'],
        ]);

        $record = $this->editingVisitId
            ? ProjectMonitoringVisit::query()->where('project_id', $this->projectId)->findOrFail($this->editingVisitId)
            : new ProjectMonitoringVisit(['project_id' => $this->projectId, 'created_by' => auth()->id()]);

        $record->fill([
            'visit_type' => $validated['visit_type'],
            'visit_date' => $validated['visit_date'],
            'status' => $validated['visit_status'],
            'conducted_by_name' => $this->nullableTrim($validated['conducted_by_name'] ?? null),
            'location' => $this->nullableTrim($validated['visit_location'] ?? null),
            'accomplishment_percentage' => $validated['visit_accomplishment'],
            'observations' => $this->nullableTrim($validated['observations'] ?? null),
            'recommendations' => $this->nullableTrim($validated['recommendations'] ?? null),
            'next_monitoring_date' => $validated['next_monitoring_date'] ?: null,
            'updated_by' => auth()->id(),
        ])->save();

        $this->resetVisitForm();
        session()->flash('monitoring-status', 'Monitoring visit saved successfully.');
    }

    public function cancelVisit(): void { $this->resetVisitForm(); }

    public function startFinding(?int $visitId = null): void
    {
        $this->authorizeUpdate();
        $this->resetFindingForm();
        $this->finding_visit_id = $visitId ? (string) $visitId : '';
        $this->showFindingForm = true;
    }

    public function editFinding(int $id): void
    {
        $this->authorizeUpdate();
        $record = ProjectMonitoringFinding::query()->where('project_id', $this->projectId)->findOrFail($id);

        $this->editingFindingId = $record->id;
        $this->finding_visit_id = $record->monitoring_visit_id ? (string) $record->monitoring_visit_id : '';
        $this->finding_text = $record->finding;
        $this->corrective_action = $record->corrective_action ?? '';
        $this->finding_due_date = $record->due_date?->format('Y-m-d') ?? '';
        $this->finding_status = $record->status->value;
        $this->resolution_notes = $record->resolution_notes ?? '';
        $this->showFindingForm = true;
    }

    public function saveFinding(): void
    {
        $this->authorizeUpdate();

        $validated = $this->validate([
            'finding_visit_id' => ['nullable', 'integer'],
            'finding_text' => ['required', 'string', 'max:5000'],
            'corrective_action' => ['nullable', 'string', 'max:5000'],
            'finding_due_date' => ['nullable', 'date'],
            'finding_status' => ['required', Rule::enum(MonitoringFindingStatus::class)],
            'resolution_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $visitId = filled($validated['finding_visit_id'] ?? null)
            ? (int) $validated['finding_visit_id']
            : null;

        if ($visitId) {
            ProjectMonitoringVisit::query()->where('project_id', $this->projectId)->findOrFail($visitId);
        }

        $status = MonitoringFindingStatus::from($validated['finding_status']);
        $record = $this->editingFindingId
            ? ProjectMonitoringFinding::query()->where('project_id', $this->projectId)->findOrFail($this->editingFindingId)
            : new ProjectMonitoringFinding(['project_id' => $this->projectId, 'created_by' => auth()->id()]);

        if (in_array($status, [MonitoringFindingStatus::Resolved, MonitoringFindingStatus::Closed], true) && blank($validated['resolution_notes'] ?? null)) {
            throw ValidationException::withMessages([
                'resolution_notes' => 'Resolution notes are required when resolving or closing a finding.',
            ]);
        }

        $record->fill([
            'monitoring_visit_id' => $visitId,
            'finding' => trim($validated['finding_text']),
            'corrective_action' => $this->nullableTrim($validated['corrective_action'] ?? null),
            'due_date' => $validated['finding_due_date'] ?: null,
            'status' => $status,
            'resolved_at' => in_array($status, [MonitoringFindingStatus::Resolved, MonitoringFindingStatus::Closed], true) ? ($record->resolved_at ?? now()) : null,
            'resolution_notes' => $this->nullableTrim($validated['resolution_notes'] ?? null),
            'updated_by' => auth()->id(),
        ])->save();

        $this->resetFindingForm();
        session()->flash('monitoring-status', 'Monitoring finding saved successfully.');
    }

    public function cancelFinding(): void { $this->resetFindingForm(); }

    public function startFollowUp(int $findingId): void
    {
        $this->authorizeUpdate();
        $finding = ProjectMonitoringFinding::query()->where('project_id', $this->projectId)->findOrFail($findingId);
        $this->resetFollowUpForm();
        $this->followUpFindingId = $finding->id;
        $this->follow_up_date = now()->format('Y-m-d');
        $this->follow_up_status = $finding->status->value;
        $this->showFollowUpForm = true;
    }

    public function saveFollowUp(): void
    {
        $this->authorizeUpdate();

        $validated = $this->validate([
            'follow_up_date' => ['required', 'date'],
            'follow_up_notes' => ['required', 'string', 'max:5000'],
            'next_follow_up_date' => ['nullable', 'date', 'after_or_equal:follow_up_date'],
            'follow_up_status' => ['required', Rule::enum(MonitoringFindingStatus::class)],
        ]);

        $finding = ProjectMonitoringFinding::query()
            ->where('project_id', $this->projectId)
            ->findOrFail($this->followUpFindingId);

        $status = MonitoringFindingStatus::from($validated['follow_up_status']);

        ProjectMonitoringFollowUp::create([
            'monitoring_finding_id' => $finding->id,
            'follow_up_date' => $validated['follow_up_date'],
            'notes' => trim($validated['follow_up_notes']),
            'next_follow_up_date' => $validated['next_follow_up_date'] ?: null,
            'status_after' => $status,
            'created_by' => auth()->id(),
        ]);

        $finding->update([
            'status' => $status,
            'resolved_at' => in_array($status, [MonitoringFindingStatus::Resolved, MonitoringFindingStatus::Closed], true) ? ($finding->resolved_at ?? now()) : null,
            'updated_by' => auth()->id(),
        ]);

        $this->resetFollowUpForm();
        session()->flash('monitoring-status', 'Finding follow-up recorded successfully.');
    }

    public function cancelFollowUp(): void { $this->resetFollowUpForm(); }

    public function startCompliance(): void
    {
        $this->authorizeUpdate();
        $this->resetComplianceForm();
        $this->showComplianceForm = true;
    }

    public function editCompliance(int $id): void
    {
        $this->authorizeUpdate();
        $record = ProjectComplianceReport::query()->where('project_id', $this->projectId)->findOrFail($id);

        $this->editingComplianceId = $record->id;
        $this->report_type = $record->report_type;
        $this->period_label = $record->period_label ?? '';
        $this->period_start = $record->period_start?->format('Y-m-d') ?? '';
        $this->period_end = $record->period_end?->format('Y-m-d') ?? '';
        $this->compliance_due_date = $record->due_date?->format('Y-m-d') ?? '';
        $this->compliance_status = $record->status->value;
        $this->compliance_accomplishment = $record->accomplishment_percentage !== null ? (string) $record->accomplishment_percentage : '';
        $this->linked_document_id = $record->document_id ? (string) $record->document_id : '';
        $this->compliance_remarks = $record->remarks ?? '';
        $this->showComplianceForm = true;
    }

    public function saveCompliance(): void
    {
        $this->authorizeUpdate();

        $validated = $this->validate([
            'report_type' => ['required', 'string', 'max:120'],
            'period_label' => ['nullable', 'string', 'max:120'],
            'period_start' => ['nullable', 'date'],
            'period_end' => ['nullable', 'date', 'after_or_equal:period_start'],
            'compliance_due_date' => ['nullable', 'date'],
            'compliance_status' => ['required', Rule::enum(ComplianceReportStatus::class)],
            'compliance_accomplishment' => ['nullable', 'integer', 'min:0', 'max:100'],
            'linked_document_id' => ['nullable', 'integer'],
            'compliance_remarks' => ['nullable', 'string', 'max:5000'],
        ]);

        $documentId = filled($validated['linked_document_id'] ?? null) ? (int) $validated['linked_document_id'] : null;
        if ($documentId) {
            $this->project()->documents()->findOrFail($documentId);
        }

        $status = ComplianceReportStatus::from($validated['compliance_status']);
        $record = $this->editingComplianceId
            ? ProjectComplianceReport::query()->where('project_id', $this->projectId)->findOrFail($this->editingComplianceId)
            : new ProjectComplianceReport(['project_id' => $this->projectId, 'created_by' => auth()->id()]);

        $submittedAt = $record->submitted_at;
        $verifiedAt = $record->verified_at;
        $verifiedBy = $record->verified_by;

        if (in_array($status, [ComplianceReportStatus::Submitted, ComplianceReportStatus::Verified], true)) {
            $submittedAt ??= now();
        }
        if ($status === ComplianceReportStatus::Verified) {
            $verifiedAt = now();
            $verifiedBy = auth()->id();
        } else {
            $verifiedAt = null;
            $verifiedBy = null;
        }

        $record->fill([
            'report_type' => trim($validated['report_type']),
            'period_label' => $this->nullableTrim($validated['period_label'] ?? null),
            'period_start' => $validated['period_start'] ?: null,
            'period_end' => $validated['period_end'] ?: null,
            'due_date' => $validated['compliance_due_date'] ?: null,
            'submitted_at' => $submittedAt,
            'verified_at' => $verifiedAt,
            'status' => $status,
            'accomplishment_percentage' => $validated['compliance_accomplishment'] !== '' ? $validated['compliance_accomplishment'] : null,
            'document_id' => $documentId,
            'remarks' => $this->nullableTrim($validated['compliance_remarks'] ?? null),
            'verified_by' => $verifiedBy,
            'updated_by' => auth()->id(),
        ])->save();

        $this->resetComplianceForm();
        session()->flash('monitoring-status', 'Compliance/reporting record saved successfully.');
    }

    public function cancelCompliance(): void { $this->resetComplianceForm(); }

    public function render(): View
    {
        Gate::authorize(PermissionName::ProjectMonitoringView->value);

        $project = Project::query()
            ->with([
                'proponent', 'workflowState', 'implementation', 'documents',
                'monitoringVisits.findings',
                'monitoringFindings.visit',
                'monitoringFindings.followUps' => fn ($query) => $query->latest('follow_up_date')->latest('id'),
                'complianceReports.document',
            ])
            ->findOrFail($this->projectId);

        return view('livewire.projects.monitoring-details', [
            'project' => $project,
            'isUnlocked' => app(ProjectProcessingService::class)->isUnlocked($project),
            'summary' => app(ProjectMonitoringService::class)->summary($project),
            'visitTypes' => MonitoringVisitType::cases(),
            'visitStatuses' => MonitoringVisitStatus::cases(),
            'findingStatuses' => MonitoringFindingStatus::cases(),
            'complianceStatuses' => ComplianceReportStatus::cases(),
        ]);
    }

    private function authorizeUpdate(): void
    {
        Gate::authorize(PermissionName::ProjectMonitoringUpdate->value);
        app(ProjectProcessingService::class)->assertUnlocked($this->project());
    }

    private function project(): Project
    {
        return Project::query()->with(['workflowState', 'documents'])->findOrFail($this->projectId);
    }

    private function resetVisitForm(): void
    {
        $this->editingVisitId = null;
        $this->visit_type = MonitoringVisitType::Regular->value;
        $this->visit_date = '';
        $this->visit_status = MonitoringVisitStatus::Scheduled->value;
        $this->conducted_by_name = '';
        $this->visit_location = '';
        $this->visit_accomplishment = '0';
        $this->observations = '';
        $this->recommendations = '';
        $this->next_monitoring_date = '';
        $this->showVisitForm = false;
        $this->resetValidation();
    }

    private function resetFindingForm(): void
    {
        $this->editingFindingId = null;
        $this->finding_visit_id = '';
        $this->finding_text = '';
        $this->corrective_action = '';
        $this->finding_due_date = '';
        $this->finding_status = MonitoringFindingStatus::Open->value;
        $this->resolution_notes = '';
        $this->showFindingForm = false;
        $this->resetValidation();
    }

    private function resetFollowUpForm(): void
    {
        $this->followUpFindingId = null;
        $this->follow_up_date = '';
        $this->follow_up_notes = '';
        $this->next_follow_up_date = '';
        $this->follow_up_status = '';
        $this->showFollowUpForm = false;
        $this->resetValidation();
    }

    private function resetComplianceForm(): void
    {
        $this->editingComplianceId = null;
        $this->report_type = '';
        $this->period_label = '';
        $this->period_start = '';
        $this->period_end = '';
        $this->compliance_due_date = '';
        $this->compliance_status = ComplianceReportStatus::Pending->value;
        $this->compliance_accomplishment = '';
        $this->linked_document_id = '';
        $this->compliance_remarks = '';
        $this->showComplianceForm = false;
        $this->resetValidation();
    }

    private function nullableTrim(?string $value): ?string
    {
        return filled($value) ? trim($value) : null;
    }
}
