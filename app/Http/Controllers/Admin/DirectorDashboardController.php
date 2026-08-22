<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Lead;
use App\Models\Membership;
use App\Models\Order;
use App\Models\PayrollAccrual;
use App\Models\ScheduleSlot;
use App\Models\Visit;

class DirectorDashboardController extends Controller
{
    public function __invoke()
    {
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();
        $todayStart = today();
        $todayEnd = today()->endOfDay();

        $paidMonth = Order::query()->where('payment_status','paid')->whereBetween('paid_at',[$monthStart,$monthEnd]);
        $monthRevenue = (float) (clone $paidMonth)->sum('total');
        $monthOrders = (int) (clone $paidMonth)->count();
        $todayRevenue = (float) Order::query()->where('payment_status','paid')->whereBetween('paid_at',[$todayStart,$todayEnd])->sum('total');
        $avgCheck = $monthOrders ? $monthRevenue / $monthOrders : 0;

        $membershipSales = Order::query()->where('payment_status','paid')->whereBetween('paid_at',[$monthStart,$monthEnd])
            ->whereHas('items.product.membershipPlan')->count();

        $newMemberships = Membership::query()->whereBetween('created_at',[$monthStart,$monthEnd])->get(['id','customer_id','membership_plan_id','created_at']);
        $renewals = $newMemberships->filter(fn($m) => Membership::query()
            ->where('customer_id',$m->customer_id)->where('membership_plan_id',$m->membership_plan_id)
            ->where('id','!=',$m->id)->where('created_at','<',$m->created_at)->exists())->count();

        $visitorsToday = Visit::query()->whereBetween('visited_at',[$todayStart,$todayEnd])->count();
        $cancellations = Booking::query()->where('status','cancelled')->whereBetween('updated_at',[$monthStart,$monthEnd])->count();
        $debt = (float) Order::query()->whereNotIn('payment_status',['paid','refunded'])->where('status','!=','cancelled')->sum('total');
        $payrollFund = (float) PayrollAccrual::query()->whereDate('period_month',$monthStart->toDateString())->sum('amount');

        $leadsTotal = Lead::query()->whereBetween('created_at',[$monthStart,$monthEnd])->count();
        $leadsWon = Lead::query()->whereBetween('created_at',[$monthStart,$monthEnd])->where('status','won')->count();
        $leadConversion = $leadsTotal ? round($leadsWon / $leadsTotal * 100, 1) : 0;

        $dailyOrders = Order::query()->where('payment_status','paid')->where('paid_at','>=',now()->subDays(29)->startOfDay())->get(['paid_at','total']);
        $dailyRevenue = collect(range(29,0))->map(function($daysAgo) use ($dailyOrders){
            $date = today()->subDays($daysAgo);
            return ['date'=>$date->format('d.m'),'value'=>(float)$dailyOrders->filter(fn($o)=>$o->paid_at?->isSameDay($date))->sum('total')];
        })->push(['date'=>today()->format('d.m'),'value'=>(float)$dailyOrders->filter(fn($o)=>$o->paid_at?->isToday())->sum('total')]);

        $elapsedDays = max(1, now()->day);
        $daysInMonth = now()->daysInMonth;
        $forecastRevenue = round(($monthRevenue / $elapsedDays) * $daysInMonth, 2);

        $slots = ScheduleSlot::query()->whereBetween('starts_at',[$monthStart,$monthEnd])->get(['starts_at','capacity','booked_count']);
        $hourlyLoad = $slots->groupBy(fn($s)=>$s->starts_at->format('H:00'))->map(function($rows){
            $capacity = max(1, (int)$rows->sum('capacity'));
            return round(((int)$rows->sum('booked_count')) / $capacity * 100, 1);
        })->sortKeys();

        return view('admin.director.dashboard', compact(
            'todayRevenue','monthRevenue','avgCheck','membershipSales','renewals','visitorsToday','cancellations',
            'debt','payrollFund','leadConversion','forecastRevenue','dailyRevenue','hourlyLoad','monthOrders','leadsTotal','leadsWon'
        ));
    }
}
