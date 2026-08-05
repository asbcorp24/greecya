<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\Order;

class DashboardController extends Controller
{
    public function __invoke()
    {
        return view('admin.dashboard', [
            'todayBookings' => Booking::query()->whereHas('slot', fn ($q) => $q->whereDate('starts_at', today()))->count(),
            'newLeads' => Lead::query()->where('status', 'new')->count(),
            'customers' => Customer::query()->count(),
            'monthRevenue' => Order::query()->where('payment_status', 'paid')->whereMonth('paid_at', now()->month)->whereYear('paid_at', now()->year)->sum('total'),
            'upcomingBookings' => Booking::query()->with(['customer', 'service', 'slot'])->whereHas('slot', fn ($q) => $q->where('starts_at', '>=', now()))->whereNotIn('status', ['cancelled', 'completed'])->orderBy(ScheduleSlotSubquery::startsAt())->take(8)->get(),
            'recentOrders' => Order::query()->with('customer')->latest()->take(6)->get(),
        ]);
    }
}

final class ScheduleSlotSubquery
{
    public static function startsAt()
    {
        return \App\Models\ScheduleSlot::select('starts_at')->whereColumn('schedule_slots.id', 'bookings.schedule_slot_id');
    }
}
