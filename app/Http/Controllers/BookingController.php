<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\PoolZone;
use App\Models\ScheduleSlot;
use App\Models\Service;
use App\Services\DynamicPricingService;
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

        $slots = ScheduleSlot::query()
            ->with(['trainer:id,name,specialization','service:id,price'])
            ->where('service_id', $service->id)
            ->whereDate('starts_at', $data['date'])
            ->where('starts_at', '>', now())
            ->where('status', 'open')
            ->whereColumn('booked_count', '<', 'capacity')
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
            'date' => ['required', 'date'],
            'schedule_slot_id' => ['required', 'exists:schedule_slots,id'],
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:190'],
            'people' => ['required', 'integer', 'min:1', 'max:10'],
            'comment' => ['nullable', 'string', 'max:1000'],
            'privacy' => ['accepted'],
        ]);

        $booking = DB::transaction(function () use ($data, $pricing) {
            $slot = ScheduleSlot::query()->with('service')->lockForUpdate()->findOrFail($data['schedule_slot_id']);

            $poolAvailable = !$slot->pool_zone_id
                || PoolZone::query()
                    ->whereKey($slot->pool_zone_id)
                    ->where('is_active', true)
                    ->exists();

            $serviceAvailable = $slot->service
                && $slot->service->is_active
                && $slot->service->online_booking;

            if ((int) $data['service_id'] !== $slot->service_id || ! $serviceAvailable || ! $poolAvailable || $slot->status !== 'open' || $slot->starts_at->isPast() || $slot->available_places < $data['people']) {
                throw ValidationException::withMessages(['schedule_slot_id' => 'Выбранное время уже занято, услуга или бассейн недоступны либо слот не относится к выбранной услуге. Пожалуйста, выберите другое время.']);
            }

            $phone = preg_replace('/\D+/', '', $data['phone']);
            if (strlen($phone) < 10) {
                throw ValidationException::withMessages(['phone' => 'Укажите корректный номер телефона.']);
            }

            $customer = Customer::query()->updateOrCreate(
                ['phone' => $phone],
                ['name' => $data['name'], 'email' => $data['email'] ?? null, 'source' => 'site']
            );
            $quote = $pricing->forService($slot->service, $slot, $customer);
            $people = (int)$data['people'];

            $booking = Booking::query()->create([
                'public_id' => (string) Str::uuid(),
                'customer_id' => $customer->id,
                'service_id' => $slot->service_id,
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

            $slot->increment('booked_count', $people);
            return $booking;
        });

        return redirect()->route('booking.success', $booking);
    }

    public function success(Booking $booking)
    {
        return view('catalog.success', ['booking' => $booking->load(['customer', 'service', 'slot', 'trainer'])]);
    }
}
