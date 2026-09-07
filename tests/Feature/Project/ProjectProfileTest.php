<?php

namespace Tests\Feature\Project;

use App\Enums\PermissionName;
use App\Enums\UserRole;
use App\Livewire\Projects\BeneficiaryDetails;
use App\Livewire\Projects\BudgetDetails;
use App\Livewire\Projects\ConvergenceDetails;
use App\Livewire\Projects\LivelihoodDetails;
use App\Models\BeneficiarySector;
use App\Models\ConvergenceProgram;
use App\Models\Livelihood;
use App\Models\Project;
use App\Models\ProjectBudgetItem;
use App\Models\ProjectPurpose;
use App\Models\ProjectType;
use App\Models\Proponent;
use App\Models\User;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectProfileTest extends TestCase
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

    public function test_super_admin_can_view_project_profile(): void
    {
        $user = $this->superAdmin();
        $project = $this->project($user);

        $this->actingAs($user)
            ->get(route('projects.show', $project))
            ->assertOk()
            ->assertSee('Project Profile')
            ->assertSee('Phase 3 Test Project')
            ->assertSee('Beneficiaries')
            ->assertSee('Budget Items');
    }

    public function test_operational_role_has_read_only_phase_three_permissions(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
        ]);

        $user->assignRole(UserRole::Focal->value);

        $this->assertTrue($user->can(PermissionName::ProjectBeneficiariesView->value));
        $this->assertTrue($user->can(PermissionName::ProjectLivelihoodsView->value));
        $this->assertTrue($user->can(PermissionName::ProjectBudgetItemsView->value));
        $this->assertTrue($user->can(PermissionName::ProjectConvergenceView->value));

        $this->assertFalse($user->can(PermissionName::ProjectBeneficiariesUpdate->value));
        $this->assertFalse($user->can(PermissionName::ProjectBudgetItemsUpdate->value));
    }

    public function test_super_admin_can_add_beneficiary_with_multiple_sectors(): void
    {
        $user = $this->superAdmin();
        $project = $this->project($user);
        $sectors = BeneficiarySector::query()->take(2)->get();

        Livewire::actingAs($user)
            ->test(BeneficiaryDetails::class, ['projectId' => $project->id])
            ->set('reference_number', 'BEN-001')
            ->set('first_name', 'Juan')
            ->set('last_name', 'Dela Cruz')
            ->set('sex', 'male')
            ->set('sector_ids', $sectors->pluck('id')->all())
            ->call('save')
            ->assertHasNoErrors();

        $beneficiary = $project->beneficiaries()->firstOrFail();

        $this->assertSame('Juan Dela Cruz', $beneficiary->fullName());
        $this->assertCount(2, $beneficiary->sectors);
    }

    public function test_only_one_project_livelihood_is_primary_after_updates(): void
    {
        $user = $this->superAdmin();
        $project = $this->project($user);
        $livelihoods = Livelihood::query()->take(2)->get();

        Livewire::actingAs($user)
            ->test(LivelihoodDetails::class, ['projectId' => $project->id])
            ->set('livelihood_id', $livelihoods[0]->id)
            ->set('is_primary', true)
            ->set('target_beneficiaries', 10)
            ->call('save')
            ->assertHasNoErrors();

        Livewire::actingAs($user)
            ->test(LivelihoodDetails::class, ['projectId' => $project->id])
            ->set('livelihood_id', $livelihoods[1]->id)
            ->set('is_primary', true)
            ->set('target_beneficiaries', 20)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(
            1,
            $project->livelihoods()->where('is_primary', true)->count()
        );
    }

    public function test_budget_item_amount_is_calculated_from_quantity_and_unit_cost(): void
    {
        $user = $this->superAdmin();
        $project = $this->project($user);

        Livewire::actingAs($user)
            ->test(BudgetDetails::class, ['projectId' => $project->id])
            ->set('component', 'equipment_materials_tools')
            ->set('item_description', 'Starter tool kit')
            ->set('quantity', '5.00')
            ->set('unit', 'set')
            ->set('unit_cost', '2500.00')
            ->call('save')
            ->assertHasNoErrors();

        $item = ProjectBudgetItem::query()->firstOrFail();

        $this->assertSame(12500.0, (float) $item->amount);
    }

    public function test_super_admin_can_add_convergence_assistance(): void
    {
        $user = $this->superAdmin();
        $project = $this->project($user);
        $program = ConvergenceProgram::query()->firstOrFail();

        Livewire::actingAs($user)
            ->test(ConvergenceDetails::class, ['projectId' => $project->id])
            ->set('convergence_program_id', $program->id)
            ->set('reference_number', 'CONV-001')
            ->set('assistance_description', 'Partner assistance')
            ->set('assistance_amount', '15000.00')
            ->set('assistance_date', '2026-09-01')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('project_convergences', [
            'project_id' => $project->id,
            'convergence_program_id' => $program->id,
            'reference_number' => 'CONV-001',
            'assistance_amount' => '15000.00',
        ]);
    }

    private function superAdmin(): User
    {
        $user = User::factory()->create([
            'is_active' => true,
        ]);

        $user->assignRole(UserRole::SuperAdmin->value);

        return $user;
    }

    private function project(?User $user = null): Project
    {
        $proponent = Proponent::create([
            'type' => 'individual',
            'name' => 'Phase 3 Test Proponent',
            'is_active' => true,
        ]);

        return Project::create([
            'fiscal_year' => 2026,
            'project_code' => 'PHASE3-' . uniqid(),
            'title' => 'Phase 3 Test Project',
            'proponent_id' => $proponent->id,
            'project_type_id' => ProjectType::query()->firstOrFail()->id,
            'project_purpose_id' => ProjectPurpose::query()->firstOrFail()->id,
            'record_status' => 'draft',
            'created_by' => $user?->id,
            'updated_by' => $user?->id,
        ]);
    }
}
