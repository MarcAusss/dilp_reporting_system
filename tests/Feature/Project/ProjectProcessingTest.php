<?php

namespace Tests\Feature\Project;

use App\Enums\DisbursementStatus;
use App\Enums\ObligationStatus;
use App\Enums\PermissionName;
use App\Enums\ProjectWorkflowAction;
use App\Enums\UserRole;
use App\Livewire\Projects\ProcessingDetails;
use App\Models\Project;
use App\Models\ProjectFinancial;
use App\Models\ProjectPurpose;
use App\Models\ProjectType;
use App\Models\Proponent;
use App\Models\User;
use App\Services\ProjectWorkflowService;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectProcessingTest extends TestCase
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

    public function test_gip_has_read_only_processing_access(): void
    {
        $gip = $this->user(UserRole::GIP);
        $project = $this->project();

        $this->assertTrue($gip->can(PermissionName::ProjectProcessingView->value));
        $this->assertFalse($gip->can(PermissionName::ProjectProcessingUpdate->value));

        $this->actingAs($gip)
            ->get(route('projects.processing', $project))
            ->assertOk()
            ->assertSee('Financial &amp; Implementation Processing', false);
    }

    public function test_processing_is_locked_until_workflow_is_approved(): void
    {
        $coordinator = $this->user(UserRole::DilpCoordinator);
        $project = $this->project();

        Livewire::actingAs($coordinator)
            ->test(ProcessingDetails::class, ['projectId' => $project->id])
            ->call('startObligation')
            ->assertHasErrors('processing');
    }

    public function test_approved_project_can_record_obligation_and_paid_disbursement(): void
    {
        $focal = $this->user(UserRole::Focal);
        $project = $this->approvedProject();
        $this->financial($project, 100000);

        Livewire::actingAs($focal)
            ->test(ProcessingDetails::class, ['projectId' => $project->id])
            ->call('startObligation')
            ->set('obligation_number', 'ORS-2026-001')
            ->set('obligation_amount', '80000.00')
            ->set('obligation_status', ObligationStatus::Obligated->value)
            ->call('saveObligation')
            ->assertHasNoErrors()
            ->call('selectSection', 'disbursement')
            ->call('startDisbursement')
            ->set('disbursement_number', 'DV-2026-001')
            ->set('payment_reference', 'PAY-2026-001')
            ->set('disbursement_amount', '60000.00')
            ->set('disbursement_status', DisbursementStatus::Paid->value)
            ->call('saveDisbursement')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('project_obligations', [
            'project_id' => $project->id,
            'obligation_number' => 'ORS-2026-001',
            'amount' => 80000,
            'status' => ObligationStatus::Obligated->value,
        ]);

        $this->assertDatabaseHas('project_disbursements', [
            'project_id' => $project->id,
            'disbursement_number' => 'DV-2026-001',
            'amount' => 60000,
            'status' => DisbursementStatus::Paid->value,
        ]);
    }

    public function test_obligation_cannot_exceed_saved_dole_share(): void
    {
        $focal = $this->user(UserRole::Focal);
        $project = $this->approvedProject();
        $this->financial($project, 50000);

        Livewire::actingAs($focal)
            ->test(ProcessingDetails::class, ['projectId' => $project->id])
            ->call('startObligation')
            ->set('obligation_amount', '50000.01')
            ->set('obligation_status', ObligationStatus::Obligated->value)
            ->call('saveObligation')
            ->assertHasErrors('obligation_amount');
    }

    public function test_paid_disbursement_cannot_exceed_obligated_amount(): void
    {
        $focal = $this->user(UserRole::Focal);
        $project = $this->approvedProject();
        $this->financial($project, 100000);

        $project->obligations()->create([
            'obligation_number' => 'ORS-LIMIT',
            'amount' => 40000,
            'status' => ObligationStatus::Obligated,
            'created_by' => $focal->id,
            'updated_by' => $focal->id,
        ]);

        Livewire::actingAs($focal)
            ->test(ProcessingDetails::class, ['projectId' => $project->id])
            ->call('selectSection', 'disbursement')
            ->call('startDisbursement')
            ->set('disbursement_amount', '40000.01')
            ->set('disbursement_status', DisbursementStatus::Paid->value)
            ->call('saveDisbursement')
            ->assertHasErrors('disbursement_amount');
    }

    public function test_completed_implementation_requires_one_hundred_percent_accomplishment(): void
    {
        $coordinator = $this->user(UserRole::DilpCoordinator);
        $project = $this->approvedProject();

        Livewire::actingAs($coordinator)
            ->test(ProcessingDetails::class, ['projectId' => $project->id])
            ->call('selectSection', 'implementation')
            ->set('implementation_start_date', '2026-09-01')
            ->set('actual_completion_date', '2026-09-05')
            ->set('implementation_status', 'completed')
            ->set('accomplishment_percentage', '99')
            ->call('saveImplementation')
            ->assertHasErrors('accomplishment_percentage');
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
        $workflow->act($project, $superAdmin, ProjectWorkflowAction::Approved, 'Approved for Phase 5 processing.');

        return $project->refresh();
    }

    private function financial(Project $project, float $doleShare): ProjectFinancial
    {
        return ProjectFinancial::create([
            'project_id' => $project->id,
            'equipment_materials_tools' => $doleShare,
            'insurance' => 0,
            'training' => 0,
            'proponent_partner_equity' => 0,
            'beneficiary_equity' => 0,
        ]);
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
            'name' => 'Phase 5 Test Proponent',
            'is_active' => true,
        ]);

        return Project::create([
            'fiscal_year' => 2026,
            'project_code' => 'PHASE5-'.uniqid(),
            'title' => 'Phase 5 Test Project',
            'proponent_id' => $proponent->id,
            'project_type_id' => ProjectType::query()->firstOrFail()->id,
            'project_purpose_id' => ProjectPurpose::query()->firstOrFail()->id,
            'record_status' => 'draft',
            'created_by' => $creator?->id,
            'updated_by' => $creator?->id,
        ]);
    }
}
