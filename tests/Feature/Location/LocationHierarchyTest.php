<?php

namespace Tests\Feature\Location;

use App\Enums\UserRole;
use App\Livewire\Locations\Index;
use App\Models\Barangay;
use App\Models\Municipality;
use App\Models\Province;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LocationHierarchyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $this->seed(
            RolePermissionSeeder::class
        );
    }

    public function test_super_admin_can_view_location_page(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
        ]);

        $user->assignRole(
            UserRole::SuperAdmin->value
        );

        $response = $this
            ->actingAs($user)
            ->get('/administration/locations');

        $response->assertOk();

        $response->assertSee(
            'Location Hierarchy'
        );
    }

    public function test_gip_cannot_view_location_page(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
        ]);

        $user->assignRole(
            UserRole::GIP->value
        );

        $response = $this
            ->actingAs($user)
            ->get('/administration/locations');

        $response->assertForbidden();
    }

    public function test_super_admin_can_create_province(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
        ]);

        $user->assignRole(
            UserRole::SuperAdmin->value
        );

        Livewire::actingAs($user)
            ->test(Index::class)
            ->call('startCreate')
            ->set(
                'name',
                'Test Province'
            )
            ->set(
                'code',
                'TEST-PROV'
            )
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas(
            'provinces',
            [
                'name' => 'Test Province',
                'code' => 'TEST-PROV',
            ]
        );
    }

    public function test_super_admin_can_create_municipality(): void
    {
        $province = Province::create([
            'name' => 'Test Province',
            'code' => 'TP',
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'is_active' => true,
        ]);

        $user->assignRole(
            UserRole::SuperAdmin->value
        );

        Livewire::actingAs($user)
            ->test(Index::class)
            ->call(
                'selectLevel',
                'municipality'
            )
            ->call('startCreate')
            ->set(
                'province_id',
                $province->id
            )
            ->set(
                'name',
                'Test Municipality'
            )
            ->set(
                'type',
                'municipality'
            )
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas(
            'municipalities',
            [
                'province_id' => $province->id,
                'name' => 'Test Municipality',
                'type' => 'municipality',
            ]
        );
    }

    public function test_super_admin_can_create_city(): void
    {
        $province = Province::create([
            'name' => 'Test Province',
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'is_active' => true,
        ]);

        $user->assignRole(
            UserRole::SuperAdmin->value
        );

        Livewire::actingAs($user)
            ->test(Index::class)
            ->call(
                'selectLevel',
                'municipality'
            )
            ->call('startCreate')
            ->set(
                'province_id',
                $province->id
            )
            ->set(
                'name',
                'Test City'
            )
            ->set(
                'type',
                'city'
            )
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas(
            'municipalities',
            [
                'name' => 'Test City',
                'type' => 'city',
            ]
        );
    }

    public function test_super_admin_can_create_barangay(): void
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

        $user = User::factory()->create([
            'is_active' => true,
        ]);

        $user->assignRole(
            UserRole::SuperAdmin->value
        );

        Livewire::actingAs($user)
            ->test(Index::class)
            ->call(
                'selectLevel',
                'barangay'
            )
            ->call('startCreate')
            ->set(
                'province_id',
                $province->id
            )
            ->set(
                'municipality_id',
                $municipality->id
            )
            ->set(
                'name',
                'Test Barangay'
            )
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas(
            'barangays',
            [
                'municipality_id' =>
                    $municipality->id,

                'name' =>
                    'Test Barangay',
            ]
        );
    }

    public function test_location_relationships_are_correct(): void
    {
        $province = Province::create([
            'name' => 'Province A',
            'is_active' => true,
        ]);

        $municipality = Municipality::create([
            'province_id' => $province->id,
            'name' => 'Municipality A',
            'type' => 'municipality',
            'is_active' => true,
        ]);

        $barangay = Barangay::create([
            'municipality_id' =>
                $municipality->id,

            'name' =>
                'Barangay A',

            'is_active' =>
                true,
        ]);

        $this->assertTrue(
            $province->municipalities
                ->contains($municipality)
        );

        $this->assertTrue(
            $municipality->barangays
                ->contains($barangay)
        );

        $this->assertTrue(
            $barangay->municipality
                ->province
                ->is($province)
        );
    }

    public function test_province_with_active_municipalities_cannot_be_deactivated(): void
    {
        $province = Province::create([
            'name' => 'Province A',
            'is_active' => true,
        ]);

        Municipality::create([
            'province_id' => $province->id,
            'name' => 'Municipality A',
            'type' => 'municipality',
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'is_active' => true,
        ]);

        $user->assignRole(
            UserRole::SuperAdmin->value
        );

        Livewire::actingAs($user)
            ->test(Index::class)
            ->call(
                'toggleStatus',
                $province->id
            );

        $this->assertDatabaseHas(
            'provinces',
            [
                'id' => $province->id,
                'is_active' => true,
            ]
        );
    }
}