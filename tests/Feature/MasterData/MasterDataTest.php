<?php

namespace Tests\Feature\MasterData;

use App\Enums\UserRole;
use App\Livewire\MasterData\Index;
use App\Models\ProjectPurpose;
use App\Models\ProjectType;
use App\Models\User;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MasterDataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_super_admin_can_view_master_data_page(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
        ]);

        $user->assignRole(UserRole::SuperAdmin->value);

        $response = $this
            ->actingAs($user)
            ->get('/administration/master-data');

        $response->assertOk();
        $response->assertSee('Master Data');
        $response->assertSee('Project Types');
    }

    public function test_gip_cannot_view_master_data_page(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
        ]);

        $user->assignRole(UserRole::GIP->value);

        $response = $this
            ->actingAs($user)
            ->get('/administration/master-data');

        $response->assertForbidden();
    }

    public function test_master_data_seeder_creates_project_types(): void
    {
        $this->seed(MasterDataSeeder::class);

        $this->assertDatabaseHas('project_types', [
            'code' => 'INDIVIDUAL',
            'name' => 'Individual',
        ]);

        $this->assertDatabaseHas('project_types', [
            'code' => 'GROUP',
            'name' => 'Group',
        ]);
    }

    public function test_master_data_seeder_creates_project_purposes(): void
    {
        $this->seed(MasterDataSeeder::class);

        $this->assertDatabaseHas('project_purposes', [
            'code' => 'FORMATION',
            'name' => 'Formation',
        ]);

        $this->assertDatabaseHas('project_purposes', [
            'code' => 'ENHANCEMENT',
            'name' => 'Enhancement',
        ]);
    }

    public function test_super_admin_can_create_project_type(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
        ]);

        $user->assignRole(UserRole::SuperAdmin->value);

        Livewire::actingAs($user)
            ->test(Index::class)
            ->call('startCreate')
            ->set('name', 'Test Project Type')
            ->set('code', 'TEST-TYPE')
            ->set('description', 'Created by automated test.')
            ->set('sort_order', 30)
            ->set('is_active', true)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('project_types', [
            'name' => 'Test Project Type',
            'code' => 'TEST-TYPE',
            'is_active' => true,
        ]);
    }

    public function test_super_admin_can_update_project_type(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
        ]);

        $user->assignRole(UserRole::SuperAdmin->value);

        $projectType = ProjectType::create([
            'code' => 'TEST',
            'name' => 'Original Name',
            'is_active' => true,
            'sort_order' => 10,
        ]);

        Livewire::actingAs($user)
            ->test(Index::class)
            ->call('edit', $projectType->id)
            ->set('name', 'Updated Name')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('project_types', [
            'id' => $projectType->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_super_admin_can_deactivate_master_data(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
        ]);

        $user->assignRole(UserRole::SuperAdmin->value);

        $projectType = ProjectType::create([
            'code' => 'TEMP',
            'name' => 'Temporary Type',
            'is_active' => true,
            'sort_order' => 10,
        ]);

        Livewire::actingAs($user)
            ->test(Index::class)
            ->call('toggleStatus', $projectType->id);

        $this->assertDatabaseHas('project_types', [
            'id' => $projectType->id,
            'is_active' => false,
        ]);
    }
}