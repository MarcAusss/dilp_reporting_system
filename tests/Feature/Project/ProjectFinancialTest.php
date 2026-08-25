<?php

namespace Tests\Feature\Project;

use App\Enums\PermissionName;
use App\Enums\UserRole;
use App\Livewire\Projects\FinancialDetails;
use App\Models\Project;
use App\Models\ProjectFinancial;
use App\Models\ProjectPurpose;
use App\Models\ProjectType;
use App\Models\Proponent;
use App\Models\User;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectFinancialTest extends TestCase
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

    public function test_super_admin_can_view_project_financial_page(): void
    {
        $user = $this->superAdmin();

        $project = $this->project($user);

        $response = $this
            ->actingAs($user)
            ->get(
                route(
                    'projects.financials',
                    $project
                )
            );

        $response->assertOk();

        $response->assertSee(
            'Project Financial Details'
        );

        $response->assertSee(
            'Financial Test Project'
        );
    }

    public function test_gip_can_view_project_financial_page(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
        ]);

        $user->assignRole(
            UserRole::GIP->value
        );

        $project = $this->project();

        $response = $this
            ->actingAs($user)
            ->get(
                route(
                    'projects.financials',
                    $project
                )
            );

        $response->assertOk();

        $response->assertSee(
            'read-only access'
        );
    }

    public function test_operational_user_does_not_have_update_permission(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
        ]);

        $user->assignRole(
            UserRole::Focal->value
        );

        $this->assertTrue(
            $user->can(
                PermissionName::ProjectFinancialsView->value
            )
        );

        $this->assertFalse(
            $user->can(
                PermissionName::ProjectFinancialsUpdate->value
            )
        );
    }

    public function test_super_admin_can_save_project_financial_details(): void
    {
        $user = $this->superAdmin();

        $project = $this->project($user);

        Livewire::actingAs($user)
            ->test(
                FinancialDetails::class,
                [
                    'projectId' => $project->id,
                ]
            )
            ->set(
                'equipment_materials_tools',
                '100000.00'
            )
            ->set(
                'insurance',
                '2500.00'
            )
            ->set(
                'training',
                '7500.00'
            )
            ->set(
                'proponent_partner_equity',
                '10000.00'
            )
            ->set(
                'beneficiary_equity',
                '5000.00'
            )
            ->set(
                'remarks',
                'Test financial breakdown.'
            )
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas(
            'project_financials',
            [
                'project_id' => $project->id,

                'equipment_materials_tools' =>
                    '100000.00',

                'insurance' =>
                    '2500.00',

                'training' =>
                    '7500.00',

                'proponent_partner_equity' =>
                    '10000.00',

                'beneficiary_equity' =>
                    '5000.00',
            ]
        );
    }

    public function test_project_financial_calculations_are_correct(): void
    {
        $financial = new ProjectFinancial([
            'equipment_materials_tools' =>
                100000,

            'insurance' =>
                2500,

            'training' =>
                7500,

            'proponent_partner_equity' =>
                10000,

            'beneficiary_equity' =>
                5000,
        ]);

        $this->assertSame(
            110000.00,
            $financial->doleShare()
        );

        $this->assertSame(
            15000.00,
            $financial->totalEquity()
        );

        $this->assertSame(
            125000.00,
            $financial->totalProjectCost()
        );

        $this->assertSame(
            12.00,
            $financial->equityPercentage()
        );
    }

    public function test_zero_project_cost_has_zero_equity_percentage(): void
    {
        $financial = new ProjectFinancial([
            'equipment_materials_tools' => 0,
            'insurance' => 0,
            'training' => 0,
            'proponent_partner_equity' => 0,
            'beneficiary_equity' => 0,
        ]);

        $this->assertSame(
            0.0,
            $financial->equityPercentage()
        );
    }

    public function test_updating_financial_details_does_not_create_duplicate_record(): void
    {
        $user = $this->superAdmin();

        $project = $this->project($user);

        ProjectFinancial::create([
            'project_id' => $project->id,

            'equipment_materials_tools' =>
                50000,

            'created_by' =>
                $user->id,

            'updated_by' =>
                $user->id,
        ]);

        Livewire::actingAs($user)
            ->test(
                FinancialDetails::class,
                [
                    'projectId' => $project->id,
                ]
            )
            ->set(
                'equipment_materials_tools',
                '75000.00'
            )
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(
            1,
            ProjectFinancial::query()
                ->where(
                    'project_id',
                    $project->id
                )
                ->count()
        );

        $this->assertDatabaseHas(
            'project_financials',
            [
                'project_id' => $project->id,

                'equipment_materials_tools' =>
                    '75000.00',
            ]
        );
    }

    public function test_negative_financial_amount_is_rejected(): void
    {
        $user = $this->superAdmin();

        $project = $this->project($user);

        Livewire::actingAs($user)
            ->test(
                FinancialDetails::class,
                [
                    'projectId' => $project->id,
                ]
            )
            ->set(
                'equipment_materials_tools',
                '-100.00'
            )
            ->call('save')
            ->assertHasErrors([
                'equipment_materials_tools',
            ]);

        $this->assertDatabaseMissing(
            'project_financials',
            [
                'project_id' =>
                    $project->id,
            ]
        );
    }

    private function superAdmin(): User
    {
        $user = User::factory()->create([
            'is_active' => true,
        ]);

        $user->assignRole(
            UserRole::SuperAdmin->value
        );

        return $user;
    }

    private function project(
        ?User $user = null
    ): Project {
        $projectType =
            ProjectType::query()
                ->firstOrFail();

        $projectPurpose =
            ProjectPurpose::query()
                ->firstOrFail();

        $proponent = Proponent::create([
            'type' => 'individual',
            'name' => 'Financial Test Proponent',
            'is_active' => true,
        ]);

        return Project::create([
            'fiscal_year' => 2026,

            'project_code' =>
                'FIN-TEST-' . uniqid(),

            'title' =>
                'Financial Test Project',

            'proponent_id' =>
                $proponent->id,

            'project_type_id' =>
                $projectType->id,

            'project_purpose_id' =>
                $projectPurpose->id,

            'record_status' =>
                'active',

            'created_by' =>
                $user?->id,

            'updated_by' =>
                $user?->id,
        ]);
    }
}