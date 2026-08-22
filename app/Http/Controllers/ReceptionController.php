<?php

namespace App\Http\Controllers;

use App\Models\AccessCard;
use App\Models\Customer;
use App\Models\Locker;
use App\Models\LockerRental;
use App\Models\MedicalClearance;
use App\Models\PoolZone;
use App\Services\MembershipEligibilityService;
use Illuminate\Http\Request;

class ReceptionController extends Controller
{
    public function index(Request $request, MembershipEligibilityService $eligibility)
    {
        $q=trim((string)$request->input('q',''));
        $results=collect();
        if($q!==''){
            $like='%'.$q.'%';
            $results=Customer::query()
                ->with(['accessCards','medicalClearances','wallet','families.wallet','lockerRentals'=>fn($r)=>$r->where('status','active')->with('locker')])
                ->where(fn($c)=>$c->where('name','like',$like)->orWhere('phone','like',$like)->orWhere('email','like',$like))
                ->orWhereHas('accessCards',fn($c)=>$c->where('code','like',$like))
                ->limit(20)->get();
        }

        $customer=null;
        if($request->integer('customer')){
            $customer=Customer::with(['accessCards','medicalClearances','wallet','families.wallet','lockerRentals'=>fn($r)=>$r->where('status','active')->with('locker'),'bookings'=>fn($b)=>$b->with(['service','slot'])->whereHas('slot',fn($s)=>$s->whereDate('starts_at',today())),'orders'])->find($request->integer('customer'));
        }elseif($results->count()===1){
            $customer=$results->first();
            $customer->load(['bookings'=>fn($b)=>$b->with(['service','slot'])->whereHas('slot',fn($s)=>$s->whereDate('starts_at',today())),'orders']);
        }

        $status=null;
        if($customer){
            $membership=$eligibility->findUsable($customer,null,null,now());
            $medical=$customer->medicalClearances->sortByDesc('expires_on')->first(fn($m)=>$m->isValid());
            $blockedMedical=$customer->medicalClearances->first(fn($m)=>$m->access_blocked || in_array($m->status,['revoked','expired'],true));
            $debt=$customer->orders->whereNotIn('payment_status',['paid','refunded'])->sum(fn($o)=>(float)$o->total);
            $rental=$customer->lockerRentals->first();
            $status=compact('membership','medical','blockedMedical','debt','rental');
        }

        return view('reception.index',[
            'query'=>$q,'results'=>$results,'customer'=>$customer,'status'=>$status,
            'zones'=>PoolZone::where('is_active',true)->orderBy('name')->get(),
            'availableLockers'=>Locker::where('is_active',true)->where('status','available')->orderBy('number')->get(),
        ]);
    }
}
