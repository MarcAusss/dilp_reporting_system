<?php

namespace Tests\Feature\Reports;

use App\Enums\DisbursementStatus;
use App\Enums\ObligationStatus;
use App\Enums\ReportType;
use App\Enums\UserRole;
use App\Models\Project;
use App\Models\ProjectDisbursement;
use App\Models\ProjectFinancial;
use App\Models\ProjectObligation;
use App\Models\ProjectPurpose;
use App\Models\ProjectType;
use App\Models\Proponent;
use App\Models\User;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DilpReportsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed([RolePermissionSeeder::class, MasterDataSeeder::class]);
    }

    public function test_operational_user_can_open_reports_index(): void
    {
        $user = $this->user(UserRole::GIP);

        $this->actingAs($user)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertSee('Reports')
            ->assertSee('RAOD')
            ->assertSee('CQPR');
    }

    public function test_summary_report_renders_financial_totals(): void
    {
        $user = $this->user(UserRole::SuperAdmin);
        $project = $this->project($user);

        ProjectFinancial::create([
            'project_id' => $project->id,
            'equipment_materials_tools' => 100000,
            'insurance' => 5000,
            'training' => 10000,
            'proponent_partner_equity' => 5000,
            'beneficiary_equity' => 0,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        ProjectObligation::create([
            'project_id' => $project->id,
            'obligation_number' => 'OBL-001',
            'amount' => 100000,
            'status' => ObligationStatus::Obligated,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        ProjectDisbursement::create([
            'project_id' => $project->id,
            'disbursement_number' => 'DV-001',
            'amount' => 80000,
            'status' => DisbursementStatus::Paid,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('reports.show', [
                'report' => ReportType::Summary->value,
                'fiscal_year' => 2026,
            ]))
            ->assertOk()
            ->assertSee($project->title)
            ->assertSee('115,000.00')
            ->assertSee('80,000.00');
    }

    public function test_excel_export_returns_excel_compatible_workbook(): void
    {
        $user = $this->user(UserRole::SuperAdmin);
        $this->project($user);

        $response = $this->actingAs($user)
            ->get(route('reports.excel', [
                'report' => ReportType::Raod->value,
                'fiscal_year' => 2026,
            ]));

        $response->assertOk();
        $this->assertStringContainsString(
            'application/vnd.ms-excel',
            (string) $response->headers->get('content-type')
        );
    }

    public function test_report_route_rejects_unknown_report_type(): void
    {
        $user = $this->user(UserRole::SuperAdmin);

        $this->actingAs($user)
            ->get('/reports/not-a-report?fiscal_year=2026')
            ->assertNotFound();
    }

    private function user(UserRole $role): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($role->value);

        return $user;
    }

    private function project(User $creator): Project
    {
        $proponent = Proponent::create([
            'type' => 'individual',
            'name' => 'Phase 7 Report Proponent',
            'is_active' => true,
        ]);

        return Project::create([
            'fiscal_year' => 2026,
            'project_code' => 'RPT-' . uniqid(),
            'title' => 'Phase 7 Reporting Test Project',
            'proponent_id' => $proponent->id,
            'project_type_id' => ProjectType::query()->firstOrFail()->id,
            'project_purpose_id' => ProjectPurpose::query()->firstOrFail()->id,
            'record_status' => 'active',
            'created_by' => $creator->id,
            'updated_by' => $creator->id,
        ]);
    }
}
