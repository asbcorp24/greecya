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
            'upcomingBookings' => Booking::query()
                ->select('bookings.*')
                ->with(['customer', 'service', 'slot'])
                ->join('schedule_slots', 'schedule_slots.id', '=', 'bookings.schedule_slot_id')
                ->where('schedule_slots.starts_at', '>=', now())
                ->whereNotIn('bookings.status', ['cancelled', 'completed'])
                ->orderBy('schedule_slots.starts_at')
                ->take(8)
                ->get(),
            'recentOrders' => Order::query()->with('customer')->latest()->take(6)->get(),
        ]);
    }
}
