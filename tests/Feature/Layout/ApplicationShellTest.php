<?php

namespace Tests\Feature\Layout;

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationShellTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_gip_user_can_render_application_shell(): void
    {
        $user = User::factory()->create([
            'name' => 'GIP Test User',
            'is_active' => true,
        ]);

        $user->assignRole(UserRole::GIP->value);

        $response = $this
            ->actingAs($user)
            ->get('/dashboard');

        $response->assertOk();

        $response->assertSee('DILP Reporting');
        $response->assertSee('Dashboard');
        $response->assertSee('GIP');
        $response->assertSee('Project Management');
        $response->assertSee('Monitoring');
    }

    public function test_super_admin_can_see_administration_navigation(): void
    {
        $user = User::factory()->create([
            'name' => 'Super Admin User',
            'is_active' => true,
        ]);

        $user->assignRole(UserRole::SuperAdmin->value);

        $response = $this
            ->actingAs($user)
            ->get('/dashboard');

        $response->assertOk();

        $response->assertSee('Super Admin');
        $response->assertSee('Administration');
        $response->assertSee('Users');
        $response->assertSee('Master Data');
        $response->assertSee('Audit Logs');
    }

    public function test_gip_user_does_not_see_administration_navigation(): void
    {
        $user = User::factory()->create([
            'name' => 'GIP User',
            'is_active' => true,
        ]);

        $user->assignRole(UserRole::GIP->value);

        $response = $this
            ->actingAs($user)
            ->get('/dashboard');

        $response->assertOk();

        $response->assertDontSee('Administration');
        $response->assertDontSee('Audit Logs');
    }

    public function test_focal_role_is_displayed_correctly(): void
    {
        $user = User::factory()->create([
            'name' => 'Focal User',
            'is_active' => true,
        ]);

        $user->assignRole(UserRole::Focal->value);

        $response = $this
            ->actingAs($user)
            ->get('/dashboard');

        $response->assertOk();
        $response->assertSee('Focal');
    }

    public function test_dilp_coordinator_role_is_displayed_correctly(): void
    {
        $user = User::factory()->create([
            'name' => 'Coordinator User',
            'is_active' => true,
        ]);

        $user->assignRole(
            UserRole::DilpCoordinator->value
        );

        $response = $this
            ->actingAs($user)
            ->get('/dashboard');

        $response->assertOk();
        $response->assertSee('DILP Coordinator');
    }
}