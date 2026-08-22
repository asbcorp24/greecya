<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\MaintenanceTask;
use App\Models\PoolLane;
use App\Models\PoolZone;
use App\Models\ScheduleSlot;
use App\Models\WaitlistEntry;
use App\Services\PoolMonitoringService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PoolController extends Controller
{
    public function index()
    {
        return view('admin.pool.index', [
            'zones' => PoolZone::with([
                'lanes',
                'waterLogs' => fn ($q) => $q->limit(10),
            ])->orderBy('name')->get(),
            'slots' => ScheduleSlot::with(['service', 'trainer', 'zone', 'lanes', 'waitlist.customer'])
                ->whereBetween('starts_at', [today(), now()->addDays(7)->endOfDay()])
                ->orderBy('starts_at')
                ->get(),
            'maintenance' => MaintenanceTask::with(['zone', 'lane'])
                ->orderByRaw("CASE WHEN status='open' THEN 0 ELSE 1 END")
                ->orderBy('due_at')
                ->limit(50)
                ->get(),
            'customers' => Customer::orderBy('name')->get(['id', 'name', 'phone']),
        ]);
    }

    public function storeZone(Request $request)
    {
        $action = $request->input('action', 'create');

        if ($action === 'delete') {
            $data = $request->validate([
                'zone_id' => ['required', 'integer', 'exists:pool_zones,id'],
            ]);

            $zone = PoolZone::findOrFail($data['zone_id']);
            $blockers = $this->zoneDeleteBlockers($zone);

            if ($blockers !== []) {
                return back()->withErrors([
                    'zone' => 'Удаление «'.$zone->name.'» запрещено: есть связанные данные ('.implode(', ', $blockers).'). Отключите бассейн вместо удаления, чтобы сохранить историю.',
                ]);
            }

            $name = $zone->name;
            $zone->delete();

            return back()->with('success', 'Бассейн / зона «'.$name.'» удалён.');
        }

        if ($action === 'update') {
            $zoneId = (int) $request->input('zone_id');
            $zone = PoolZone::findOrFail($zoneId);

            $data = $request->validate([
                'name' => ['required', 'string', 'max:190'],
                'code' => ['required', 'string', 'max:60', Rule::unique('pool_zones', 'code')->ignore($zone->id)],
                'type' => ['required', Rule::in(['pool', 'spa', 'kids_pool', 'other'])],
                'capacity' => ['required', 'integer', 'min:1', 'max:1000'],
            ]);

            $data['is_active'] = $request->boolean('is_active');
            $zone->update($data);

            return back()->with('success', 'Бассейн / зона обновлён.');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'code' => ['required', 'string', 'max:60', 'unique:pool_zones,code'],
            'type' => ['required', Rule::in(['pool', 'spa', 'kids_pool', 'other'])],
            'capacity' => ['required', 'integer', 'min:1', 'max:1000'],
        ]);

        $data['is_active'] = $request->boolean('is_active');
        PoolZone::create($data);

        return back()->with('success', 'Бассейн / зона создан.');
    }

    private function zoneDeleteBlockers(PoolZone $zone): array
    {
        $checks = [
            'дорожки' => fn () => $zone->lanes()->exists(),
            'сеансы' => fn () => $zone->slots()->exists(),
            'замеры воды' => fn () => $zone->waterLogs()->exists(),
            'техобслуживание' => fn () => DB::table('maintenance_tasks')->where('pool_zone_id', $zone->id)->exists(),
            'проходы СКУД' => fn () => DB::table('access_events')->where('pool_zone_id', $zone->id)->exists(),
            'эксплуатационные операции' => fn () => DB::table('pool_operations')->where('pool_zone_id', $zone->id)->exists(),
            'предупреждения по воде' => fn () => DB::table('pool_alerts')->where('pool_zone_id', $zone->id)->exists(),
            'нормативы воды' => fn () => DB::table('pool_norms')->where('pool_zone_id', $zone->id)->exists(),
            'технические чек-листы' => fn () => DB::table('technical_checklists')->where('pool_zone_id', $zone->id)->exists(),
            'инциденты' => fn () => DB::table('safety_incidents')->where('pool_zone_id', $zone->id)->exists(),
            'расход реагентов' => fn () => DB::table('chemical_usages')->where('pool_zone_id', $zone->id)->exists(),
            'складские движения' => fn () => DB::table('inventory_movements')->where('pool_zone_id', $zone->id)->exists(),
        ];

        $blockers = [];
        foreach ($checks as $label => $check) {
            if ($check()) {
                $blockers[] = $label;
            }
        }

        return $blockers;
    }

    public function storeLane(Request $request)
    {
        $data = $request->validate([
            'pool_zone_id' => 'required|exists:pool_zones,id',
            'name' => 'required|string|max:100',
            'number' => 'required|integer|min:1|max:100',
            'length_meters' => 'required|numeric|min:1|max:100',
            'capacity' => 'required|integer|min:1|max:100',
        ]);

        $data['status'] = 'open';
        $data['is_active'] = true;
        PoolLane::create($data);

        return back()->with('success', 'Дорожка добавлена.');
    }

    public function updateLane(Request $request, PoolLane $lane)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['open', 'reserved', 'maintenance', 'closed'])],
            'capacity' => 'required|integer|min:1|max:100',
        ]);

        $lane->update($data + ['is_active' => $request->boolean('is_active')]);

        return back()->with('success', 'Дорожка обновлена.');
    }

    public function assignLane(Request $request, ScheduleSlot $slot)
    {
        $data = $request->validate([
            'pool_lane_id' => 'required|exists:pool_lanes,id',
            'capacity' => 'nullable|integer|min:1|max:100',
        ]);

        $slot->lanes()->syncWithoutDetaching([
            $data['pool_lane_id'] => ['capacity' => $data['capacity'] ?? null],
        ]);

        return back()->with('success', 'Дорожка назначена на сеанс.');
    }

    public function detachLane(ScheduleSlot $slot, PoolLane $lane)
    {
        $slot->lanes()->detach($lane->id);

        return back()->with('success', 'Дорожка снята с сеанса.');
    }

    public function waitlist(Request $request, ScheduleSlot $slot)
    {
        $data = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'people' => 'required|integer|min:1|max:20',
        ]);

        if ($slot->waitlist()->where('status', 'waiting')->count() >= $slot->waitlist_capacity) {
            return back()->withErrors(['waitlist' => 'Лист ожидания заполнен.']);
        }

        WaitlistEntry::updateOrCreate(
            ['schedule_slot_id' => $slot->id, 'customer_id' => $data['customer_id']],
            [
                'people' => $data['people'],
                'priority' => ($slot->waitlist()->max('priority') ?: 0) + 10,
                'status' => 'waiting',
            ]
        );

        return back()->with('success', 'Клиент добавлен в лист ожидания.');
    }

    public function promoteWaitlist(ScheduleSlot $slot, WaitlistEntry $entry)
    {
        abort_unless($entry->schedule_slot_id === $slot->id && $entry->status === 'waiting', 422);

        DB::transaction(function () use ($slot, $entry) {
            $locked = ScheduleSlot::whereKey($slot->id)->lockForUpdate()->first();

            if ($locked->available_places < $entry->people) {
                abort(422, 'Недостаточно свободных мест.');
            }

            Booking::create([
                'public_id' => (string) Str::uuid(),
                'customer_id' => $entry->customer_id,
                'service_id' => $locked->service_id,
                'schedule_slot_id' => $locked->id,
                'trainer_id' => $locked->trainer_id,
                'people' => $entry->people,
                'total' => $locked->service->price * $entry->people,
                'status' => 'confirmed',
                'payment_status' => 'unpaid',
                'source' => 'waitlist',
                'confirmed_at' => now(),
            ]);

            $locked->increment('booked_count', $entry->people);
            $entry->update(['status' => 'promoted', 'notified_at' => now()]);
        });

        return back()->with('success', 'Клиент переведён из листа ожидания в подтверждённую запись.');
    }

    public function storeWater(Request $request, PoolMonitoringService $monitoring)
    {
        $data = $request->validate([
            'pool_zone_id' => 'required|exists:pool_zones,id',
            'measured_at' => 'required|date',
            'temperature' => 'nullable|numeric|min:0|max:50',
            'ph' => 'nullable|numeric|min:0|max:14',
            'free_chlorine' => 'nullable|numeric|min:0|max:20',
            'redox' => 'nullable|numeric|min:0|max:1500',
            'turbidity' => 'nullable|numeric|min:0|max:100',
            'notes' => 'nullable|string|max:3000',
        ]);

        $monitoring->record($data, $request->user()->id);

        return back()->with('success', 'Показатели воды сохранены и проверены по нормативам.');
    }

    public function storeMaintenance(Request $request)
    {
        $data = $request->validate([
            'pool_zone_id' => 'nullable|exists:pool_zones,id',
            'pool_lane_id' => 'nullable|exists:pool_lanes,id',
            'title' => 'required|string|max:190',
            'type' => 'required|string|max:80',
            'due_at' => 'nullable|date',
            'notes' => 'nullable|string|max:3000',
        ]);

        MaintenanceTask::create($data + [
            'assigned_to' => $request->user()->id,
            'status' => 'open',
        ]);

        return back()->with('success', 'Задача техобслуживания создана.');
    }

    public function updateMaintenance(Request $request, MaintenanceTask $task)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['open', 'in_progress', 'completed', 'cancelled'])],
            'notes' => 'nullable|string|max:3000',
        ]);

        $data['completed_at'] = $data['status'] === 'completed' ? now() : null;
        $task->update($data);

        return back()->with('success', 'Задача обновлена.');
    }
}
