<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Membership;
use App\Models\MembershipFreeze;
use App\Models\TrainingProgressEntry;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccountController extends Controller
{
    public function dashboard(Request $request)
    {
        $customer=$request->user()->customer;
        $customer->load([
            'bookings'=>fn($q)=>$q->with(['service','slot','trainer','membership.plan'])->latest()->limit(30),
            'orders'=>fn($q)=>$q->with(['items.product'])->where('payment_status','paid')->latest()->limit(20),
            'certificates'=>fn($q)=>$q->with('product')->latest(),
            'visits'=>fn($q)=>$q->latest('visited_at')->limit(30),
            'trainingPlans'=>fn($q)=>$q->with(['trainer','items','progressEntries'])->latest(),
            'progressEntries'=>fn($q)=>$q->latest('recorded_on')->limit(20),
            'memberships'=>fn($q)=>$q->with(['plan','freezes'])->latest(),
            'wallet.transactions'=>fn($q)=>$q->limit(20),
            'accessCards'=>fn($q)=>$q->latest(),
            'medicalClearances'=>fn($q)=>$q->latest('expires_on'),
        ]);
        return view('account.dashboard',compact('customer'));
    }

    public function updateProfile(Request $request)
    {
        $data=$request->validate(['name'=>['required','string','max:120'],'phone'=>['required','string','max:40'],'birth_date'=>['nullable','date','before:today']]);
        $request->user()->update(['name'=>$data['name'],'phone'=>$data['phone']]);$request->user()->customer->update($data);return back()->with('success','Профиль обновлён.');
    }

    public function storeProgress(Request $request)
    {
        $customer=$request->user()->customer;$data=$request->validate(['training_plan_id'=>['nullable','integer'],'recorded_on'=>['required','date','before_or_equal:today'],'weight'=>['nullable','numeric','min:20','max:300'],'distance_meters'=>['nullable','integer','min:0','max:100000'],'duration_seconds'=>['nullable','integer','min:0','max:86400'],'note'=>['nullable','string','max:2000']]);if(!empty($data['training_plan_id']))abort_unless($customer->trainingPlans()->whereKey($data['training_plan_id'])->exists(),403);TrainingProgressEntry::create($data+['customer_id'=>$customer->id]);return back()->with('success','Результат тренировки сохранён.');
    }

    public function freezeMembership(Request $request,Membership $membership)
    {
        $customer=$request->user()->customer;abort_unless($membership->customer_id===$customer->id,403);abort_unless(in_array($membership->status,['active','frozen'],true),422);
        $data=$request->validate(['starts_on'=>['required','date','after_or_equal:today'],'ends_on'=>['required','date','after_or_equal:starts_on'],'reason'=>['nullable','string','max:255']]);$start=Carbon::parse($data['starts_on']);$end=Carbon::parse($data['ends_on']);$days=$start->diffInDays($end)+1;$left=$membership->freeze_days_total-$membership->freeze_days_used;if($days>$left)return back()->withErrors(['freeze'=>'Доступно дней заморозки: '.$left]);
        DB::transaction(function()use($membership,$data,$start,$end,$days){MembershipFreeze::create(['membership_id'=>$membership->id,'starts_on'=>$start,'ends_on'=>$end,'days'=>$days,'reason'=>$data['reason']??'Запрос клиента','status'=>'approved']);$membership->update(['freeze_days_used'=>$membership->freeze_days_used+$days,'ends_on'=>$membership->ends_on->copy()->addDays($days),'status'=>today()->between($start,$end)?'frozen':$membership->status]);});return back()->with('success','Заморозка оформлена. Срок членства продлён на '.$days.' дн.');
    }

    public function cancelBooking(Request $request,Booking $booking)
    {
        $customer=$request->user()->customer;abort_unless($booking->customer_id===$customer->id,403);$booking->load('slot');abort_unless($booking->slot&&$booking->slot->starts_at->isFuture(),422);abort_unless(in_array($booking->status,['new','confirmed'],true),422);
        DB::transaction(function()use($booking){$slot=$booking->slot()->lockForUpdate()->first();$slot->update(['booked_count'=>max(0,$slot->booked_count-$booking->people)]);$booking->update(['status'=>'cancelled','cancelled_at'=>now()]);});return back()->with('success','Запись отменена, место возвращено в расписание.');
    }
}
