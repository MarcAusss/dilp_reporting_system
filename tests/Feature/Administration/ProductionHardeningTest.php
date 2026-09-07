<?php

namespace Tests\Feature\Administration;

use App\Enums\PermissionName;
use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_security_headers_are_added_to_web_responses(): void
    {
        $response = $this->get('/');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_only_super_admin_receives_import_and_audit_permissions(): void
    {
        $superAdmin = User::factory()->create(['is_active' => true]);
        $superAdmin->assignRole(UserRole::SuperAdmin->value);
        $focal = User::factory()->create(['is_active' => true]);
        $focal->assignRole(UserRole::Focal->value);

        $this->assertTrue($superAdmin->can(PermissionName::DataImportsManage->value));
        $this->assertTrue($superAdmin->can(PermissionName::AuditLogsView->value));
        $this->assertFalse($focal->can(PermissionName::DataImportsManage->value));
        $this->assertFalse($focal->can(PermissionName::AuditLogsView->value));
        $this->assertTrue($focal->can(PermissionName::FundTargetsView->value));
        $this->assertTrue($focal->can(PermissionName::BeneficiariesView->value));
    }
}
