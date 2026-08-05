<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $bookings = Booking::query()
            ->select('bookings.*')
            ->with(['customer', 'service', 'slot', 'trainer'])
            ->join('schedule_slots', 'schedule_slots.id', '=', 'bookings.schedule_slot_id')
            ->when($request->filled('status'), fn ($q) => $q->where('bookings.status', $request->input('status')))
            ->when($request->filled('date'), fn ($q) => $q->whereDate('schedule_slots.starts_at', $request->input('date')))
            ->orderByDesc('schedule_slots.starts_at')
            ->paginate(25)
            ->withQueryString();

        return view('admin.bookings.index', compact('bookings'));
    }

    public function update(Request $request, Booking $booking)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['new', 'confirmed', 'completed', 'cancelled', 'no_show'])],
            'payment_status' => ['required', Rule::in(['unpaid', 'pending', 'paid', 'refunded'])],
        ]);

        DB::transaction(function () use ($booking, $data) {
            $oldStatus = $booking->status;
            $slot = $booking->slot()->lockForUpdate()->firstOrFail();

            if ($oldStatus !== 'cancelled' && $data['status'] === 'cancelled') {
                $slot->update(['booked_count' => max(0, $slot->booked_count - $booking->people)]);
            }

            if ($oldStatus === 'cancelled' && $data['status'] !== 'cancelled') {
                if ($slot->available_places < $booking->people) {
                    throw ValidationException::withMessages(['status' => 'Нельзя восстановить запись: в этом слоте уже недостаточно свободных мест.']);
                }
                $slot->increment('booked_count', $booking->people);
            }

            $booking->update($data + [
                'confirmed_at' => $data['status'] === 'confirmed' ? ($booking->confirmed_at ?: now()) : $booking->confirmed_at,
                'cancelled_at' => $data['status'] === 'cancelled' ? now() : null,
            ]);
        });

        return back()->with('success', 'Статус записи обновлён.');
    }
}
