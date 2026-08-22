<?php

namespace Tests\Feature;

use App\Models\MaintenanceTask;
use App\Models\PoolLane;
use App\Models\PoolZone;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PoolStructureSafeDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_soft_delete_pool_with_related_lane_and_history_then_restore_it(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::where('email', 'admin@greecya.local')->firstOrFail();

        $zone = PoolZone::create([
            'name' => 'Safe Delete Pool',
            'code' => 'SAFE-POOL',
            'type' => 'pool',
            'capacity' => 20,
            'is_active' => true,
        ]);

        $lane = PoolLane::create([
            'pool_zone_id' => $zone->id,
            'name' => 'Историческая дорожка',
            'number' => 91,
            'length_meters' => 25,
            'capacity' => 6,
            'status' => 'open',
            'is_active' => true,
        ]);

        $task = MaintenanceTask::create([
            'pool_zone_id' => $zone->id,
            'pool_lane_id' => $lane->id,
            'assigned_to' => $admin->id,
            'title' => 'Историческое ТО',
            'type' => 'maintenance',
            'status' => 'completed',
            'notes' => 'Должно сохраниться после safe delete',
        ]);

        $this->actingAs($admin)
            ->post('/admin/pool/zones', [
                'action' => 'delete',
                'zone_id' => $zone->id,
            ])
            ->assertRedirect();

        $this->assertSoftDeleted('pool_zones', ['id' => $zone->id]);
        $this->assertSoftDeleted('pool_lanes', ['id' => $lane->id]);
        $this->assertDatabaseHas('maintenance_tasks', [
            'id' => $task->id,
            'pool_zone_id' => $zone->id,
            'pool_lane_id' => $lane->id,
        ]);

        $task->refresh();
        $this->assertSame($zone->id, $task->zone?->id);
        $this->assertSame($lane->id, $task->lane?->id);
        $this->assertTrue($task->zone->trashed());
        $this->assertTrue($task->lane->trashed());

        $this->actingAs($admin)
            ->get('/admin/pool/archive')
            ->assertOk()
            ->assertSee('Safe Delete Pool')
            ->assertSee('Историческая дорожка');

        $this->actingAs($admin)
            ->post('/admin/pool/zones', [
                'action' => 'restore',
                'zone_id' => $zone->id,
            ])
            ->assertRedirect();

        $restoredZone = PoolZone::findOrFail($zone->id);
        $restoredLane = PoolLane::findOrFail($lane->id);

        $this->assertFalse($restoredZone->is_active);
        $this->assertFalse($restoredLane->is_active);
        $this->assertSame('closed', $restoredLane->status);
        $this->assertDatabaseHas('maintenance_tasks', ['id' => $task->id]);
    }

    public function test_admin_can_soft_delete_lane_even_when_it_has_related_history(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::where('email', 'admin@greecya.local')->firstOrFail();

        $zone = PoolZone::create([
            'name' => 'Lane Parent Pool',
            'code' => 'LANE-PARENT',
            'type' => 'pool',
            'capacity' => 20,
            'is_active' => true,
        ]);

        $lane = PoolLane::create([
            'pool_zone_id' => $zone->id,
            'name' => 'Safe Lane',
            'number' => 92,
            'length_meters' => 25,
            'capacity' => 6,
            'status' => 'open',
            'is_active' => true,
        ]);

        $task = MaintenanceTask::create([
            'pool_zone_id' => $zone->id,
            'pool_lane_id' => $lane->id,
            'assigned_to' => $admin->id,
            'title' => 'ТО дорожки',
            'type' => 'maintenance',
            'status' => 'open',
        ]);

        $this->actingAs($admin)
            ->post('/admin/pool/lanes', [
                'action' => 'delete',
                'lane_id' => $lane->id,
            ])
            ->assertRedirect();

        $this->assertSoftDeleted('pool_lanes', ['id' => $lane->id]);
        $this->assertDatabaseHas('maintenance_tasks', ['id' => $task->id, 'pool_lane_id' => $lane->id]);
        $this->assertSame($lane->id, $task->fresh()->lane?->id);

        $this->actingAs($admin)
            ->post('/admin/pool/lanes', [
                'action' => 'restore',
                'lane_id' => $lane->id,
            ])
            ->assertRedirect();

        $restored = PoolLane::findOrFail($lane->id);
        $this->assertFalse($restored->is_active);
        $this->assertSame('closed', $restored->status);
    }

    public function test_non_admin_cannot_safe_delete_pool_structure(): void
    {
        $this->seed(DatabaseSeeder::class);
        $manager = User::where('email', 'manager@greecya.local')->firstOrFail();

        $zone = PoolZone::create([
            'name' => 'Admin Only Pool',
            'code' => 'ADMIN-ONLY',
            'type' => 'pool',
            'capacity' => 20,
            'is_active' => true,
        ]);

        $lane = PoolLane::create([
            'pool_zone_id' => $zone->id,
            'name' => 'Admin Only Lane',
            'number' => 93,
            'length_meters' => 25,
            'capacity' => 6,
            'status' => 'open',
            'is_active' => true,
        ]);

        $this->actingAs($manager)
            ->post('/admin/pool/zones', ['action' => 'delete', 'zone_id' => $zone->id])
            ->assertForbidden();

        $this->actingAs($manager)
            ->post('/admin/pool/lanes', ['action' => 'delete', 'lane_id' => $lane->id])
            ->assertForbidden();

        $this->assertDatabaseHas('pool_zones', ['id' => $zone->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('pool_lanes', ['id' => $lane->id, 'deleted_at' => null]);
    }
}
