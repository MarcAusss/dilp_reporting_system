<?php

namespace Tests\Feature\Administration;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\SpreadsheetParityService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpreadsheetParityAuditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_manifest_accounts_for_every_working_sheet_header(): void
    {
        $service = app(SpreadsheetParityService::class);

        $this->assertSame(20, $service->summary()['sheet_count']);
        $this->assertSame(310, $service->workingSheetFieldCount());
        $this->assertSame(0, $service->summary()['unclassified_count']);
        $this->assertFalse($service->hasUnclassifiedFields());
        $this->assertGreaterThanOrEqual(382, $service->summary()['audited_field_count']);
    }

    public function test_super_admin_can_open_and_export_spreadsheet_parity_audit(): void
    {
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole(UserRole::SuperAdmin->value);

        $this->actingAs($admin)
            ->get(route('spreadsheet-parity.index'))
            ->assertOk()
            ->assertSee('Spreadsheet Parity Audit')
            ->assertSee('310 labeled fields');

        $this->actingAs($admin)
            ->get(route('spreadsheet-parity.export'))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_operational_user_cannot_open_admin_spreadsheet_parity_audit(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole(UserRole::GIP->value);

        $this->actingAs($user)
            ->get(route('spreadsheet-parity.index'))
            ->assertForbidden();
    }
}
