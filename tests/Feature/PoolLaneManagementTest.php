<?php

namespace Tests\Feature;

use App\Models\MaintenanceTask;
use App\Models\PoolLane;
use App\Models\PoolZone;
use App\Models\ScheduleSlot;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PoolLaneManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_safe_delete_empty_lane(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::where('email', 'admin@greecya.local')->firstOrFail();

        $zone = PoolZone::create([
            'name' => 'Бассейн для удаления дорожки',
            'code' => 'LANEDELETEPOOL',
            'type' => 'pool',
            'capacity' => 20,
            'is_active' => true,
        ]);

        $lane = PoolLane::create([
            'pool_zone_id' => $zone->id,
            'name' => 'Временная дорожка',
            'number' => 91,
            'length_meters' => 25,
            'capacity' => 6,
            'status' => 'open',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->post('/admin/pool/lanes', [
                'action' => 'delete',
                'lane_id' => $lane->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSoftDeleted('pool_lanes', ['id' => $lane->id]);
    }

    public function test_lane_with_maintenance_history_can_be_safe_deleted_and_history_is_preserved(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::where('email', 'admin@greecya.local')->firstOrFail();

        $zone = PoolZone::create([
            'name' => 'Защищённый бассейн дорожки',
            'code' => 'LANEPROTECTEDPOOL',
            'type' => 'pool',
            'capacity' => 20,
            'is_active' => true,
        ]);

        $lane = PoolLane::create([
            'pool_zone_id' => $zone->id,
            'name' => 'Историческая дорожка',
            'number' => 92,
            'length_meters' => 25,
            'capacity' => 6,
            'status' => 'closed',
            'is_active' => false,
        ]);

        $task = MaintenanceTask::create([
            'pool_zone_id' => $zone->id,
            'pool_lane_id' => $lane->id,
            'assigned_to' => $admin->id,
            'title' => 'Проверка дорожки',
            'type' => 'inspection',
            'status' => 'completed',
        ]);

        $this->actingAs($admin)
            ->post('/admin/pool/lanes', [
                'action' => 'delete',
                'lane_id' => $lane->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSoftDeleted('pool_lanes', ['id' => $lane->id]);
        $this->assertDatabaseHas('maintenance_tasks', [
            'id' => $task->id,
            'pool_lane_id' => $lane->id,
        ]);
    }

    public function test_lane_assigned_to_schedule_slot_can_be_safe_deleted_without_losing_pivot_history(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::where('email', 'admin@greecya.local')->firstOrFail();
        $slot = ScheduleSlot::firstOrFail();
        $zone = PoolZone::firstOrFail();

        $lane = PoolLane::create([
            'pool_zone_id' => $zone->id,
            'name' => 'Дорожка с сеансом',
            'number' => 93,
            'length_meters' => 25,
            'capacity' => 6,
            'status' => 'open',
            'is_active' => true,
        ]);

        $slot->lanes()->attach($lane->id, ['capacity' => 6]);

        $this->actingAs($admin)
            ->post('/admin/pool/lanes', [
                'action' => 'delete',
                'lane_id' => $lane->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSoftDeleted('pool_lanes', ['id' => $lane->id]);
        $this->assertDatabaseHas('schedule_slot_lane', [
            'schedule_slot_id' => $slot->id,
            'pool_lane_id' => $lane->id,
        ]);
    }

    public function test_pool_page_loads_lane_safe_delete_control(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::where('email', 'admin@greecya.local')->firstOrFail();

        $this->actingAs($admin)
            ->get('/admin/pool')
            ->assertOk()
            ->assertSee('js/pool-lane-delete.js');
    }
}
