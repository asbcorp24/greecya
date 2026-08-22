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
        $customers = collect();
        if ($request->filled('q')) {
            $s = '%'.$request->string('q').'%';
            $customers = Customer::with(['memberships.plan','accessCards','medicalClearances','wallet','families.wallet'])
                ->where(fn($q) => $q->where('name','like',$s)->orWhere('phone','like',$s)->orWhere('email','like',$s))
                ->orWhereHas('accessCards', fn($q) => $q->where('code','like',$s))
                ->limit(20)->get();
        }

        return view('admin.access.index', [
            'customers'=>$customers,
            'allCustomers'=>Customer::orderBy('name')->get(['id','name','phone']),
            'zones'=>PoolZone::where('is_active',true)->orderBy('name')->get(),
            'lockers'=>Locker::orderBy('number')->get(),
            'rentals'=>LockerRental::with(['locker','customer'])->where('status','active')->latest()->get(),
            'events'=>AccessEvent::with(['customer','zone'])->latest('occurred_at')->limit(50)->get(),
        ]);
    }

    public function storeLocker(Request $request)
    {
        $d=$request->validate(['number'=>'required|string|max:50|unique:lockers,number','zone'=>'required|string|max:80','gender'=>'nullable|in:male,female,unisex']);
        Locker::create($d+['status'=>'available','is_active'=>true]);
        return back()->with('success','Шкафчик создан.');
    }

    public function issueCard(Request $request,Customer $customer)
    {
        $d=$request->validate(['code'=>'nullable|string|max:190|unique:access_cards,code','type'=>'required|string|max:50','expires_at'=>'nullable|date']);
        AccessCard::create(['customer_id'=>$customer->id,'code'=>$d['code']?:'QR-'.Str::upper(Str::random(16)),'type'=>$d['type'],'status'=>'active','issued_at'=>now(),'expires_at'=>$d['expires_at']??null]);
        return back()->with('success','Карта доступа выдана.');
    }

    public function medical(Request $request,Customer $customer)
    {
        $d=$request->validate(['type'=>'required|string|max:80','issued_on'=>'nullable|date','expires_on'=>'nullable|date','notes'=>'nullable|string|max:3000']);
        MedicalClearance::create($d+['customer_id'=>$customer->id,'status'=>'valid']);
        return back()->with('success','Медицинский допуск добавлен.');
    }

    public function assignLocker(Request $request)
    {
        $d=$request->validate(['locker_id'=>'required|exists:lockers,id','customer_id'=>'required|exists:customers,id','membership_id'=>'nullable|exists:memberships,id','ends_at'=>'nullable|date','deposit'=>'nullable|numeric|min:0']);
        DB::transaction(function()use($d){
            $locker=Locker::whereKey($d['locker_id'])->where('status','available')->lockForUpdate()->firstOrFail();
            $locker->update(['status'=>'occupied']);
            LockerRental::create($d+['started_at'=>now(),'status'=>'active']);
        });
        return back()->with('success','Шкафчик выдан клиенту.');
    }

    public function returnLocker(LockerRental $rental)
    {
        DB::transaction(function()use($rental){
            $rental->update(['status'=>'returned','returned_at'=>now()]);
            $rental->locker->update(['status'=>'available']);
        });
        return back()->with('success','Шкафчик освобождён.');
    }

    public function checkin(Request $request, MembershipEligibilityService $eligibility)
    {
        $d=$request->validate(['code'=>'nullable|string','customer_id'=>'nullable|exists:customers,id','pool_zone_id'=>'required|exists:pool_zones,id','event_type'=>'required|in:enter,exit']);
        $card=null;$customer=null;
        if(!empty($d['code'])){
            $card=AccessCard::with('customer')->where('code',trim($d['code']))->where('status','active')->first();
            $customer=$card?->customer;
        }
        if(!$customer&&!empty($d['customer_id']))$customer=Customer::find($d['customer_id']);
        if(!$customer)return back()->withErrors(['access'=>'Клиент или карта не найдены.']);

        $zone=PoolZone::findOrFail($d['pool_zone_id']);
        if($d['event_type']==='exit'){
            AccessEvent::create(['customer_id'=>$customer->id,'access_card_id'=>$card?->id,'pool_zone_id'=>$zone->id,'event_type'=>'exit','result'=>'allowed','occurred_at'=>now()]);
            return back()->with('success','Выход зарегистрирован: '.$customer->name);
        }

        $booking=Booking::with('slot')->where('customer_id',$customer->id)
            ->whereIn('status',['new','confirmed'])
            ->whereHas('slot',fn($q)=>$q->whereDate('starts_at',today()))
            ->orderBy('created_at')->first();

        $membership=$eligibility->findUsable($customer,$booking?->slot,$zone,now());
        $requiresMedical = $zone->type==='pool' && (!$membership || $membership->plan->requires_medical_clearance);
        if($requiresMedical){
            $medical=MedicalClearance::where('customer_id',$customer->id)->where('status','valid')
                ->where(fn($q)=>$q->whereNull('expires_on')->orWhereDate('expires_on','>=',today()))->exists();
            if(!$medical){
                $this->deny($customer,$card,$zone,'Нет действующего медицинского допуска');
                return back()->withErrors(['access'=>'Нет действующего медицинского допуска.']);
            }
        }

        if(!$membership&&!$booking){
            $this->deny($customer,$card,$zone,'Нет подходящего активного членства или записи');
            return back()->withErrors(['access'=>'Нет подходящего активного абонемента или записи на сегодня.']);
        }

        DB::transaction(function()use($customer,$card,$zone,$membership,$booking){
            if($membership){
                $locked=\App\Models\Membership::whereKey($membership->id)->lockForUpdate()->first();
                if($locked->visits_total!==null)$locked->increment('visits_used');
            }
            $visit=Visit::create([
                'customer_id'=>$customer->id,
                'booking_id'=>$booking?->id,
                'membership_id'=>$membership?->id,
                'visited_at'=>now(),
                'guests'=>1,
                'source'=>'access',
                'notes'=>$membership?'Проход по членству '.$membership->number:'Проход по записи',
            ]);
            $customer->update(['last_visit_at'=>now()]);
            AccessEvent::create(['customer_id'=>$customer->id,'access_card_id'=>$card?->id,'pool_zone_id'=>$zone->id,'visit_id'=>$visit->id,'event_type'=>'enter','result'=>'allowed','occurred_at'=>now()]);
        });

        return back()->with('success','Проход разрешён: '.$customer->name.($membership?' · '.$membership->number:''));
    }

    private function deny(Customer $customer,?AccessCard $card,PoolZone $zone,string $reason):void
    {
        AccessEvent::create(['customer_id'=>$customer->id,'access_card_id'=>$card?->id,'pool_zone_id'=>$zone->id,'event_type'=>'enter','result'=>'denied','reason'=>$reason,'occurred_at'=>now()]);
    }
}
