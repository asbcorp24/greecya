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

    public function archive(Request $request)
    {
        $this->ensureStructureAdmin($request);

        return view('admin.pool.archive', [
            'archivedZones' => PoolZone::onlyTrashed()
                ->with([
                    'deletedBy',
                    'lanesWithTrashed' => fn ($query) => $query->with('deletedBy')->orderBy('number'),
                ])
                ->orderByDesc('deleted_at')
                ->get(),
            'archivedLanes' => PoolLane::onlyTrashed()
                ->where('deleted_with_zone', false)
                ->with(['zone', 'deletedBy'])
                ->orderByDesc('deleted_at')
                ->get(),
        ]);
    }

    public function storeZone(Request $request)
    {
        $action = $request->input('action', 'create');

        if ($action === 'delete') {
            $this->ensureStructureAdmin($request);

            $data = $request->validate([
                'zone_id' => ['required', 'integer', 'exists:pool_zones,id'],
            ]);

            $zone = PoolZone::findOrFail($data['zone_id']);
            $name = $zone->name;
            $userId = $request->user()->id;

            DB::transaction(function () use ($zone, $userId) {
                PoolLane::where('pool_zone_id', $zone->id)->update([
                    'is_active' => false,
                    'status' => 'closed',
                    'deleted_by_user_id' => $userId,
                    'deleted_with_zone' => true,
                    'deleted_at' => now(),
                ]);

                $zone->update([
                    'is_active' => false,
                    'deleted_by_user_id' => $userId,
                ]);
                $zone->delete();
            });

            return back()->with('success', 'Бассейн / зона «'.$name.'» безопасно удалён в архив. Все связанные данные сохранены.');
        }

        if ($action === 'restore') {
            $this->ensureStructureAdmin($request);

            $data = $request->validate([
                'zone_id' => ['required', 'integer', 'exists:pool_zones,id'],
            ]);

            $zone = PoolZone::onlyTrashed()->findOrFail($data['zone_id']);
            $name = $zone->name;

            DB::transaction(function () use ($zone) {
                $zone->restore();
                $zone->update([
                    'is_active' => false,
                    'deleted_by_user_id' => null,
                ]);

                PoolLane::onlyTrashed()
                    ->where('pool_zone_id', $zone->id)
                    ->where('deleted_with_zone', true)
                    ->update([
                        'deleted_at' => null,
                        'deleted_by_user_id' => null,
                        'deleted_with_zone' => false,
                        'is_active' => false,
                        'status' => 'closed',
                    ]);
            });

            return back()->with('success', 'Бассейн / зона «'.$name.'» восстановлен. Для безопасности он и восстановленные дорожки оставлены выключенными/закрытыми.');
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

    public function storeLane(Request $request)
    {
        $action = $request->input('action', 'create');

        if ($action === 'delete') {
            $this->ensureStructureAdmin($request);

            $data = $request->validate([
                'lane_id' => ['required', 'integer', 'exists:pool_lanes,id'],
            ]);

            $lane = PoolLane::with('zone')->findOrFail($data['lane_id']);
            $name = $lane->name;
            $zoneName = $lane->zone?->name;

            $lane->update([
                'is_active' => false,
                'status' => 'closed',
                'deleted_by_user_id' => $request->user()->id,
                'deleted_with_zone' => false,
            ]);
            $lane->delete();

            return back()->with('success', 'Дорожка «'.$name.'»'.($zoneName ? ' бассейна «'.$zoneName.'»' : '').' безопасно удалена в архив. Все связи сохранены.');
        }

        if ($action === 'restore') {
            $this->ensureStructureAdmin($request);

            $data = $request->validate([
                'lane_id' => ['required', 'integer', 'exists:pool_lanes,id'],
            ]);

            $lane = PoolLane::onlyTrashed()->with('zone')->findOrFail($data['lane_id']);

            if (!$lane->zone || $lane->zone->trashed()) {
                return back()->withErrors([
                    'lane' => 'Сначала восстановите бассейн, к которому относится дорожка «'.$lane->name.'».',
                ]);
            }

            $name = $lane->name;
            $lane->restore();
            $lane->update([
                'is_active' => false,
                'status' => 'closed',
                'deleted_by_user_id' => null,
                'deleted_with_zone' => false,
            ]);

            return back()->with('success', 'Дорожка «'.$name.'» восстановлена в закрытом и неактивном состоянии.');
        }

        $data = $request->validate([
            'pool_zone_id' => [
                'required',
                Rule::exists('pool_zones', 'id')->whereNull('deleted_at'),
            ],
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
            'pool_lane_id' => [
                'required',
                Rule::exists('pool_lanes', 'id')->whereNull('deleted_at'),
            ],
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
            'pool_zone_id' => [
                'required',
                Rule::exists('pool_zones', 'id')->whereNull('deleted_at'),
            ],
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
            'pool_zone_id' => [
                'nullable',
                Rule::exists('pool_zones', 'id')->whereNull('deleted_at'),
            ],
            'pool_lane_id' => [
                'nullable',
                Rule::exists('pool_lanes', 'id')->whereNull('deleted_at'),
            ],
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

    private function ensureStructureAdmin(Request $request): void
    {
        abort_unless(
            $request->user()?->role === 'admin',
            403,
            'Удаление и восстановление бассейнов и дорожек доступно только администратору.'
        );
    }
}
