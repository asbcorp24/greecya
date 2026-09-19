<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\PoolZone;
use App\Models\ScheduleSlot;
use App\Models\Service;
use App\Services\DynamicPricingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        return view('booking.index', [
            'services' => Service::query()
                ->where('is_active', true)
                ->where('online_booking', true)
                ->orderBy('sort_order')
                ->get(),
            'selectedService' => $request->integer('service'),
        ]);
    }

    public function slots(Request $request, DynamicPricingService $pricing)
    {
        $data = $request->validate([
            'service_id' => ['required', 'exists:services,id'],
            'date' => ['required', 'date', 'after_or_equal:today'],
        ]);

        $service = Service::query()
            ->whereKey($data['service_id'])
            ->where('is_active', true)
            ->where('online_booking', true)
            ->first();

        if (! $service) {
            throw ValidationException::withMessages(['service_id' => 'Эта услуга сейчас недоступна для онлайн-записи.']);
        }

        $customer = $request->user()?->role === 'customer' ? $request->user()->customer : null;

        if ($service->unrestricted_booking) {
            return response()->json([
                'unrestricted' => true,
                'duration_minutes' => (int) $service->duration_minutes,
                'base_price' => (float) $service->price,
                'message' => 'Для этой услуги доступна запись без ограничений. Выберите любое удобное время.',
            ]);
        }

        $slots = ScheduleSlot::query()
            ->with(['trainer:id,name,specialization','service:id,price'])
            ->where('service_id', $service->id)
            ->whereDate('starts_at', $data['date'])
            ->where('starts_at', '>', now())
            ->where('status', 'open')
            ->whereColumn('booked_count', '<', 'capacity')
            ->where(function ($query) {
                $query->whereNull('session_type')
                    ->orWhere('session_type', '!=', 'unrestricted_booking');
            })
            ->where(function ($query) {
                $query->whereNull('pool_zone_id')
                    ->orWhereExists(function ($subquery) {
                        $subquery->selectRaw('1')
                            ->from('pool_zones')
                            ->whereColumn('pool_zones.id', 'schedule_slots.pool_zone_id')
                            ->whereNull('pool_zones.deleted_at')
                            ->where('pool_zones.is_active', true);
                    });
            })
            ->orderBy('starts_at')
            ->get()
            ->map(function (ScheduleSlot $slot) use ($pricing, $customer) {
                $quote = $pricing->forService($slot->service, $slot, $customer);
                return [
                    'id' => $slot->id,
                    'time' => $slot->starts_at->format('H:i'),
                    'ends_at' => $slot->ends_at->format('H:i'),
                    'places' => $slot->available_places,
                    'trainer' => $slot->trainer?->name,
                    'base_price' => $quote['base'],
                    'price' => $quote['price'],
                    'pricing_rules' => collect($quote['rules'])->pluck('name')->values(),
                ];
            });

        return response()->json($slots);
    }

    public function store(Request $request, DynamicPricingService $pricing)
    {
        $data = $request->validate([
            'service_id' => ['required', 'exists:services,id'],
            'date' => ['required', 'date', 'after_or_equal:today'],
            'schedule_slot_id' => ['nullable', 'exists:schedule_slots,id'],
            'requested_time' => ['nullable', 'date_format:H:i'],
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:190'],
            'people' => ['required', 'integer', 'min:1', 'max:10'],
            'comment' => ['nullable', 'string', 'max:1000'],
            'privacy' => ['accepted'],
        ]);

        $service = Service::query()
            ->whereKey($data['service_id'])
            ->where('is_active', true)
            ->where('online_booking', true)
            ->first();

        if (! $service) {
            throw ValidationException::withMessages(['service_id' => 'Эта услуга сейчас недоступна для онлайн-записи.']);
        }

        if ($service->unrestricted_booking && empty($data['requested_time'])) {
            throw ValidationException::withMessages(['requested_time' => 'Выберите удобное время.']);
        }

        if (! $service->unrestricted_booking && empty($data['schedule_slot_id'])) {
            throw ValidationException::withMessages(['schedule_slot_id' => 'Выберите свободное время из расписания.']);
        }

        $booking = DB::transaction(function () use ($data, $pricing, $service) {
            $phone = preg_replace('/\D+/', '', $data['phone']);
            if (strlen($phone) < 10) {
                throw ValidationException::withMessages(['phone' => 'Укажите корректный номер телефона.']);
            }

            $customer = Customer::query()->updateOrCreate(
                ['phone' => $phone],
                ['name' => $data['name'], 'email' => $data['email'] ?? null, 'source' => 'site']
            );

            $people = (int) $data['people'];

            if ($service->unrestricted_booking) {
                $requestedStart = Carbon::createFromFormat(
                    'Y-m-d H:i',
                    $data['date'].' '.$data['requested_time'],
                    config('app.timezone')
                )->seconds(0);

                if ($requestedStart->lte(now())) {
                    throw ValidationException::withMessages(['requested_time' => 'Выберите время в будущем.']);
                }

                $requestedEnd = $requestedStart->copy()->addMinutes(max(1, (int) $service->duration_minutes));

                // Для динамической цены учитываем выбранные дату и время, но не применяем
                // скидки/наценки по загрузке: у режима без ограничений нет лимита мест.
                $pricingSlot = new ScheduleSlot([
                    'starts_at' => $requestedStart,
                    'ends_at' => $requestedEnd,
                    'capacity' => 0,
                    'booked_count' => 0,
                ]);
                $pricingSlot->setRelation('service', $service);
                $quote = $pricing->forService($service, $pricingSlot, $customer);

                // Технический слот сохраняет точное выбранное клиентом время для CRM,
                // но не участвует в обычной выдаче свободных слотов.
                $slot = ScheduleSlot::query()->create([
                    'service_id' => $service->id,
                    'trainer_id' => null,
                    'pool_zone_id' => null,
                    'session_type' => 'unrestricted_booking',
                    'starts_at' => $requestedStart,
                    'ends_at' => $requestedEnd,
                    'capacity' => $people,
                    'booked_count' => $people,
                    'status' => 'closed',
                    'online_booking' => false,
                ]);
            } else {
                $slot = ScheduleSlot::query()
                    ->with('service')
                    ->lockForUpdate()
                    ->findOrFail($data['schedule_slot_id']);

                $poolAvailable = ! $slot->pool_zone_id
                    || PoolZone::query()
                        ->whereKey($slot->pool_zone_id)
                        ->where('is_active', true)
                        ->exists();

                $serviceAvailable = $slot->service
                    && $slot->service->is_active
                    && $slot->service->online_booking;

                if ((int) $data['service_id'] !== $slot->service_id
                    || ! $serviceAvailable
                    || ! $poolAvailable
                    || $slot->status !== 'open'
                    || $slot->starts_at->isPast()
                    || $slot->available_places < $people) {
                    throw ValidationException::withMessages([
                        'schedule_slot_id' => 'Выбранное время уже занято, услуга или бассейн недоступны либо слот не относится к выбранной услуге. Пожалуйста, выберите другое время.',
                    ]);
                }

                $quote = $pricing->forService($slot->service, $slot, $customer);
            }

            $booking = Booking::query()->create([
                'public_id' => (string) Str::uuid(),
                'customer_id' => $customer->id,
                'service_id' => $service->id,
                'schedule_slot_id' => $slot->id,
                'trainer_id' => $slot->trainer_id,
                'people' => $people,
                'base_total' => $quote['base'] * $people,
                'total' => $quote['price'] * $people,
                'pricing_meta' => $quote,
                'status' => 'new',
                'payment_status' => 'unpaid',
                'comment' => $data['comment'] ?? null,
                'source' => 'site',
            ]);

            if (! $service->unrestricted_booking) {
                $slot->increment('booked_count', $people);
            }

            return $booking;
        });

        return redirect()->route('booking.success', $booking);
    }

    public function success(Booking $booking)
    {
        return view('booking.success', ['booking' => $booking->load(['customer', 'service', 'slot', 'trainer'])]);
    }
}
