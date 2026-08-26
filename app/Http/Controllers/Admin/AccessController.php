<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccessCard;
use App\Models\AccessEvent;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Locker;
use App\Models\LockerRental;
use App\Models\MedicalClearance;
use App\Models\OrderItem;
use App\Models\PoolZone;
use App\Models\Visit;
use App\Services\MembershipEligibilityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AccessController extends Controller
{
    public function index(Request $request)
    {
        $customers=collect();
        if($request->filled('q')){$s='%'.$request->string('q').'%';$customers=Customer::with(['memberships.plan','families.memberships.plan','accessCards','medicalClearances','wallet','lockerRentals'=>fn($r)=>$r->where('status','active')->with('locker')])->where(fn($q)=>$q->where('name','like',$s)->orWhere('phone','like',$s)->orWhere('email','like',$s))->orWhereHas('accessCards',fn($q)=>$q->where('code','like',$s))->limit(20)->get();}
        return view('admin.access.index',['customers'=>$customers,'allCustomers'=>Customer::orderBy('name')->get(['id','name','phone']),'zones'=>PoolZone::where('is_active',true)->orderBy('name')->get(),'lockers'=>Locker::orderBy('number')->get(),'rentals'=>LockerRental::with(['locker','customer'])->where('status','active')->latest()->get(),'events'=>AccessEvent::with(['customer','zone'])->latest('occurred_at')->limit(50)->get()]);
    }

    public function storeLocker(Request $request){$d=$request->validate(['number'=>'required|string|max:50|unique:lockers,number','zone'=>'required|string|max:80','gender'=>'nullable|in:male,female,unisex']);Locker::create($d+['status'=>'available','is_active'=>true]);return back()->with('success','Шкафчик создан.');}
    public function issueCard(Request $request,Customer $customer){$d=$request->validate(['code'=>'nullable|string|max:190|unique:access_cards,code','type'=>'required|string|max:50','expires_at'=>'nullable|date']);AccessCard::create(['customer_id'=>$customer->id,'code'=>$d['code']?:'QR-'.Str::upper(Str::random(16)),'type'=>$d['type'],'status'=>'active','issued_at'=>now(),'expires_at'=>$d['expires_at']??null]);return back()->with('success','Карта доступа выдана.');}
    public function medical(Request $request,Customer $customer){$d=$request->validate(['type'=>'required|string|max:80','issued_on'=>'nullable|date','expires_on'=>'nullable|date','notes'=>'nullable|string|max:3000']);MedicalClearance::create($d+['customer_id'=>$customer->id,'status'=>'valid','access_blocked'=>false,'verified_by'=>$request->user()->id,'verified_at'=>now()]);return back()->with('success','Медицинский допуск добавлен.');}
    public function assignLocker(Request $request){$d=$request->validate(['locker_id'=>'required|exists:lockers,id','customer_id'=>'required|exists:customers,id','membership_id'=>'nullable|exists:memberships,id','ends_at'=>'nullable|date','deposit'=>'nullable|numeric|min:0']);DB::transaction(function()use($d){$locker=Locker::whereKey($d['locker_id'])->where('status','available')->lockForUpdate()->firstOrFail();$locker->update(['status'=>'occupied']);LockerRental::create($d+['started_at'=>now(),'status'=>'active']);});return back()->with('success','Шкафчик выдан клиенту.');}
    public function returnLocker(LockerRental $rental){DB::transaction(function()use($rental){$rental->update(['status'=>'returned','returned_at'=>now()]);$rental->locker->update(['status'=>'available']);});return back()->with('success','Шкафчик освобождён.');}

    public function checkin(Request $request,MembershipEligibilityService $eligibility)
    {
        $d=$request->validate(['code'=>'nullable|string','customer_id'=>'nullable|exists:customers,id','pool_zone_id'=>'required|exists:pool_zones,id','event_type'=>'required|in:enter,exit']);$card=null;$customer=null;
        if(!empty($d['code'])){$card=AccessCard::with('customer')->where('code',trim($d['code']))->where('status','active')->where(fn($q)=>$q->whereNull('expires_at')->orWhere('expires_at','>=',now()))->first();$customer=$card?->customer;}
        if(!$customer&&!empty($d['customer_id']))$customer=Customer::find($d['customer_id']);if(!$customer)return back()->withErrors(['access'=>'Клиент или карта не найдены.']);
        $zone=PoolZone::findOrFail($d['pool_zone_id']);
        if($d['event_type']==='exit'){AccessEvent::create(['customer_id'=>$customer->id,'access_card_id'=>$card?->id,'pool_zone_id'=>$zone->id,'event_type'=>'exit','result'=>'allowed','occurred_at'=>now()]);return back()->with('success','Выход зарегистрирован: '.$customer->name);}

        if($zone->type==='pool'){
            $blocked=MedicalClearance::where('customer_id',$customer->id)->where('access_blocked',true)->latest()->first();
            if($blocked){$reason=$blocked->blocked_reason?:'Медицинский доступ заблокирован';$this->deny($customer,$card,$zone,$reason);return back()->withErrors(['access'=>$reason.'.']);}
        }

        $booking=Booking::with('slot')->where('customer_id',$customer->id)->whereIn('status',['new','confirmed'])->whereHas('slot',fn($q)=>$q->whereDate('starts_at',today()))->orderBy('created_at')->first();
        $membership=$eligibility->findUsable($customer,$booking?->slot,$zone,now());

        $ticket=null;
        if(!$membership&&!$booking){
            $ticket=OrderItem::query()
                ->whereHas('order',fn($q)=>$q->where('customer_id',$customer->id)->where('payment_status','paid'))
                ->whereHas('product',fn($q)=>$q->where('type','ticket')->where('is_active',true))
                ->whereNotNull('ticket_code')
                ->where(fn($q)=>$q->whereNull('valid_until')->orWhereDate('valid_until','>=',today()))
                ->where(fn($q)=>$q->whereNull('visits_left')->orWhere('visits_left','>',0))
                ->orderByRaw('CASE WHEN valid_until IS NULL THEN 1 ELSE 0 END')
                ->orderBy('valid_until')
                ->orderBy('id')
                ->first();
        }

        $requiresMedical=$zone->type==='pool'&&($membership?$membership->plan->requires_medical_clearance:true);
        if($requiresMedical){$medical=MedicalClearance::where('customer_id',$customer->id)->where('status','valid')->where('access_blocked',false)->where(fn($q)=>$q->whereNull('expires_on')->orWhereDate('expires_on','>=',today()))->exists();if(!$medical){$this->deny($customer,$card,$zone,'Нет действующего медицинского допуска');return back()->withErrors(['access'=>'Нет действующего медицинского допуска.']);}}
        if(!$membership&&!$booking&&!$ticket){$reason='Нет подходящего активного абонемента, записи или оплаченного билета';$this->deny($customer,$card,$zone,$reason);return back()->withErrors(['access'=>$reason.' на сегодня.']);}

        DB::transaction(function()use($customer,$card,$zone,$membership,$booking,$ticket){
            if($membership){$locked=\App\Models\Membership::whereKey($membership->id)->lockForUpdate()->first();if($locked->visits_total!==null)$locked->increment('visits_used');}
            $lockedTicket=null;
            if($ticket){$lockedTicket=OrderItem::whereKey($ticket->id)->lockForUpdate()->first();if($lockedTicket->visits_left!==null&&$lockedTicket->visits_left>0)$lockedTicket->decrement('visits_left');}
            $visit=Visit::create(['customer_id'=>$customer->id,'booking_id'=>$booking?->id,'membership_id'=>$membership?->id,'order_item_id'=>$lockedTicket?->id,'visited_at'=>now(),'guests'=>1,'source'=>'access','notes'=>$membership?'Проход по членству '.$membership->number:($lockedTicket?'Проход по билету '.$lockedTicket->ticket_code:'Проход по записи')]);
            $customer->update(['last_visit_at'=>now()]);
            AccessEvent::create(['customer_id'=>$customer->id,'access_card_id'=>$card?->id,'pool_zone_id'=>$zone->id,'visit_id'=>$visit->id,'event_type'=>'enter','result'=>'allowed','occurred_at'=>now()]);
        });
        return back()->with('success','Проход разрешён: '.$customer->name.($membership?' · '.$membership->number:($ticket?' · билет '.$ticket->ticket_code:'')));
    }

    private function deny(Customer $customer,?AccessCard $card,PoolZone $zone,string $reason):void{AccessEvent::create(['customer_id'=>$customer->id,'access_card_id'=>$card?->id,'pool_zone_id'=>$zone->id,'event_type'=>'enter','result'=>'denied','reason'=>$reason,'occurred_at'=>now()]);}
}
