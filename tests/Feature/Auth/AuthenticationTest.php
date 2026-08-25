<?php

namespace Tests\Feature\Auth;

use App\Enums\PermissionName;
use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_guest_can_view_login_page(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
        $response->assertViewIs('auth.login');
    }

    public function test_active_user_can_authenticate(): void
    {
        $user = User::factory()->create([
            'email' => 'gip@example.gov.ph',
            'password' => 'Password123!',
            'is_active' => true,
        ]);

        $user->assignRole(UserRole::GIP->value);

        $response = $this->post('/login', [
            'email' => 'gip@example.gov.ph',
            'password' => 'Password123!',
        ]);

        $response->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);
    }

    public function test_inactive_user_cannot_authenticate(): void
    {
        $user = User::factory()->create([
            'email' => 'inactive@example.gov.ph',
            'password' => 'Password123!',
            'is_active' => false,
        ]);

        $user->assignRole(UserRole::Focal->value);

        $response = $this->post('/login', [
            'email' => 'inactive@example.gov.ph',
            'password' => 'Password123!',
        ]);

        $response->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_invalid_password_is_rejected(): void
    {
        User::factory()->create([
            'email' => 'user@example.gov.ph',
            'password' => 'Password123!',
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'email' => 'user@example.gov.ph',
            'password' => 'WrongPassword!',
        ]);

        $response->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_guest_is_redirected_from_dashboard(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
    }

    public function test_authorized_user_can_view_dashboard(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
        ]);

        $user->assignRole(UserRole::DilpCoordinator->value);

        $response = $this
            ->actingAs($user)
            ->get('/dashboard');

        $response->assertOk();
    }

    public function test_user_without_dashboard_permission_is_forbidden(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/dashboard');

        $response->assertForbidden();
    }

    public function test_super_admin_has_global_permission_access(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
        ]);

        $user->assignRole(UserRole::SuperAdmin->value);

        $this->assertTrue(
            $user->can(PermissionName::UsersView->value)
        );

        $this->assertTrue(
            $user->can('some.future.permission')
        );
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
        ]);

        $this->actingAs($user);

        $response = $this->post('/logout');

        $response->assertRedirect('/login');

        $this->assertGuest();
    }
}