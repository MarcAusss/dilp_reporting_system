<?php

namespace Tests\Feature\Administration;

use App\Enums\DataImportRowStatus;
use App\Enums\DataImportStatus;
use App\Enums\UserRole;
use App\Models\DataImportBatch;
use App\Models\DataImportRow;
use App\Models\Project;
use App\Models\ProjectPurpose;
use App\Models\ProjectType;
use App\Models\Proponent;
use App\Models\User;
use App\Services\Imports\LegacyProjectImportService;
use App\Services\Imports\LegacySpreadsheetReader;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegacyImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed([RolePermissionSeeder::class, MasterDataSeeder::class]);
    }

    public function test_csv_reader_extracts_headers_and_rows(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'dilp_csv_');
        file_put_contents($path, "FY,Project Title,Proponent,Project Type,Project Purpose\n2026,Test Project,Test Group,Individual,Formation\n");

        try {
            $result = app(LegacySpreadsheetReader::class)->read($path, 'csv');
            $this->assertSame(['FY', 'Project Title', 'Proponent', 'Project Type', 'Project Purpose'], $result['headers']);
            $this->assertCount(1, $result['rows']);
            $this->assertSame('Test Project', $result['rows'][0]['data']['Project Title']);
        } finally {
            @unlink($path);
        }
    }

    public function test_validated_legacy_row_can_create_project_without_overwriting_existing_data(): void
    {
        $user = $this->superAdmin();
        $batch = $this->batch([
            ['FY' => '2026', 'Code' => 'LEG-001', 'Title' => 'Imported Project', 'Proponent' => 'Imported Group', 'Type' => 'Individual', 'Purpose' => 'Formation', 'EMT' => '100000'],
        ]);

        $service = app(LegacyProjectImportService::class);
        $batch = $service->validateBatch($batch, $this->map());

        $this->assertSame(DataImportStatus::Ready, $batch->status);
        $this->assertSame(1, $batch->valid_rows);
        $this->assertTrue($batch->canCommit());

        $batch = $service->commit($batch, $user->id);

        $this->assertSame(DataImportStatus::Completed, $batch->status);
        $this->assertSame(1, $batch->imported_rows);
        $this->assertDatabaseHas('projects', [
            'fiscal_year' => 2026,
            'project_code' => 'LEG-001',
            'title' => 'Imported Project',
        ]);
        $project = Project::query()->where('project_code', 'LEG-001')->firstOrFail();
        $this->assertSame(100000.0, $project->financial->doleShare());
    }

    public function test_duplicate_rows_are_flagged_and_skipped_while_new_rows_can_still_commit(): void
    {
        $user = $this->superAdmin();
        $type = ProjectType::query()->where('name', 'Individual')->firstOrFail();
        $purpose = ProjectPurpose::query()->where('name', 'Formation')->firstOrFail();
        $proponent = Proponent::query()->create(['type' => 'group', 'name' => 'Existing Group', 'is_active' => true]);
        Project::query()->create([
            'fiscal_year' => 2026,
            'project_code' => 'EXIST-001',
            'title' => 'Existing Project',
            'proponent_id' => $proponent->id,
            'project_type_id' => $type->id,
            'project_purpose_id' => $purpose->id,
            'record_status' => 'active',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $batch = $this->batch([
            ['FY' => '2026', 'Code' => 'EXIST-001', 'Title' => 'Existing Project', 'Proponent' => 'Existing Group', 'Type' => 'Individual', 'Purpose' => 'Formation', 'EMT' => '0'],
            ['FY' => '2026', 'Code' => 'NEW-002', 'Title' => 'New Project', 'Proponent' => 'New Group', 'Type' => 'Individual', 'Purpose' => 'Formation', 'EMT' => '50000'],
        ]);

        $service = app(LegacyProjectImportService::class);
        $batch = $service->validateBatch($batch, $this->map());

        $this->assertSame(DataImportStatus::Ready, $batch->status);
        $this->assertSame(1, $batch->duplicate_rows);
        $this->assertSame(1, $batch->valid_rows);
        $this->assertTrue($batch->canCommit());

        $batch = $service->commit($batch, $user->id);
        $this->assertSame(1, $batch->imported_rows);
        $this->assertSame(1, $batch->skipped_rows);
        $this->assertDatabaseCount('projects', 2);
        $this->assertDatabaseHas('data_import_rows', ['batch_id' => $batch->id, 'status' => DataImportRowStatus::Duplicate->value]);
    }

    private function superAdmin(): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole(UserRole::SuperAdmin->value);
        return $user;
    }

    private function batch(array $rows): DataImportBatch
    {
        $batch = DataImportBatch::query()->create([
            'original_filename' => 'legacy.csv',
            'stored_path' => 'legacy-imports/test.csv',
            'file_type' => 'csv',
            'status' => 'uploaded',
            'total_rows' => count($rows),
            'headers' => array_keys($rows[0]),
            'header_map' => $this->map(),
        ]);

        foreach ($rows as $index => $data) {
            $batch->rows()->create([
                'source_sheet' => 'CSV',
                'row_number' => $index + 2,
                'raw_data' => $data,
                'status' => 'pending',
            ]);
        }

        return $batch;
    }

    private function map(): array
    {
        return [
            'FY' => 'fiscal_year',
            'Code' => 'project_code',
            'Title' => 'title',
            'Proponent' => 'proponent_name',
            'Type' => 'project_type',
            'Purpose' => 'project_purpose',
            'EMT' => 'equipment_materials_tools',
        ];
    }
}
