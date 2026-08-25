<?php

namespace Tests\Feature\Project;

use App\Enums\ProjectRecordStatus;
use App\Enums\UserRole;
use App\Livewire\Projects\Index;
use App\Models\Barangay;
use App\Models\Municipality;
use App\Models\Project;
use App\Models\ProjectPurpose;
use App\Models\ProjectType;
use App\Models\Province;
use App\Models\User;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectRegistryTest extends TestCase
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

    public function test_super_admin_can_view_project_registry(): void
    {
        $user = $this->superAdmin();

        $response = $this
            ->actingAs($user)
            ->get('/projects');

        $response->assertOk();
        $response->assertSee('Project Registry');
    }

    public function test_gip_can_view_project_registry(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
        ]);

        $user->assignRole(
            UserRole::GIP->value
        );

        $response = $this
            ->actingAs($user)
            ->get('/projects');

        $response->assertOk();
    }

    public function test_super_admin_can_create_project(): void
    {
        $user = $this->superAdmin();

        [
            $province,
            $municipality,
            $barangay,
        ] = $this->locationHierarchy();

        $projectType =
            ProjectType::query()
                ->where('code', 'INDIVIDUAL')
                ->firstOrFail();

        $projectPurpose =
            ProjectPurpose::query()
                ->where('code', 'FORMATION')
                ->firstOrFail();

        Livewire::actingAs($user)
            ->test(Index::class)
            ->call('startCreate')
            ->set('fiscal_year', 2026)
            ->set(
                'project_code',
                'DILP-TEST-001'
            )
            ->set(
                'title',
                'Test Livelihood Project'
            )
            ->set(
                'proponent_type',
                'individual'
            )
            ->set(
                'proponent_name',
                'Juan Dela Cruz'
            )
            ->set(
                'project_type_id',
                $projectType->id
            )
            ->set(
                'project_purpose_id',
                $projectPurpose->id
            )
            ->set(
                'province_id',
                $province->id
            )
            ->set(
                'municipality_id',
                $municipality->id
            )
            ->set(
                'barangay_id',
                $barangay->id
            )
            ->set(
                'record_status',
                'draft'
            )
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas(
            'projects',
            [
                'fiscal_year' => 2026,
                'project_code' => 'DILP-TEST-001',
                'title' => 'Test Livelihood Project',
            ]
        );

        $this->assertDatabaseHas(
            'proponents',
            [
                'name' => 'Juan Dela Cruz',
            ]
        );
    }

    public function test_project_receives_registry_number(): void
    {
        $user = $this->superAdmin();

        [$province] = $this->locationHierarchy();

        $projectType =
            ProjectType::query()->firstOrFail();

        $projectPurpose =
            ProjectPurpose::query()->firstOrFail();

        Livewire::actingAs($user)
            ->test(Index::class)
            ->call('startCreate')
            ->set('fiscal_year', 2026)
            ->set('title', 'Registry Test')
            ->set(
                'proponent_name',
                'Registry Proponent'
            )
            ->set(
                'project_type_id',
                $projectType->id
            )
            ->set(
                'project_purpose_id',
                $projectPurpose->id
            )
            ->set(
                'province_id',
                $province->id
            )
            ->call('save');

        $project = Project::query()
            ->firstOrFail();

        $this->assertNotNull(
            $project->registry_number
        );

        $this->assertStringStartsWith(
            'DILP-2026-',
            $project->registry_number
        );
    }

    public function test_project_code_can_be_null(): void
    {
        $user = $this->superAdmin();

        [$province] = $this->locationHierarchy();

        $projectType =
            ProjectType::query()->firstOrFail();

        $projectPurpose =
            ProjectPurpose::query()->firstOrFail();

        Livewire::actingAs($user)
            ->test(Index::class)
            ->call('startCreate')
            ->set('fiscal_year', 2026)
            ->set('project_code', '')
            ->set('title', 'Uncoded Project')
            ->set(
                'proponent_name',
                'Test Proponent'
            )
            ->set(
                'project_type_id',
                $projectType->id
            )
            ->set(
                'project_purpose_id',
                $projectPurpose->id
            )
            ->set(
                'province_id',
                $province->id
            )
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas(
            'projects',
            [
                'title' => 'Uncoded Project',
                'project_code' => null,
            ]
        );
    }

    public function test_invalid_location_hierarchy_is_rejected(): void
    {
        $user = $this->superAdmin();

        $provinceA = Province::create([
            'name' => 'Province A',
            'is_active' => true,
        ]);

        $provinceB = Province::create([
            'name' => 'Province B',
            'is_active' => true,
        ]);

        $municipality = Municipality::create([
            'province_id' => $provinceB->id,
            'name' => 'Municipality B',
            'type' => 'municipality',
            'is_active' => true,
        ]);

        $projectType =
            ProjectType::query()->firstOrFail();

        $projectPurpose =
            ProjectPurpose::query()->firstOrFail();

        Livewire::actingAs($user)
            ->test(Index::class)
            ->call('startCreate')
            ->set('title', 'Invalid Location')
            ->set(
                'proponent_name',
                'Test Proponent'
            )
            ->set(
                'project_type_id',
                $projectType->id
            )
            ->set(
                'project_purpose_id',
                $projectPurpose->id
            )
            ->set(
                'province_id',
                $provinceA->id
            )
            ->set(
                'municipality_id',
                $municipality->id
            )
            ->call('save')
            ->assertHasErrors(
                'municipality_id'
            );
    }

    public function test_super_admin_can_archive_project(): void
    {
        $user = $this->superAdmin();

        [$province] = $this->locationHierarchy();

        $project = $this->makeProject(
            $user,
            $province
        );

        Livewire::actingAs($user)
            ->test(Index::class)
            ->call(
                'toggleArchive',
                $project->id
            );

        $this->assertDatabaseHas(
            'projects',
            [
                'id' => $project->id,

                'record_status' =>
                    ProjectRecordStatus::Archived->value,
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

    private function locationHierarchy(): array
    {
        $province = Province::create([
            'name' => 'Test Province',
            'is_active' => true,
        ]);

        $municipality = Municipality::create([
            'province_id' => $province->id,
            'name' => 'Test Municipality',
            'type' => 'municipality',
            'is_active' => true,
        ]);

        $barangay = Barangay::create([
            'municipality_id' =>
                $municipality->id,

            'name' => 'Test Barangay',
            'is_active' => true,
        ]);

        return [
            $province,
            $municipality,
            $barangay,
        ];
    }

    private function makeProject(
        User $user,
        Province $province
    ): Project {
        $projectType =
            ProjectType::query()->firstOrFail();

        $projectPurpose =
            ProjectPurpose::query()->firstOrFail();

        $proponent =
            \App\Models\Proponent::create([
                'type' => 'individual',
                'name' => 'Test Proponent',
                'is_active' => true,
            ]);

        $project = Project::create([
            'fiscal_year' => 2026,
            'title' => 'Test Project',
            'proponent_id' => $proponent->id,
            'project_type_id' =>
                $projectType->id,
            'project_purpose_id' =>
                $projectPurpose->id,
            'record_status' => 'active',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $project->locations()->create([
            'province_id' => $province->id,
            'is_primary' => true,
        ]);

        return $project;
    }
}