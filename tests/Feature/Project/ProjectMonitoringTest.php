<?php

namespace Tests\Feature\Project;

use App\Enums\ComplianceReportStatus;
use App\Enums\MonitoringFindingStatus;
use App\Enums\MonitoringVisitStatus;
use App\Enums\MonitoringVisitType;
use App\Enums\PermissionName;
use App\Enums\ProjectDocumentStatus;
use App\Enums\ProjectWorkflowAction;
use App\Enums\UserRole;
use App\Livewire\Projects\DocumentDetails;
use App\Livewire\Projects\MonitoringDetails;
use App\Models\Project;
use App\Models\ProjectPurpose;
use App\Models\ProjectType;
use App\Models\Proponent;
use App\Models\User;
use App\Services\ProjectWorkflowService;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectMonitoringTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed([
            RolePermissionSeeder::class,
            MasterDataSeeder::class,
        ]);
    }

    public function test_gip_has_read_only_document_and_monitoring_access(): void
    {
        $gip = $this->user(UserRole::GIP);
        $project = $this->project();

        $this->assertTrue($gip->can(PermissionName::ProjectDocumentsView->value));
        $this->assertFalse($gip->can(PermissionName::ProjectDocumentsUpdate->value));
        $this->assertTrue($gip->can(PermissionName::ProjectMonitoringView->value));
        $this->assertFalse($gip->can(PermissionName::ProjectMonitoringUpdate->value));

        $this->actingAs($gip)->get(route('projects.documents', $project))->assertOk();
        $this->actingAs($gip)->get(route('projects.monitoring', $project))->assertOk();
    }

    public function test_document_requirement_can_be_recorded_before_project_approval(): void
    {
        $coordinator = $this->user(UserRole::DilpCoordinator);
        $project = $this->project();

        Livewire::actingAs($coordinator)
            ->test(DocumentDetails::class, ['projectId' => $project->id])
            ->call('startDocument')
            ->set('category', 'proposal')
            ->set('name', 'Approved Project Proposal')
            ->set('due_date', '2026-09-30')
            ->set('status', ProjectDocumentStatus::Missing->value)
            ->call('saveDocument')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('project_documents', [
            'project_id' => $project->id,
            'name' => 'Approved Project Proposal',
            'status' => ProjectDocumentStatus::Missing->value,
        ]);
    }

    public function test_private_document_upload_can_be_downloaded_by_authorized_user(): void
    {
        Storage::fake('local');

        $coordinator = $this->user(UserRole::DilpCoordinator);
        $project = $this->project();

        Livewire::actingAs($coordinator)
            ->test(DocumentDetails::class, ['projectId' => $project->id])
            ->call('startDocument')
            ->set('name', 'Monitoring Report')
            ->set('status', ProjectDocumentStatus::Submitted->value)
            ->set('upload', UploadedFile::fake()->create('monitoring-report.pdf', 100, 'application/pdf'))
            ->call('saveDocument')
            ->assertHasNoErrors();

        $document = $project->documents()->firstOrFail();

        Storage::disk('local')->assertExists($document->file_path);

        $this->actingAs($coordinator)
            ->get(route('projects.documents.download', [$project, $document]))
            ->assertOk();
    }

    public function test_verified_document_requires_an_attachment(): void
    {
        $coordinator = $this->user(UserRole::DilpCoordinator);
        $project = $this->project();

        Livewire::actingAs($coordinator)
            ->test(DocumentDetails::class, ['projectId' => $project->id])
            ->call('startDocument')
            ->set('name', 'Required Certification')
            ->set('status', ProjectDocumentStatus::Verified->value)
            ->call('saveDocument')
            ->assertHasErrors('upload');
    }

    public function test_monitoring_is_locked_until_project_is_approved(): void
    {
        $coordinator = $this->user(UserRole::DilpCoordinator);
        $project = $this->project();

        Livewire::actingAs($coordinator)
            ->test(MonitoringDetails::class, ['projectId' => $project->id])
            ->call('startVisit')
            ->assertHasErrors('processing');
    }

    public function test_approved_project_can_record_visit_finding_follow_up_and_compliance(): void
    {
        $focal = $this->user(UserRole::Focal);
        $project = $this->approvedProject();

        $component = Livewire::actingAs($focal)
            ->test(MonitoringDetails::class, ['projectId' => $project->id])
            ->call('startVisit')
            ->set('visit_type', MonitoringVisitType::Regular->value)
            ->set('visit_date', '2026-09-07')
            ->set('visit_status', MonitoringVisitStatus::Completed->value)
            ->set('visit_accomplishment', '75')
            ->set('observations', 'Project is operational with minor corrective items.')
            ->call('saveVisit')
            ->assertHasNoErrors();

        $visit = $project->monitoringVisits()->firstOrFail();

        $component
            ->call('startFinding', $visit->id)
            ->set('finding_text', 'Inventory log needs updating.')
            ->set('corrective_action', 'Update and submit the inventory log.')
            ->set('finding_due_date', '2026-09-15')
            ->set('finding_status', MonitoringFindingStatus::Open->value)
            ->call('saveFinding')
            ->assertHasNoErrors();

        $finding = $project->monitoringFindings()->firstOrFail();

        $component
            ->call('startFollowUp', $finding->id)
            ->set('follow_up_date', '2026-09-10')
            ->set('follow_up_notes', 'Updated inventory log was presented for review.')
            ->set('follow_up_status', MonitoringFindingStatus::Resolved->value)
            ->call('saveFollowUp')
            ->assertHasNoErrors()
            ->call('startCompliance')
            ->set('report_type', 'Quarterly Progress Report')
            ->set('period_label', 'Q3 2026')
            ->set('compliance_due_date', '2026-10-15')
            ->set('compliance_status', ComplianceReportStatus::Submitted->value)
            ->set('compliance_accomplishment', '75')
            ->call('saveCompliance')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('project_monitoring_visits', [
            'project_id' => $project->id,
            'accomplishment_percentage' => 75,
            'status' => MonitoringVisitStatus::Completed->value,
        ]);

        $this->assertDatabaseHas('project_monitoring_findings', [
            'project_id' => $project->id,
            'status' => MonitoringFindingStatus::Resolved->value,
        ]);

        $this->assertDatabaseHas('project_monitoring_follow_ups', [
            'monitoring_finding_id' => $finding->id,
            'status_after' => MonitoringFindingStatus::Resolved->value,
        ]);

        $this->assertDatabaseHas('project_compliance_reports', [
            'project_id' => $project->id,
            'report_type' => 'Quarterly Progress Report',
            'status' => ComplianceReportStatus::Submitted->value,
        ]);
    }

    private function approvedProject(): Project
    {
        $coordinator = $this->user(UserRole::DilpCoordinator);
        $focal = $this->user(UserRole::Focal);
        $superAdmin = $this->user(UserRole::SuperAdmin);
        $project = $this->project($superAdmin);
        $workflow = app(ProjectWorkflowService::class);

        $workflow->act($project, $coordinator, ProjectWorkflowAction::Started);
        $workflow->act($project, $coordinator, ProjectWorkflowAction::Advanced);
        $workflow->act($project, $coordinator, ProjectWorkflowAction::Started);
        $workflow->act($project, $coordinator, ProjectWorkflowAction::Advanced);
        $workflow->act($project, $focal, ProjectWorkflowAction::Started);
        $workflow->act($project, $focal, ProjectWorkflowAction::Advanced);
        $workflow->act($project, $superAdmin, ProjectWorkflowAction::Started);
        $workflow->act($project, $superAdmin, ProjectWorkflowAction::Approved, 'Approved for Phase 6 monitoring.');

        return $project->refresh();
    }

    private function user(UserRole $role): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($role->value);

        return $user;
    }

    private function project(?User $creator = null): Project
    {
        $proponent = Proponent::create([
            'type' => 'individual',
            'name' => 'Phase 6 Test Proponent',
            'is_active' => true,
        ]);

        return Project::create([
            'fiscal_year' => 2026,
            'project_code' => 'PHASE6-'.uniqid(),
            'title' => 'Phase 6 Test Project',
            'proponent_id' => $proponent->id,
            'project_type_id' => ProjectType::query()->firstOrFail()->id,
            'project_purpose_id' => ProjectPurpose::query()->firstOrFail()->id,
            'record_status' => 'draft',
            'created_by' => $creator?->id,
            'updated_by' => $creator?->id,
        ]);
    }
}
