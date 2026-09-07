<?php

namespace Tests\Feature\Project;

use App\Enums\PermissionName;
use App\Enums\UserRole;
use App\Livewire\Projects\SpreadsheetParityDetails;
use App\Models\Project;
use App\Models\ProjectPurpose;
use App\Models\ProjectType;
use App\Models\Proponent;
use App\Models\User;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SpreadsheetParityDetailsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed([RolePermissionSeeder::class, MasterDataSeeder::class]);
    }

    public function test_operational_user_can_save_missing_spreadsheet_domains(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole(UserRole::DilpCoordinator->value);
        $project = $this->project($user);

        Livewire::actingAs($user)
            ->test(SpreadsheetParityDetails::class, ['projectId' => $project->id])
            ->set('funding.adl_nta_number', 'ADL-2026-001')
            ->set('funding.fund_sponsor', 'Test Sponsor')
            ->set('proponent.abbreviation', 'TSP')
            ->set('dis.proposal_status', 'For Updating')
            ->set('beneficiaryMetric.total_beneficiaries', 100)
            ->set('beneficiaryMetric.female_beneficiaries', 60)
            ->set('beneficiaryMetric.female_assistance_amount', 500000)
            ->set('moa.received_at', '2026-09-01')
            ->call('saveCoreDetails')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('project_funding_details', ['project_id'=>$project->id,'adl_nta_number'=>'ADL-2026-001']);
        $this->assertDatabaseHas('project_beneficiary_metrics', ['project_id'=>$project->id,'total_beneficiaries'=>100,'female_beneficiaries'=>60]);
        $this->assertDatabaseHas('project_moa_records', ['project_id'=>$project->id,'received_at'=>'2026-09-01']);
    }

    public function test_repeated_endorsements_lcom_gpai_and_stage_metrics_are_preserved(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole(UserRole::Focal->value);
        $project = $this->project($user);

        Livewire::actingAs($user)
            ->test(SpreadsheetParityDetails::class, ['projectId'=>$project->id])
            ->set('endorsement.sequence_no', 1)
            ->set('endorsement.endorsement_date', '2026-08-01')
            ->call('addEndorsement')
            ->set('communication.type', 'LCOM')
            ->set('communication.status', 'open')
            ->set('communication.issued_at', '2026-08-05')
            ->call('addCommunication')
            ->set('gpai.beneficiaries_enrolled', 50)
            ->set('gpai.gpai_amount', 2500)
            ->call('addGpai')
            ->set('stageMetric.stage', 'obligated')
            ->set('stageMetric.beneficiary_count', 100)
            ->set('stageMetric.amount', 1000000)
            ->call('addStageMetric');

        $this->assertDatabaseCount('project_endorsements', 1);
        $this->assertDatabaseCount('project_compliance_communications', 1);
        $this->assertDatabaseCount('project_gpai_records', 1);
        $this->assertDatabaseCount('project_stage_metrics', 1);
    }


    public function test_extended_sector_program_reporting_and_post_implementation_metrics_are_stored(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole(UserRole::DilpCoordinator->value);
        $project = $this->project($user);

        Livewire::actingAs($user)
            ->test(SpreadsheetParityDetails::class, ['projectId'=>$project->id])
            ->set('sectorMetric.sector_name', 'PWD')
            ->set('sectorMetric.beneficiary_count', 10)
            ->set('sectorMetric.female_count', 6)
            ->set('sectorMetric.assistance_amount', 120000)
            ->call('addSectorMetric')
            ->set('livelihoodMetric.livelihood_name', 'Baking')
            ->set('livelihoodMetric.beneficiary_count', 10)
            ->set('livelihoodMetric.assistance_amount', 120000)
            ->call('addLivelihoodMetric')
            ->set('specialMetric.program_name', 'YAKAP PROGRAM')
            ->set('specialMetric.beneficiary_count', 10)
            ->call('addSpecialMetric')
            ->set('reportingInclusion.report_type', 'CQPR')
            ->set('reportingInclusion.quarter', 3)
            ->set('reportingInclusion.status', 'reported')
            ->call('addReportingInclusion')
            ->set('postImplementation.status', 'received')
            ->set('postImplementation.received_at', '2026-09-01')
            ->call('addPostImplementation')
            ->set('convergenceMetric.initiative_name', 'TAV')
            ->set('convergenceMetric.beneficiary_count', 5)
            ->call('addConvergenceMetric')
            ->set('statusSnapshot.dimension', 'TSSD LEVEL')
            ->set('statusSnapshot.status', 'For Approval')
            ->call('addStatusSnapshot');

        $this->assertDatabaseHas('project_sector_metrics', ['project_id'=>$project->id,'sector_name'=>'PWD']);
        $this->assertDatabaseHas('project_livelihood_metrics', ['project_id'=>$project->id,'livelihood_name'=>'Baking']);
        $this->assertDatabaseHas('project_special_program_metrics', ['project_id'=>$project->id,'program_name'=>'YAKAP PROGRAM']);
        $this->assertDatabaseHas('project_reporting_inclusions', ['project_id'=>$project->id,'report_type'=>'CQPR','quarter'=>3]);
        $this->assertDatabaseHas('project_post_implementation_records', ['project_id'=>$project->id,'status'=>'received']);
        $this->assertDatabaseHas('project_convergence_metrics', ['project_id'=>$project->id,'initiative_name'=>'TAV']);
        $this->assertDatabaseHas('project_status_snapshots', ['project_id'=>$project->id,'dimension'=>'TSSD LEVEL']);
    }

    private function project(User $creator): Project
    {
        $proponent = Proponent::create(['type'=>'individual','name'=>'Phase 10 Proponent','is_active'=>true]);
        return Project::create([
            'fiscal_year'=>2026,
            'project_code'=>'P10-'.uniqid(),
            'title'=>'Phase 10 Project',
            'proponent_id'=>$proponent->id,
            'project_type_id'=>ProjectType::query()->firstOrFail()->id,
            'project_purpose_id'=>ProjectPurpose::query()->firstOrFail()->id,
            'record_status'=>'draft',
            'created_by'=>$creator->id,
            'updated_by'=>$creator->id,
        ]);
    }
}
