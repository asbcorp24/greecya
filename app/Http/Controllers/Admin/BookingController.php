<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $bookings = Booking::query()->with(['customer', 'service', 'slot', 'trainer'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('date'), fn ($q) => $q->whereHas('slot', fn ($s) => $s->whereDate('starts_at', $request->date('date'))))
            ->whereHas('slot', fn ($q) => $q->orderBy('starts_at'))
            ->latest()->paginate(25)->withQueryString();

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
            $booking->update($data + [
                'confirmed_at' => $data['status'] === 'confirmed' ? ($booking->confirmed_at ?: now()) : $booking->confirmed_at,
                'cancelled_at' => $data['status'] === 'cancelled' ? now() : null,
            ]);

            if ($data['status'] === 'cancelled' && $oldStatus !== 'cancelled') {
                $booking->slot()->lockForUpdate()->first()?->decrement('booked_count', $booking->people);
            }
        });

        return back()->with('success', 'Статус записи обновлён.');
    }
}
