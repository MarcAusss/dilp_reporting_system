<?php

namespace Tests\Feature\Project;

use App\Enums\PermissionName;
use App\Enums\ProjectWorkflowAction;
use App\Enums\ProjectWorkflowStage;
use App\Enums\ProjectWorkflowStatus;
use App\Enums\UserRole;
use App\Livewire\Projects\WorkflowDetails;
use App\Models\Project;
use App\Models\ProjectPurpose;
use App\Models\ProjectType;
use App\Models\Proponent;
use App\Models\User;
use App\Services\ProjectWorkflowService;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectWorkflowTest extends TestCase
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

    public function test_new_project_starts_in_evaluation_queue(): void
    {
        $creator = $this->user(UserRole::SuperAdmin);
        $project = $this->project($creator);

        $project->load('workflowState');

        $this->assertSame(
            ProjectWorkflowStage::Evaluation,
            $project->workflowState->stage
        );

        $this->assertSame(
            ProjectWorkflowStatus::Pending,
            $project->workflowState->status
        );

        $this->assertSame(
            UserRole::DilpCoordinator->value,
            $project->workflowState->assigned_role
        );

        $this->assertDatabaseHas('project_workflow_events', [
            'project_id' => $project->id,
            'action' => ProjectWorkflowAction::Registered->value,
            'to_stage' => ProjectWorkflowStage::Evaluation->value,
            'to_status' => ProjectWorkflowStatus::Pending->value,
        ]);
    }

    public function test_dilp_coordinator_can_process_evaluation_and_endorsement(): void
    {
        $coordinator = $this->user(UserRole::DilpCoordinator);
        $project = $this->project();

        Livewire::actingAs($coordinator)
            ->test(WorkflowDetails::class, ['projectId' => $project->id])
            ->set('reference_number', 'EVAL-2026-001')
            ->set('remarks', 'Evaluation started.')
            ->call('startProcessing')
            ->assertHasNoErrors()
            ->call('advance')
            ->assertHasNoErrors();

        $state = $project->workflowState()->firstOrFail();

        $this->assertSame(ProjectWorkflowStage::Endorsement, $state->stage);
        $this->assertSame(ProjectWorkflowStatus::Pending, $state->status);
        $this->assertSame(UserRole::DilpCoordinator->value, $state->assigned_role);

        app(ProjectWorkflowService::class)->act(
            $project,
            $coordinator,
            ProjectWorkflowAction::Started,
        );

        app(ProjectWorkflowService::class)->act(
            $project,
            $coordinator,
            ProjectWorkflowAction::Advanced,
        );

        $state->refresh();

        $this->assertSame(ProjectWorkflowStage::Validation, $state->stage);
        $this->assertSame(ProjectWorkflowStatus::Pending, $state->status);
        $this->assertSame(UserRole::Focal->value, $state->assigned_role);
    }

    public function test_focal_cannot_act_before_project_is_assigned_to_focal_role(): void
    {
        $focal = $this->user(UserRole::Focal);
        $project = $this->project();

        $this->expectException(AuthorizationException::class);

        app(ProjectWorkflowService::class)->act(
            $project,
            $focal,
            ProjectWorkflowAction::Started,
        );
    }

    public function test_focal_can_process_validation_and_submit_for_approval(): void
    {
        $coordinator = $this->user(UserRole::DilpCoordinator);
        $focal = $this->user(UserRole::Focal);
        $project = $this->project();
        $service = app(ProjectWorkflowService::class);

        $service->act($project, $coordinator, ProjectWorkflowAction::Started);
        $service->act($project, $coordinator, ProjectWorkflowAction::Advanced);
        $service->act($project, $coordinator, ProjectWorkflowAction::Started);
        $service->act($project, $coordinator, ProjectWorkflowAction::Advanced);

        $service->act($project, $focal, ProjectWorkflowAction::Started);
        $service->act($project, $focal, ProjectWorkflowAction::Advanced);

        $state = $project->workflowState()->firstOrFail();

        $this->assertSame(ProjectWorkflowStage::Approval, $state->stage);
        $this->assertSame(ProjectWorkflowStatus::Pending, $state->status);
        $this->assertSame(UserRole::SuperAdmin->value, $state->assigned_role);
    }

    public function test_super_admin_can_complete_approval(): void
    {
        $coordinator = $this->user(UserRole::DilpCoordinator);
        $focal = $this->user(UserRole::Focal);
        $superAdmin = $this->user(UserRole::SuperAdmin);
        $project = $this->project();
        $service = app(ProjectWorkflowService::class);

        $service->act($project, $coordinator, ProjectWorkflowAction::Started);
        $service->act($project, $coordinator, ProjectWorkflowAction::Advanced);
        $service->act($project, $coordinator, ProjectWorkflowAction::Started);
        $service->act($project, $coordinator, ProjectWorkflowAction::Advanced);
        $service->act($project, $focal, ProjectWorkflowAction::Started);
        $service->act($project, $focal, ProjectWorkflowAction::Advanced);
        $service->act($project, $superAdmin, ProjectWorkflowAction::Started);
        $service->act(
            $project,
            $superAdmin,
            ProjectWorkflowAction::Approved,
            'Approved after final review.',
            'APP-2026-001',
        );

        $state = $project->workflowState()->firstOrFail();

        $this->assertSame(ProjectWorkflowStage::Completed, $state->stage);
        $this->assertSame(ProjectWorkflowStatus::Completed, $state->status);
        $this->assertNull($state->assigned_role);

        $this->assertDatabaseHas('project_workflow_events', [
            'project_id' => $project->id,
            'action' => ProjectWorkflowAction::Approved->value,
            'reference_number' => 'APP-2026-001',
            'to_stage' => ProjectWorkflowStage::Completed->value,
            'to_status' => ProjectWorkflowStatus::Completed->value,
        ]);
    }

    public function test_return_for_correction_requires_remarks_and_reassigns_to_coordinator(): void
    {
        $coordinator = $this->user(UserRole::DilpCoordinator);
        $project = $this->project();
        $service = app(ProjectWorkflowService::class);

        $service->act($project, $coordinator, ProjectWorkflowAction::Started);

        try {
            $service->act(
                $project,
                $coordinator,
                ProjectWorkflowAction::Returned,
            );

            $this->fail('Expected validation exception was not thrown.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('remarks', $exception->errors());
        }

        $service->act(
            $project,
            $coordinator,
            ProjectWorkflowAction::Returned,
            'Please correct the project documents.',
        );

        $state = $project->workflowState()->firstOrFail();

        $this->assertSame(ProjectWorkflowStage::Evaluation, $state->stage);
        $this->assertSame(ProjectWorkflowStatus::Returned, $state->status);
        $this->assertSame(UserRole::DilpCoordinator->value, $state->assigned_role);
    }

    public function test_work_queue_is_available_to_processing_roles_but_not_gip(): void
    {
        $coordinator = $this->user(UserRole::DilpCoordinator);
        $gip = $this->user(UserRole::GIP);
        $project = $this->project();

        $this->actingAs($coordinator)
            ->get(route('work-queues.index'))
            ->assertOk()
            ->assertSee($project->title);

        $this->actingAs($gip)
            ->get(route('work-queues.index'))
            ->assertForbidden();
    }

    public function test_gip_has_read_only_workflow_access(): void
    {
        $gip = $this->user(UserRole::GIP);
        $project = $this->project();

        $this->assertTrue(
            $gip->can(PermissionName::ProjectWorkflowView->value)
        );

        $this->assertFalse(
            $gip->can(PermissionName::ProjectWorkflowUpdate->value)
        );

        $this->actingAs($gip)
            ->get(route('projects.workflow', $project))
            ->assertOk()
            ->assertSee('Project Workflow');
    }

    private function user(UserRole $role): User
    {
        $user = User::factory()->create([
            'is_active' => true,
        ]);

        $user->assignRole($role->value);

        return $user;
    }

    private function project(?User $creator = null): Project
    {
        $proponent = Proponent::create([
            'type' => 'individual',
            'name' => 'Phase 4 Test Proponent',
            'is_active' => true,
        ]);

        return Project::create([
            'fiscal_year' => 2026,
            'project_code' => 'PHASE4-' . uniqid(),
            'title' => 'Phase 4 Test Project',
            'proponent_id' => $proponent->id,
            'project_type_id' => ProjectType::query()->firstOrFail()->id,
            'project_purpose_id' => ProjectPurpose::query()->firstOrFail()->id,
            'record_status' => 'draft',
            'created_by' => $creator?->id,
            'updated_by' => $creator?->id,
        ]);
    }
}
