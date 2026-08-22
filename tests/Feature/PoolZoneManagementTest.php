<?php

namespace Tests\Feature;

use App\Models\PoolLane;
use App\Models\PoolZone;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PoolZoneManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_update_disable_and_safe_delete_pool_zone(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::where('email', 'admin@greecya.local')->firstOrFail();

        $this->actingAs($admin)
            ->post('/admin/pool/zones', [
                'action' => 'create',
                'name' => 'Тестовый бассейн',
                'code' => 'TESTPOOL',
                'type' => 'pool',
                'capacity' => 20,
                'is_active' => 1,
            ])
            ->assertRedirect();

        $zone = PoolZone::where('code', 'TESTPOOL')->firstOrFail();
        $this->assertTrue($zone->is_active);

        $this->actingAs($admin)
            ->post('/admin/pool/zones', [
                'action' => 'update',
                'zone_id' => $zone->id,
                'name' => 'Тестовый бассейн 25 м',
                'code' => 'TESTPOOL25',
                'type' => 'pool',
                'capacity' => 30,
            ])
            ->assertRedirect();

        $zone->refresh();
        $this->assertSame('Тестовый бассейн 25 м', $zone->name);
        $this->assertSame('TESTPOOL25', $zone->code);
        $this->assertSame(30, $zone->capacity);
        $this->assertFalse($zone->is_active);

        $this->actingAs($admin)
            ->post('/admin/pool/zones', [
                'action' => 'delete',
                'zone_id' => $zone->id,
            ])
            ->assertRedirect();

        $this->assertSoftDeleted('pool_zones', ['id' => $zone->id]);
    }

    public function test_pool_zone_with_lanes_is_safe_deleted_together_with_lanes(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::where('email', 'admin@greecya.local')->firstOrFail();

        $zone = PoolZone::create([
            'name' => 'Защищённый бассейн',
            'code' => 'PROTECTEDPOOL',
            'type' => 'pool',
            'capacity' => 20,
            'is_active' => true,
        ]);

        $lane = PoolLane::create([
            'pool_zone_id' => $zone->id,
            'name' => 'Дорожка 1',
            'number' => 1,
            'length_meters' => 25,
            'capacity' => 6,
            'status' => 'open',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->post('/admin/pool/zones', [
                'action' => 'delete',
                'zone_id' => $zone->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSoftDeleted('pool_zones', ['id' => $zone->id]);
        $this->assertSoftDeleted('pool_lanes', ['id' => $lane->id]);
        $this->assertDatabaseHas('pool_lanes', [
            'id' => $lane->id,
            'deleted_with_zone' => 1,
            'status' => 'closed',
            'is_active' => 0,
        ]);
    }

    public function test_pool_page_shows_zone_management_controls(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::where('email', 'admin@greecya.local')->firstOrFail();

        $this->actingAs($admin)
            ->get('/admin/pool')
            ->assertOk()
            ->assertSee('Добавить бассейн / зону')
            ->assertSee('Редактировать бассейн / зону')
            ->assertSee('Удалить');
    }
}
