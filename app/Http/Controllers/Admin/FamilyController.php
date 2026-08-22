<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Family;
use App\Models\FamilyConsent;
use App\Models\FamilyMember;
use App\Models\FamilyWallet;
use App\Models\FamilyWalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class FamilyController extends Controller
{
    public function index(Request $request)
    {
        $families = Family::query()->with(['primaryCustomer','members.customer','wallet'])
            ->when($request->filled('q'), function($q) use ($request){
                $s='%'.$request->string('q').'%';
                $q->where('name','like',$s)->orWhereHas('members.customer',fn($c)=>$c->where('name','like',$s)->orWhere('phone','like',$s));
            })->latest()->paginate(30)->withQueryString();
        return view('admin.families.index',['families'=>$families,'customers'=>Customer::orderBy('name')->get(['id','name','phone','birth_date'])]);
    }

    public function show(Family $family)
    {
        $family->load(['primaryCustomer','members.customer.medicalClearances','members.customer.memberships.plan','wallet.transactions.customer','consents.guardian','consents.child','members.customer.swimGroupMemberships.group']);
        return view('admin.families.show',['family'=>$family,'customers'=>Customer::orderBy('name')->get(['id','name','phone','birth_date'])]);
    }

    public function store(Request $request)
    {
        $d=$request->validate(['name'=>'required|string|max:190','primary_customer_id'=>'required|exists:customers,id','notes'=>'nullable|string|max:3000']);
        DB::transaction(function()use($d){
            $family=Family::create($d+['status'=>'active']);
            FamilyMember::create(['family_id'=>$family->id,'customer_id'=>$d['primary_customer_id'],'relation'=>'guardian','is_guardian'=>true,'can_manage_bookings'=>true,'can_use_wallet'=>true]);
            FamilyWallet::create(['family_id'=>$family->id]);
        });
        return back()->with('success','Семья создана.');
    }

    public function addMember(Request $request, Family $family)
    {
        $d=$request->validate([
            'customer_id'=>'required|exists:customers,id',
            'relation'=>'required|string|max:50',
        ]);
        FamilyMember::updateOrCreate(['family_id'=>$family->id,'customer_id'=>$d['customer_id']],$d+[
            'is_guardian'=>$request->boolean('is_guardian'),
            'can_manage_bookings'=>$request->boolean('can_manage_bookings'),
            'can_use_wallet'=>$request->boolean('can_use_wallet'),
        ]);
        return back()->with('success','Участник семьи добавлен.');
    }

    public function storeChild(Request $request, Family $family)
    {
        $d=$request->validate([
            'name'=>'required|string|max:120','birth_date'=>'required|date','gender'=>['nullable',Rule::in(['male','female'])],
            'phone'=>'nullable|string|max:30','email'=>'nullable|email|max:190','emergency_contact'=>'nullable|string|max:120','relation'=>'required|string|max:50',
        ]);
        DB::transaction(function()use($family,$d){
            $customer=Customer::create([
                'name'=>$d['name'],'birth_date'=>$d['birth_date'],'gender'=>$d['gender']??null,'phone'=>$d['phone']??null,'email'=>$d['email']??null,
                'emergency_contact'=>$d['emergency_contact']??$family->primaryCustomer?->phone,'source'=>'family',
            ]);
            FamilyMember::create(['family_id'=>$family->id,'customer_id'=>$customer->id,'relation'=>$d['relation'],'is_guardian'=>false,'can_manage_bookings'=>false,'can_use_wallet'=>true]);
        });
        return back()->with('success','Ребёнок добавлен в семью и клиентскую базу.');
    }

    public function consent(Request $request, Family $family)
    {
        $d=$request->validate([
            'guardian_customer_id'=>'required|exists:customers,id','child_customer_id'=>'required|exists:customers,id','type'=>'required|string|max:80',
            'expires_on'=>'nullable|date','notes'=>'nullable|string|max:2000','document'=>'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);
        $guardianOk=FamilyMember::where('family_id',$family->id)->where('customer_id',$d['guardian_customer_id'])->where('is_guardian',true)->exists();
        $childOk=FamilyMember::where('family_id',$family->id)->where('customer_id',$d['child_customer_id'])->exists();
        abort_unless($guardianOk&&$childOk,422,'Опекун или ребёнок не входят в эту семью.');
        $path=$request->file('document')?->store('family-consents','public');
        FamilyConsent::updateOrCreate(
            ['family_id'=>$family->id,'guardian_customer_id'=>$d['guardian_customer_id'],'child_customer_id'=>$d['child_customer_id'],'type'=>$d['type']],
            ['status'=>'signed','signed_at'=>now(),'expires_on'=>$d['expires_on']??null,'document_path'=>$path,'notes'=>$d['notes']??null]
        );
        return back()->with('success','Согласие родителя сохранено.');
    }

    public function wallet(Request $request, Family $family)
    {
        $d=$request->validate(['wallet_type'=>['required',Rule::in(['deposit','bonus'])],'direction'=>['required',Rule::in(['credit','debit'])],'amount'=>'required|numeric|min:0.01|max:1000000','customer_id'=>'nullable|exists:customers,id','description'=>'nullable|string|max:255']);
        DB::transaction(function()use($request,$family,$d){
            $wallet=FamilyWallet::where('family_id',$family->id)->lockForUpdate()->first() ?: FamilyWallet::create(['family_id'=>$family->id]);
            $field=$d['wallet_type']==='bonus'?'bonus_balance':'deposit_balance';
            $next=(float)$wallet->{$field}+($d['direction']==='credit'?(float)$d['amount']:-(float)$d['amount']);
            abort_if($next<0,422,'Недостаточно средств семейного кошелька.');
            $wallet->update([$field=>$next]);
            FamilyWalletTransaction::create(['family_wallet_id'=>$wallet->id,'customer_id'=>$d['customer_id']??null,'created_by'=>$request->user()->id,'wallet_type'=>$d['wallet_type'],'direction'=>$d['direction'],'amount'=>$d['amount'],'description'=>$d['description']??null]);
        });
        return back()->with('success','Операция по семейному кошельку проведена.');
    }
}
