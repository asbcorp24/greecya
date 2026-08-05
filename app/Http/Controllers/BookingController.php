<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\ScheduleSlot;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        return view('booking.index', [
            'services' => Service::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'selectedService' => $request->integer('service'),
        ]);
    }

    public function slots(Request $request)
    {
        $data = $request->validate([
            'service_id' => ['required', 'exists:services,id'],
            'date' => ['required', 'date', 'after_or_equal:today'],
        ]);

        $slots = ScheduleSlot::query()
            ->with('trainer:id,name,specialization')
            ->where('service_id', $data['service_id'])
            ->whereDate('starts_at', $data['date'])
            ->where('starts_at', '>', now())
            ->where('status', 'open')
            ->whereColumn('booked_count', '<', 'capacity')
            ->orderBy('starts_at')
            ->get()
            ->map(fn (ScheduleSlot $slot) => [
                'id' => $slot->id,
                'time' => $slot->starts_at->format('H:i'),
                'ends_at' => $slot->ends_at->format('H:i'),
                'places' => $slot->available_places,
                'trainer' => $slot->trainer?->name,
            ]);

        return response()->json($slots);
    }

    public function store(Request $request)
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

        $booking = DB::transaction(function () use ($data) {
            $slot = ScheduleSlot::query()->with('service')->lockForUpdate()->findOrFail($data['schedule_slot_id']);

            if ((int) $data['service_id'] !== $slot->service_id || $slot->status !== 'open' || $slot->starts_at->isPast() || $slot->available_places < $data['people']) {
                throw ValidationException::withMessages(['schedule_slot_id' => 'Выбранное время уже занято или не относится к выбранной услуге. Пожалуйста, выберите другой слот.']);
            }

            $phone = preg_replace('/\D+/', '', $data['phone']);
            if (strlen($phone) < 10) {
                throw ValidationException::withMessages(['phone' => 'Укажите корректный номер телефона.']);
            }

            $customer = Customer::query()->updateOrCreate(
                ['phone' => $phone],
                ['name' => $data['name'], 'email' => $data['email'] ?? null, 'source' => 'site']
            );

            $booking = Booking::query()->create([
                'public_id' => (string) Str::uuid(),
                'customer_id' => $customer->id,
                'service_id' => $slot->service_id,
                'schedule_slot_id' => $slot->id,
                'trainer_id' => $slot->trainer_id,
                'people' => $data['people'],
                'total' => $slot->service->price * $data['people'],
                'status' => 'new',
                'payment_status' => 'unpaid',
                'comment' => $data['comment'] ?? null,
                'source' => 'site',
            ]);

            $slot->increment('booked_count', $data['people']);

            return $booking;
        });

        return redirect()->route('booking.success', $booking);
    }

    public function success(Booking $booking)
    {
        return view('booking.success', ['booking' => $booking->load(['customer', 'service', 'slot', 'trainer'])]);
    }
}
