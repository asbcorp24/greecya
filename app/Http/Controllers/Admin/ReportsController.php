<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\CashTransaction;
use App\Models\Membership;
use App\Models\Payment;
use App\Models\ScheduleSlot;
use App\Models\Trainer;
use App\Models\Visit;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportsController extends Controller
{
    public function index(Request $request)
    {
        $from=Carbon::parse($request->input('from',now()->startOfMonth()->toDateString()))->startOfDay();$to=Carbon::parse($request->input('to',today()->toDateString()))->endOfDay();
        $payments=Payment::where('status','paid')->whereBetween('paid_at',[$from,$to])->get();$visits=Visit::whereBetween('visited_at',[$from,$to])->get();$bookings=Booking::with(['service','trainer','slot'])->whereBetween('created_at',[$from,$to])->get();$slots=ScheduleSlot::whereBetween('starts_at',[$from,$to])->get();
        $revenue=(float)$payments->sum('amount');$capacity=(int)$slots->sum('capacity');$booked=(int)$slots->sum('booked_count');
        $dailyRevenue=$payments->groupBy(fn($p)=>optional($p->paid_at)->format('Y-m-d')?:$p->created_at->format('Y-m-d'))->map->sum('amount');
        $dailyVisits=$visits->groupBy(fn($v)=>$v->visited_at->format('Y-m-d'))->map->count();
        $services=$bookings->groupBy(fn($b)=>$b->service?->name?:'Без услуги')->map(fn($g)=>['bookings'=>$g->count(),'revenue'=>(float)$g->sum('total')])->sortByDesc('bookings');
        $trainers=$bookings->filter(fn($b)=>$b->trainer)->groupBy(fn($b)=>$b->trainer->name)->map(fn($g)=>['bookings'=>$g->count(),'people'=>(int)$g->sum('people'),'revenue'=>(float)$g->sum('total')])->sortByDesc('bookings');
        $hours=$slots->groupBy(fn($s)=>$s->starts_at->format('H:00'))->map(fn($g)=>['capacity'=>(int)$g->sum('capacity'),'booked'=>(int)$g->sum('booked_count')])->sortKeys();
        return view('admin.reports.index',compact('from','to','revenue','visits','bookings','capacity','booked','dailyRevenue','dailyVisits','services','trainers','hours')+['activeMemberships'=>Membership::where('status','active')->whereDate('starts_on','<=',today())->whereDate('ends_on','>=',today())->count(),'cashFlow'=>(float)CashTransaction::whereBetween('occurred_at',[$from,$to])->whereNotIn('type',['refund','expense','withdrawal'])->sum('amount')]);
    }
}
