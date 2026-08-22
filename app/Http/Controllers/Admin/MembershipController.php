<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerWallet;
use App\Models\Membership;
use App\Models\MembershipFreeze;
use App\Models\MembershipPlan;
use App\Models\Product;
use App\Models\WalletTransaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class MembershipController extends Controller
{
    public function index(Request $request)
    {
        $memberships=Membership::with(['customer','plan','freezes'])->when($request->filled('q'),function($q)use($request){$s='%'.$request->string('q').'%';$q->where('number','like',$s)->orWhereHas('customer',fn($c)=>$c->where('name','like',$s)->orWhere('phone','like',$s));})->latest()->paginate(30)->withQueryString();
        return view('admin.memberships.index',['memberships'=>$memberships,'plans'=>MembershipPlan::orderBy('name')->get(),'customers'=>Customer::orderBy('name')->get(['id','name','phone']),'products'=>Product::whereIn('type',['ticket','subscription'])->orderBy('name')->get()]);
    }
    public function storePlan(Request $request){$d=$request->validate(['product_id'=>'nullable|exists:products,id','name'=>'required|string|max:190','code'=>'required|string|max:80|unique:membership_plans,code','type'=>'required|string|max:50','duration_days'=>'required|integer|min:1|max:3650','visits_included'=>'nullable|integer|min:1|max:10000','price'=>'required|numeric|min:0','freeze_days'=>'required|integer|min:0|max:365','guest_visits'=>'required|integer|min:0|max:100','access_from'=>'nullable','access_to'=>'nullable']);$d['is_active']=$request->boolean('is_active');MembershipPlan::create($d);return back()->with('success','Тариф членства создан.');}
    public function store(Request $request){$d=$request->validate(['customer_id'=>'required|exists:customers,id','membership_plan_id'=>'required|exists:membership_plans,id','starts_on'=>'required|date','price_paid'=>'nullable|numeric|min:0','notes'=>'nullable|string|max:3000']);$plan=MembershipPlan::findOrFail($d['membership_plan_id']);$start=Carbon::parse($d['starts_on']);Membership::create(['number'=>$this->number(),'customer_id'=>$d['customer_id'],'membership_plan_id'=>$plan->id,'status'=>'active','starts_on'=>$start->toDateString(),'ends_on'=>$start->copy()->addDays($plan->duration_days-1)->toDateString(),'visits_total'=>$plan->visits_included,'visits_used'=>0,'freeze_days_total'=>$plan->freeze_days,'guest_visits_left'=>$plan->guest_visits,'price_paid'=>$d['price_paid']??$plan->price,'notes'=>$d['notes']??null]);CustomerWallet::firstOrCreate(['customer_id'=>$d['customer_id']]);return back()->with('success','Членство активировано.');}
    public function update(Request $request,Membership $membership){$d=$request->validate(['status'=>['required',Rule::in(['active','frozen','expired','cancelled'])],'ends_on'=>'required|date','notes'=>'nullable|string|max:3000']);$d['auto_renew']=$request->boolean('auto_renew');$membership->update($d);return back()->with('success','Членство обновлено.');}
    public function freeze(Request $request,Membership $membership){$d=$request->validate(['starts_on'=>'required|date','ends_on'=>'required|date|after_or_equal:starts_on','reason'=>'nullable|string|max:255']);$start=Carbon::parse($d['starts_on']);$end=Carbon::parse($d['ends_on']);$days=$start->diffInDays($end)+1;$left=$membership->freeze_days_total-$membership->freeze_days_used;if($days>$left)return back()->withErrors(['freeze'=>'Доступно только '.$left.' дней заморозки.']);DB::transaction(function()use($request,$membership,$d,$days,$start,$end){MembershipFreeze::create(['membership_id'=>$membership->id,'starts_on'=>$start,'ends_on'=>$end,'days'=>$days,'reason'=>$d['reason']??null,'status'=>'approved','created_by'=>$request->user()->id]);$membership->update(['freeze_days_used'=>$membership->freeze_days_used+$days,'ends_on'=>$membership->ends_on->copy()->addDays($days),'status'=>today()->between($start,$end)?'frozen':$membership->status]);});return back()->with('success','Заморозка оформлена, срок членства продлён.');}
    public function wallet(Request $request,Customer $customer){$d=$request->validate(['wallet_type'=>['required',Rule::in(['deposit','bonus'])],'direction'=>['required',Rule::in(['credit','debit'])],'amount'=>'required|numeric|min:0.01|max:1000000','description'=>'nullable|string|max:255']);DB::transaction(function()use($request,$customer,$d){$wallet=CustomerWallet::where('customer_id',$customer->id)->lockForUpdate()->first();if(!$wallet)$wallet=CustomerWallet::create(['customer_id'=>$customer->id]);$field=$d['wallet_type']==='bonus'?'bonus_balance':'deposit_balance';$current=(float)$wallet->$field;$next=$d['direction']==='credit'?$current+(float)$d['amount']:$current-(float)$d['amount'];if($next<0)abort(422,'Недостаточно средств на счёте.');$wallet->update([$field=>$next]);WalletTransaction::create(['customer_wallet_id'=>$wallet->id,'wallet_type'=>$d['wallet_type'],'direction'=>$d['direction'],'amount'=>$d['amount'],'description'=>$d['description']??null,'created_by'=>$request->user()->id]);});return back()->with('success','Баланс клиента изменён.');}
    private function number():string{do{$n='MEM-'.now()->format('ymd').'-'.Str::upper(Str::random(6));}while(Membership::where('number',$n)->exists());return $n;}
}
